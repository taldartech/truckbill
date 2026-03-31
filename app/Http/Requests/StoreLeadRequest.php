<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->routeIs('leads.website.store')) {
            return true;
        }

        return auth()->check() && auth()->user()->userRole->name === 'master_admin';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'mobile' => 'required|digits:10',
            'email' => 'required|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'source' => ['nullable', Rule::in(['website', 'manual'])],
        ];
    }
}
