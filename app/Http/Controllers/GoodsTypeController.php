<?php

namespace App\Http\Controllers;

use App\Models\GoodsType;
use Illuminate\Http\Request;

class GoodsTypeController extends Controller
{
    public function index()
    {
        $goodsTypes = GoodsType::all();
        return view('master.goods-types', compact('goodsTypes'));
    }

    public function create()
    {
        return view('master.goods-types-form', ['goodsType' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        GoodsType::create($validated);
        return redirect()->route('master.goods-types.index')->with('success', 'Goods type created successfully');
    }

    public function edit(GoodsType $goodsType)
    {
        return view('master.goods-types-form', compact('goodsType'));
    }

    public function update(Request $request, GoodsType $goodsType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $goodsType->update($validated);
        return redirect()->route('master.goods-types.index')->with('success', 'Goods type updated successfully');
    }

    public function destroy(GoodsType $goodsType)
    {
        $goodsType->delete();
        return redirect()->route('master.goods-types.index')->with('success', 'Goods type deleted successfully');
    }
}
