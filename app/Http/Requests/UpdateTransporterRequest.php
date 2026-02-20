<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransporterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->canActAsAdmin();
    }

    public function rules(): array
    {
        $transporter = $this->route('transporter');
        $companyId = $this->input('company_id', $transporter->company_id);
        $isSuperAdmin = auth()->user()->userRole->name === 'super_admin';

        $rules = [
            'company_id' => 'required|exists:companies,id',
            'contact_person_name' => 'nullable|string|max:255',
            'mobile_number' => [
                'required',
                'digits:10',
                Rule::unique('transporters')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')->where('is_owner', 0))
                    ->ignore($transporter->id),
            ],
            'advance_amount' => 'nullable|numeric|min:0',
            'tds_percentage' => 'nullable|numeric|min:0|max:100',
            'declaration_given' => 'nullable|boolean',
            'total_trucks' => 'nullable|integer|min:0',
        ];

        if ($isSuperAdmin) {
            $rules['short_name'] = [
                'nullable',
                'string',
                'max:255',
                Rule::unique('transporters')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')->where('is_owner', 0))
                    ->ignore($transporter->id),
            ];
            $rules['beneficiary_id'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'mobile_number.unique' => 'This mobile number is already associated with another transporter in this company.',
            'short_name.unique' => 'This short name is already associated with another transporter in this company.',
        ];
    }
}
