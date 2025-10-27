<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class VoucherTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $company = Company::all();
        return view('voucher_templates.index', compact('company'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $company = Company::findOrFail($id);
        return view('voucher_templates.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'template_name' => 'required|string',
            'template_address' => 'required|string',
            'template_header' => 'nullable|string',
            'template_note' => 'nullable|string',
            'template_jurisdictional_boundary' => 'nullable|string',
        ]);

        $company = Company::findOrFail($id);
        $company->update($data);

        return redirect()->route('voucher_templates.index')->with('success', 'Voucher template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
