<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVoucherTemplateRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoucherTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $user = Auth::user();

        // Super admin can only see their own companies
        $companies = Company::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('voucher_templates.index', compact('companies'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $voucher_template): View
    {
        $user = Auth::user();

        // Check authorization - user can only edit voucher templates for their own companies
        if ($voucher_template->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        return view('voucher_templates.edit', compact('voucher_template'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVoucherTemplateRequest $request, Company $voucher_template): RedirectResponse
    {
        // Form Request validation already passed, now add controller-level validation
        $validated = $request->validated();

        // Additional controller-level validation for voucher template constraints
        $this->validateVoucherTemplateConstraints($validated, $voucher_template);

        DB::transaction(function () use ($validated, $voucher_template) {
            $voucher_template->update($validated);
        });

        return redirect()
            ->route('voucher_templates.index')
            ->with('success', 'Voucher template updated successfully.');
    }

    /**
     * Validate voucher template constraints for update.
     * Controller-level validation for business logic.
     */
    private function validateVoucherTemplateConstraints(array $validated, Company $voucher_template): void
    {
        $user = Auth::user();

        // Validate company exists and belongs to user (additional controller-level check)
        if (! $voucher_template->exists) {
            throw ValidationException::withMessages([
                'company_id' => 'The selected company does not exist.',
            ]);
        }

        if ($voucher_template->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'company_id' => 'You can only update voucher templates for your own companies.',
            ]);
        }

        // Validate template name is not empty after trimming
        if (empty(trim($validated['template_name']))) {
            throw ValidationException::withMessages([
                'template_name' => 'The template name cannot be empty or contain only whitespace.',
            ]);
        }

        // Validate template address is not empty after trimming
        if (empty(trim($validated['template_address']))) {
            throw ValidationException::withMessages([
                'template_address' => 'The template address cannot be empty or contain only whitespace.',
            ]);
        }

        // Validate template note doesn't exceed reasonable length if provided
        if (isset($validated['template_note']) && strlen($validated['template_note']) > 5000) {
            throw ValidationException::withMessages([
                'template_note' => 'The template note must not exceed 5000 characters.',
            ]);
        }
    }
}
