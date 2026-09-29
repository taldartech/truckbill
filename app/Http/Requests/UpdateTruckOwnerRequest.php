<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTransporterTdsOverride;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTruckOwnerRequest extends FormRequest
{
    use ValidatesTransporterTdsOverride;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->canActAsAdmin();
    }

    public function rules(): array
    {
        $truckOwner = $this->route('truck_owner');
        $companyId = $this->input('company_id', $truckOwner->company_id);
        $isSuperAdmin = auth()->user()->userRole->name === 'super_admin';

        $rules = array_merge([
            'company_id' => 'required|exists:companies,id',
            'contact_person_name' => 'nullable|string|max:255',
            'mobile_number' => [
                'required',
                'digits:10',
                Rule::unique('transporters')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')->where('is_owner', 1))
                    ->ignore($truckOwner->id),
            ],
            'advance_amount' => 'nullable|numeric|min:0',
            'declaration_given' => 'required|boolean',
            'declaration_194c6_date' => 'nullable|date',
            'total_trucks' => 'required|integer|min:0|max:10000',
            'is_msme' => 'nullable|boolean',
            'msme_reg_no' => 'nullable|string|max:50',
        ], $this->tdsOverrideFieldRules());

        if ($isSuperAdmin) {
            $rules['short_name'] = [
                'nullable',
                'string',
                'max:255',
                Rule::unique('transporters')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')->where('is_owner', 1))
                    ->ignore($truckOwner->id),
            ];
            $rules['beneficiary_id'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $truckOwner = $this->route('truck_owner');
            $master = $truckOwner->transporterMaster;
            if (! $master) {
                return;
            }
            $declaration = filter_var($this->input('declaration_given'), FILTER_VALIDATE_BOOLEAN);
            $this->validateTdsOverrideAgainstCalculated(
                $validator,
                $declaration,
                (string) $master->pan_number,
                (bool) $master->pan_active
            );
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
