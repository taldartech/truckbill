<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->userRole->name === 'master_admin';
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => 'nullable|string|max:1000',
        ];
    }
}
