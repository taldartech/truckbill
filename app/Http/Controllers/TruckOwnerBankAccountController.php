<?php

namespace App\Http\Controllers;

use App\Models\TruckOwner;
use App\Models\TruckOwnerBankAccount;
use Illuminate\Http\Request;
use App\Models\Transporter;
use App\Models\TransporterBankAccount;

class TruckOwnerBankAccountController extends Controller
{
    public function index(Request $request)
    {
        // Get all companies for super admin
        $companies = \App\Models\Company::all();
        
        // Get selected company ID
        $selectedCompanyId = $request->get('company_id');
        
        // Initialize empty collections
        $truckOwners = collect();
        
        // If company is selected, get truck owners for that company
        if ($selectedCompanyId) {
            $truckOwners = Transporter::where('is_owner', 1)
                ->where('company_id', $selectedCompanyId)
                ->get();
        }
        
        return view('truck_owner_bank_accounts.index', compact('companies', 'truckOwners', 'selectedCompanyId'));
    }

    public function create($transporter_id)
    {
        $transporter = Transporter::findOrFail($transporter_id);
        return view('truck_owner_bank_accounts.create', compact('transporter', 'transporter_id'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:11',
            'beneficiary_name' => 'required|string|max:255',
            'bulk_payment_id' => 'nullable|string|max:255',
        ]);

        TransporterBankAccount::create($validated);
        return redirect()->route('truck_owner_bank_accounts.index')->with('success', 'Bank account added successfully!');
    }

    public function edit(TransporterBankAccount $account)
    {
        $transporters = Transporter::where('is_owner', 1)->get();
        return view('truck_owner_bank_accounts.edit', compact('account', 'transporters'));
    }

    public function update(Request $request, TransporterBankAccount $account)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:11',
            'beneficiary_name' => 'required|string|max:255',
            'bulk_payment_id' => 'nullable|string|max:255',
        ]);

        $account->update($validated);
        return redirect()->route('truck_owner_bank_accounts.index')->with('success', 'Bank account updated successfully!');
    }

    public function destroy(TransporterBankAccount $account)
    {
        $account->delete();
        return redirect()->route('truck_owner_bank_accounts.index')->with('success', 'Bank account deleted successfully!');
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
