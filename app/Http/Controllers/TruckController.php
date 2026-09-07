<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\TruckType;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TruckController extends Controller
{
    private function getCurrentCompanyId(): ?int
    {
        if (Auth::user()->userRole->name === 'super_admin') {
            return session('super_admin_company_id');
        }

        return Auth::user()->company_id;
    }

    private function isTruckNumberTakenInCompany(string $truckNumber, int $companyId, ?int $ignoreTruckId = null): bool
    {
        return Truck::query()
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

    private function isTruckAccessibleToCurrentCompany(Truck $truck): bool
    {
        $companyId = $this->getCurrentCompanyId();

        if ($truck->transporter) {
            return $truck->transporter->company_id === $companyId;
        }

        return (int) $truck->company_id === (int) $companyId;
    }

    public function getTrucksForTransporter($transporterId)
    {
        $companyId = $this->getCurrentCompanyId();
        $trucks = Truck::with(['transporter.transporterMaster', 'driver'])
            ->where('transporter_id', $transporterId)
            ->whereHas('transporter', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();

        return response()->json($trucks);
    }

    public function index(Request $request)
    {
        $companyId = $this->getCurrentCompanyId();
        if ($request->ajax()) {
            return $this->getTrucksData($request);
        }
        $transportersBaseQuery = Transporter::query()
            ->with('transporterMaster:id,name,pan_number')
            ->where('company_id', $companyId);

        $owners = (clone $transportersBaseQuery)
            ->where('is_owner', 1)
            ->get();

        $transporters = (clone $transportersBaseQuery)->get();

        return view('trucks.index', compact('transporters', 'owners'));
    }

    private function getTrucksData(Request $request)
    {
        $companyId = $this->getCurrentCompanyId();
        $query = Truck::query()
            ->leftJoin('transporters', 'trucks.transporter_id', '=', 'transporters.id')
            ->leftJoin('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
            ->leftJoin('drivers', 'trucks.driver_id', '=', 'drivers.id')
            ->where(function ($q) use ($companyId) {
                $q->where('transporters.company_id', $companyId)
                    ->orWhere(function ($orphanQuery) use ($companyId) {
                        $orphanQuery->whereNull('trucks.transporter_id')
                            ->where('trucks.company_id', $companyId);
                    });
            })
            ->select([
                'trucks.*',
                'transporter_masters.name as transporter_name',
                'drivers.name as driver_name',
            ]);

        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('truck_number', 'like', "%{$searchValue}%")
                    ->orWhere('truck_type', 'like', "%{$searchValue}%")
                    ->orWhere('registration_date', 'like', "%{$searchValue}%")
                    ->orWhere('transporter_masters.name', 'like', "%{$searchValue}%")
                    ->orWhere('drivers.name', 'like', "%{$searchValue}%");
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
                            $query->where('transporter_masters.name', 'like', "%{$searchValue}%");
                            break;
                        case 5: // Driver
                            $query->where('drivers.name', 'like', "%{$searchValue}%");
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
                    $query->orderBy('transporter_masters.name', $orderDirection);
                    break;
                case 5: // Driver
                    $query->orderBy('drivers.name', $orderDirection);
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
        $totalQuery = Truck::where(function ($q) use ($companyId) {
            $q->whereHas('transporter', function ($subQuery) use ($companyId) {
                $subQuery->where('company_id', $companyId);
            })
                ->orWhere(function ($orphanQuery) use ($companyId) {
                    $orphanQuery->whereNull('transporter_id')
                        ->where('company_id', $companyId);
                });
        });
        $totalRecords = $totalQuery->count();
        $filteredRecords = (clone $query)->count('trucks.id');

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
                'transporter' => $truck->transporter_name ?: '-',
                'driver' => $truck->driver_name ?: '-',
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
        $buttons = '<div class="app-index-table-actions">';

        // Assign Driver button
        $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-action-driver assign-driver-btn" title="Assign Driver" data-bs-toggle="tooltip" ';
        $buttons .= 'data-truck-id="'.$truck->id.'" ';
        $buttons .= 'data-truck-number="'.htmlspecialchars($truck->truck_number).'" ';
        $buttons .= 'data-truck-type="'.htmlspecialchars($truck->truck_type).'" ';
        $buttons .= 'data-transporter-name="'.htmlspecialchars($truck->transporter_name ?? '-').'" ';

        $buttons .= 'data-current-driver-id="'.($truck->driver_id ?: '').'" ';
        $buttons .= 'data-current-driver-name="'.htmlspecialchars($truck->driver_name ?? '').'">';
        $buttons .= '<i class="bi bi-person-plus"></i></button>';

        // Delete button
        $deleteUrl = route('trucks.destroy', $truck->id);
        $buttons .= '<form action="'.$deleteUrl.'" method="POST" onsubmit="return confirm(\'Are you sure you want to delete this truck?\')">';
        $buttons .= csrf_field().method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-icon btn-action-delete" title="Delete Truck" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';

        // show button to assign truck owner for those whose transporter_id is null
        if ($truck->transporter_id == null) {
            $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-action-view assign-owner-btn" title="Assign Truck Owner" data-bs-toggle="tooltip" ';
            $buttons .= 'data-truck-id="'.$truck->id.'" ';
            $buttons .= 'data-truck-number="'.htmlspecialchars($truck->truck_number).'" ';
            $buttons .= 'data-truck-type="'.htmlspecialchars($truck->truck_type).'" ';
            $buttons .= 'data-transporter-name="'.htmlspecialchars($truck->transporter_name ?? '-').'" ';
            $buttons .= 'data-current-driver-id="'.($truck->driver_id ?: '').'" ';
            $buttons .= 'data-current-driver-name="'.htmlspecialchars($truck->driver_name ?? '').'">';
            $buttons .= '<i class="bi bi-truck"></i></button>';
        }

        $buttons .= '</div>';

        return $buttons;
    }

    public function getOwnerTransporters($truckId)
    {
        $companyId = $this->getCurrentCompanyId();
        $truck = Truck::with('transporter.transporterMaster')
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
                    'name' => $transporter->displayNameWithShortName(),
                ],
            ]);
        }

        return response()->json([]);
    }

    public function create($transporter_id = null)
    {
        $companyId = $this->getCurrentCompanyId();
        $transporter = null;

        if ($transporter_id) {
            $transporter = Transporter::with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('id', $transporter_id)
                ->where('is_owner', 1)
                ->firstOrFail();
        }

        $selectedTransporterId = old('transporter_id', $transporter?->id);
        $selectedDriverId = old('driver_id');

        $transporters = collect();
        if ($selectedTransporterId) {
            $selectedTransporter = Transporter::with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('is_owner', 1)
                ->where('id', $selectedTransporterId)
                ->first();

            if ($selectedTransporter) {
                $transporters = collect([$selectedTransporter]);
            }
        }

        $drivers = collect();
        if ($selectedDriverId) {
            $selectedDriver = Driver::query()
                ->where('company_id', $companyId)
                ->where('id', $selectedDriverId)
                ->first();

            if ($selectedDriver) {
                $drivers = collect([$selectedDriver]);
            }
        }

        $truckTypes = TruckType::orderBy('name')->get();

        return view('trucks.create', compact('transporter', 'transporters', 'drivers', 'truckTypes'));
    }

    public function store(Request $request)
    {
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
            'errors' => [
                'truck_number' => [$errorMessage],
            ],
        ], 422);
    }

        return redirect()
        ->back()
        ->withErrors(['truck_number' => $errorMessage])
        ->withInput();
 }

        $validated['company_id'] = $companyId;
        try {
       $truck = Truck::create($validated);
         $truck->load([
        'transporter.transporterMaster',
        'driver',
    ]);

    if ($request->expectsJson() || $request->ajax()) {
        return response()->json([
            'success' => true,
            'message' => 'Truck created successfully!',
            'truck' => [
                'id' => $truck->id,
                'truck_number' => $truck->truck_number,
                'truck_type' => $truck->truck_type,
                'transporter_id' => $truck->transporter_id,
                'transporter_name' => $truck->transporter?->name ?? '',
                'driver_id' => $truck->driver_id,
                'driver_name' => $truck->driver?->name ?? '',
            ],
        ]);
    }

    return redirect()->route('trucks.index')
        ->with('success', 'Truck created successfully!');
            } catch (QueryException $e) {
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
        $company_id = $this->getCurrentCompanyId();
        $exists = Truck::where('truck_number', $request->truck_number)
            ->where(function ($query) use ($company_id) {
                $query->where('company_id', $company_id)
                    ->orWhereHas('transporter', function ($transporters) use ($company_id) {
                        $transporters->where('company_id', $company_id);
                    });
            })
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function edit(Truck $truck)
    {
        $companyId = $this->getCurrentCompanyId();
        $selectedTransporterId = old('transporter_id', $truck->transporter_id);
        $selectedDriverId = old('driver_id', $truck->driver_id);

        $transporters = collect();
        if ($selectedTransporterId) {
            $selectedTransporter = Transporter::with('transporterMaster')
                ->where('company_id', $companyId)
                ->where(function ($query) use ($selectedTransporterId) {
                    $query->where('is_owner', 1)
                        ->orWhere('id', $selectedTransporterId);
                })
                ->where('id', $selectedTransporterId)
                ->first();

            if ($selectedTransporter) {
                $transporters = collect([$selectedTransporter]);
            }
        }

        $drivers = collect();
        if ($selectedDriverId) {
            $selectedDriver = Driver::withTrashed()
                ->where(function ($query) use ($companyId, $selectedDriverId) {
                    $query->where('company_id', $companyId)
                        ->orWhere('id', $selectedDriverId);
                })
                ->where('id', $selectedDriverId)
                ->first();

            if ($selectedDriver) {
                $drivers = collect([$selectedDriver]);
            }
        }

        $truckTypes = TruckType::orderBy('name')->get();

        return view('trucks.edit', compact('truck', 'transporters', 'drivers', 'truckTypes'));
    }

    public function update(Request $request, Truck $truck)
    {
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
        } catch (QueryException $e) {
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

    public function removeTransporter(Truck $truck)
    {
        if (! $this->isTruckAccessibleToCurrentCompany($truck)) {
            abort(403, 'Unauthorized access to this truck.');
        }
        // Check if this truck has any undelivered Lorry Receipts
        $hasUndeliveredLr = LorryReceipt::where('truck_id', $truck->id)
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

    public function destroy(Truck $truck)
    {
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

    public function show(Truck $truck)
    {
        $truck->load(['transporter', 'driver']);

        if (! $this->isTruckAccessibleToCurrentCompany($truck)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Format the data for display
        $truckData = [
            'id' => $truck->id,
            'truck_number' => $truck->truck_number,
            'truck_type' => $truck->truck_type,
            'registration_date' => $truck->registration_date ? Carbon::parse($truck->registration_date)->format('d-m-Y') : null,
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

    public function assignDriver(Request $request, Truck $truck)
    {
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

    /**
     * Assign a truck owner to a truck
     *
     * @return JsonResponse
     */
    public function assignOwner(Request $request)
    {
        $request->validate([
            'truck_id' => 'required|exists:trucks,id',
            'transporter_id' => 'required|exists:transporters,id,is_owner,1,company_id,'.Auth::user()->company_id,
        ]);

        try {
            $truck = Truck::findOrFail($request->truck_id);

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
