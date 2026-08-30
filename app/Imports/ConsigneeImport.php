<?php

namespace App\Imports;

use App\Models\City;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ConsigneeImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    public int $successCount = 0;

    public array $errors = [];

    private array $uploadedContacts = [];

    private array $uploadedEmails = [];

    private array $uploadedGsts = [];

    private array $uploadedPans = [];


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE DATA
    |--------------------------------------------------------------------------
    */

    private function normalizeData(array $data): array
    {
        foreach ($data as $key => $value) {

            if (is_string($value)) {
                $value = trim($value);
            }

            $data[$key] = $value;
        }


        /*
         * Optional fields
         */

        foreach ([
            'email',
            'gst_number',
            'pan_number',
            'address',
        ] as $field) {

            if (
                !isset($data[$field]) ||
                trim((string) $data[$field]) === ''
            ) {
                $data[$field] = null;
            }
        }


        /*
         * Uppercase GST / PAN
         */

        if (!empty($data['gst_number'])) {
            $data['gst_number'] =
                strtoupper(trim($data['gst_number']));
        }

        if (!empty($data['pan_number'])) {
            $data['pan_number'] =
                strtoupper(trim($data['pan_number']));
        }

        if (!empty($data['email'])) {
            $data['email'] =
                strtolower(trim($data['email']));
        }


        /*
         * Contact number should remain string
         */

        if (
            isset($data['contact_number']) &&
            $data['contact_number'] !== null
        ) {
            $data['contact_number'] =
                trim((string) $data['contact_number']);
        }


        return $data;
    }


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    private function validateRow(array $data): array
    {
        $validator = Validator::make($data, [

            /*
             * REQUIRED
             */

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'contact_number' => [
                'required',
                'digits:10',
            ],

            'country_id' => [
                'required',
                'string',
            ],

            'state_id' => [
                'required',
                'string',
            ],

            'city_id' => [
                'required',
                'string',
            ],


            /*
             * OPTIONAL
             */

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'gst_number' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            ],

            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],

        ], [

            'name.required' =>
                'Consignee Name is required.',

            'contact_number.required' =>
                'Contact Number is required.',

            'contact_number.digits' =>
                'Contact Number must be exactly 10 digits.',

            'country_id.required' =>
                'Country is required.',

            'state_id.required' =>
                'State is required.',

            'city_id.required' =>
                'City is required.',

            'email.email' =>
                'Email must be a valid email address.',

            'gst_number.size' =>
                'GST Number must be exactly 15 characters.',

            'gst_number.regex' =>
                'GST Number format is invalid.',

            'pan_number.size' =>
                'PAN Number must be exactly 10 characters.',

            'pan_number.regex' =>
                'PAN Number format is invalid.',

        ]);


        if ($validator->fails()) {

            return $validator
                ->errors()
                ->all();
        }


        return [];
    }


    /*
    |--------------------------------------------------------------------------
    | COUNTRY / STATE / CITY
    |--------------------------------------------------------------------------
    */

    private function resolveLocationIds(array &$data): array
    {
        $errors = [];


        /*
         * COUNTRY
         */

        $country = Country::where(
            'name',
            trim((string) $data['country_id'])
        )->first();


        if (!$country) {

            $errors[] =
                'Country not found: ' .
                $data['country_id'];

        } else {

            $data['country_id'] =
                $country->id;
        }


        /*
         * STATE
         */

        if (empty($errors)) {

            $state = State::where(
                'name',
                trim((string) $data['state_id'])
            )
                ->where(
                    'country_id',
                    $data['country_id']
                )
                ->first();


            if (!$state) {

                $errors[] =
                    'State not found for selected country: ' .
                    $data['state_id'];

            } else {

                $data['state_id'] =
                    $state->id;
            }
        }


        /*
         * CITY
         */

        if (empty($errors)) {

            $city = City::where(
                'name',
                trim((string) $data['city_id'])
            )
                ->where(
                    'state_id',
                    $data['state_id']
                )
                ->first();


            if (!$city) {

                $errors[] =
                    'City not found for selected state: ' .
                    $data['city_id'];

            } else {

                $data['city_id'] =
                    $city->id;
            }
        }


        return $errors;
    }


    /*
    |--------------------------------------------------------------------------
    | DUPLICATE CHECK
    |--------------------------------------------------------------------------
    */

    private function validateDuplicates(array $data): array
    {
        $errors = [];

        $companyId =
            $data['company_id'];


        /*
         * CONTACT NUMBER
         */

        $contact =
            trim((string) ($data['contact_number'] ?? ''));


        if ($contact !== '') {

            if (
                in_array(
                    $contact,
                    $this->uploadedContacts,
                    true
                )
            ) {

                $errors[] =
                    'Duplicate contact number in uploaded file.';

            } else {

                $exists = Consignee::where(
                    'company_id',
                    $companyId
                )
                    ->where(
                        'contact_number',
                        $contact
                    )
                    ->whereNull('deleted_at')
                    ->exists();


                if ($exists) {

                    $errors[] =
                        'Contact number already exists for this company.';

                } else {

                    $this->uploadedContacts[] =
                        $contact;
                }
            }
        }


        /*
         * EMAIL
         */

        $email =
            strtolower(
                trim((string) ($data['email'] ?? ''))
            );


        if ($email !== '') {

            if (
                in_array(
                    $email,
                    $this->uploadedEmails,
                    true
                )
            ) {

                $errors[] =
                    'Duplicate email in uploaded file.';

            } else {

                $exists = Consignee::where(
                    'company_id',
                    $companyId
                )
                    ->where(
                        'email',
                        $email
                    )
                    ->whereNull('deleted_at')
                    ->exists();


                if ($exists) {

                    $errors[] =
                        'Email already exists for this company.';

                } else {

                    $this->uploadedEmails[] =
                        $email;
                }
            }
        }


        /*
         * GST NUMBER
         */

        $gst =
            strtoupper(
                trim((string) ($data['gst_number'] ?? ''))
            );


        if ($gst !== '') {

            if (
                in_array(
                    $gst,
                    $this->uploadedGsts,
                    true
                )
            ) {

                $errors[] =
                    'Duplicate GST number in uploaded file.';

            } else {

                $exists = Consignee::where(
                    'company_id',
                    $companyId
                )
                    ->where(
                        'gst_number',
                        $gst
                    )
                    ->whereNull('deleted_at')
                    ->exists();


                if ($exists) {

                    $errors[] =
                        'GST number already exists for this company.';

                } else {

                    $this->uploadedGsts[] =
                        $gst;
                }
            }
        }


        /*
         * PAN NUMBER
         */

        $pan =
            strtoupper(
                trim((string) ($data['pan_number'] ?? ''))
            );


        if ($pan !== '') {

            if (
                in_array(
                    $pan,
                    $this->uploadedPans,
                    true
                )
            ) {

                $errors[] =
                    'Duplicate PAN number in uploaded file.';

            } else {

                $exists = Consignee::where(
                    'company_id',
                    $companyId
                )
                    ->where(
                        'pan_number',
                        $pan
                    )
                    ->whereNull('deleted_at')
                    ->exists();


                if ($exists) {

                    $errors[] =
                        'PAN number already exists for this company.';

                } else {

                    $this->uploadedPans[] =
                        $pan;
                }
            }
        }


        return $errors;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE CONSIGNEE
    |--------------------------------------------------------------------------
    */

    private function createConsignee(array $data): Consignee
    {
        return DB::transaction(function () use ($data) {

            return Consignee::create([

                'company_id' =>
                    $data['company_id'],

                'name' =>
                    $data['name'],

                'contact_number' =>
                    $data['contact_number'],

                'email' =>
                    $data['email'] ?? null,

                'gst_number' =>
                    $data['gst_number'] ?? null,

                'pan_number' =>
                    $data['pan_number'] ?? null,

                'country_id' =>
                    $data['country_id'],

                'state_id' =>
                    $data['state_id'],

                'city_id' =>
                    $data['city_id'],

                'address' =>
                    $data['address'] ?? null,

            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | IMPORT COLLECTION
    |--------------------------------------------------------------------------
    */

    public function collection(Collection $collection)
    {
        foreach ($collection as $index => $row) {

            /*
             * Excel row number:
             *
             * Heading = row 1
             * First data row = row 2
             */

            $excelRowNumber =
                $index + 2;


            try {

                /*
                 * Read Excel columns
                 *
                 * WithHeadingRow converts:
                 * "Consignee Name" -> consignee_name
                 */

                $data = [

                    'name' =>
                        $row['consignee_name'] ?? null,

                    'contact_number' =>
                        $row['contact_number'] ?? null,

                    'email' =>
                        $row['email'] ?? null,

                    'gst_number' =>
                        $row['gst_number'] ?? null,

                    'pan_number' =>
                        $row['pan_number'] ?? null,

                    'country_id' =>
                        $row['country'] ?? null,

                    'state_id' =>
                        $row['state'] ?? null,

                    'city_id' =>
                        $row['city'] ?? null,

                    'address' =>
                        $row['address'] ?? null,

                ];


                /*
                 * Company
                 */

                $data['company_id'] =
                    Auth::user()->company_id;


                /*
                 * Normalize
                 */

                $data =
                    $this->normalizeData($data);


                /*
                 * 1. BASIC VALIDATION
                 */

                $validationErrors =
                    $this->validateRow($data);


                if (!empty($validationErrors)) {

                    $this->errors[] = [

                        'row' =>
                            $excelRowNumber,

                        'error' =>
                            implode(
                                ' ',
                                $validationErrors
                            ),

                    ];

                    continue;
                }


                /*
                 * 2. COUNTRY / STATE / CITY
                 */

                $locationErrors =
                    $this->resolveLocationIds($data);


                if (!empty($locationErrors)) {

                    $this->errors[] = [

                        'row' =>
                            $excelRowNumber,

                        'error' =>
                            implode(
                                ' ',
                                $locationErrors
                            ),

                    ];

                    continue;
                }


                /*
                 * 3. DUPLICATE CHECK
                 */

                $duplicateErrors =
                    $this->validateDuplicates($data);


                if (!empty($duplicateErrors)) {

                    $this->errors[] = [

                        'row' =>
                            $excelRowNumber,

                        'error' =>
                            implode(
                                ' ',
                                $duplicateErrors
                            ),

                    ];

                    continue;
                }


                /*
                 * 4. CREATE
                 */

                $this->createConsignee($data);


                /*
                 * 5. SUCCESS COUNT
                 */

                $this->successCount++;


            } catch (\Throwable $e) {

                /*
                 * Unexpected error for this row
                 * should not stop remaining rows.
                 */

                $this->errors[] = [

                    'row' =>
                        $excelRowNumber,

                    'error' =>
                        $e->getMessage(),

                ];
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CHUNK SIZE
    |--------------------------------------------------------------------------
    */

    public function chunkSize(): int
    {
        return 500;
    }
}