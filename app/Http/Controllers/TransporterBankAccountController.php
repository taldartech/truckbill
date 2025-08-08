<?php

namespace App\Http\Controllers;

use App\Models\Transporter;
use Illuminate\Http\Request;

class TransporterBankAccountController extends Controller
{
    public function index(Request $request)
    {
        // Get all companies for super admin
        $companies = \App\Models\Company::all();
        
        // Get selected company ID
        $selectedCompanyId = $request->get('company_id');
        
        // Initialize empty collections
        $transporters = collect();
        
        // If company is selected, get transporters for that company (excluding truck owners)
        if ($selectedCompanyId) {
            $transporters = Transporter::where('is_owner', 0)
                ->where('company_id', $selectedCompanyId)
                ->get();
        }
        
        return view('transporter_bank_accounts.index', compact('companies', 'transporters', 'selectedCompanyId'));
    }

    public function updateBeneficiaryId(Request $request, $transporterId)
    {
        $transporter = Transporter::findOrFail($transporterId);
        
        $validated = $request->validate([
            'beneficiary_id' => [
                'required',
                'string',
                'max:255',
                // Check uniqueness but exclude current transporter
                \Illuminate\Validation\Rule::unique('transporters', 'beneficiary_id')->ignore($transporter->id)
            ],
        ], [
            'beneficiary_id.unique' => 'This Beneficiary ID is already in use by another transporter/truck owner.',
        ]);

        $transporter->update(['beneficiary_id' => $validated['beneficiary_id']]);

        return redirect()->back()->with('success', 'Beneficiary ID updated successfully!');
    }
}
