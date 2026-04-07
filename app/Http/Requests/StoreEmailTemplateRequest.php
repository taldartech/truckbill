<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->userRole?->name === 'master_admin';
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:email_templates,key'],
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'allowed_variables' => ['nullable', 'array'],
            'allowed_variables.*' => ['string', 'max:100', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'],
            'allowed_roles' => ['nullable', 'array'],
            'allowed_roles.*' => ['string', 'in:super_admin,admin,moderator,accountant,master_admin'],
            'send_to_transporter' => ['nullable', 'boolean'],
            'send_to_customer' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
