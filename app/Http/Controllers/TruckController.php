<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
           // Get only transporters who are owners
    $owners = \App\Models\Transporter::where('is_owner', 1)
    ->where('company_id', auth()->user()->company_id)
    ->get();

        $transporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)->get();
        $drivers = \App\Models\Driver::all();
        return view('trucks.index', compact('transporters', 'drivers', 'owners'));
    }

    private function getTrucksData(Request $request)
    {
        // $query = \App\Models\Truck::with(['transporter', 'driver'])->whereHas('transporter', function($q) {
        //     $q->where('company_id', auth()->user()->company_id);
           
        // });
        $query = \App\Models\Truck::with(['transporter', 'driver'])
    ->where(function ($q) {
        $q->whereHas('transporter', function ($subQuery) {
            $subQuery->where('company_id', auth()->user()->company_id);
        })
        ->orWhereNull('transporter_id');  // Include trucks with no transporter
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
        // $totalQuery = \App\Models\Truck::whereHas('transporter', function($q) {
        //     $q->where('company_id', auth()->user()->company_id);
        // });
        $totalQuery = \App\Models\Truck::where(function ($q) {
            $q->whereHas('transporter', function ($subQuery) {
                $subQuery->where('company_id', auth()->user()->company_id);
            })
            ->orWhereNull('transporter_id');
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
                'truck_number' => '<a href="#" class="truck-number-link text-primary fw-bold" data-id="' . $truck->id . '" style="text-decoration: none; cursor: pointer;">' . $truck->truck_number . '</a>',
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
        $buttons .= '<button type="button" class="btn btn-sm btn-outline-success assign-driver-btn" title="Assign Driver" data-bs-toggle="tooltip" ';
        $buttons .= 'data-truck-id="' . $truck->id . '" ';
        $buttons .= 'data-truck-number="' . htmlspecialchars($truck->truck_number) . '" ';
        $buttons .= 'data-truck-type="' . htmlspecialchars($truck->truck_type) . '" ';
        // $buttons .= 'data-transporter-name="' . htmlspecialchars($truck->transporter->name) . '":"-" ';
        $buttons .= 'data-transporter-name="' . htmlspecialchars($truck->transporter ? $truck->transporter->name : '-') . '" ';

        $buttons .= 'data-current-driver-id="' . ($truck->driver_id ?: '') . '" ';
        $buttons .= 'data-current-driver-name="' . htmlspecialchars($truck->driver->name ?? '') . '">';
        $buttons .= '<i class="bi bi-person-plus"></i></button>';

        // Delete button
        $deleteUrl = route('trucks.destroy', $truck->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this truck?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Truck" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';

        //show button to assign truck owner for those whose transporter_id is null
        if ($truck->transporter_id == null) {
            $buttons .= '<button type="button" class="btn btn-sm btn-outline-primary assign-owner-btn" title="Assign Truck Owner" data-bs-toggle="tooltip" ';
            $buttons .= 'data-truck-id="' . $truck->id . '" ';
            $buttons .= 'data-truck-number="' . htmlspecialchars($truck->truck_number) . '" ';
            $buttons .= 'data-truck-type="' . htmlspecialchars($truck->truck_type) . '" ';
            $buttons .= 'data-transporter-name="' . htmlspecialchars($truck->transporter ? $truck->transporter->name : '-') . '" ';
            $buttons .= 'data-current-driver-id="' . ($truck->driver_id ?: '') . '" ';
            $buttons .= 'data-current-driver-name="' . htmlspecialchars($truck->driver->name ?? '') . '">';
            $buttons .= '<i class="bi bi-truck"></i></button>';
        }

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
        // Fetch company_id for the given transporter_id
    $companyId = \App\Models\Transporter::where('id', $request->transporter_id)->value('company_id');

    // Perform manual uniqueness check scoped by company_id
    $exists = \DB::table('trucks')
        ->join('transporters', 'trucks.transporter_id', '=', 'transporters.id')
        ->where('trucks.truck_number', $request->truck_number)
        ->where('transporters.company_id', $companyId)
        ->exists();

    if ($exists) {
        $errorMessage = 'The truck number must be unique within the company.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'errors' => ['truck_number' => [$errorMessage]]
            ], 422);
        }
        return redirect()->back()->withErrors(['truck_number' => $errorMessage])->withInput();
    }

    // Proceed with validation of other fields except unique rule on truck_number
    $validated = $request->validate([
        'transporter_id' => 'required|exists:transporters,id',
        'truck_number' => [
            'required',
            'string',
            'size:10',
            'regex:/^[A-Z]{2}\d{2}[A-Z]{2}\d{4}$/',
            // No unique rule here since handled above
        ],
        'truck_type' => 'required|string|max:50',
        'driver_id' => 'nullable|exists:drivers,id',
        'registration_date' => 'nullable|date',
    ]);
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
    
    $company_id = auth()->user()->company_id;
    $exists = \App\Models\Truck::where('truck_number', $request->truck_number)
    ->whereHas('transporter', function ($query) use ($company_id) {
        $query->where('company_id', $company_id);
    })

    ->exists();
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
         // Get company_id from transporter_id in request
    $companyId = \App\Models\Transporter::where('id', $request->transporter_id)->value('company_id');

    // Manual uniqueness check ignoring the current truck ID
    $exists = \DB::table('trucks')
        ->join('transporters', 'trucks.transporter_id', '=', 'transporters.id')
        ->where('trucks.truck_number', $request->truck_number)
        ->where('transporters.company_id', $companyId)
        ->where('trucks.id', '<>', $truck->id) // exclude current truck id
        ->exists();

    if ($exists) {
        $errorMessage = 'The truck number must be unique within the company.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'errors' => ['truck_number' => [$errorMessage]]
            ], 422);
        }
        return redirect()->back()->withErrors(['truck_number' => $errorMessage])->withInput();
    }

    // Validation excluding unique rule on truck_number because handled manually
    $validated = $request->validate([
        'transporter_id' => 'required|exists:transporters,id',
        'truck_number' => [
            'required',
            'string',
            'min:6',
            'max:15',
            // no unique rule here
        ],
        'truck_type' => 'required|string|max:50',
        'driver_id' => 'nullable|exists:drivers,id',
        'registration_date' => 'nullable|date',
    ]);

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
//     public function removeTransporter(\App\Models\Truck $truck)
// {

