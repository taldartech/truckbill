<?php

namespace App\Http\Controllers;

use App\Models\TruckType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TruckController extends Controller
{
    private function ensureCompanyContext(): void
    {
        if (Auth::user()->userRole->name === 'super_admin' && ! session('super_admin_company_id')) {
            abort(403, 'Please enter a company first.');
        }
    }

    private function getCurrentCompanyId(): ?int
    {
        if (Auth::user()->userRole->name === 'super_admin') {
            return session('super_admin_company_id');
        }

        return Auth::user()->company_id;
    }

    private function isTruckNumberTakenInCompany(string $truckNumber, int $companyId, ?int $ignoreTruckId = null): bool
    {
        return \App\Models\Truck::query()
            ->where('truck_number', $truckNumber)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('transporter', function ($transporterQuery) use ($companyId) {
                        $transporterQuery->where('company_id', $companyId);
                    });
            })
            ->when($ignoreTruckId, function ($query) use ($ignoreTruckId) {
                $query->where('id', '!=', $ignoreTruckId);
            })
            ->exists();
    }

    private function isTruckAccessibleToCurrentCompany(\App\Models\Truck $truck): bool
    {
        $companyId = $this->getCurrentCompanyId();

        if ($truck->transporter) {
            return $truck->transporter->company_id === $companyId;
        }

        return (int) $truck->company_id === (int) $companyId;
    }

    public function getTrucksForTransporter($transporterId)
    {
        $this->ensureCompanyContext();
        $companyId = $this->getCurrentCompanyId();
        $trucks = \App\Models\Truck::with(['transporter.transporterMaster', 'driver'])
            ->where('transporter_id', $transporterId)
            ->whereHas('transporter', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();

        return response()->json($trucks);
    }

    public function index(Request $request)
    {
        $this->ensureCompanyContext();
        $companyId = $this->getCurrentCompanyId();
        if ($request->ajax()) {
            return $this->getTrucksData($request);
        }
        // Get only transporters who are owners
        $owners = \App\Models\Transporter::with('transporterMaster')
            ->where('is_owner', 1)
            ->where('company_id', $companyId)
            ->get();

        $transporters = \App\Models\Transporter::with('transporterMaster')
            ->where('company_id', $companyId)
            ->get();
        $drivers = \App\Models\Driver::all();

        return view('trucks.index', compact('transporters', 'drivers', 'owners'));
    }

    private function getTrucksData(Request $request)
    {
        $companyId = $this->getCurrentCompanyId();
        // $query = \App\Models\Truck::with(['transporter', 'driver'])->whereHas('transporter', function($q) {
        //     $q->where('company_id', Auth::user()->company_id);

        // });
        $query = \App\Models\Truck::with(['transporter.transporterMaster', 'driver'])
            ->where(function ($q) use ($companyId) {
                $q->whereHas('transporter', function ($subQuery) use ($companyId) {
                    $subQuery->where('company_id', $companyId);
                })
                    ->orWhere(function ($orphanQuery) use ($companyId) {
                        $orphanQuery->whereNull('transporter_id')
                            ->where('company_id', $companyId);
                    });
            });

        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('truck_number', 'like', "%{$searchValue}%")
                    ->orWhere('truck_type', 'like', "%{$searchValue}%")
                    ->orWhere('registration_date', 'like', "%{$searchValue}%")
                    ->orWhereHas('transporter.transporterMaster', function ($transporterQuery) use ($searchValue) {
                        $transporterQuery->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
                        $driverQuery->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (! empty($column['search']['value'])) {
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
                            $query->whereHas('transporter.transporterMaster', function ($transporterQuery) use ($searchValue) {
                                $transporterQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 5: // Driver
                            $query->whereHas('driver', function ($driverQuery) use ($searchValue) {
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
                        ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                        ->orderBy('transporter_masters.name', $orderDirection)
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
        //     $q->where('company_id', Auth::user()->company_id);
        // });
        $totalQuery = \App\Models\Truck::where(function ($q) use ($companyId) {
            $q->whereHas('transporter', function ($subQuery) use ($companyId) {
                $subQuery->where('company_id', $companyId);
            })
                ->orWhere(function ($orphanQuery) use ($companyId) {
                    $orphanQuery->whereNull('transporter_id')
                        ->where('company_id', $companyId);
                });
        });
        $totalRecords = $totalQuery->count();
        $filteredRecords = $query->count();

        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $trucks = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = $trucks->map(function ($truck, $index) {
            return [
                'id' => $truck->id,
                'truck_number' => '<a href="#" class="truck-number-link text-brand-primary fw-bold" data-id="'.$truck->id.'" style="text-decoration: none; cursor: pointer;">'.$truck->truck_number.'</a>',
                'truck_type' => $truck->truck_type ?: '-',
                'registration_date' => $truck->registration_date ? date('Y-m-d', strtotime($truck->registration_date)) : '-',
                'transporter' => $truck->transporter ? $truck->transporter->name : '-',
                'driver' => $truck->driver ? $truck->driver->name : '-',
                // 'actions' => $this->getActionButtons($truck)
                'actions' => Auth::user()->canActAsAdmin()
                    ? $this->getActionButtons($truck)
                    : '',  // empty string for non-admins
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function getActionButtons($truck)
    {
        $buttons = '<div class="d-flex gap-1">';

        // Assign Driver button
        $buttons .= '<button type="button" class="btn btn-sm btn-success assign-driver-btn" title="Assign Driver" data-bs-toggle="tooltip" ';
        $buttons .= 'data-truck-id="'.$truck->id.'" ';
        $buttons .= 'data-truck-number="'.htmlspecialchars($truck->truck_number).'" ';
        $buttons .= 'data-truck-type="'.htmlspecialchars($truck->truck_type).'" ';
        // $buttons .= 'data-transporter-name="' . htmlspecialchars($truck->transporter->name) . '":"-" ';
        $buttons .= 'data-transporter-name="'.htmlspecialchars($truck->transporter ? $truck->transporter->name : '-').'" ';

        $buttons .= 'data-current-driver-id="'.($truck->driver_id ?: '').'" ';
        $buttons .= 'data-current-driver-name="'.htmlspecialchars($truck->driver->name ?? '').'">';
        $buttons .= '<i class="bi bi-person-plus"></i></button>';

        // Delete button
        $deleteUrl = route('trucks.destroy', $truck->id);
        $buttons .= '<form action="'.$deleteUrl.'" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this truck?\')">';
        $buttons .= csrf_field().method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Truck" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';

        // show button to assign truck owner for those whose transporter_id is null
        if ($truck->transporter_id == null) {
            $buttons .= '<button type="button" class="btn btn-sm btn-view assign-owner-btn" title="Assign Truck Owner" data-bs-toggle="tooltip" ';
            $buttons .= 'data-truck-id="'.$truck->id.'" ';
            $buttons .= 'data-truck-number="'.htmlspecialchars($truck->truck_number).'" ';
            $buttons .= 'data-truck-type="'.htmlspecialchars($truck->truck_type).'" ';
            $buttons .= 'data-transporter-name="'.htmlspecialchars($truck->transporter ? $truck->transporter->name : '-').'" ';
            $buttons .= 'data-current-driver-id="'.($truck->driver_id ?: '').'" ';
            $buttons .= 'data-current-driver-name="'.htmlspecialchars($truck->driver->name ?? '').'">';
            $buttons .= '<i class="bi bi-truck"></i></button>';
        }

        $buttons .= '</div>';

        return $buttons;
    }

    public function getOwnerTransporters($truckId)
    {
        $this->ensureCompanyContext();
        $companyId = $this->getCurrentCompanyId();
        $truck = \App\Models\Truck::with('transporter.transporterMaster')
            ->where('id', $truckId)
            ->where(function ($query) use ($companyId) {
                $query->whereHas('transporter', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->orWhere(function ($orphanQuery) use ($companyId) {
                    $orphanQuery->whereNull('transporter_id')
                        ->where('company_id', $companyId);
                });
            })
            ->first();

        if (! $truck) {
            // Keep status 200 so frontend can treat "not found" as "no owner"
            return response()->json([]);
        }

        // Each truck has exactly one transporter assigned.
        // If that transporter is marked as an owner, return just that owner.
        $transporter = $truck->transporter;
        if ($transporter && $transporter->is_owner) {
            return response()->json([
                [
                    'id' => $transporter->id,
                    'name' => $transporter->name,
                ],
            ]);
        }

        return response()->json([]);
    }

    public function getAgencyTransporters($truckId)
    {
        $this->ensureCompanyContext();
        $companyId = $this->getCurrentCompanyId();
        $truck = \App\Models\Truck::with('transporter.transporterMaster')
            ->where('id', $truckId)
            ->where(function ($query) use ($companyId) {
                $query->whereHas('transporter', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->orWhere(function ($orphanQuery) use ($companyId) {
                    $orphanQuery->whereNull('transporter_id')
                        ->where('company_id', $companyId);
                });
            })
            ->first();

        if (! $truck) {
            return response()->json([], 404);
        }

        // Get all transporters that are agencies
        $agencyTransporters = \App\Models\Transporter::with('transporterMaster')
            ->where('company_id', $companyId)
            ->where('is_owner', 0)
            ->get();

        return response()->json($agencyTransporters);
    }

    public function create($transporter_id = null)
    {
        $this->ensureCompanyContext();
        $companyId = $this->getCurrentCompanyId();
        $transporter = null;

        if ($transporter_id) {
            $transporter = \App\Models\Transporter::with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('id', $transporter_id)
                ->firstOrFail();
        }

        $transporters = \App\Models\Transporter::with('transporterMaster')
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->get();

        $drivers = \App\Models\Driver::all();
        $truckTypes = TruckType::orderBy('name')->get();

        return view('trucks.create', compact('transporter', 'transporters', 'drivers', 'truckTypes'));
    }

    public function store(Request $request)
    {
        $this->ensureCompanyContext();
        $companyId = $this->getCurrentCompanyId();
        $validated = $request->validate([
            'transporter_id' => [
                'nullable',
                Rule::exists('transporters', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                }),
            ],
            'truck_number' => [
                'required',
                'string',
                'regex:/^[A-Z]{2}[A-Z0-9]{1,2}[A-Z0-9]{1,3}[0-9]{4}$/',
            ],
            'truck_type' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:drivers,id',
            'registration_date' => 'nullable|date',
        ]);

        $exists = $this->isTruckNumberTakenInCompany($validated['truck_number'], $companyId);
        if ($exists) {
            $errorMessage = 'The truck number must be unique within the company.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => ['truck_number' => [$errorMessage]],
                ], 422);
            }

            return redirect()->back()->withErrors(['truck_number' => $errorMessage])->withInput();
        }

        $validated['company_id'] = $companyId;
        try {
            $truck = \App\Models\Truck::create($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Truck created successfully!',
                    'truck' => $truck,
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
                        'errors' => ['truck_number' => [$errorMessage]],
                    ], 422);
                }

                return redirect()->back()->withErrors(['truck_number' => $errorMessage])->withInput();
            }

            // Handle other database errors
            $genericError = 'Unable to create truck due to a database error. Please try again.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $genericError,
                ], 500);
            }

            return redirect()->back()->withErrors(['general' => $genericError])->withInput();
        }
    }

    public function checkTruckNumber(Request $request)
    {
        $this->ensureCompanyContext();
        $company_id = $this->getCurrentCompanyId();
        $exists = \App\Models\Truck::where('truck_number', $request->truck_number)
            ->where(function ($query) use ($company_id) {
                $query->where('company_id', $company_id)
                    ->orWhereHas('transporter', function ($transporters) use ($company_id) {
                        $transporters->where('company_id', $company_id);
                    });
            })
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function edit(\App\Models\Truck $truck)
    {
        $this->ensureCompanyContext();
        $companyId = $this->getCurrentCompanyId();
        $transporters = \App\Models\Transporter::with('transporterMaster')
            ->where('company_id', $companyId)
            ->get();
        $drivers = \App\Models\Driver::all();
        $truckTypes = TruckType::orderBy('name')->get();

        return view('trucks.edit', compact('truck', 'transporters', 'drivers', 'truckTypes'));
    }

    public function update(Request $request, \App\Models\Truck $truck)
    {
        $this->ensureCompanyContext();
        $companyId = $this->getCurrentCompanyId();
        $validated = $request->validate([
            'transporter_id' => [
                'nullable',
                Rule::exists('transporters', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                }),
            ],
            'truck_number' => [
                'required',
                'string',
                'regex:/^[A-Z]{2}[A-Z0-9]{1,2}[A-Z0-9]{1,3}[0-9]{4}$/',
            ],
            'truck_type' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:drivers,id',
            'registration_date' => 'nullable|date',
        ]);

        $exists = $this->isTruckNumberTakenInCompany($validated['truck_number'], $companyId, $truck->id);
        if ($exists) {
            $errorMessage = 'The truck number must be unique within the company.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => ['truck_number' => [$errorMessage]],
                ], 422);
            }

            return redirect()->back()->withErrors(['truck_number' => $errorMessage])->withInput();
        }

        $validated['company_id'] = $companyId;

        try {
            $truck->update($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Truck updated successfully!',
                    'truck' => $truck->fresh(),
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
                        'errors' => ['truck_number' => [$errorMessage]],
                    ], 422);
                }

                return redirect()->back()->withErrors(['truck_number' => $errorMessage])->withInput();
            }

            // Handle other database errors
            $genericError = 'Unable to update truck due to a database error. Please try again.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $genericError,
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
        $this->ensureCompanyContext();
        if (! $this->isTruckAccessibleToCurrentCompany($truck)) {
            abort(403, 'Unauthorized access to this truck.');
        }
        // Check if this truck has any undelivered Lorry Receipts
        $hasUndeliveredLr = \App\Models\LorryReceipt::where('truck_id', $truck->id)
            ->where('delivery_status', '!=', 'delivered')
            ->exists();

        if (! $hasUndeliveredLr) {
            // If no undelivered Lorry Receipts exist, allow transporter removal
            $truck->update(['transporter_id' => null]);

            return response()->json([
                'success' => 'Truck has been removed from the transporter successfully.',
            ]);
        } else {
            // If undelivered Lorry Receipts exist, show error
            return response()->json([
                'error' => 'Truck cannot be removed from the transporter (it is in use)!!',
            ]);
        }
    }

    public function destroy(\App\Models\Truck $truck)
    {
        $this->ensureCompanyContext();
        if (! $this->isTruckAccessibleToCurrentCompany($truck)) {
            abort(403, 'Unauthorized access to this truck.');
        }
        try {
            // Soft delete the truck
            $truck->delete();

            // Return JSON response for AJAX requests
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Truck deleted successfully!',
                ]);
            }

            return redirect()->route('trucks.index')->with('success', 'Truck deleted successfully!');
        } catch (\Exception $e) {
            // Handle any errors during deletion
            $errorMessage = 'Unable to delete truck. Please try again.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            }

            return redirect()->back()->withErrors(['general' => $errorMessage]);
        }
    }

    public function show(\App\Models\Truck $truck)
    {
        $this->ensureCompanyContext();
        $truck->load(['transporter', 'driver']);

        if (! $this->isTruckAccessibleToCurrentCompany($truck)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Format the data for display
        $truckData = [
            'id' => $truck->id,
            'truck_number' => $truck->truck_number,
            'truck_type' => $truck->truck_type,
            'registration_date' => $truck->registration_date ? \Carbon\Carbon::parse($truck->registration_date)->format('d-m-Y') : null,
            'transporter' => $truck->transporter ? [
                'id' => $truck->transporter->id,
                'name' => $truck->transporter->name,
                'short_name' => $truck->transporter->short_name,
                'mobile_number' => $truck->transporter->mobile_number,
                'gst_number' => $truck->transporter->gst_number,
                'pan_number' => $truck->transporter->pan_number,
                'address' => $truck->transporter->address,
            ] : null,
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
        $this->ensureCompanyContext();
        $validated = $request->validate([
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        // Check if user has access to this truck (same company)
        if (! $this->isTruckAccessibleToCurrentCompany($truck)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update truck with driver assignment
        if ($validated['driver_id']) {
            $truck->update([
                'driver_id' => $validated['driver_id'],
            ]);
            $message = 'Driver assigned successfully!';
        } else {
            $truck->update([
                'driver_id' => null,
            ]);
            $message = 'Driver removed successfully!';
        }

        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'truck' => $truck->fresh()->load('driver'),
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function getTrucksFromLrs($transporterId)
    {
        $this->ensureCompanyContext();
        $lrs = \App\Models\LorryReceipt::where('transporter_id', $transporterId)->get(['truck_id']);
        $trucks = \App\Models\Truck::with(['transporter.transporterMaster', 'driver'])
            ->whereIn('id', $lrs->pluck('truck_id'))
            ->get();

        return response()->json($trucks);
    }

    /**
     * Search trucks for Select2 AJAX.
     * Returns Select2-compatible payload: { results: [{id,text,transporter_id,transporter_name,driver_name}], pagination: { more } }
     */
    public function searchTrucksApi(Request $request)
    {
        try {
            $this->ensureCompanyContext();

            $companyId = $this->getCurrentCompanyId();
            $q = trim((string) $request->query('q', ''));
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 20);
            $limit = max(1, min($limit, 50));
            $page = max(1, $page);

            $query = \App\Models\Truck::with(['transporter.transporterMaster', 'driver'])
                ->where(function ($qq) use ($companyId) {
                    $qq->whereHas('transporter', function ($sub) use ($companyId) {
                        $sub->where('company_id', $companyId);
                    })->orWhere(function ($orphanQuery) use ($companyId) {
                        $orphanQuery->whereNull('transporter_id')
                            ->where('company_id', $companyId);
                    });
                });

            if ($q !== '') {
                $query->where(function ($qq) use ($q) {
                    $qq->where('truck_number', 'like', "%{$q}%")
                        ->orWhere('truck_type', 'like', "%{$q}%")
                        ->orWhereHas('transporter.transporterMaster', function ($tm) use ($q) {
                            $tm->where('name', 'like', "%{$q}%")
                                ->orWhere('pan_number', 'like', "%{$q}%");
                        })
                        ->orWhereHas('driver', function ($d) use ($q) {
                            $d->where('name', 'like', "%{$q}%");
                        });
                });
            }

            $query->orderBy('truck_number', 'asc');

            $offset = ($page - 1) * $limit;
            $items = $query->offset($offset)->limit($limit + 1)->get();
            $more = $items->count() > $limit;
            $items = $items->take($limit);

            $results = $items->map(function ($truck) {
                $transporterName = $truck->transporter ? ($truck->transporter->name ?: 'No Transporter') : 'No Transporter';
                $driverName = $truck->driver ? $truck->driver->name : null;

                $text = $truck->truck_number ?: '';
                if ($driverName) {
                    $text .= ' - Driver: '.$driverName;
                }
                $text .= ' ('.$transporterName.')';

                return [
                    'id' => $truck->id,
                    'text' => $text,
                    'transporter_id' => $truck->transporter_id,
                    'transporter_name' => $transporterName,
                    'driver_name' => $driverName,
                ];
            })->values();

            return response()->json([
                'results' => $results,
                'pagination' => [
                    'more' => $more,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching trucks: '.$e->getMessage());

            return response()->json([
                'results' => [],
                'pagination' => [
                    'more' => false,
                ],
            ]);
        }
    }

    /**
     * Assign a truck owner to a truck
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignOwner(Request $request)
    {
        $this->ensureCompanyContext();
        $request->validate([
            'truck_id' => 'required|exists:trucks,id',
            'transporter_id' => 'required|exists:transporters,id,is_owner,1,company_id,'.Auth::user()->company_id,
        ]);

        try {
            $truck = \App\Models\Truck::findOrFail($request->truck_id);

            // Check if the truck already has this owner
            if ($truck->transporter_id == $request->transporter_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This truck is already assigned to the selected owner.',
                ]);
            }

            $truck->transporter_id = $request->transporter_id;
            $truck->save();

            return response()->json([
                'success' => true,
                'message' => 'Truck owner assigned successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error assigning truck owner: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign truck owner. Please try again.',
            ], 500);
        }
    }
}
