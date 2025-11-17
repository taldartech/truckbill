<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $promoCodes = PromoCode::orderBy('created_at', 'desc')->get();
        return view('promo_code.index', compact('promoCodes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('promo_code.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:promo_codes,code',
            'discount_type' => 'required|in:percentage,absolute',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        PromoCode::create([
            'code' => $request->code,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'max_uses' => $request->max_uses,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        return redirect()->route('promo_codes.index')->with('success', 'Promo code created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $promoCode = PromoCode::findOrFail($id);
        return view('promo_code.show', compact('promoCode'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $promoCode = PromoCode::findOrFail($id);
        return view('promo_code.edit', compact('promoCode'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $promoCode = PromoCode::findOrFail($id);

        $request->validate([
            'code' => 'required|unique:promo_codes,code,' . $id,
            'discount_type' => 'required|in:percentage,absolute',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $promoCode->update([
            'code' => $request->code,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'max_uses' => $request->max_uses,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : $promoCode->is_active,
        ]);

        return redirect()->route('promo_codes.index')->with('success', 'Promo code updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $promoCode = PromoCode::findOrFail($id);
        $promoCode->delete();

        return redirect()->route('promo_codes.index')->with('success', 'Promo code deleted successfully.');
    }
}
