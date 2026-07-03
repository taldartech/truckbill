<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();
        $company = $this->route('company');

        if (! $user || ! $company instanceof Company) {
            return false;
        }

        return (int) $company->user_id === (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_name' => 'required|string|max:255',
            'template_address' => 'required|string|max:500',
            'template_header' => 'nullable|string|max:255',
            'template_note' => 'nullable|string|max:5000',
            'template_jurisdictional_boundary' => 'nullable|string|max:255',
            'invoice_template' => 'required|in:classic,modern,compact',
            'lr_copy_template' => 'required|in:classic,modern,compact',
            'freight_challan_template' => 'required|in:classic,modern',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_name.required' => 'The voucher header name is required.',
            'template_address.required' => 'The voucher header address is required.',
            'ifsc.regex' => 'IFSC must be 11 characters (e.g. SBIN0001234).',
        ];
    }
}
