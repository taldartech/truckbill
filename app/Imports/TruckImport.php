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
            $ownerName = ! empty($data['truck_owner']) ? trim((string) $data['truck_owner']) : null;
            $ownerPan = ! empty($data['truck_owner_pan']) ? strtoupper(trim((string) $data['truck_owner_pan'])) : null;

            if ($ownerName || $ownerPan) {
                $ownerQuery = Transporter::query()
                    ->where('company_id', $this->companyId)
                    ->where('is_owner', 1);

                if ($ownerPan) {
                    $cleanPan = strtoupper(str_replace(' ', '', (string) $ownerPan));
                    $ownerQuery->whereHas('transporterMaster', function ($sub) use ($cleanPan) {
                        $sub->whereRaw("UPPER(REPLACE(pan_number, ' ', '')) = ?", [$cleanPan]);
                    });
                }

                if ($ownerName) {
                    $ownerQuery->where(function ($q) use ($ownerName) {
                        $q->where('short_name', $ownerName)
                            ->orWhereHas('transporterMaster', function ($sub) use ($ownerName) {
                                $sub->where('name', $ownerName)
                                    ->orWhere('pan_number', strtoupper($ownerName))
                                    ->orWhereRaw('LOWER(name) = ?', [strtolower($ownerName)]);
                            });
                    });
                }

                $owners = $ownerQuery->get();

                if ($owners->isEmpty()) {
                    $msg = 'Truck owner does not exist';
                    if ($ownerName) {
                        $msg .= ": {$ownerName}";
                    }
                    if ($ownerPan) {
                        $msg .= " (PAN: {$ownerPan})";
                    }
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            $msg . '.',
                        ],
                    ];
                    continue;
                }

                if ($owners->count() > 1) {
                    $errorMsg = empty($ownerPan)
                        ? "Multiple truck owners found with name '{$ownerName}'. 'Truck Owner PAN' is required to identify the truck owner."
                        : "Multiple truck owners found with name '{$ownerName}' and PAN '{$ownerPan}'. Please ensure master records are unique.";

                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            $errorMsg,
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
            $driverName = ! empty($data['driver']) ? trim((string) $data['driver']) : null;
            $driverLicense = ! empty($data['driver_license']) ? trim((string) $data['driver_license']) : null;

            if ($driverName || $driverLicense) {
                $driverQuery = Driver::query()
                    ->where('company_id', $this->companyId);

                if ($driverLicense) {
                    $cleanLicense = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $driverLicense));
                    $driverQuery->where(function ($sub) use ($driverLicense, $cleanLicense) {
                        $sub->whereRaw('LOWER(license_number) = ?', [strtolower($driverLicense)])
                            ->orWhereRaw("UPPER(REPLACE(REPLACE(REPLACE(license_number, ' ', ''), '-', ''), '.', '')) = ?", [$cleanLicense]);
                    });
                }

                if ($driverName) {
                    $driverQuery->where(function ($q) use ($driverName) {
                        $q->where('name', $driverName)
                            ->orWhere('mobile', $driverName)
                            ->orWhereRaw('LOWER(name) = ?', [strtolower($driverName)]);
                    });
                }

                $drivers = $driverQuery->get();

                if ($drivers->isEmpty()) {
                    $msg = 'Driver does not exist';
                    if ($driverName) {
                        $msg .= ": {$driverName}";
                    }
                    if ($driverLicense) {
                        $msg .= " (License: {$driverLicense})";
                    }
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            $msg . '.',
                        ],
                    ];
                    continue;
                }

                if ($drivers->count() > 1) {
                    $errorMsg = empty($driverLicense)
                        ? "Multiple drivers found with name '{$driverName}'. 'Driver License Number' is required to identify the driver."
                        : "Multiple drivers found with name '{$driverName}' and License '{$driverLicense}'. Please ensure driver records are unique.";

                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            $errorMsg,
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
        $truckOwnerPan = $normalized['truck_owner_pan'] ?? $normalized['owner_pan'] ?? $normalized['pan_number'] ?? $normalized['pan_no'] ?? $normalized['pan'] ?? null;
        $driver = $normalized['driver'] ?? $normalized['driver_name'] ?? null;
        $driverLicense = $normalized['driver_license_number'] ?? $normalized['driver_license'] ?? $normalized['license_number'] ?? $normalized['license_no'] ?? $normalized['license'] ?? null;

        return [
            'truck_number' => $cleanTruckNumber,
            'truck_type' => $truckType ? trim((string) $truckType) : '',
            'registration_date' => $registrationDate,
            'truck_owner' => $truckOwner ? trim((string) $truckOwner) : null,
            'truck_owner_pan' => $truckOwnerPan ? strtoupper(trim((string) $truckOwnerPan)) : null,
            'driver' => $driver ? trim((string) $driver) : null,
            'driver_license' => $driverLicense ? trim((string) $driverLicense) : null,
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
                'truck_owner_pan' => [
                    'nullable',
                    'string',
                    'max:10',
                ],
                'driver' => [
                    'nullable',
                    'string',
                ],
                'driver_license' => [
                    'nullable',
                    'string',
                    'max:50',
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