//     $truck->update(['transporter_id' => null]);
    
//     return response()->json([
//         'message' => 'Truck has been removed from the transporter successfully'
//     ]);
// }
// public function removeTransporter(\App\Models\Truck $truck)
// {
//     // Check if this truck has any delivered Lorry Receipts
//     $lorryReceipt = \App\Models\LorryReceipt::where('truck_id', $truck->id)
//         ->where('delivery_status', 'delivered')
//         ->first();

//     if ($lorryReceipt) {
//         // If delivered Lorry Receipt exists, allow transporter removal
//         $truck->update(['transporter_id' => null]);

//         return response()->json([
//             'success' => 'Truck has been removed from the transporter successfully.'
//         ]);
//     } else {
//         // If not delivered (in use), show error
//         return response()->json([
//             'error' => 'Truck cannot be removed from the transporter (it is in use)!!'
//         ]);
//     }
// }
public function removeTransporter(\App\Models\Truck $truck)
{
    // Check if this truck has any undelivered Lorry Receipts
    $hasUndeliveredLr = \App\Models\LorryReceipt::where('truck_id', $truck->id)
        ->where('delivery_status', '!=', 'delivered')
        ->exists();

    if (!$hasUndeliveredLr) {
        // If no undelivered Lorry Receipts exist, allow transporter removal
        $truck->update(['transporter_id' => null]);

        return response()->json([
            'success' => 'Truck has been removed from the transporter successfully.'
        ]);
    } else {
        // If undelivered Lorry Receipts exist, show error
        return response()->json([
            'error' => 'Truck cannot be removed from the transporter (it is in use)!!'
        ]);
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

    public function getTrucksFromLrs($transporterId)
    {
        $lrs = \App\Models\LorryReceipt::where('transporter_id', $transporterId)->get(['truck_id']);
        $trucks = \App\Models\Truck::whereIn('id', $lrs->pluck('truck_id'))->get();
        return response()->json($trucks);
    }

    /**
     * Assign a truck owner to a truck
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignOwner(Request $request)
    {
        $request->validate([
            'truck_id' => 'required|exists:trucks,id',
            'transporter_id' => 'required|exists:transporters,id,is_owner,1,company_id,' . auth()->user()->company_id,
        ]);

        try {
            $truck = \App\Models\Truck::findOrFail($request->truck_id);
            
            // Check if the truck already has this owner
            if ($truck->transporter_id == $request->transporter_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This truck is already assigned to the selected owner.'
                ]);
            }

            $truck->transporter_id = $request->transporter_id;
            $truck->save();

            return response()->json([
                'success' => true,
                'message' => 'Truck owner assigned successfully.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error assigning truck owner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign truck owner. Please try again.'
            ], 500);
        }
    }
}
