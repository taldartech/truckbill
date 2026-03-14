<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTruckTypeRequest;
use App\Http\Requests\UpdateTruckTypeRequest;
use App\Models\TruckType;
use Illuminate\Http\Request;

class TruckTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getTruckTypesData($request);
        }

        return view('master.truck-types');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function getTruckTypesData(Request $request)
    {
        $query = TruckType::query();

        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where('name', 'like', '%' . $searchValue . '%');
        }

        if ($request->has('columns')) {
            foreach ($request->input('columns') as $index => $column) {
                if (! empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    if ($index === 1) {
                        $query->where('name', 'like', '%' . $searchValue . '%');
                    }
                }
            }
        }

        if ($request->has('order.0')) {
            $orderColumn = (int) $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'asc');
            $orderDir = $orderDir === 'desc' ? 'desc' : 'asc';
            switch ($orderColumn) {
                case 0:
                    $query->orderBy('id', $orderDir);
                    break;
                case 1:
                    $query->orderBy('name', $orderDir);
                    break;
                default:
                    $query->orderBy('id', 'asc');
            }
        } else {
            $query->orderBy('name', 'asc');
        }

        $totalRecords = TruckType::count();
        $filteredRecords = $query->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? $length : $totalRecords;
        $truckTypes = $query->skip($start)->take($length)->get();

        $data = $truckTypes->map(function (TruckType $truckType) {
            return [
                'id' => $truckType->id,
                'name' => $truckType->name,
                'actions' => $this->getActionButtons($truckType),
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function getActionButtons(TruckType $truckType): string
    {
        if (! auth()->user()->canActAsAdmin()) {
            return '';
        }

        $editUrl = route('master.truck-types.edit', $truckType);
        $deleteUrl = route('master.truck-types.destroy', $truckType);

        return '
            <div class="d-flex gap-1">
                <a href="' . e($editUrl) . '" class="btn btn-sm btn-edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="' . e($deleteUrl) . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this truck type? Trucks using it will keep the name.\')">
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
        return view('master.truck-types-form', ['truckType' => null]);
    }

    public function store(StoreTruckTypeRequest $request)
    {
        TruckType::create($request->validated());

        return redirect()->route('master.truck-types.index')->with('success', 'Truck type created successfully.');
    }

    public function edit(TruckType $truck_type)
    {
        return view('master.truck-types-form', ['truckType' => $truck_type]);
    }

    public function update(UpdateTruckTypeRequest $request, TruckType $truck_type)
    {
        $truck_type->update($request->validated());

        return redirect()->route('master.truck-types.index')->with('success', 'Truck type updated successfully.');
    }

    public function destroy(TruckType $truck_type)
    {
        $truck_type->delete();

        return redirect()->route('master.truck-types.index')->with('success', 'Truck type deleted successfully.');
    }
}
