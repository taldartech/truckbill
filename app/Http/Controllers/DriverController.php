<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $company_id = auth()->user()->company_id;
        return view('drivers.index', compact('company_id'));
    }

    public function getDriversData(Request $request)
    {
        $query = \App\Models\Driver::query();
        $company_id = auth()->user()->company_id;
        $query->where('company_id', $company_id);
        // Handle search
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'LIKE', '%' . $searchValue . '%')
                  ->orWhere('mobile', 'LIKE', '%' . $searchValue . '%')
                  ->orWhere('license_number', 'LIKE', '%' . $searchValue . '%')
                  ->orWhere('address', 'LIKE', '%' . $searchValue . '%');
            });
        }

        // Handle individual column search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Name
                            $query->where('name', 'LIKE', '%' . $searchValue . '%');
                            break;
                        case 2: // Mobile
                            $query->where('mobile', 'LIKE', '%' . $searchValue . '%');
                            break;
                        case 3: // License Number
                            $query->where('license_number', 'LIKE', '%' . $searchValue . '%');
                            break;
                        case 4: // Address
                            $query->where('address', 'LIKE', '%' . $searchValue . '%');
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
                case 0: // Sr. No
                    // No sorting for serial number column
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                case 2: // Mobile
                    $query->orderBy('mobile', $orderDirection);
                    break;
                case 3: // License Number
                    $query->orderBy('license_number', $orderDirection);
                    break;
                case 4: // Address
                    $query->orderBy('address', $orderDirection);
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $totalRecords = \App\Models\Driver::count();
        $filteredRecords = $query->count();

        $drivers = $query->skip($request->start ?? 0)
                        ->take($request->length ?? 10)
                        ->get();

        $data = [];
        foreach ($drivers as $index => $driver) {
            $data[] = [
                'DT_RowIndex' => $request->start + $index + 1,
                'id' => $driver->id,
                'name' => $driver->name ?? '-',
                'mobile' => $driver->mobile ?? '-',
                'license_number' => $driver->license_number ?? '-',
                'address' => $driver->address ?? '-',
                'actions' => $this->getActionButtons($driver)
            ];
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    private function getActionButtons($driver)
    {
        $buttons = '<div class="d-flex gap-1">';
        
       
            $buttons .= '<button type="button" class="btn btn-sm btn-outline-primary edit-driver-btn" title="Edit Driver" ';
            $buttons .= 'data-driver-id="' . $driver->id . '" ';
            $buttons .= 'data-driver-name="' . htmlspecialchars($driver->name) . '" ';
            $buttons .= 'data-driver-mobile="' . htmlspecialchars($driver->mobile ?? '') . '" ';
            $buttons .= 'data-driver-license="' . htmlspecialchars($driver->license_number ?? '') . '" ';
            $buttons .= 'data-driver-address="' . htmlspecialchars($driver->address ?? '') . '">';
            $buttons .= '<i class="bi bi-pencil"></i></button>';
            
            $buttons .= '<form action="' . route('drivers.destroy', $driver->id) . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this driver?\')">';
            $buttons .= csrf_field() . method_field('DELETE');
            $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Driver">';
            $buttons .= '<i class="bi bi-trash"></i></button></form>';
        
        
        $buttons .= '</div>';
        return $buttons;
    }

    public function getDriversApi()
    {
        $drivers = \App\Models\Driver::select('id', 'name', 'mobile')
        ->where('company_id', auth()->user()->company_id) // ✅ filter by company
        ->orderBy('name')
        ->get();
        
        return response()->json($drivers);
    }

    public function create()
    {
        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
           
            'name' => 'required|string|max:100',
            'mobile' => [
                'required',
                'digits:10',
                'unique:drivers,mobile',
            ],
            'license_number' => [
                'nullable',
                'string',
                'unique:drivers,license_number',
            ],
            'address' => 'nullable|string|max:255',
        ]);
        $validated['company_id'] = auth()->user()->company_id;
        
        $driver = \App\Models\Driver::create($validated);
        
        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Driver created successfully!',
                'driver' => $driver
            ]);
        }
        
        return redirect()->route('drivers.index')->with('success', 'Driver created successfully!');
    }

    public function edit(\App\Models\Driver $driver)
    {
        return view('drivers.edit', compact('driver'));
    }

    public function update(Request $request, \App\Models\Driver $driver)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:100',
            'mobile' => [
                'required',
                'digits:10',
                'unique:drivers,mobile,' . $driver->id,
            ],
            'license_number' => [
                'nullable',
                'string',
                'unique:drivers,license_number,' . $driver->id,
            ],
            'address' => 'nullable|string|max:255',
        ]);
        
        $driver->update($validated);
        
        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Driver updated successfully!',
                'driver' => $driver->fresh()
            ]);
        }
        
        return redirect()->route('drivers.index')->with('success', 'Driver updated successfully!');
    }

    public function destroy(\App\Models\Driver $driver)
    {
        $driver->delete();
        return redirect()->route('drivers.index')->with('success', 'Driver deleted successfully!');
    }

    //
}
