<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTransporterTdsOverride;
use App\Models\City;
use App\Models\Company;
use App\Models\State;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransporterRequest extends FormRequest
{
    use ValidatesTransporterTdsOverride;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->canAccessCompanyPanel();
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id');
        $dummyPan = null;
        $panNumberInput = strtoupper((string) $this->input('pan_number', ''));
        if ($companyId !== null && $companyId !== '' && is_numeric((string) $companyId)) {
            $dummyPan = Company::dummyTransporterTruckOwnerPanNumber((int) $companyId);
        }
        $isDummyPan = $dummyPan !== null && $panNumberInput !== '' && $panNumberInput === $dummyPan;
        $baseRules = array_merge([
            'company_id' => 'required|exists:companies,id',
            'contact_person_name' => 'nullable|string|max:255',
            'mobile_number' => [
                'required',
                'digits:10',
                Rule::unique('transporters')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')->where('is_owner', 0)),
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('transporters')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')->where('is_owner', 0)),
            ],
            'advance_amount' => 'nullable|numeric|min:0',
            'declaration_given' => 'required|boolean',
            'declaration_194c6_date' => 'nullable|date',
        ], $this->tdsOverrideFieldRules());

        if ($isDummyPan) {
            // Dummy PAN records must always have a remark and TDS defaults to 0.00.
            // TDS forcing is done in controllers; here we only enforce the remark requirement.
            $baseRules['tds_adjustment_remark'] = 'required|string|max:1000';
        }

        if ($this->filled('transporter_master_id')) {
            return array_merge($baseRules, [
                'transporter_master_id' => [
                    'required',
                    'exists:transporter_masters,id',
                    function ($attr, $value, $fail) {
                        $companyId = (int) $this->input('company_id');
                        $master = TransporterMaster::find($value);

                        // Allow multiple transporter records per company for the dummy PAN.
                        if ($master && strtoupper((string) $master->pan_number) === Company::dummyTransporterTruckOwnerPanNumber($companyId)) {
                            return;
                        }

                        $exists = Transporter::where('transporter_master_id', $value)
                            ->where('company_id', $this->input('company_id'))
                            ->where('is_owner', 0)
                            ->exists();
                        if ($exists) {
                            $fail('This PAN is already registered as a transporter/agency for this company.');
                        }
                    },
                ],
            ]);
        }

        return array_merge($baseRules, [
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', 'unique:transporter_masters,email', 'unique:users,email'],
            'pan_number' => array_merge([
                'required',
                'string',
                'size:10',
                function ($attr, $value, $fail) use ($dummyPan): void {
                    $pan = strtoupper((string) $value);
                    if ($dummyPan !== null && $pan === $dummyPan) {
                        return;
                    }
                    if (! preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
                        $fail('The PAN number format is invalid.');
                    }
                },
            ], $isDummyPan ? [] : ['unique:transporter_masters,pan_number']),
            'gst_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            ],
            'gst_type' => 'nullable|in:composition,regular',
            'pan_active' => 'required|boolean',
            'address' => 'nullable|string|max:500',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => [
                'nullable',
                'exists:states,id',
                function ($attr, $value, $fail) {
                    if ($value && $this->country_id) {
                        $state = State::find($value);
                        if ($state && $state->country_id != $this->country_id) {
                            $fail('The selected state does not belong to the selected country.');
                        }
                    }
                },
            ],
            'city_id' => [
                'nullable',
                'exists:cities,id',
                function ($attr, $value, $fail) {
                    if ($value && $this->state_id) {
                        $city = City::find($value);
                        if ($city && $city->state_id != $this->state_id) {
                            $fail('The selected city does not belong to the selected state.');
                        }
                    }
                },
            ],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $declaration = filter_var($this->input('declaration_given'), FILTER_VALIDATE_BOOLEAN);
            if ($this->filled('transporter_master_id')) {
                $master = TransporterMaster::find($this->input('transporter_master_id'));
                if (! $master) {
                    return;
                }
                $this->validateTdsOverrideAgainstCalculated(
                    $validator,
                    $declaration,
                    (string) $master->pan_number,
                    (bool) $master->pan_active
                );

                return;
            }
            $pan = strtoupper((string) $this->input('pan_number', ''));
            $panActive = filter_var($this->input('pan_active'), FILTER_VALIDATE_BOOLEAN);
            $this->validateTdsOverrideAgainstCalculated($validator, $declaration, $pan, $panActive);
        });
    }

    public function messages(): array
    {
        return [
            'pan_number.unique' => 'This PAN number is already registered.',
            'email.unique' => 'This email is already registered.',
            'mobile_number.unique' => 'This mobile number is already associated with another transporter in this company.',
            'short_name.unique' => 'This short name is already associated with another transporter in this company.',
        ];
    }
}
