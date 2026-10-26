<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
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

        $branch = $this->route('branch');

        if (!$branch) {
            return false;
        }

        if (! $branch instanceof \App\Models\Branch) {
            $branch = \App\Models\Branch::find($branch);
        }

        if (!$branch) {
            return false;
        }

        // User must own the company that owns the branch
        $company = Company::find($branch->company_id);

        if (!$company) {
            return false;
        }

        return $company->user_id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $branch = $this->route('branch');
        $user = Auth::user();
        $companyIds = Company::where('user_id', $user->id)->pluck('id')->toArray();

        return [
            'company_id' => [
                'required',
                'exists:companies,id',
                'in:' . implode(',', $companyIds),
            ],
            'branch_name' => 'required|string|max:255',
            'branch_address' => 'nullable|string',
            'branch_contact_number' => [
                'nullable',
                'digits:10',
                Rule::unique('branches', 'branch_contact_number')->ignore($branch->id),
            ],
            'head_office' => 'required|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'The company is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'company_id.in' => 'You can only assign branches to your own companies.',
            'branch_name.required' => 'The branch name is required.',
            'branch_name.max' => 'The branch name must not exceed 255 characters.',
            'branch_contact_number.unique' => 'This contact number is already used by another branch.',
            'branch_contact_number.digits' => 'The contact number must be exactly 10 digits.',
            'head_office.required' => 'Please specify if this is a head office.',
            'head_office.boolean' => 'The head office field must be true or false.',
        ];
    }
}
