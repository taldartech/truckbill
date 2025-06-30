<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TransporterController extends Controller
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
        \Log::info('Transporters found: ' . count($transporters));
        
        return view('transporters.index', compact('transporters', 'companies'));
    }

    public function create()
    {
        $companies = \App\Models\Company::all();
        return view('transporters.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'gst_number' => 'nullable|string|max:50',
            'gst_type' => 'required|in:composition,regular',
            'pan_number' => 'nullable|string|max:20',
            'is_owner' => 'required|boolean',
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0',
            'advance_amount' => 'nullable|numeric',
        ]);
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        \App\Models\Transporter::create($validated);
        return redirect()->route('transporters.index')->with('success', 'Transporter created successfully!');
    }

    public function edit(\App\Models\Transporter $transporter)
    {
        $companies = \App\Models\Company::all();
        return view('transporters.edit', compact('transporter', 'companies'));
    }

    public function update(Request $request, \App\Models\Transporter $transporter)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'gst_number' => 'nullable|string|max:50',
            'gst_type' => 'required|in:composition,regular',
            'pan_number' => 'nullable|string|max:20',
            'is_owner' => 'required|boolean',
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0',
            'advance_amount' => 'nullable|numeric',
        ]);
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        $transporter->update($validated);
        return redirect()->route('transporters.index')->with('success', 'Transporter updated successfully!');
    }

    public function destroy(\App\Models\Transporter $transporter)
    {
        $transporter->delete();
        return redirect()->route('transporters.index')->with('success', 'Transporter deleted successfully!');
    }

    //
}
