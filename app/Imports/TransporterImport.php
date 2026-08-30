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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TransporterImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    public int $successCount = 0;

    public array $errors = [];

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

            try {
                $companyId = Auth::user()->company_id;

                if (! $companyId) {
                    throw new \Exception('Company could not be identified.');
                }

                /*
                 * ---------------------------------------------------------
                 * FIND / CREATE COUNTRY
                 * ---------------------------------------------------------
                 */
                $countryId = null;

                if (! empty($data['country'])) {
                    $country = Country::firstOrCreate([
                        'name' => trim($data['country']),
                    ]);

                    $countryId = $country->id;
                }

                /*
                 * ---------------------------------------------------------
                 * FIND / CREATE STATE
                 * ---------------------------------------------------------
                 */
                $stateId = null;

                if (! empty($data['state']) && $countryId) {
                    $state = State::firstOrCreate([
                        'country_id' => $countryId,
                        'name' => trim($data['state']),
                    ]);

                    $stateId = $state->id;
                }

                /*
                 * ---------------------------------------------------------
                 * FIND / CREATE CITY
                 * ---------------------------------------------------------
                 */
                $cityId = null;

                if (! empty($data['city']) && $stateId) {
                    $city = City::firstOrCreate([
                        'state_id' => $stateId,
                        'name' => trim($data['city']),
                    ]);

                    $cityId = $city->id;
                }

                /*
                 * ---------------------------------------------------------
                 * CHECK EXISTING TRANSPORTER MASTER BY PAN
                 * ---------------------------------------------------------
                 */
                $panNumber = strtoupper(trim((string) $data['pan_number']));

                $master = TransporterMaster::where(
                    'pan_number',
                    $panNumber
                )->first();

                /*
                 * ---------------------------------------------------------
                 * CREATE TRANSPORTER MASTER
                 * ---------------------------------------------------------
                 */
                if (! $master) {

                    $master = TransporterMaster::create([
                        'name' => $data['transport_name'],
                        'email' => ! empty($data['email'])
                            ? trim($data['email'])
                            : null,
                        'gst_number' => ! empty($data['gst_number'])
                            ? strtoupper(trim($data['gst_number']))
                            : null,
                        'pan_number' => $panNumber,
                        'pan_active' => $this->booleanValue(
                            $data['pan_active'] ?? null
                        ),
                        'address' => $data['address'] ?? null,
                        'city_id' => $cityId,
                        'state_id' => $stateId,
                        'country_id' => $countryId,
                    ]);
                }

                /*
                 * ---------------------------------------------------------
                 * CHECK DUPLICATE TRANSPORTER
                 * ---------------------------------------------------------
                 */
                $alreadyExists = Transporter::where(
                    'company_id',
                    $companyId
                )
                    ->where(
                        'transporter_master_id',
                        $master->id
                    )
                    ->where('is_owner', 0)
                    ->exists();

                if ($alreadyExists) {
                    throw new \Exception(
                        'Transporter already exists for this company.'
                    );
                }

                /*
                 * ---------------------------------------------------------
                 * CREATE TRANSPORTER
                 * ---------------------------------------------------------
                 */
                Transporter::create([
                    'transporter_master_id' => $master->id,
                    'company_id' => $companyId,
                    'is_owner' => 0,
                    'contact_person_name' =>
                        $data['contact_person_name'] ?? null,
                    'mobile_number' => $data['mobile_number'],
                    'declaration_given' => $this->booleanValue(
                        $data['declaration_given'] ?? null
                    ),
                    'total_trucks' => 0,
                    'advance_amount' =>
                        ! empty($data['advance_amount'])
                            ? (float) $data['advance_amount']
                            : 0,
                    'short_name' =>
                        $data['short_name'] ?? null,
                    'tds_percentage' =>
                        ! empty($data['tds_percentage'])
                            ? (float) $data['tds_percentage']
                            : 0,
                    'tds_adjustment_remark' =>
                        $data['tds_adjustment_remark'] ?? null,
                ]);

                $this->successCount++;

            } catch (\Throwable $e) {

                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => [
                        'Unable to create transporter: ' .
                        $e->getMessage(),
                    ],
                ];
            }
        }
    }

  private function normalizeRow(array $row): array
{
    $normalized = [];

   foreach ($row as $key => $value) {
    $key = Str::snake(trim($key));

    if (is_string($value)) {
        $value = trim($value);
    }

    if ($key === 'mobile_number' && $value !== null && $value !== '') {
        $value = (string) $value;
    }

    if ($key === 'tds') {
        $key = 'tds_percentage';
    }

    $normalized[$key] = $value;
}

    return $normalized;
}
    private function validateRow(array $data): array
    {
        $validator = Validator::make($data, [

            'pan_number' => [
    'required',
    'string',
    'max:10',
],

'transport_name' => [
    'required',
    'string',
    'max:255',
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

'pan_active' => [
    'required',
],

'contact_person_name' => [
    'nullable',
    'string',
    'max:255',
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

'address' => [
    'nullable',
    'string',
],

'declaration_given' => [
    'required',
],

'tds_percentage' => [
    'required',
    'numeric',
    'min:0',
],

'tds_adjustment_remark' => [
    'nullable',
    'string',
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
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return [];
    }

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

    public function chunkSize(): int
    {
        return 500;
    }
}