<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransporterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->canAccessCompanyPanel();
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id');
        $baseRules = [
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
        ];

        if ($this->filled('transporter_master_id')) {
            return array_merge($baseRules, [
                'transporter_master_id' => [
                    'required',
                    'exists:transporter_masters,id',
                    function ($attr, $value, $fail) {
                        $exists = \App\Models\Transporter::where('transporter_master_id', $value)
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
            'email' => 'required|email|unique:transporter_masters,email',
            'pan_number' => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'unique:transporter_masters,pan_number',
            ],
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
                        $state = \App\Models\State::find($value);
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
                        $city = \App\Models\City::find($value);
                        if ($city && $city->state_id != $this->state_id) {
                            $fail('The selected city does not belong to the selected state.');
                        }
                    }
                },
            ],
        ]);
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
