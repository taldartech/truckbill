<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\User;
use App\Services\TransporterTdsCalculator;
use App\Services\Notifications\TransporterOnboardingNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Transporter;
use App\Models\TransporterMaster;

class TransporterImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    public int $successCount = 0;

    public array $errors = [];
    private array $uploadedPans = [];

private array $uploadedMobiles = [];

private array $uploadedEmails = [];

private array $uploadedShortNames = [];

  public function __construct(
    private readonly TransporterTdsCalculator $tdsCalculator,
    private readonly TransporterOnboardingNotificationService $transporterOnboardingNotificationService,
) {}

    private function validateRow(array $data): array
{
    $validator = \Illuminate\Support\Facades\Validator::make(
        $data,
        [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'pan_number' => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            ],

            'gst_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            ],

            'pan_active' => ['required', 'boolean'],

            'contact_person_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mobile_number' => [
                'required',
                'digits:10',
            ],

            'address' => [
                'nullable',
                'string',
                'max:500',
            ],

            'declaration_given' => [
                'required',
                'boolean',
            ],

            'tds_percentage' => [
                'required',
                'numeric',
                'min:0',
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

            'advance_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]
    );

    if ($validator->fails()) {
        return $validator->errors()->all();
    }

    return [];
}
private function resolveLocationIds(array &$data): array
{
    $errors = [];

    if (!empty($data['country_id'])) {
        $country = Country::where('name', trim($data['country_id']))->first();

        if (!$country) {
            $errors[] = 'Country not found: ' . $data['country_id'];
        } else {
            $data['country_id'] = $country->id;
        }
    }

    if (!empty($data['state_id']) && !empty($data['country_id']) && is_numeric($data['country_id'])) {
        $state = State::where('name', trim($data['state_id']))
            ->where('country_id', $data['country_id'])
            ->first();

        if (!$state) {
            $errors[] = 'State not found for selected country: ' . $data['state_id'];
        } else {
            $data['state_id'] = $state->id;
        }
    }

    if (!empty($data['city_id']) && !empty($data['state_id']) && is_numeric($data['state_id'])) {
        $city = City::where('name', trim($data['city_id']))
            ->where('state_id', $data['state_id'])
            ->first();

        if (!$city) {
            $errors[] = 'City not found for selected state: ' . $data['city_id'];
        } else {
            $data['city_id'] = $city->id;
        }
    }

    return $errors;
}

private function validateDuplicates(array $data): array
{
    $errors = [];

    $companyId = $data['company_id'];

    // Duplicate PAN
    if (!empty($data['pan_number'])) {
        $panExists = TransporterMaster::where(
            'pan_number',
            strtoupper(trim($data['pan_number']))
        )->exists();

        if ($panExists) {
            $errors[] = 'PAN number already exists.';
        }
    }

    // Duplicate email
    if (!empty($data['email'])) {
        $emailExists = TransporterMaster::where(
            'email',
            trim($data['email'])
        )->exists();

        if ($emailExists) {
            $errors[] = 'Email already exists.';
        }
    }

    // Duplicate mobile in same company
    if (!empty($data['mobile_number'])) {
        $mobileExists = Transporter::where('company_id', $companyId)
            ->where('mobile_number', $data['mobile_number'])
            ->where('is_owner', 0)
            ->whereNull('deleted_at')
            ->exists();

        if ($mobileExists) {
            $errors[] = 'Mobile number already exists for this company.';
        }
    }

    // Duplicate short name in same company
    if (!empty($data['short_name'])) {
        $shortNameExists = Transporter::where('company_id', $companyId)
            ->where('short_name', $data['short_name'])
            ->where('is_owner', 0)
            ->whereNull('deleted_at')
            ->exists();

        if ($shortNameExists) {
            $errors[] = 'Short name already exists for this company.';
        }
    }

    $pan = strtoupper(trim((string) ($data['pan_number'] ?? '')));
$mobile = trim((string) ($data['mobile_number'] ?? ''));
$email = strtolower(trim((string) ($data['email'] ?? '')));
$shortName = strtolower(trim((string) ($data['short_name'] ?? '')));

if ($pan !== '') {
    if (in_array($pan, $this->uploadedPans, true)) {
        $errors[] = 'Duplicate PAN number in uploaded file.';
    } else {
        $this->uploadedPans[] = $pan;
    }
}

if ($mobile !== '') {
    if (in_array($mobile, $this->uploadedMobiles, true)) {
        $errors[] = 'Duplicate mobile number in uploaded file.';
    } else {
        $this->uploadedMobiles[] = $mobile;
    }
}

if ($email !== '') {
    if (in_array($email, $this->uploadedEmails, true)) {
        $errors[] = 'Duplicate email in uploaded file.';
    } else {
        $this->uploadedEmails[] = $email;
    }
}

if ($shortName !== '') {
    if (in_array($shortName, $this->uploadedShortNames, true)) {
        $errors[] = 'Duplicate short name in uploaded file.';
    } else {
        $this->uploadedShortNames[] = $shortName;
    }
}

    return $errors;
}


private function createTransporter(array $data): array
{
    return DB::transaction(function () use ($data) {

        $companyId = (int) $data['company_id'];

        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($companyId);

        $declarationGiven = filter_var(
            $data['declaration_given'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $email = TransporterMaster::normalizeEmail(
            $data['email'] ?? null
        );

        $masterData = [
            'name' => $data['name'],
            'email' => $email,
            'pan_number' => strtoupper($data['pan_number']),
            'gst_number' => !empty($data['gst_number'])
                ? strtoupper($data['gst_number'])
                : null,
            'gst_type' => $data['gst_type'] ?? null,
            'pan_active' => filter_var(
                $data['pan_active'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            ),
            'address' => $data['address'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'state_id' => $data['state_id'] ?? null,
            'country_id' => $data['country_id'] ?? null,
        ];

        $master = TransporterMaster::create($masterData);

        $isDummyPan = strtoupper(
            (string) $master->pan_number
        ) === $dummyPan;

        $submittedTds = round(
            (float) ($data['tds_percentage'] ?? 0),
            2
        );

        $calculatedTds = $this->tdsCalculator->calculate(
            $declarationGiven,
            (string) $master->pan_number,
            (bool) $master->pan_active
        );

        if ($isDummyPan) {

            $remark = trim(
                (string) ($data['tds_adjustment_remark'] ?? '')
            );

            if ($remark === '') {
                throw new \RuntimeException(
                    'TDS adjustment remark is required for dummy PAN.'
                );
            }

        } else {

            $remark = abs(
                $submittedTds - round($calculatedTds, 2)
            ) > 0.01
                ? trim(
                    (string) ($data['tds_adjustment_remark'] ?? '')
                )
                : null;
        }

        $transporter = Transporter::create([
            'transporter_master_id' => $master->id,
            'company_id' => $companyId,
            'is_owner' => 0,
            'contact_person_name' => $data['contact_person_name'] ?? null,
            'mobile_number' => $data['mobile_number'],
            'short_name' => $data['short_name'] ?? null,
            'advance_amount' => $data['advance_amount'] ?? 0,
            'declaration_given' => $declarationGiven,
            'total_trucks' => 0,
            'tds_percentage' => $submittedTds,
            'tds_adjustment_remark' => $remark,
        ]);

        if ($email !== null) {

            $transporterRoleId = DB::table('user_roles')
                ->where('name', 'transporter')
                ->value('id');

            User::create([
                'name' => $master->name,
                'email' => $master->email,
                'password' => bcrypt($data['mobile_number']),
                'user_role_id' => $transporterRoleId,
                'company_id' => null,
                'transporter_master_id' => $master->id,
            ]);
        }

        return [
            'transporter' => $transporter,
            'master' => $master,
        ];
    });
}

    public function collection(Collection $collection)
    {
        foreach ($collection as $index => $row) {
            try {
                $data = [
                    'pan_number' => $row['pan_number'] ?? null,
                    'name' => $row['transport_name'] ?? null,
                    'email' => $row['email'] ?? null,
                    'gst_number' => $row['gst_number'] ?? null,
                    'pan_active' => $row['pan_active'] ?? null,
                    'contact_person_name' => $row['contact_person_name'] ?? null,
                    'mobile_number' => $row['mobile_number'] ?? null,
                    'country_id' => $row['country'] ?? null,
                    'state_id' => $row['state'] ?? null,
                    'city_id' => $row['city'] ?? null,
                    'address' => $row['address'] ?? null,
                    'declaration_given' => $row['declaration_given'] ?? null,
                    'tds_percentage' => $row['tds_percentage'] ?? null,
                    'tds_adjustment_remark' => $row['tds_adjustment_remark'] ?? null,
                    'short_name' => $row['short_name'] ?? null,
                    'advance_amount' => $row['advance_amount'] ?? null,
                ];

                $data['company_id'] = Auth::user()->company_id;
                $validationErrors = $this->validateRow($data);

        if (!empty($validationErrors)) {
       $this->errors[] = [
        'row' => $index + 2,
        'error' => implode(' ', $validationErrors),
      ];

     continue;
    }

    $locationErrors = $this->resolveLocationIds($data);

if (!empty($locationErrors)) {
    $this->errors[] = [
        'row' => $index + 2,
        'error' => implode(' ', $locationErrors),
    ];

    continue;
}

$duplicateErrors = $this->validateDuplicates($data);

if (!empty($duplicateErrors)) {
    $this->errors[] = [
        'row' => $index + 2,
        'error' => implode(' ', $duplicateErrors),
    ];

    continue;
}
        $this->createTransporter($data);
          $this->successCount++;
          
            } catch (\Throwable $e) {
                $this->errors[] = [
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}