<?php

namespace App\Imports;

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
    public $errors = [];

    public $imported = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            $rowNumber = $index + 2;

            try {

                $data = $this->normalizeRow($row->toArray());

                /*
                |--------------------------------------------------------------------------
                | VALIDATION
                |--------------------------------------------------------------------------
                */

                $validator = Validator::make($data, [

                    'name' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'pan_active' => [
                        'required',
                    ],

                    'mobile_number' => [
                        'required',
                        'string',
                        'max:20',
                    ],

                    'country' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'state' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'city' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'declaration_given' => [
                        'required',
                    ],

                    'tds_percentage' => [
                        'required',
                        'numeric',
                        'min:0',
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

                    'contact_person_name' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],

                    'address' => [
                        'nullable',
                        'string',
                    ],

                    'tds_adjustment_remark' => [
                        'nullable',
                        'string',
                    ],

                ], [

                    'name.required' =>
                        'The truck owner name field is required.',

                    'pan_active.required' =>
                        'The PAN active field is required.',

                    'mobile_number.required' =>
                        'The mobile number field is required.',

                    'country.required' =>
                        'The country field is required.',

                    'state.required' =>
                        'The state field is required.',

                    'city.required' =>
                        'The city field is required.',

                    'declaration_given.required' =>
                        'The declaration given field is required.',

                    'tds_percentage.required' =>
                        'The TDS percentage field is required.',

                    'tds_percentage.numeric' =>
                        'The TDS percentage field must be a number.',
                ]);

                if ($validator->fails()) {

                    throw new \Exception(
                        implode(' ', $validator->errors()->all())
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | CREATE RECORDS
                |--------------------------------------------------------------------------
                */

                DB::transaction(function () use ($data) {

                    /*
                    |--------------------------------------------------------------------------
                    | Transporter Master
                    |--------------------------------------------------------------------------
                    */

                    $master = TransporterMaster::create([

                        'name' =>
                            $data['name'],

                        'email' =>
                            !empty($data['email'])
                                ? $data['email']
                                : null,

                        'pan_number' =>
                            !empty($data['pan_number'])
                                ? strtoupper($data['pan_number'])
                                : '',

                        'gst_number' =>
                            !empty($data['gst_number'])
                                ? strtoupper($data['gst_number'])
                                : null,

                        'gst_type' =>
                            null,

                        'pan_active' =>
                            $this->booleanValue(
                                $data['pan_active'] ?? null
                            ),

                        'address' =>
                            $data['address'] ?? null,

                        'city_id' =>
                            $data['city_id'] ?? null,

                        'state_id' =>
                            $data['state_id'] ?? null,

                        'country_id' =>
                            $data['country_id'] ?? null,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Transporter
                    |--------------------------------------------------------------------------
                    */

                    Transporter::create([

                        'transporter_master_id' =>
                            $master->id,

                        'company_id' =>
                            Auth::user()->company->id,

                        'is_owner' =>
                            1,

                        'contact_person_name' =>
                            $data['contact_person_name'] ?? null,

                        'mobile_number' =>
                            $data['mobile_number'],

                        'short_name' =>
                            $data['short_name'] ?? null,

                        'declaration_given' =>
                            $this->booleanValue(
                                $data['declaration_given'] ?? null
                            ),

                        'total_trucks' =>
                            !empty($data['total_trucks'])
                                ? (int) $data['total_trucks']
                                : 0,

                        'advance_amount' =>
                            !empty($data['advance_amount'])
                                ? (float) $data['advance_amount']
                                : 0,

                        'tds_percentage' =>
                            !empty($data['tds_percentage'])
                                ? (float) $data['tds_percentage']
                                : 0,

                        'tds_adjustment_remark' =>
                            $data['tds_adjustment_remark'] ?? null,
                    ]);
                });

                $this->imported++;

            } catch (\Throwable $e) {

                $this->errors[] = [

                    'row' => $rowNumber,

                    'errors' => [
                        $e->getMessage()
                    ],
                ];
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE EXCEL HEADERS
    |--------------------------------------------------------------------------
    */

    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {

            $key = Str::snake(trim($key));

            if (is_string($value)) {
                $value = trim($value);
            }

            if (
                $key === 'mobile_number' &&
                $value !== null &&
                $value !== ''
            ) {
                $value = (string) $value;
            }

            /*
             * Excel header:
             * TDS %
             *
             * becomes:
             * tds
             *
             * Convert it to:
             * tds_percentage
             */

            if ($key === 'tds') {
                $key = 'tds_percentage';
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | BOOLEAN VALUE
    |--------------------------------------------------------------------------
    */

    private function booleanValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(
            strtolower(trim((string) $value)),
            ['1', 'yes', 'true', 'y'],
            true
        );
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