<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateVoucherTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $company = $this->route('voucher_template'); // Route model binding with Company model

        if (!$company) {
            return false;
        }

        // Super admin can only update voucher templates for their own companies
        return $company->user_id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'template_name' => 'required|string|max:255',
            'template_address' => 'required|string|max:500',
            'template_header' => 'nullable|string|max:255',
            'template_note' => 'nullable|string',
            'template_jurisdictional_boundary' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'template_name.required' => 'The template name is required.',
            'template_name.max' => 'The template name must not exceed 255 characters.',
            'template_address.required' => 'The template address is required.',
            'template_address.max' => 'The template address must not exceed 500 characters.',
            'template_header.max' => 'The template header must not exceed 255 characters.',
            'template_jurisdictional_boundary.max' => 'The jurisdictional boundary must not exceed 255 characters.',
        ];
    }
}
