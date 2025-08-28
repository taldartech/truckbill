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
        
        // Get selected company ID and search term
        $selectedCompanyId = $request->get('company_id');
        $search = $request->get('search');
        
        // Initialize empty collections
        $transporters = collect();
        
        // If company is selected, get transporters for that company (excluding truck owners)
        if ($selectedCompanyId) {
            $query = Transporter::where('is_owner', 0)
                ->where('company_id', $selectedCompanyId);
            
            // Add search functionality
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('gst_number', 'LIKE', '%' . $search . '%')
                      ->orWhere('pan_number', 'LIKE', '%' . $search . '%')
                      ->orWhere('beneficiary_id', 'LIKE', '%' . $search . '%');
                });
            }
            
            $transporters = $query->paginate(10)->appends($request->query());
        }
        
        return view('transporter_bank_accounts.index', compact('companies', 'transporters', 'selectedCompanyId', 'search'));
    }

    public function updateBeneficiaryId(Request $request, $transporterId)
    {
        $transporter = Transporter::findOrFail($transporterId);
        
        $validated = $request->validate([
            'beneficiary_id' => 'required|string|max:255|unique:transporters,beneficiary_id,' . $transporter->id,
            'short_name' => 'required|string|max:255|unique:transporters,short_name,' . $transporter->id,
        ], [
            'beneficiary_id.unique' => 'This Beneficiary ID is already in use by another transporter/truck owner.',
            'short_name.unique' => 'This Short Name is already in use by another transporter/truck owner.',
        ]);

        $transporter->update(['beneficiary_id' => $validated['beneficiary_id'], 'short_name' => $validated['short_name']]);

        return redirect()->back()->with('success', 'Beneficiary ID and Short Name updated successfully!');
    }
}
