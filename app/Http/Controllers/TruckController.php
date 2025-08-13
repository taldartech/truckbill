<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TruckController extends Controller
{
    public function getTrucksForTransporter($transporterId)
    {
        $trucks = \App\Models\Truck::where('transporter_id', $transporterId)
            ->whereHas('transporter', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->get();

        return response()->json($trucks);
    }
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getTrucksData($request);
        }
        
        $transporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)->get();
        $drivers = \App\Models\Driver::all();
        return view('trucks.index', compact('transporters', 'drivers'));
    }
    
    private function getTrucksData(Request $request)
    {
        $query = \App\Models\Truck::with(['transporter', 'driver'])->whereHas('transporter', function($q) {
            $q->where('company_id', auth()->user()->company_id);
        });
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('truck_number', 'like', "%{$searchValue}%")
                  ->orWhere('truck_type', 'like', "%{$searchValue}%")
                  ->orWhere('registration_date', 'like', "%{$searchValue}%")
                  ->orWhereHas('transporter', function($transporterQuery) use ($searchValue) {
                      $transporterQuery->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('driver', function($driverQuery) use ($searchValue) {
                      $driverQuery->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }
        
        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Truck Number
                            $query->where('truck_number', 'like', "%{$searchValue}%");
                            break;
                        case 2: // Truck Type
                            $query->where('truck_type', 'like', "%{$searchValue}%");
                            break;
                        case 3: // Registration Date
                            $query->where('registration_date', 'like', "%{$searchValue}%");
                            break;
                        case 4: // Transporter
                            $query->whereHas('transporter', function($transporterQuery) use ($searchValue) {
                                $transporterQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 5: // Driver
                            $query->whereHas('driver', function($driverQuery) use ($searchValue) {
                                $driverQuery->where('name', 'like', "%{$searchValue}%");
                            });
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
                case 1: // Truck Number
                    $query->orderBy('truck_number', $orderDirection);
                    break;
                case 2: // Truck Type
                    $query->orderBy('truck_type', $orderDirection);
                    break;
                case 3: // Registration Date
                    $query->orderBy('registration_date', $orderDirection);
                    break;
                case 4: // Transporter
                    $query->join('transporters', 'trucks.transporter_id', '=', 'transporters.id')
                          ->orderBy('transporters.name', $orderDirection)
                          ->select('trucks.*');
                    break;
                case 5: // Driver
                    $query->leftJoin('drivers', 'trucks.driver_id', '=', 'drivers.id')
                          ->orderBy('drivers.name', $orderDirection)
                          ->select('trucks.*');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Get total count before pagination
        $totalQuery = \App\Models\Truck::whereHas('transporter', function($q) {
            $q->where('company_id', auth()->user()->company_id);
        });
        $totalRecords = $totalQuery->count();
        $filteredRecords = $query->count();
        
        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $trucks = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $trucks->map(function($truck, $index) use ($start) {
            return [
                'id' => $truck->id,
                'truck_number' => $truck->truck_number,
                'truck_type' => $truck->truck_type ?: '-',
                'registration_date' => $truck->registration_date ? date('Y-m-d', strtotime($truck->registration_date)) : '-',
                'transporter' => $truck->transporter ? $truck->transporter->name : '-',
                'driver' => $truck->driver ? $truck->driver->name : '-',
                // 'actions' => $this->getActionButtons($truck)
                'actions' => auth()->user()->userRole->name === 'admin'
                        ? $this->getActionButtons($truck)
                        : ''  // empty string for non-admins
            ];
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($truck)
    {
        $buttons = '<div class="d-flex gap-1">';
        
        // Assign Driver button
        $buttons .= '<button type="button" class="btn btn-sm btn-outline-success assign-driver-btn" title="Assign Driver" ';
        $buttons .= 'data-truck-id="' . $truck->id . '" ';
        $buttons .= 'data-truck-number="' . htmlspecialchars($truck->truck_number) . '" ';
        $buttons .= 'data-truck-type="' . htmlspecialchars($truck->truck_type) . '" ';
        $buttons .= 'data-transporter-name="' . htmlspecialchars($truck->transporter->name) . '" ';
        $buttons .= 'data-current-driver-id="' . ($truck->driver_id ?: '') . '" ';
        $buttons .= 'data-current-driver-name="' . htmlspecialchars($truck->driver->name ?? '') . '">';
        $buttons .= '<i class="bi bi-person-plus"></i></button>';
        
        // Delete button
        $deleteUrl = route('trucks.destroy', $truck->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this truck?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Truck">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';
        
        $buttons .= '</div>';
        return $buttons;
    }

    public function getOwnerTransporters($truckId)
    {
        $truck = \App\Models\Truck::with('transporter')
            ->where('id', $truckId)
            ->whereHas('transporter', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->first();

        if (!$truck) {
            return response()->json([], 404);
        }

        // Get all transporters that are owners
        $ownerTransporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)
            ->where('is_owner', 1)
            ->get();

        return response()->json($ownerTransporters);
    }

    public function getAgencyTransporters($truckId)
    {
        $truck = \App\Models\Truck::with('transporter')
            ->where('id', $truckId)
            ->whereHas('transporter', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->first();

        if (!$truck) {
            return response()->json([], 404);
        }

        // Get all transporters that are agencies
        $agencyTransporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)
            ->where('is_owner', 0)
            ->get();

        return response()->json($agencyTransporters);
    }



    public function create($transporter_id)
    {
        // Fetch only the specific transporter
        $transporter = \App\Models\Transporter::where('company_id', auth()->user()->company_id)
            ->where('id', $transporter_id)
            ->firstOrFail();
            
        $drivers = \App\Models\Driver::all();
        return view('trucks.create', compact('transporter', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
           'truck_number' => [
    'required',
    'string',
    'size:10',
    'regex:/^[A-Z]{2}\d{2}[A-Z]{2}\d{4}$/',
],
            'truck_type' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:drivers,id',
            'registration_date' => 'nullable|date',
        ]);

        // Driver relationship will be handled automatically via driver_id

        try {
            $truck = \App\Models\Truck::create($validated);
            
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Truck created successfully!',
                    'truck' => $truck
                ]);
            }
            
            return redirect()->route('trucks.index')->with('success', 'Truck created successfully!');
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database constraint violations
            if ($e->errorInfo[1] == 1062) { // Duplicate entry error
                $errorMessage = 'This truck number is already registered in the system. Please use a different truck number.';
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['truck_number' => [$errorMessage]]
                    ], 422);
                }
                
                return redirect()->back()->withErrors(['truck_number' => $errorMessage])->withInput();
            }
            
            // Handle other database errors
            $genericError = 'Unable to create truck due to a database error. Please try again.';
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $genericError
                ], 500);
            }
            
            return redirect()->back()->withErrors(['general' => $genericError])->withInput();
        }
    }
    public function checkTruckNumber(Request $request)
{
    $exists = \App\Models\Truck::where('truck_number', $request->truck_number)->exists();
    return response()->json(['exists' => $exists]);
}

    public function edit(\App\Models\Truck $truck)
    {
        $transporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)->get();
        $drivers = \App\Models\Driver::all();
        return view('trucks.edit', compact('truck', 'transporters', 'drivers'));
    }

    public function update(Request $request, \App\Models\Truck $truck)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'truck_number' => [
                'required',
                'string',
                'min:6',
                'max:15',
                'unique:trucks,truck_number,' . $truck->id,
            ],
            'truck_type' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:drivers,id',
            'registration_date' => 'nullable|date',
        ]);

        // Driver relationship will be handled automatically via driver_id

        try {
            $truck->update($validated);
            
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Truck updated successfully!',
                    'truck' => $truck->fresh()
                ]);
            }
            
            return redirect()->route('trucks.index')->with('success', 'Truck updated successfully!');
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database constraint violations
            if ($e->errorInfo[1] == 1062) { // Duplicate entry error
                $errorMessage = 'This truck number is already registered in the system. Please use a different truck number.';
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['truck_number' => [$errorMessage]]
                    ], 422);
                }
                
                return redirect()->back()->withErrors(['truck_number' => $errorMessage])->withInput();
            }
            
            // Handle other database errors
            $genericError = 'Unable to update truck due to a database error. Please try again.';
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $genericError
                ], 500);
            }
            
            return redirect()->back()->withErrors(['general' => $genericError])->withInput();
        }
    }

    public function destroy(\App\Models\Truck $truck)
    {
        try {
            // Soft delete the truck
            $truck->delete();
            
            // Return JSON response for AJAX requests
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Truck deleted successfully!'
                ]);
            }
            
            return redirect()->route('trucks.index')->with('success', 'Truck deleted successfully!');
            
        } catch (\Exception $e) {
            // Handle any errors during deletion
            $errorMessage = 'Unable to delete truck. Please try again.';
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->withErrors(['general' => $errorMessage]);
        }
    }

    public function show(\App\Models\Truck $truck)
    {
        // Load relationships
        $truck->load(['transporter', 'driver']);
        
        // Check if user has access to this truck (same company)
        if ($truck->transporter->company_id !== auth()->user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Format the data for display
        $truckData = [
            'id' => $truck->id,
            'truck_number' => $truck->truck_number,
            'truck_type' => $truck->truck_type,
            'registration_date' => $truck->registration_date ? \Carbon\Carbon::parse($truck->registration_date)->format('d-m-Y') : null,
            'transporter' => [
                'id' => $truck->transporter->id,
                'name' => $truck->transporter->name,
                'short_name' => $truck->transporter->short_name,
                'mobile_number' => $truck->transporter->mobile_number,
                'gst_number' => $truck->transporter->gst_number,
                'pan_number' => $truck->transporter->pan_number,
                'address' => $truck->transporter->address,
            ],
            'driver' => $truck->driver ? [
                'id' => $truck->driver->id,
                'name' => $truck->driver->name,
                'mobile' => $truck->driver->mobile,
                'license_number' => $truck->driver->license_number,
            ] : null,
            'created_at' => $truck->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y h:i A'),
            'updated_at' => $truck->updated_at->setTimezone('Asia/Kolkata')->format('d-m-Y h:i A'),
        ];
        
        return response()->json($truckData);
    }

    public function assignDriver(Request $request, \App\Models\Truck $truck)
    {
        $validated = $request->validate([
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        // Check if user has access to this truck (same company)
        if ($truck->transporter->company_id !== auth()->user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update truck with driver assignment
        if ($validated['driver_id']) {
            $truck->update([
                'driver_id' => $validated['driver_id']
            ]);
            $message = 'Driver assigned successfully!';
        } else {
            $truck->update([
                'driver_id' => null
            ]);
            $message = 'Driver removed successfully!';
        }

        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'truck' => $truck->fresh()->load('driver')
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function report(Request $request)
    {
        $query = \App\Models\Truck::with(['transporter', 'driver']);

        if ($request->filled('truck_type')) {
            $query->where('truck_type', $request->truck_type);
        }
        if ($request->filled('transporter_id')) {
            $query->where('transporter_id', $request->transporter_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('registration_date_from')) {
            $query->whereDate('registration_date', '>=', $request->registration_date_from);
        }
        if ($request->filled('registration_date_to')) {
            $query->whereDate('registration_date', '<=', $request->registration_date_to);
        }
        $trucks = $query->orderByDesc('created_at')->get();
        $total = $trucks->count();
        $transporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)->get();
        $drivers = \App\Models\Driver::all();
        $truckTypes = [
            'Open', 'Closed', 'Container', 'Trailer', 'Tanker', 'Refrigerated', 'Flatbed', 'Tipper', 'Other'
        ];
        return view('trucks.report', compact('trucks', 'total', 'transporters', 'drivers', 'truckTypes'));
    }
}
