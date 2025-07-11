<?php

namespace App\Http\Controllers;

use App\Models\TruckOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TruckOwnerController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Transporter::with('company');
        
        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }
        
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('gst_number')) {
            $query->where('gst_number', 'like', '%' . $request->gst_number . '%');
        }
        if ($request->filled('gst_type')) {
            $query->where('gst_type', $request->gst_type);
        }
        if ($request->filled('pan_number')) {
            $query->where('pan_number', 'like', '%' . $request->pan_number . '%');
        }
        if ($request->filled('is_owner')) {
            $query->where('is_owner', $request->is_owner);
        }
        if ($request->filled('company_id') && auth()->user()->userRole->name === 'super_admin') {
            $query->where('company_id', $request->company_id);
        }
        
        $transporters = $query->orderByDesc('created_at')->get();
        $companies = \App\Models\Company::all();
        
        // Log the number of transporters found
        \Log::info('TruckOwner found: ' . count($transporters));
        
        return view('truck_owner.index', compact('transporters', 'companies'));
    }

    public function create()
    {
        $companies = \App\Models\Company::all();
        // For create, we can't filter trucks as we don't have transporter_id yet
        $trucks = \App\Models\Truck::all();
        return view('truck_owner.create', compact('companies', 'trucks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
           'gst_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_type' => 'required|in:composition,regular',
            'is_owner' => 'required|boolean',
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0',
            'advance_amount' => 'nullable|numeric',
            'pan_active' => 'required|boolean',
        ]);
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
        \App\Models\Transporter::create($validated);
        return redirect()->route('truck_owner.index')->with('success', 'TruckOwner created successfully!');
    }

    public function edit(\App\Models\Transporter $transporter)
    {
        $companies = \App\Models\Company::all();
        // Filter trucks to show only those belonging to this transporter
        $trucks = \App\Models\Truck::where('transporter_id', $transporter->id)->get();
        return view('truck_owner.edit', compact('transporter', 'companies', 'trucks'));
    }

    public function update(Request $request, \App\Models\Transporter $transporter)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
           'gst_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_type' => 'required|in:composition,regular',
            'is_owner' => 'required|boolean',
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0',
            'advance_amount' => 'nullable|numeric',
            'pan_active' => 'required|boolean',
        ]); 
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
        $transporter->update($validated);
        return redirect()->route('truck_owner.index')->with('success', 'TruckOwner updated successfully!');
    }

    public function destroy(\App\Models\Transporter $transporter)
    {
        $transporter->delete();
        return redirect()->route('truck_owner.index')->with('success', 'TruckOwner deleted successfully!');
    }

    //
}
