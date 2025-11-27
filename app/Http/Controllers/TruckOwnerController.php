<?php

namespace App\Http\Controllers;

use App\Models\Transporter;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Truck;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TruckOwnerController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getTruckOwnersData($request);
        }

        $companies = \App\Models\Company::all();
        return view('truck_owner.index', compact('companies'));
    }

    private function getTruckOwnersData(Request $request)
    {
        try {
            $query = \App\Models\Transporter::with(['company', 'city'])->where('is_owner', 1); // Show only truck owners

            // Only apply company_id filter for non-super_admin users
            if (auth()->user()->userRole->name !== 'super_admin') {
                $query->where('company_id', auth()->user()->company_id);
            }

            // Handle search
            if ($request->has('search') && $request->search['value']) {
                $searchValue = $request->search['value'];
                $query->where(function ($q) use ($searchValue) {
                    $q->where('transporters.name', 'like', "%{$searchValue}%")
                        ->orWhere('transporters.pan_number', 'like', "%{$searchValue}%")
                        ->orWhere('transporters.mobile_number', 'like', "%{$searchValue}%")
                        ->orWhereHas('city', function ($cityQuery) use ($searchValue) {
                            $cityQuery->where('name', 'like', "%{$searchValue}%");
                        });
                });
            }

            // Handle individual column searches
            if ($request->has('columns')) {
                foreach ($request->columns as $index => $column) {
                    if (!empty($column['search']['value'])) {
                        $searchValue = $column['search']['value'];
                        switch ($index) {
                            case 1: // Name
                                $query->where('transporters.name', 'like', "%{$searchValue}%");
                                break;
                            case 2: // Short Name
                                $query->where('transporters.short_name', 'like', "%{$searchValue}%");
                                break;
                            case 3: // PAN Number
                                $query->where('transporters.pan_number', 'like', "%{$searchValue}%");
                                break;
                            case 4: // Location (City)
                                $query->whereHas('city', function ($cityQuery) use ($searchValue) {
                                    $cityQuery->where('name', 'like', "%{$searchValue}%");
                                });
                                break;
                            case 5: // Contact Number (Mobile)
                                $query->where('transporters.mobile_number', 'like', "%{$searchValue}%");
                                break;
                            case 6: // Declaration Given
                                if (strtolower($searchValue) === 'yes') {
                                    $query->where('transporters.declaration_given', 1);
                                } elseif (strtolower($searchValue) === 'no') {
                                    $query->where('transporters.declaration_given', 0);
                                }
                                break;
                        }
                    }
                }
            }

            // Handle ordering
            if ($request->has('order')) {
                $orderColumn = $request->order[0]['column'];
                $orderDir = $request->order[0]['dir'];

                switch ($orderColumn) {
                    case 1: // Name
                        $query->orderBy('transporters.name', $orderDir);
                        break;
                    case 2: // Short Name
                        $query->orderBy('transporters.short_name', $orderDir);
                        break;
                    case 3: // PAN Number
                        $query->orderBy('transporters.pan_number', $orderDir);
                        break;
                    case 4: // Location (City)
                        $query->join('cities', 'transporters.city_id', '=', 'cities.id')
                            ->orderBy('cities.name', $orderDir)
                            ->select('transporters.*');
                        break;
                    case 5: // Contact Number (Mobile)
                        $query->orderBy('transporters.mobile_number', $orderDir);
                        break;
                    case 6: // Declaration Given
                        $query->orderBy('transporters.declaration_given', $orderDir);
                        break;
                    case 7: // Advance Amount
                        $query->orderBy('transporters.advance_amount', $orderDir);
                        break;
                    default:
                        $query->orderBy('transporters.id', 'desc');
                }
            } else {
                $query->orderBy('transporters.id', 'desc');
            }

            $totalRecords = \App\Models\Transporter::where('is_owner', 1)->count();
            $filteredRecords = $query->count();

            $start = $request->start ?? 0;
            $length = $request->length ?? 10;

            $truckOwners = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($truckOwners as $index => $truckOwner) {
                $trucks = $truckOwner->trucks();
                $truckNumbers = $trucks->pluck('truck_number')->toArray();
                $truckList = implode(', ', $truckNumbers);
                $data[] = [
                    'name' => '<a href="#" class="truck-owner-name-link text-primary fw-bold" data-bs-toggle="tooltip" title="Trucks: ' . htmlspecialchars($truckList) . '" data-id="' . $truckOwner->id . '" style="text-decoration: none;">' . ($truckOwner->name ?? 'N/A') . '</a>',
                    'short_name' => $truckOwner->short_name ?? 'N/A',
                    'pan_number' => $truckOwner->pan_number ?? 'N/A',
                    'location' => $truckOwner->city->name ?? 'N/A',
                    'contact_number' => $truckOwner->mobile_number ?? 'N/A',
                    'declaration_given' => $truckOwner->declaration_given ? 'Yes' : 'No',
                    'advance_amount' => '₹' . number_format($truckOwner->advance_amount, 2),
                    'actions' => $this->getActionButtons($truckOwner)
                ];
            }

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('TruckOwner DataTables Error: ' . $e->getMessage());
            return response()->json([
                'draw' => intval($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data.'
            ]);
        }
    }

    private function getActionButtons($truckOwner)
    {
        $is_admin = Auth::user()->userRole->name === 'admin';
        $buttons = '<div class="d-flex gap-1">';

        // Add Truck Button (modal trigger for truck owners)
        $buttons .= '<button type="button" class="btn btn-sm btn-outline-primary add-truck-btn" aria-label="Add Truck" title="Add Truck" data-bs-placement="top" data-bs-toggle="tooltip" data-transporter-id="' . $truckOwner->id . '" data-transporter-name="' . htmlspecialchars($truckOwner->name) . '">';
        $buttons .= '<i class="bi bi-truck"></i></button>';

        if ($is_admin) {
            // Advance Payment Button (same as transporters)
            $buttons .= '<button type="button" class="btn btn-sm btn-outline-success advance-payment-btn" aria-label="Advance Payment" title="Advance Payment" data-bs-placement="top" data-bs-toggle="tooltip" data-transporter-id="' . $truckOwner->id . '" data-transporter-name="' . htmlspecialchars($truckOwner->name) . '">';
            $buttons .= '<i class="bi bi-currency-rupee"></i>';
            $buttons .= '</button>';

            // Edit Button (same as transporters)
            $editUrl = route('truck_owners.edit', $truckOwner->id);
            $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info" title="Edit Truck Owner" aria-label="Edit Truck Owner" data-bs-placement="top" data-bs-toggle="tooltip">';
            $buttons .= '<i class="bi bi-pencil"></i></a>';

            // Delete Button (AJAX-based)
            $buttons .= '<button type="button" class="btn btn-sm btn-danger delete-truck-owner-btn" aria-label="Delete Truck Owner" title="Delete Truck Owner" data-bs-placement="top" data-bs-toggle="tooltip" data-truck-owner-id="' . $truckOwner->id . '" data-truck-owner-name="' . htmlspecialchars($truckOwner->name) . '">';
            $buttons .= '<i class="bi bi-trash"></i></button>';
        }
        $buttons .= '</div>';
        return $buttons;
    }

    public function create()
    {
        $companies = Company::all();
        $countries = Country::all();
        $states = State::all();
        $cities = City::all();

        return view('truck_owner.create', compact('companies', 'countries', 'states', 'cities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'gst_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                Rule::unique('transporters')
                    ->where(
                        fn($query) =>
                        $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 1)
                    ),
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                Rule::unique('transporters')
                    ->where(
                        fn($query) =>
                        $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 1)
                    ),
            ],
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0|max:10000',
            'advance_amount' => 'nullable|numeric',
            'pan_active' => 'required|boolean',
            'address' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => [
                'nullable',
                'exists:states,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->country_id) {
                        $state = \App\Models\State::find($value);
                        if ($state && $state->country_id != $request->country_id) {
                            $fail('The selected state does not belong to the selected country.');
                        }
                    }
                },
            ],
            'city_id' => [
                'nullable',
                'exists:cities,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->state_id) {
                        $city = \App\Models\City::find($value);
                        if ($city && $city->state_id != $request->state_id) {
                            $fail('The selected city does not belong to the selected state.');
                        }
                    }
                    if ($value && $request->country_id) {
                        $city = \App\Models\City::find($value);
                        if ($city && $city->country_id != $request->country_id) {
                            $fail('The selected city does not belong to the selected country.');
                        }
                    }
                },
            ],
            'mobile_number' => [
                'required',
                'digits:10',
                Rule::unique('transporters')
                    ->where(
                        fn($query) =>
                        $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 1)
                    ),
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('transporters')
                    ->where(
                        fn($query) =>
                        $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 1)
                    ),
            ],
            'contact_person_name' => 'nullable|string|max:255',
        ], [
            'pan_number.unique' => 'This PAN number is already registered with another truck owner or transporter.',
            'gst_number.unique' => 'This GST number is already registered with another truck owner or transporter.',
            'mobile_number.unique' => 'This mobile number is already registered with another truck owner or transporter.',
        ]);

        // Ensure uppercase PAN and GST numbers
        if (!empty($validated['pan_number'])) {
            $validated['pan_number'] = strtoupper($validated['pan_number']);
        }
        if (!empty($validated['gst_number'])) {
            $validated['gst_number'] = strtoupper($validated['gst_number']);
        }

        // Always set truck owner specific fields (system controlled, not user input)
        // Handle advance_amount - use provided value or default to 0
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        $validated['is_owner'] = 1; // Always true for truck owners (this differentiates from transporters)

        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);

        if ($validated['declaration_given']) {
            // Rule 1: Declaration given → TDS = 0%
            $validated['tds_percentage'] = 0.00;
        } else {
            // Rule 2: Declaration not given
            if ($validated['pan_active'] == false) {
                // PAN not active → 20%
                $validated['tds_percentage'] = 20.00;
            } else {
                // PAN active → check 4th character
                $fourthChar = strtoupper(substr($validated['pan_number'], 3, 1));
                if (in_array($fourthChar, ['P', 'H'])) {
                    $validated['tds_percentage'] = 1.00;
                } else if (in_array($fourthChar, ['C', 'F', 'A', 'B', 'J', 'L', 'T'])) {
                    $validated['tds_percentage'] = 2.00;
                } else {
                    $validated['tds_percentage'] = 20.00;
                }
            }
        }

        \App\Models\Transporter::create($validated);
        return redirect()->route('truck_owners.index')->with('success', 'Truck Owner created successfully!');
    }

    public function calculateTruckOwnerTdsPercentage(Request $request)
    {
        $panNumber = $request->input('pan_number');
        $panActive = $request->input('pan_active');
        $declarationGiven = $request->input('declaration_given');

        $tdsPercentage = 0.00;

        if ($declarationGiven == '1') {
            // Rule 1: Declaration given → TDS = 0%
            $tdsPercentage = 0.00;
        } else {
            // Rule 2: Declaration not given
            if ($panActive == '0') {
                // PAN not active → 20%
                $tdsPercentage = 20.00;
            } elseif ($panNumber && strlen($panNumber) >= 4) {
                // PAN active → check 4th character
                $fourthChar = strtoupper(substr($panNumber, 3, 1));
                if (in_array($fourthChar, ['P', 'H'])) {
                    $tdsPercentage = 1.00;
                } else if (in_array($fourthChar, ['C', 'F', 'A', 'B', 'J', 'L', 'T'])) {
                    $tdsPercentage = 2.00;
                } else {
                    $tdsPercentage = 20.00; // Changed from null to 0.00 for display consistency
                }
            }
        }

        return response()->json([
            'tds_percentage' => number_format($tdsPercentage, 2)
        ]);
    }

    public function show($id)
    {
        $transporter = \App\Models\Transporter::with(['company', 'city.state.country'])->findOrFail($id);

        // Check if user has permission to view this truck owner
        if (auth()->user()->userRole->name !== 'super_admin' && $transporter->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access to this truck owner.');
        }

        // Ensure this is actually a truck owner (is_owner = 1)
        if (!$transporter->is_owner) {
            abort(404, 'Truck owner not found.');
        }

        // Get last 10 advance payments for this truck owner
        $advancePayments = \App\Models\AdvancePayment::where('transporter_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => '₹' . number_format($payment->advance_amount, 2),
                    'payment_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : 'Not specified',
                    'remark' => $payment->remark ?: 'No remarks',
                    'created_at' => $payment->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i')
                ];
            });

        // Get registered trucks for this truck owner
        $trucks = \App\Models\Truck::where('transporter_id', $id)
            ->with('driver')
            ->get()
            ->map(function ($truck) {
                return [
                    'id' => $truck->id,
                    'truck_number' => $truck->truck_number,
                    'truck_type' => $truck->truck_type,
                    'driver_name' => optional($truck->driver)->name ?: 'Not assigned',
                    'driver_mobile' => optional($truck->driver)->mobile ?: 'Not provided'
                ];
            });

        return response()->json([
            'id' => $transporter->id,
            'name' => $transporter->name,
            'short_name' => $transporter->short_name,
            'contact_person_name' => $transporter->contact_person_name,
            'gst_number' => $transporter->gst_number ?: 'Not provided',
            'pan_number' => $transporter->pan_number,
            'pan_active' => $transporter->pan_active ? 'Yes' : 'No',
            'mobile_number' => $transporter->mobile_number,
            'address' => $transporter->address ?: 'Not provided',
            'country' => optional($transporter->city->state->country)->name ?: 'Not specified',
            'state' => optional($transporter->city->state)->name ?: 'Not specified',
            'city' => optional($transporter->city)->name ?: 'Not specified',
            'company' => optional($transporter->company)->name ?: 'Not specified',
            'is_owner' => 'Truck Owner',
            'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
            'tds_percentage' => $transporter->tds_percentage ?: '0',
            'total_trucks' => $transporter->total_trucks,
            'advance_amount' => '₹' . number_format($transporter->advance_amount, 2),
            'created_at' => $transporter->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'updated_at' => $transporter->updated_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'advance_payments' => $advancePayments,
            'trucks' => $trucks
        ]);
    }

    public function edit(\App\Models\Transporter $transporter)
    {
        // Load the trucks relationship with driver information
        $transporter->load(['trucks.driver']);

        $companies = Company::all();
        $trucks = Truck::where('transporter_id', $transporter->id)->get();
        $countries = Country::all();
        $states = State::all();
        $cities = City::all();

        return view('truck_owner.edit', compact('transporter', 'companies', 'trucks', 'countries', 'states', 'cities'));
    }

    public function update(Request $request, \App\Models\Transporter $transporter)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'gst_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                Rule::unique('transporters')
                    ->where(
                        fn($query) =>
                        $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 1)
                    )
                    ->ignore($transporter->id),
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                Rule::unique('transporters')
                    ->where(
                        fn($query) =>
                        $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 1)
                    )
                    ->ignore($transporter->id),
            ],
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0|max:10000',
            'advance_amount' => 'nullable|numeric',
            'pan_active' => 'required|boolean',
            'address' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => [
                'nullable',
                'exists:states,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->country_id) {
                        $state = \App\Models\State::find($value);
                        if ($state && $state->country_id != $request->country_id) {
                            $fail('The selected state does not belong to the selected country.');
                        }
                    }
                },
            ],
            'city_id' => [
                'nullable',
                'exists:cities,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->state_id) {
                        $city = \App\Models\City::find($value);
                        if ($city && $city->state_id != $request->state_id) {
                            $fail('The selected city does not belong to the selected state.');
                        }
                    }
                    if ($value && $request->country_id) {
                        $city = \App\Models\City::find($value);
                        if ($city && $city->country_id != $request->country_id) {
                            $fail('The selected city does not belong to the selected country.');
                        }
                    }
                },
            ],
            'mobile_number' => [
                'required',
                'digits:10',
                Rule::unique('transporters')
                    ->where(
                        fn($query) =>
                        $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 1)
                    )
                    ->ignore($transporter->id),
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('transporters')
                    ->where(
                        fn($query) =>
                        $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 1)
                    )
                    ->ignore($transporter->id),
            ],
            'contact_person_name' => 'nullable|string|max:255',
        ], [
            'pan_number.unique' => 'This PAN number is already registered with another truck owner or transporter.',
            'gst_number.unique' => 'This GST number is already registered with another truck owner or transporter.',
            'mobile_number.unique' => 'This mobile number is already registered with another truck owner or transporter.',
        ]);

        // Ensure uppercase PAN and GST numbers
        if (!empty($validated['pan_number'])) {
            $validated['pan_number'] = strtoupper($validated['pan_number']);
        }
        if (!empty($validated['gst_number'])) {
            $validated['gst_number'] = strtoupper($validated['gst_number']);
        }

        // Always set truck owner specific fields (system controlled, not user input)
        // Handle advance_amount - use provided value or default to 0
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        $validated['is_owner'] = 1; // Always true for truck owners (this differentiates from transporters)

        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);

        if ($validated['declaration_given']) {
            // Rule 1: Declaration given → TDS = 0%
            $validated['tds_percentage'] = 0.00;
        } else {
            // Rule 2: Declaration not given
            if ($validated['pan_active'] == false) {
                // PAN not active → 20%
                $validated['tds_percentage'] = 20.00;
            } else {
                // PAN active → check 4th character
                $fourthChar = strtoupper(substr($validated['pan_number'], 3, 1));
                if (in_array($fourthChar, ['P', 'H'])) {
                    $validated['tds_percentage'] = 1.00;
                } else if (in_array($fourthChar, ['C', 'F', 'A', 'B', 'J', 'L', 'T'])) {
                    $validated['tds_percentage'] = 2.00;
                } else {
                    $validated['tds_percentage'] = 20.00;
                }
            }
        }

        $transporter->update($validated);
        return redirect()->route('truck_owners.index')->with('success', 'Truck Owner updated successfully!');
    }

    public function destroy(\App\Models\Transporter $transporter)
    {
        // Soft delete the truck owner
        $transporter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Truck Owner deleted successfully!'
        ]);
    }
}
