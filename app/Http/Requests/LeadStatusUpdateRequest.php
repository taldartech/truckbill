<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->userRole->name === 'master_admin';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['DEMO_SCHEDULED', 'DEMO_COMPLETED'])],
        ];
    }
}
