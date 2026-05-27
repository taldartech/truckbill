<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MasterPanelSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->userRole?->name;

        return $role === 'master_admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'super_admin_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_date.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            if (! $user) {
                return;
            }

            if ($user->userRole?->name === 'super_admin') {
                if ($this->filled('super_admin_user_id') && (int) $this->input('super_admin_user_id') !== $user->id) {
                    $validator->errors()->add('super_admin_user_id', 'You can only view your own summary.');
                }

                if ($this->filled('company_id')) {
                    $ownsCompany = Company::query()
                        ->whereKey((int) $this->input('company_id'))
                        ->where('user_id', $user->id)
                        ->exists();

                    if (! $ownsCompany) {
                        $validator->errors()->add('company_id', 'Selected company is not assigned to you.');
                    }
                }
            }
        });
    }
}
