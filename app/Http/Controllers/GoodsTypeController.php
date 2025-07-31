<?php

namespace App\Http\Controllers;

use App\Models\GoodsType;
use Illuminate\Http\Request;

class GoodsTypeController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getGoodsTypesData($request);
        }
        
        return view('master.goods-types');
    }
    
    private function getGoodsTypesData(Request $request)
    {
        $query = GoodsType::query();
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where('name', 'like', "%{$searchValue}%");
        }
        
        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Name column
                            $query->where('name', 'like', "%{$searchValue}%");
                            break;
                    }
                }
            }
        }
        
        // Handle sorting
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDirection = $request->order[0]['dir'];
            
            switch ($orderColumn) {
                case 0: // ID
                    $query->orderBy('id', $orderDirection);
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                default:
                    $query->orderBy('id', 'asc');
            }
        } else {
            $query->orderBy('id', 'asc');
        }
        
        // Get total count before pagination
        $totalRecords = GoodsType::count();
        $filteredRecords = $query->count();
        
        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $goodsTypes = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $goodsTypes->map(function($goodsType) {
            return [
                'id' => $goodsType->id,
                'name' => $goodsType->name,
                'actions' => $this->getActionButtons($goodsType)
            ];
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($goodsType)
    {
        $editUrl = route('master.goods-types.edit', $goodsType->id);
        $deleteUrl = route('master.goods-types.destroy', $goodsType->id);
        
        return '
            <div class="d-flex gap-1">
                <a href="' . $editUrl . '" class="btn btn-sm btn-info">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this goods type?\')">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        ';
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
