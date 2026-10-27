<?php

namespace App\Imports;

use App\Models\Driver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DriverImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    public int $successCount = 0;

    public array $errors = [];

    private array $uploadedMobiles = [];

    private array $uploadedLicenses = [];

    public function collection(Collection $rows)
    {
        $companyId = Auth::user()->company_id;

        foreach ($rows as $index => $row) {

            // Excel row number = heading row + data index
            $rowNumber = $index + 2;

            $data = [
                'name' => trim((string) ($row['name'] ?? '')),
                'mobile' => trim((string) ($row['mobile'] ?? '')),
                'license_number' => trim((string) ($row['license_number'] ?? '')),
                'address' => trim((string) ($row['address'] ?? '')),
            ];

            /*
             * VALIDATION
             */
            $validator = Validator::make($data, [

                // REQUIRED
                'name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'mobile' => [
                    'required',
                    'digits:10',
                ],

                // OPTIONAL
                'license_number' => [
                    'nullable',
                    'string',
                ],

                'address' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            /*
             * DUPLICATE MOBILE CHECK
             */
            if (
                Driver::where('company_id', $companyId)
                    ->where('mobile', $data['mobile'])
                    ->exists()
            ) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        "Mobile number '{$data['mobile']}' already exists."
                    ],
                ];

                continue;
            }

            /*
             * DUPLICATE MOBILE IN SAME EXCEL FILE
             */
            if (in_array($data['mobile'], $this->uploadedMobiles, true)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        "Mobile number '{$data['mobile']}' is duplicated in the uploaded file."
                    ],
                ];

                continue;
            }

            /*
             * DUPLICATE LICENSE CHECK
             */
            if (!empty($data['license_number'])) {

                if (
                    Driver::where('company_id', $companyId)
                        ->where('license_number', $data['license_number'])
                        ->exists()
                ) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            "License number '{$data['license_number']}' already exists."
                        ],
                    ];

                    continue;
                }

                /*
                 * DUPLICATE LICENSE IN SAME FILE
                 */
                if (in_array($data['license_number'], $this->uploadedLicenses, true)) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => [
                            "License number '{$data['license_number']}' is duplicated in the uploaded file."
                        ],
                    ];

                    continue;
                }
            }

            /*
             * CREATE DRIVER
             */
            Driver::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'license_number' => $data['license_number'] ?: null,
                'address' => $data['address'] ?: null,
            ]);

            $this->uploadedMobiles[] = $data['mobile'];

            if (!empty($data['license_number'])) {
                $this->uploadedLicenses[] = $data['license_number'];
            }

            $this->successCount++;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}