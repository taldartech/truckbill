<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyBankDetailsController extends Controller
{
    public function index()
    {
        $companies = Company::query()
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('company_bank_details.index', compact('companies'));
    }

    public function edit(string $id)
    {
        $company = Company::findOrFail($id);
        $this->ensureSuperAdminOwnsCompany($company);

        return view('company_bank_details.edit', compact('company'));
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'bank' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:34',
            'ifsc' => ['nullable', 'string', 'size:11', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
        ], [
            'ifsc.regex' => 'IFSC must be 11 characters (e.g. SBIN0001234).',
        ]);

        if (! empty($data['ifsc'] ?? null)) {
            $data['ifsc'] = strtoupper($data['ifsc']);
        }

        $company = Company::findOrFail($id);
        $this->ensureSuperAdminOwnsCompany($company);
        $company->update($data);

        return redirect()->route('company_bank_details.index')->with('success', 'Company bank details updated successfully.');
    }

    private function ensureSuperAdminOwnsCompany(Company $company): void
    {
        if ((int) $company->user_id !== (int) Auth::id()) {
            abort(403, 'You can only manage bank details for your companies.');
        }
    }
}
