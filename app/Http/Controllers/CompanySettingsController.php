<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingsRequest;
use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('company_settings.index', compact('companies'));
    }

    public function edit(Company $company): View
    {
        $this->ensureSuperAdminOwnsCompany($company);

        $setting = $company->setting()->firstOrCreate(
            ['company_id' => $company->id],
            CompanySetting::defaultsFor($company)
        );

        return view('company_settings.edit', compact('company', 'setting'));
    }

    public function update(UpdateCompanySettingsRequest $request, Company $company): RedirectResponse
    {
        $this->ensureSuperAdminOwnsCompany($company);

        $validated = $request->validated();
        $this->validateVoucherHeaderConstraints($validated);

        DB::transaction(function () use ($company, $validated): void {
            $company->setting()->updateOrCreate(
                ['company_id' => $company->id],
                $validated
            );
        });

        return redirect()
            ->route('company_settings.index')
            ->with('success', 'Company settings updated successfully.');
    }

    private function ensureSuperAdminOwnsCompany(Company $company): void
    {
        if ((int) $company->user_id !== (int) Auth::id()) {
            abort(403, 'You can only manage settings for your companies.');
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateVoucherHeaderConstraints(array $validated): void
    {
        if (empty(trim((string) ($validated['template_name'] ?? '')))) {
            throw ValidationException::withMessages([
                'template_name' => 'The voucher header name cannot be empty.',
            ]);
        }

        if (empty(trim((string) ($validated['template_address'] ?? '')))) {
            throw ValidationException::withMessages([
                'template_address' => 'The voucher header address cannot be empty.',
            ]);
        }
    }
}
