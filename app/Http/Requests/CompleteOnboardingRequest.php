<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompleteOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $existingUser = User::query()
                        ->with('userRole')
                        ->where('email', $value)
                        ->first();

                    if ($existingUser === null) {
                        return;
                    }

                    if ($existingUser->is_blocked && $existingUser->userRole?->name === 'super_admin') {
                        return;
                    }

                    $fail('The '.$attribute.' has already been taken.');
                },
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
            'referral_code' => 'nullable|string|max:20',
            'accepted_terms' => 'required|accepted',
        ];
    }
}
