<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBranchRequest extends FormRequest
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

        // Check if company_id is provided and user owns the company
        $companyId = $this->input('company_id');

        if (!$companyId) {
            // Will be caught by validation rules
            return true;
        }

        $company = Company::find($companyId);

        if (!$company) {
            // Will be caught by validation rules
            return true;
        }

        // User must own the company to create branches for it
        return $company->user_id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
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
            'branch_contact_number' => 'nullable|unique:branches,branch_contact_number|digits:10',
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
            'company_id.in' => 'You can only create branches for your own companies.',
            'branch_name.required' => 'The branch name is required.',
            'branch_name.max' => 'The branch name must not exceed 255 characters.',
            'branch_contact_number.unique' => 'This contact number is already used by another branch.',
            'branch_contact_number.digits' => 'The contact number must be exactly 10 digits.',
            'head_office.required' => 'Please specify if this is a head office.',
            'head_office.boolean' => 'The head office field must be true or false.',
        ];
    }
}
