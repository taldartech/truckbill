<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateOnboardingLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->userRole->name === 'master_admin';
    }

    public function rules(): array
    {
        return [];
    }
}
