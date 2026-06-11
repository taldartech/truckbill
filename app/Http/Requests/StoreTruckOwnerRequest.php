<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTransporterTdsOverride;
use App\Models\City;
use App\Models\State;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTruckOwnerRequest extends FormRequest
{
    use ValidatesTransporterTdsOverride;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->canAccessCompanyPanel();
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id');
        $baseRules = array_merge([
            'company_id' => 'required|exists:companies,id',
            'contact_person_name' => 'nullable|string|max:255',
            'mobile_number' => [
                'required',
                'digits:10',
                Rule::unique('transporters')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')->where('is_owner', 1)),
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('transporters')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')->where('is_owner', 1)),
            ],
            'advance_amount' => 'nullable|numeric|min:0',
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0|max:10000',
        ], $this->tdsOverrideFieldRules());

        if ($this->filled('transporter_master_id')) {
            return array_merge($baseRules, [
                'transporter_master_id' => [
                    'required',
                    'exists:transporter_masters,id',
                    function ($attr, $value, $fail) {
                        $exists = Transporter::where('transporter_master_id', $value)
                            ->where('company_id', $this->input('company_id'))
                            ->where('is_owner', 1)
                            ->exists();
                        if ($exists) {
                            $fail('This PAN is already registered as a truck owner for this company.');
                        }
                    },
                ],
            ]);
        }

        return array_merge($baseRules, [
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', 'unique:transporter_masters,email', 'unique:users,email'],
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
            'mobile_number.unique' => 'This mobile number is already associated with another truck owner in this company.',
            'short_name.unique' => 'This short name is already associated with another truck owner in this company.',
        ];
    }
}
