<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transporter;

class TruckOwnerBankAccountController extends Controller
{
    public function index(Request $request)
    {
        // Get all companies for super admin
        $companies = \App\Models\Company::all()->where('user_id', auth()->user()->id);

        // Get selected company ID and search term
        $selectedCompanyId = $request->get('company_id');
        $search = $request->get('search');

        // Initialize empty collections
        $truckOwners = collect();

        // If company is selected, get truck owners for that company
        if ($selectedCompanyId) {
            $query = Transporter::where('is_owner', 1)
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

            $truckOwners = $query->paginate(10)->appends($request->query());
        }

        return view('truck_owner_bank_accounts.index', compact('companies', 'truckOwners', 'selectedCompanyId', 'search'));
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
            'short_name' => 'required|string|max:255|unique:transporters,short_name,' . $transporter->id,
        ], [
            'beneficiary_id.unique' => 'This Beneficiary ID is already in use by another transporter/truck owner.',
        ]);

        $transporter->update([
          'beneficiary_id' => $validated['beneficiary_id'],
          'short_name' => $validated['short_name']
        ]);

        return redirect()->back()->with('success', 'Beneficiary ID updated successfully!');
    }
}
