<?php

namespace App\Imports;

use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\TruckType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TruckImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    public int $successCount = 0;

    public array $errors = [];

    private array $uploadedTruckNumbers = [];

    public function __construct(
        private readonly int $companyId
    ) {
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $data = $this->normalizeRow($row->toArray());

            $validationErrors = $this->validateRow($data);

            if (! empty($validationErrors)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validationErrors,
                ];
                continue;
            }

            /*
             * ---------------------------------------------------------
             * TRUCK NUMBER
             * ---------------------------------------------------------
             */
            $truckNumber = $data['truck_number'];

            if (in_array($truckNumber, $this->uploadedTruckNumbers, true)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        'Duplicate truck number in uploaded file: ' . $truckNumber,
                    ],
                ];
                continue;
            }

            $alreadyExists = Truck::query()
                ->where('truck_number', $truckNumber)
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->where('company_id', $this->companyId)
                        ->orWhereHas('transporter', function ($q) {
                            $q->where('company_id', $this->companyId);
                        });
                })
                ->exists();

            if ($alreadyExists) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        'Truck number already exists: ' . $truckNumber,
                    ],
                ];
                continue;
            }

            /*
             * ---------------------------------------------------------
             * TRUCK TYPE (CASE-INSENSITIVE / AUTO-CREATE)
             * ---------------------------------------------------------
             */
            $truckTypeName = trim((string) $data['truck_type']);

            $truckType = TruckType::whereRaw('LOWER(name) = ?', [strtolower($truckTypeName)])->first();

            if (! $truckType) {
                $truckType = TruckType::firstOrCreate(['name' => $truckTypeName]);
            }

            /*
             * ---------------------------------------------------------
             * REGISTRATION DATE
             * ---------------------------------------------------------
             */
            $registrationDate = $this->parseDate($data['registration_date']);
            if (! $registrationDate) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        'Invalid registration date format. Use DD-MM-YYYY or YYYY-MM-DD.',
                    ],
                ];
                continue;
            }

            /*
             * ---------------------------------------------------------
             * TRUCK OWNER - OPTIONAL (CASE-INSENSITIVE)
             * ---------------------------------------------------------
             */
            $transporterId = null;

            if (! empty($data['truck_owner'])) {
                $ownerName = trim((string) $data['truck_owner']);

                $owners = Transporter::query()
                    ->where('company_id', $this->companyId)
                    ->where('is_owner', 1)
                    ->where(function ($q) use ($ownerName) {
                        $q->where('short_name', $ownerName)
                            ->orWhereHas('transporterMaster', function ($sub) use ($ownerName) {
                                $sub->where('name', $ownerName)
                                    ->orWhere('pan_number', strtoupper($ownerName))
                                    ->orWhereRaw('LOWER(name) = ?', [strtolower($ownerName)]);
                            });
                    })
                    ->get();

                if ($owners->isEmpty()) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            'Truck owner not found: ' . $ownerName,
                        ],
                    ];
                    continue;
                }

                if ($owners->count() > 1) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            'Multiple truck owners found with name: ' . $ownerName . '. Please use a unique identifier (e.g. PAN or Short Name).',
                        ],
                    ];
                    continue;
                }

                $transporterId = $owners->first()->id;
            }

            /*
             * ---------------------------------------------------------
             * DRIVER - OPTIONAL (CASE-INSENSITIVE)
             * ---------------------------------------------------------
             */
            $driverId = null;

            if (! empty($data['driver'])) {
                $driverName = trim((string) $data['driver']);

                $drivers = Driver::query()
                    ->where('company_id', $this->companyId)
                    ->where(function ($q) use ($driverName) {
                        $q->where('name', $driverName)
                            ->orWhere('mobile', $driverName)
                            ->orWhereRaw('LOWER(name) = ?', [strtolower($driverName)]);
                    })
                    ->get();

                if ($drivers->isEmpty()) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            'Driver not found: ' . $driverName,
                        ],
                    ];
                    continue;
                }

                if ($drivers->count() > 1) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            'Multiple drivers found with name: ' . $driverName . '. Please use unique driver mobile or name.',
                        ],
                    ];
                    continue;
                }

                $driverId = $drivers->first()->id;
            }

            /*
             * ---------------------------------------------------------
             * CREATE TRUCK
             * ---------------------------------------------------------
             */
            try {
                Truck::create([
                    'company_id' => $this->companyId,
                    'transporter_id' => $transporterId,
                    'truck_number' => $truckNumber,
                    'truck_type' => $truckType->name,
                    'driver_id' => $driverId,
                    'registration_date' => $registrationDate,
                ]);

                $this->uploadedTruckNumbers[] = $truckNumber;
                $this->successCount++;

            } catch (\Throwable $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        'Unable to create truck: ' . $e->getMessage(),
                    ],
                ];
            }
        }
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $cleanKey = strtolower(trim((string) $key));
            $cleanKey = str_replace([' ', '-', '.'], '_', $cleanKey);
            $cleanKey = preg_replace('/_+/', '_', $cleanKey);

            if (is_string($value)) {
                $value = trim($value);
            }

            $normalized[$cleanKey] = $value;
        }

        $rawTruckNumber = $normalized['truck_number'] ?? $normalized['truck_no'] ?? $normalized['vehicle_number'] ?? $normalized['vehicle_no'] ?? null;
        $cleanTruckNumber = $rawTruckNumber ? strtoupper(str_replace([' ', '-', '.', '_'], '', (string) $rawTruckNumber)) : '';

        $truckType = $normalized['truck_type'] ?? $normalized['type'] ?? null;
        $registrationDate = $normalized['registration_date'] ?? $normalized['reg_date'] ?? $normalized['date'] ?? null;
        $truckOwner = $normalized['truck_owner'] ?? $normalized['owner'] ?? $normalized['owner_name'] ?? $normalized['transporter'] ?? null;
        $driver = $normalized['driver'] ?? $normalized['driver_name'] ?? null;

        return [
            'truck_number' => $cleanTruckNumber,
            'truck_type' => $truckType ? trim((string) $truckType) : '',
            'registration_date' => $registrationDate,
            'truck_owner' => $truckOwner ? trim((string) $truckOwner) : null,
            'driver' => $driver ? trim((string) $driver) : null,
        ];
    }

    private function validateRow(array $data): array
    {
        $validator = Validator::make(
            $data,
            [
                'truck_number' => [
                    'required',
                    'string',
                    'regex:/^[A-Z]{2}[A-Z0-9]{1,2}[A-Z0-9]{1,3}[0-9]{4}$/',
                ],
                'truck_type' => [
                    'required',
                    'string',
                    'max:50',
                ],
                'registration_date' => [
                    'required',
                ],
                'truck_owner' => [
                    'nullable',
                    'string',
                ],
                'driver' => [
                    'nullable',
                    'string',
                ],
            ],
            [
                'truck_number.required' => 'Truck Number is required.',
                'truck_number.regex' => 'Invalid Truck Number format. Example: MH12AB1234.',
                'truck_type.required' => 'Truck Type is required.',
                'registration_date.required' => 'Registration Date is required.',
            ]
        );

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return [];
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                $year = (int) $value->format('Y');

                if ($year >= 1900 && $year <= 2100) {
                    return $value->format('Y-m-d');
                }

                return null;
            }

            if (is_numeric($value)) {
                $date = ExcelDate::excelToDateTimeObject((float) $value);

                $year = (int) $date->format('Y');

                if ($year >= 1900 && $year <= 2100) {
                    return $date->format('Y-m-d');
                }

                return null;
            }

            $value = trim((string) $value);

            $formats = [
                'd-m-Y',
                'd/m/Y',
                'Y-m-d',
                'd.m.Y',
                'Y/m/d',
                'm/d/Y',
            ];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat('!' . $format, $value);

                if ($date && $date->format($format) === $value) {
                    $year = (int) $date->format('Y');

                    if ($year >= 1900 && $year <= 2100) {
                        return $date->format('Y-m-d');
                    }
                }
            }

            return null;

        } catch (\Throwable $e) {
            return null;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}