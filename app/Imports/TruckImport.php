<?php

namespace App\Imports;

use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\TruckType;
use Carbon\Carbon;
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

            // Excel heading row = row 1
            // Actual data starts from row 2
            $rowNumber = $index + 2;

            $data = $row->toArray();

            // Normalize values
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = trim($value);
                }
            }

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

            $truckNumber = strtoupper(trim((string) $data['truck_number']));

            if (in_array($truckNumber, $this->uploadedTruckNumbers, true)) {

                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        'Duplicate truck number in uploaded file: '.$truckNumber,
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
                        'Truck number already exists: '.$truckNumber,
                    ],
                ];

                continue;
            }

            /*
             * ---------------------------------------------------------
             * TRUCK TYPE
             * ---------------------------------------------------------
             */

            $truckTypeName = trim((string) $data['truck_type']);

            $truckType = TruckType::where(
                'name',
                $truckTypeName
            )->first();

            if (! $truckType) {

                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        'Truck type not found: '.$truckTypeName,
                    ],
                ];

                continue;
            }

            /*
             * ---------------------------------------------------------
             * REGISTRATION DATE
             * ---------------------------------------------------------
             */

            $registrationDate = $this->parseDate(
                $data['registration_date'] ?? null
            );

            if (! $registrationDate) {

                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        'Invalid registration date. Use DD-MM-YYYY or YYYY-MM-DD.',
                    ],
                ];

                continue;
            }

            /*
             * ---------------------------------------------------------
             * TRUCK OWNER - OPTIONAL
             * ---------------------------------------------------------
             */

            $transporterId = null;

            if (! empty($data['truck_owner'])) {

                $ownerName = trim((string) $data['truck_owner']);

                $owners = Transporter::query()
                    ->where('company_id', $this->companyId)
                    ->where('is_owner', 1)
                    ->whereHas('transporterMaster', function ($query) use ($ownerName) {
                        $query->where('name', $ownerName);
                    })
                    ->get();

                if ($owners->isEmpty()) {

                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            'Truck owner not found: '.$ownerName,
                        ],
                    ];

                    continue;
                }

                if ($owners->count() > 1) {

                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            'Multiple truck owners found with name: '.$ownerName,
                        ],
                    ];

                    continue;
                }

                $transporterId = $owners->first()->id;
            }

            /*
             * ---------------------------------------------------------
             * DRIVER - OPTIONAL
             * ---------------------------------------------------------
             */

            $driverId = null;

            if (! empty($data['driver'])) {

                $driverName = trim((string) $data['driver']);

                $drivers = Driver::query()
                    ->where('company_id', $this->companyId)
                    ->where('name', $driverName)
                    ->get();

                if ($drivers->isEmpty()) {

                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            'Driver not found: '.$driverName,
                        ],
                    ];

                    continue;
                }

                if ($drivers->count() > 1) {

                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            'Multiple drivers found with name: '.$driverName.'. Use a unique driver name.',
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
                        'Unable to create truck: '.$e->getMessage(),
                    ],
                ];
            }
        }
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
            'truck_number.required' =>
                'Truck Number is required.',

            'truck_number.regex' =>
                'Invalid Truck Number format. Example: MH12AB1234.',

            'truck_type.required' =>
                'Truck Type is required.',

            'registration_date.required' =>
                'Registration Date is required.',
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
        // Excel date object
        if ($value instanceof \DateTimeInterface) {
            $year = (int) $value->format('Y');

            if ($year >= 1900 && $year <= 2100) {
                return $value->format('Y-m-d');
            }

            return null;
        }

        // Excel numeric serial date
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