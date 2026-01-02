<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromoCodeRequest;
use App\Http\Requests\UpdatePromoCodeRequest;
use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PromoCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $promoCodes = PromoCode::orderBy('created_at', 'desc')->get();
        return view('promo_code.index', compact('promoCodes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('promo_code.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromoCodeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Set default is_active to true if not provided
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        } else {
            $validated['is_active'] = (bool) $validated['is_active'];
        }

        DB::transaction(function () use ($validated) {
            PromoCode::create($validated);
        });

        return redirect()
            ->route('promo_codes.index')
            ->with('success', 'Promo code created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PromoCode $promoCode): View
    {
        return view('promo_code.edit', compact('promoCode'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromoCodeRequest $request, PromoCode $promoCode): RedirectResponse
    {
        $validated = $request->validated();

        // Preserve existing is_active if not provided
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = $promoCode->is_active;
        } else {
            $validated['is_active'] = (bool) $validated['is_active'];
        }

        DB::transaction(function () use ($validated, $promoCode) {
            $promoCode->update($validated);
        });

        return redirect()
            ->route('promo_codes.index')
            ->with('success', 'Promo code updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        DB::transaction(function () use ($promoCode) {
            $promoCode->delete();
        });

        return redirect()
            ->route('promo_codes.index')
            ->with('success', 'Promo code deleted successfully.');
    }
}
