<?php

namespace App\Imports;

use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TruckOwnerImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    public int $successCount = 0;

    public int $imported = 0;

    public array $errors = [];

    private array $uploadedPans = [];

    private array $uploadedMobiles = [];

    public function collection(Collection $rows)
    {
        $companyId = Auth::user()->company_id;

        if (! $companyId) {
            $this->errors[] = [
                'row' => 1,
                'errors' => ['Company could not be identified for the current user.'],
            ];
            return;
        }

        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber((int) $companyId);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $data = $this->normalizeRow($row->toArray(), $dummyPan);

            $validationErrors = $this->validateRow($data, $dummyPan);

            if (! empty($validationErrors)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validationErrors,
                ];
                continue;
            }

            $panNumber = $data['pan_number'];
            $mobileNumber = $data['mobile_number'];

            /*
             * DUPLICATE IN UPLOADED FILE
             */
            if ($panNumber !== $dummyPan && in_array($panNumber, $this->uploadedPans, true)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ["PAN Number '{$panNumber}' is duplicated in the uploaded file."],
                ];
                continue;
            }

            if (in_array($mobileNumber, $this->uploadedMobiles, true)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ["Mobile number '{$mobileNumber}' is duplicated in the uploaded file."],
                ];
                continue;
            }

            /*
             * DUPLICATE IN DATABASE (PER COMPANY)
             */
            if ($panNumber !== $dummyPan) {
                $alreadyExists = Transporter::where('company_id', $companyId)
                    ->where('is_owner', 1)
                    ->whereHas('transporterMaster', function ($q) use ($panNumber) {
                        $q->where('pan_number', $panNumber);
                    })
                    ->exists();

                if ($alreadyExists) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => ["Truck owner with PAN '{$panNumber}' already exists for this company."],
                    ];
                    continue;
                }
            }

            $mobileExists = Transporter::where('company_id', $companyId)
                ->where('is_owner', 1)
                ->where('mobile_number', $mobileNumber)
                ->exists();

            if ($mobileExists) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ["Mobile number '{$mobileNumber}' already exists for this company."],
                ];
                continue;
            }

            try {
                DB::transaction(function () use ($data, $companyId, $panNumber, $dummyPan, $mobileNumber) {
                    /*
                     * LOCATION RESOLUTION (EXISTING RECORDS ONLY - NO AUTO-CREATION)
                     */
                    $country = Country::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $data['country']))])->first();
                    $countryId = $country?->id;

                    $state = State::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $data['state']))])
                        ->when($countryId, fn ($q) => $q->where('country_id', $countryId))
                        ->first();
                    $stateId = $state?->id;

                    $city = City::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $data['city']))])
                        ->when($stateId, fn ($q) => $q->where('state_id', $stateId))
                        ->first();
                    $cityId = $city?->id;

                    /*
                     * TRANSPORTER MASTER (REUSE OR CREATE)
                     */
                    $master = null;
                    if ($panNumber !== $dummyPan) {
                        $master = TransporterMaster::where('pan_number', $panNumber)->first();
                    }

                    if (! $master) {
                        $master = TransporterMaster::create([
                            'name' => $data['name'],
                            'email' => ! empty($data['email']) ? trim($data['email']) : null,
                            'pan_number' => $panNumber,
                            'gst_number' => ! empty($data['gst_number']) ? strtoupper(trim($data['gst_number'])) : null,
                            'gst_type' => null,
                            'pan_active' => strtolower(trim((string) $data['pan_active'])) === 'yes',
                            'address' => $data['address'] ?: null,
                            'city_id' => $cityId,
                            'state_id' => $stateId,
                            'country_id' => $countryId,
                        ]);
                    }

                    /*
                     * SHORT NAME RESOLUTION
                     */
                    $shortName = $data['short_name'] ?: null;
                    if ($shortName) {
                        $existing = Transporter::where('short_name', $shortName)
                            ->where('company_id', $companyId)
                            ->where('is_owner', 1)
                            ->exists();

                        if ($existing) {
                            $suffix = 1;
                            do {
                                $candidate = $shortName . '-' . $suffix;
                                $existing = Transporter::where('short_name', $candidate)
                                    ->where('company_id', $companyId)
                                    ->where('is_owner', 1)
                                    ->exists();
                                $suffix++;
                            } while ($existing);
                            $shortName = $candidate;
                        }
                    }

                    /*
                     * TDS PERCENTAGE RESOLUTION
                     * If TDS % is not provided, auto-calculate based on PAN, PAN active, and declaration given.
                     */
                    $tdsPercentage = $data['tds_percentage'];
                    $tdsRemark = $data['tds_adjustment_remark'];

                    if ($tdsPercentage === null) {
                        if ($panNumber === $dummyPan) {
                            $tdsPercentage = 0.00;
                        } else {
                            $declarationGiven = strtolower(trim((string) $data['declaration_given'])) === 'yes';
                            $panActive = $master ? (bool) $master->pan_active : (strtolower(trim((string) $data['pan_active'])) === 'yes');
                            $tdsPercentage = app(\App\Services\TransporterTdsCalculator::class)->calculate(
                                $declarationGiven,
                                $panNumber,
                                $panActive
                            );
                        }
                    }

                    /*
                     * CREATE TRUCK OWNER TRANSPORTER
                     */
                    Transporter::create([
                        'transporter_master_id' => $master->id,
                        'company_id' => $companyId,
                        'is_owner' => 1,
                        'contact_person_name' => $data['contact_person_name'] ?: null,
                        'mobile_number' => $mobileNumber,
                        'short_name' => $shortName,
                        'declaration_given' => strtolower(trim((string) $data['declaration_given'])) === 'yes',
                        'total_trucks' => (int) ($data['total_trucks'] ?? 0),
                        'advance_amount' => (float) ($data['advance_amount'] ?? 0),
                        'tds_percentage' => (float) $tdsPercentage,
                        'tds_adjustment_remark' => $tdsRemark ?: null,
                    ]);
                });

                if ($panNumber !== $dummyPan) {
                    $this->uploadedPans[] = $panNumber;
                }
                $this->uploadedMobiles[] = $mobileNumber;
                $this->successCount++;
                $this->imported++;

            } catch (\Throwable $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ['Unable to create truck owner: ' . $e->getMessage()],
                ];
            }
        }
    }

    private function normalizeRow(array $row, string $dummyPan): array
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

        $panNumber = $normalized['pan_number'] ?? $normalized['pan_no'] ?? $normalized['pan'] ?? null;
        $name = $normalized['name'] ?? $normalized['truck_owner_name'] ?? $normalized['owner_name'] ?? $normalized['transporter_name'] ?? null;
        $mobileNumber = $normalized['mobile_number'] ?? $normalized['mobile_no'] ?? $normalized['mobile'] ?? $normalized['contact_number'] ?? $normalized['contact_no'] ?? null;
        $contactPerson = $normalized['contact_person_name'] ?? $normalized['contact_person'] ?? $normalized['contact_name'] ?? null;
        $gstNumber = $normalized['gst_number'] ?? $normalized['gst_no'] ?? $normalized['gst'] ?? null;
        $panActive = $normalized['pan_active'] ?? $normalized['pan_status'] ?? null;
        $declarationGiven = $normalized['declaration_given'] ?? $normalized['declaration'] ?? null;
        $tdsPercentage = $normalized['tds_percentage'] ?? $normalized['tds_%'] ?? $normalized['tds'] ?? null;
        $tdsAdjustmentRemark = $normalized['tds_adjustment_remark'] ?? $normalized['tds_remark'] ?? $normalized['remark'] ?? null;
        $shortName = $normalized['short_name'] ?? null;
        $advanceAmount = $normalized['advance_amount'] ?? $normalized['opening_balance'] ?? null;
        $totalTrucks = $normalized['total_trucks']
            ?? $normalized['total_trucks_<=_10']
            ?? $normalized['total_trucks_<=10']
            ?? null;
        if ($totalTrucks === null) {
            foreach ($normalized as $k => $v) {
                if (str_starts_with($k, 'total_truck')) {
                    $totalTrucks = $v;
                    break;
                }
            }
        }
        $email = $normalized['email'] ?? $normalized['email_address'] ?? null;
        $country = $normalized['country'] ?? null;
        $state = $normalized['state'] ?? null;
        $city = $normalized['city'] ?? null;
        $address = $normalized['address'] ?? null;

        $finalPan = $panNumber ? strtoupper(trim((string) $panNumber)) : '';

        $cleanTds = $tdsPercentage !== null ? trim((string) $tdsPercentage) : '';
        $cleanTds = rtrim($cleanTds, '%');
        $cleanTds = trim($cleanTds);

        $parsedTds = $cleanTds !== '' ? (is_numeric($cleanTds) ? (float) $cleanTds : $cleanTds) : null;
        $parsedRemark = $tdsAdjustmentRemark !== null && trim((string) $tdsAdjustmentRemark) !== '' ? trim((string) $tdsAdjustmentRemark) : null;

        return [
            'pan_number' => $finalPan,
            'name' => $name ?: '',
            'email' => $email ?: null,
            'gst_number' => $gstNumber ? strtoupper(trim((string) $gstNumber)) : null,
            'pan_active' => $panActive !== null && trim((string) $panActive) !== '' ? trim((string) $panActive) : null,
            'contact_person_name' => $contactPerson ?: null,
            'mobile_number' => $mobileNumber !== null && $mobileNumber !== '' ? (string) $mobileNumber : '',
            'country' => $country ? trim((string) $country) : null,
            'state' => $state ? trim((string) $state) : null,
            'city' => $city ? trim((string) $city) : null,
            'address' => $address ?: null,
            'declaration_given' => $declarationGiven !== null && trim((string) $declarationGiven) !== '' ? trim((string) $declarationGiven) : null,
            'total_trucks' => $totalTrucks !== null && $totalTrucks !== '' ? (is_numeric($totalTrucks) ? (int) $totalTrucks : $totalTrucks) : 0,
            'tds_percentage' => $parsedTds,
            'tds_adjustment_remark' => $parsedRemark,
            'short_name' => $shortName ?: null,
            'advance_amount' => $advanceAmount !== null && $advanceAmount !== '' ? (float) $advanceAmount : 0,
        ];
    }

    private function validateRow(array $data, string $dummyPan): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'mobile_number' => [
                'required',
                'digits:10',
            ],
            'pan_number' => [
                'required',
                'string',
                'max:10',
                function ($attribute, $value, $fail) use ($dummyPan) {
                    $pan = strtoupper((string) $value);
                    if ($pan === $dummyPan) {
                        return;
                    }
                    if (! preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
                        $fail('The PAN number format is invalid (e.g. ABCDE1234F).');
                    }
                },
            ],
            'pan_active' => [
                'required',
                function ($attribute, $value, $fail) {
                    $val = strtolower(trim((string) $value));
                    if (! in_array($val, ['yes', 'no'], true)) {
                        $fail('PAN Active must be Yes or No.');
                    }
                },
            ],
            'country' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $country = Country::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $value))])->first();
                    if (! $country) {
                        $fail("Country '{$value}' does not exist in master records.");
                    }
                },
            ],
            'state' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($data) {
                    $countryName = trim((string) ($data['country'] ?? ''));
                    $country = ! empty($countryName) ? Country::whereRaw('LOWER(name) = ?', [strtolower($countryName)])->first() : null;

                    $stateQuery = State::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $value))]);
                    if ($country) {
                        $state = $stateQuery->where('country_id', $country->id)->first();
                        if (! $state) {
                            $fail("State '{$value}' does not exist for the selected country.");
                            return;
                        }
                    } else {
                        $state = $stateQuery->first();
                        if (! $state) {
                            $fail("State '{$value}' does not exist in master records.");
                            return;
                        }
                    }
                },
            ],
            'city' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($data) {
                    $countryName = trim((string) ($data['country'] ?? ''));
                    $country = ! empty($countryName) ? Country::whereRaw('LOWER(name) = ?', [strtolower($countryName)])->first() : null;

                    $stateName = trim((string) ($data['state'] ?? ''));
                    $state = ! empty($stateName) ? State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])
                        ->when($country, fn ($q) => $q->where('country_id', $country->id))
                        ->first() : null;

                    $cityQuery = City::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $value))]);
                    if ($state) {
                        $city = $cityQuery->where('state_id', $state->id)->first();
                        if (! $city) {
                            $fail("City '{$value}' does not exist for the selected state.");
                            return;
                        }
                    } else {
                        $city = $cityQuery->first();
                        if (! $city) {
                            $fail("City '{$value}' does not exist in master records.");
                            return;
                        }
                    }
                },
            ],
            'declaration_given' => [
                'required',
                function ($attribute, $value, $fail) {
                    $val = strtolower(trim((string) $value));
                    if (! in_array($val, ['yes', 'no'], true)) {
                        $fail('Declaration Given must be Yes or No.');
                    }
                },
            ],
            'tds_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'gst_number' => [
                'nullable',
                'string',
                'max:20',
            ],
            'address' => [
                'nullable',
                'string',
            ],
            'tds_adjustment_remark' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'total_trucks' => [
                'nullable',
                'integer',
                'min:0',
                'max:10',
            ],
            'advance_amount' => [
                'nullable',
                'numeric',
            ],
        ];

        if ($data['tds_percentage'] !== null) {
            $rules['tds_adjustment_remark'] = [
                'required',
                'string',
                'max:1000',
            ];
        }

        $validator = Validator::make($data, $rules, [
            'name.required' => 'Truck Owner Name is required.',
            'mobile_number.required' => 'Mobile Number is required.',
            'mobile_number.digits' => 'Mobile Number must be exactly 10 digits.',
            'pan_active.required' => 'PAN Active status is required (Yes / No).',
            'country.required' => 'Country is required.',
            'state.required' => 'State is required.',
            'city.required' => 'City is required.',
            'declaration_given.required' => 'Declaration Given status is required (Yes / No).',
            'total_trucks.max' => 'Total Trucks must be less than or equal to 10 (<= 10).',
            'total_trucks.min' => 'Total Trucks cannot be negative.',
            'total_trucks.integer' => 'Total Trucks must be a valid integer.',
            'tds_percentage.numeric' => 'TDS % must be a valid number.',
            'tds_percentage.min' => 'TDS % cannot be less than 0.',
            'tds_percentage.max' => 'TDS % cannot be greater than 100.',
            'tds_adjustment_remark.required' => 'TDS Adjustment Remark is required when TDS percentage is provided.',
            'email.email' => 'Email must be a valid email address.',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return [];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}