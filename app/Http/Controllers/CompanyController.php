<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Company::query();
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('gst_number')) {
            $query->where('gst_number', 'like', '%' . $request->gst_number . '%');
        }
        if ($request->filled('pan_number')) {
            $query->where('pan_number', 'like', '%' . $request->pan_number . '%');
        }
        $companies = $query->orderByDesc('created_at')->get();
        return view('company.index', compact('companies'));
    }

    public function edit($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        return view('company.edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gst_number' => [
    'nullable',
    'string',
    'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_scheme' => 'nullable|in:reverse charge,forward charge',
            'gst_type' => 'nullable|in:composition,regular',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
    'nullable',
    'digits:10',
],
        ]);
        $company->update($validated);
        return redirect()->route('company.index')->with('success', 'Company updated successfully!');
    }

    public function destroy($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $company->delete();
        return redirect()->route('company.index')->with('success', 'Company deleted successfully!');
    }
    public function create()
    {
        return view('company.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_scheme' => 'nullable|in:reverse charge,forward charge',
            'gst_type' => 'nullable|in:composition,regular',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
    'nullable',
    'digits:10',
],
        ]);
        
        // Set default GST type to regular if not provided
        if (!$request->filled('gst_type')) {
            $validated['gst_type'] = 'regular';
        }
        
        \App\Models\Company::create($validated);
        return redirect()->route('company.create')->with('success', 'Company registered successfully!');
    }
}
