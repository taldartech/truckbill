<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Truck;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Validation\Rule;
use App\Models\Transporter;

class TransporterController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request

        if ($request->ajax()) {
            return $this->getTransportersData($request);
        }

        $companies = \App\Models\Company::all();
        return view('transporters.index', compact('companies'));
    }

    private function getTransportersData(Request $request)
    {
        try {
            \Log::info('DataTables request started for transporters');

            $query = \App\Models\Transporter::with(['company', 'city'])
                ->where('is_owner', 0); // Default to show only Agencies

            // Only apply company_id filter for non-super_admin users
            if (auth()->user()->userRole->name !== 'super_admin') {
                $query->where('company_id', auth()->user()->company_id);
            }

            \Log::info('Query built successfully');

            // Handle global search
            if ($request->has('search') && $request->search['value']) {
                $searchValue = $request->search['value'];
                $query->where(function ($q) use ($searchValue) {
                    $q->where('transporters.name', 'like', "%{$searchValue}%")
                        ->orWhere('transporters.pan_number', 'like', "%{$searchValue}%")
                        ->orWhere('transporters.mobile_number', 'like', "%{$searchValue}%")
                        ->orWhereHas('company', function ($companyQuery) use ($searchValue) {
                            $companyQuery->where('name', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('city', function ($cityQuery) use ($searchValue) {
                            $cityQuery->where('name', 'like', "%{$searchValue}%");
                        });
                });
            }

            // Handle column-specific search
            if ($request->has('columns')) {
                foreach ($request->columns as $index => $column) {
                    if (!empty($column['search']['value'])) {
                        $searchValue = $column['search']['value'];
                        switch ($index) {
                            case 0: // Name
                                $query->where('transporters.name', 'like', "%{$searchValue}%");
                                break;
                            case 1: // Short Name
                                $query->where('transporters.short_name', 'like', "%{$searchValue}%");
                                break;
                            case 2: // Beneficiary ID
                                $query->where('transporters.beneficiary_id', 'like', "%{$searchValue}%");
                                break;
                            case 3: // PAN Number
                                $query->where('transporters.pan_number', 'like', "%{$searchValue}%");
                                break;
                            case 4: // Contact Person
                                $query->where('transporters.contact_person_name', 'like', "%{$searchValue}%");
                                break;
                            case 5: // Contact Number
                                $query->where('transporters.mobile_number', 'like', "%{$searchValue}%");
                                break;
                            case 6: // Advance Amount
                                $searchValue = floatval($searchValue);
                                $query->where('transporters.advance_amount', $searchValue);
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
                    case 0: // Name
                        $query->orderBy('transporters.name', $orderDirection);
                        break;
                    case 1: // Short Number
                        $query->orderBy('transporters.short_name', $orderDirection);
                        break;
                    case 2: // Beneficiary ID
                        $query->orderBy('transporters.beneficiary_id', $orderDirection);
                        break;
                    case 3: // PAN Number
                        $query->orderBy('transporters.pan_number', $orderDirection);
                        break;
                    case 4: // Contact Person
                        $query->orderBy('transporters.contact_person_name', $orderDirection);
                        break;
                    case 5: // Contact Number
                        $query->orderBy('transporters.mobile_number', $orderDirection);
                        break;
                    case 6: // Advance Amount
                        $query->orderBy('transporters.advance_amount', $orderDirection);
                        break;
                    default:
                        $query->orderBy('transporters.created_at', 'desc');
                }
            } else {
                $query->orderBy('transporters.created_at', 'desc');
            }

            // Get total count before pagination
            $totalQuery = \App\Models\Transporter::where('is_owner', 0);
            if (auth()->user()->userRole->name !== 'super_admin') {
                $totalQuery->where('company_id', auth()->user()->company_id);
            }
            $totalRecords = $totalQuery->count();
            $filteredRecords = $query->count();

            // Handle pagination
            $start = $request->start ?? 0;
            $length = $request->length ?? 10;
            $transporters = $query->offset($start)->limit($length)->get();

            // Format data for DataTables
            $data = $transporters->map(function ($transporter, $index) use ($start) {
                return [
                    'sr_no' => $start + $index + 1,
                    'name' => '<a href="javascript:void(0)" class="text-brand-primary fw-bold transporter-name-link" data-id="' . $transporter->id . '" style="cursor: pointer; text-decoration: none;">' . ($transporter->name ?: '-') . '</a>',
                    'short_name' => $transporter->short_name ?: '-',
                    'beneficiary_id' => $transporter->beneficiary_id ?: '-',
                    'pan_number' => $transporter->pan_number ?: '-',
                    'contact_person' => $transporter->contact_person_name ?: '-',

                    'contact_number' => $transporter->mobile_number ?: '-',
                    'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
                    'advance_amount' => '₹' . number_format($transporter->advance_amount ?: 0, 2),
                    'company' => optional($transporter->company)->name ?: '-',
                    'actions' => $this->getActionButtons($transporter)
                ];
            });

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('DataTables error in transporters: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'draw' => intval($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getActionButtons($transporter)
    {
        $buttons = '<div class="d-flex gap-1">';


        $buttons .= '<button type="button" class="btn btn-sm btn-outline-success advance-payment-btn " title="Advance Payment" data-bs-toggle="tooltip" data-transporter-id="' . $transporter->id . '" data-transporter-name="' . htmlspecialchars($transporter->name) . '">';
        $buttons .= '<i class="bi bi-currency-rupee "></i>';
        $buttons .= '</button>';


        // Edit button
        $editUrl = route('transporters.edit', $transporter->id);
        $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info " title="Edit Transporter" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-pencil "></i></a>';

        // Delete button
        $deleteUrl = route('transporters.destroy', $transporter->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this transporter?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Transporter" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';

        $buttons .= '</div>';
        return $buttons;
    }

    public function show($id)
    {
        $transporter = \App\Models\Transporter::with(['company', 'city.state.country'])->findOrFail($id);

        // Check if user has permission to view this transporter
        if (auth()->user()->userRole->name !== 'super_admin' && $transporter->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }

        // Get last 10 advance payments for this transporter
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
            'is_owner' => $transporter->is_owner ? 'Truck Owner' : 'Agency',
            'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
            'tds_percentage' => $transporter->tds_percentage ?: '0',
            'advance_amount' => '₹' . number_format($transporter->advance_amount, 2),
            'created_at' => $transporter->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'updated_at' => $transporter->updated_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'advance_payments' => $advancePayments
        ]);
    }

    public function create()
    {
        $companies = \App\Models\Company::all();

        // For create, we can't filter trucks as we don't have transporter_id yet
        $trucks = \App\Models\Truck::all();
        $countries = \App\Models\Country::all();
        $states = [];
        $cities = [];

        if (old('country_id')) {
            $states = \App\Models\State::where('country_id', old('country_id'))->get();
        }

        if (old('state_id')) {
            $cities = \App\Models\City::where('state_id', old('state_id'))->get();
        }
        return view('transporters.create', compact('companies', 'trucks', 'countries', 'states', 'cities'));
    }

    public function store(Request $request)
    {

        // dd($request->all());
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
                        fn($query) => $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 0)
                    ),
            ],
            'pan_number' => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                Rule::unique('transporters')
                    ->where(fn($query) => $query->where('company_id', $request->company_id)
                        ->whereNULL('deleted_at')
                        ->where('is_owner', 0)),
            ],
            'gst_type' => 'nullable|in:composition,regular',
            'is_owner' => 'nullable|boolean',
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
                    ->where(fn($query) => $query->where('company_id', $request->company_id)
                        ->whereNULL('deleted_at')
                        ->where('is_owner', 0)),
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('transporters')
                    ->where(fn($query) => $query->where('company_id', $request->company_id)
                        ->whereNULL('deleted_at')
                        ->where('is_owner', 0)),
            ],
            'contact_person_name' => 'nullable|string|max:255',
        ], [
            'pan_number.unique' => 'This PAN number is already associated with another transporter and cannot be used.',
            'gst_number.unique' => 'This GST number is already associated with another transporter and cannot be used.',
            'mobile_number.unique' => 'This mobile number is already associated with another transporter.',
            'short_name.unique' => 'This short name is already associated with another transporter and cannot be used.',
        ]);

        // Handle duplicate short name
        if (!empty($validated['short_name'])) {
            $existingShortName = Transporter::where('short_name', $validated['short_name'])->first();
            if ($existingShortName) {
                // Append a suffix to make it unique
                $suffix = 1;
                do {
                    $newShortName = $validated['short_name'] . '-' . $suffix;
                    $existingShortName = Transporter::where('short_name', $newShortName)->first();
                    $suffix++;
                } while ($existingShortName);
                $validated['short_name'] = $newShortName;
            }
        }

        // Ensure uppercase PAN and GST numbers
        if (!empty($validated['pan_number'])) {
            $validated['pan_number'] = strtoupper($validated['pan_number']);
        }
        if (!empty($validated['gst_number'])) {
            $validated['gst_number'] = strtoupper($validated['gst_number']);
        }
        //    dd($validated);
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        if (!isset($validated['is_owner'])) {
            $validated['is_owner'] = 0;
        }

        // Set declaration_given and total_trucks with default values for transporters
        $validated['declaration_given'] = false;
        $validated['total_trucks'] = 0;

        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
        // dd($validated);
        // Determine TDS Percentage
        if ($validated['pan_active'] == false) {
            // PAN not active → 20%
            $validated['tds_percentage'] = 20.00;
        } else {
            // PAN active → check 4th character
            $fourthChar = strtoupper(substr($validated['pan_number'], 3, 1));
            $validChars = ['C', 'F', 'A', 'B', 'J', 'L', 'T'];
            $nonValidChars = ['P', 'H'];

            if (in_array($fourthChar, $validChars)) {
                $validated['tds_percentage'] = 2.00;
            } else if (in_array($fourthChar, $nonValidChars)) {
                $validated['tds_percentage'] = 1.00;
            } else {
                // Default if PAN active but not in the given list
                $validated['tds_percentage'] = 20.00;
            }
        }

        \App\Models\Transporter::create($validated);

        return redirect()->route('transporters.index')->with('success', 'Transporter created successfully!');
    }

    public function edit(\App\Models\Transporter $transporter)
    {
        $companies = \App\Models\Company::all();
        // Filter trucks to show only those belonging to this transporter
        $trucks = \App\Models\Truck::where('transporter_id', $transporter->id)->get();
        $countries = \App\Models\Country::all();
        $states = [];
        $cities = [];

        if (old('country_id')) {
            $states = \App\Models\State::where('country_id', old('country_id'))->get();
        }

        if (old('state_id')) {
            $cities = \App\Models\City::where('state_id', old('state_id'))->get();
        }
        // $countries = \App\Models\Country::all();
        // $states = $transporter->country_id ? \App\Models\State::where('country_id', $transporter->country_id)->get() : collect();
        // $cities = $transporter->state_id ? \App\Models\City::where('state_id', $transporter->state_id)->get() : collect();
        return view('transporters.edit', compact('transporter', 'companies', 'trucks', 'countries', 'states', 'cities'));
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
                            ->where('is_owner', 0)
                    )
                    ->ignore($transporter->id),
            ],
            'pan_number' => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                Rule::unique('transporters')
                    ->where(
                        fn($query) =>
                        $query->where('company_id', $request->company_id)
                            ->whereNULL('deleted_at')
                            ->where('is_owner', 0)
                    )
                    ->ignore($transporter->id),
            ],
            'gst_type' => 'nullable|in:composition,regular',
            'is_owner' => 'nullable|boolean',
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
                            ->where('is_owner', 0)
                    )
                    ->ignore($transporter->id),
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('transporters')
                    ->where(fn($query) => $query->where('company_id', $request->company_id)
                        ->whereNULL('deleted_at')
                        ->where('is_owner', 0))
                    ->ignore($transporter->id),
            ],
            'contact_person_name' => 'nullable|string|max:255',


        ], [
            'pan_number.unique' => 'This PAN number is already associated with another transporter and cannot be used.',
            'mobile_number.unique' => 'This mobile number is already associated with another transporter.',
            'short_name.unique' => 'This short name is already associated with another transporter and cannot be used.',
        ]);
        // Ensure uppercase PAN and GST numbers
        if (!empty($validated['pan_number'])) {
            $validated['pan_number'] = strtoupper($validated['pan_number']);
        }
        if (!empty($validated['gst_number'])) {
            $validated['gst_number'] = strtoupper($validated['gst_number']);
        }

        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }

        if (!isset($validated['is_owner'])) {
            $validated['is_owner'] = 0;
        }

        // Set declaration_given and total_trucks with default values for transporters
        $validated['declaration_given'] = false;
        $validated['total_trucks'] = 0;

        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
        // Determine TDS Percentage

        if ($validated['pan_active'] == false) {

            // PAN not active → 20%
            $validated['tds_percentage'] = 20.00;
        } else {

            // PAN active → check 4th character
            $fourthChar = strtoupper(substr($validated['pan_number'], 3, 1));
            $validChars = ['C', 'F', 'A', 'B', 'J', 'L', 'T'];
            $nonValidChars = ['P', 'H'];

            if (in_array($fourthChar, $validChars)) {
                $validated['tds_percentage'] = 2.00;
            } else if (in_array($fourthChar, $nonValidChars)) {
                $validated['tds_percentage'] = 1.00;
            } else {
                // Default if PAN active but not in the given list
                $validated['tds_percentage'] = 20.00;
            }
        }
        $transporter->update($validated);
        return redirect()->route('transporters.index')->with('success', 'Transporter updated successfully!');
    }

    public function calculateTdsPercentage(Request $request)
    {
        $panNumber = $request->input('pan_number');
        $panActive = $request->input('pan_active');

        $tdsPercentage = 0.00;

        if ($panActive == '0') {
            // PAN not active
            $tdsPercentage = 20.00;
        } elseif ($panNumber && strlen($panNumber) >= 4) {
            // PAN is active, check 4th character
            $fourthChar = strtoupper(substr($panNumber, 3, 1));
            $validChars = ['C', 'F', 'A', 'B', 'J', 'L', 'T'];
            $nonValidChars = ['P', 'H'];

            if (in_array($fourthChar, $validChars)) {
                $tdsPercentage = 2.00;
            } else if (in_array($fourthChar, $nonValidChars)) {
                $tdsPercentage = 1.00;
            } else {
                $tdsPercentage = 20.00;
            }
        }

        return response()->json([
            'tds_percentage' => number_format($tdsPercentage, 2)
        ]);
    }

    public function destroy(\App\Models\Transporter $transporter)
    {
        $transporter->delete();
        return redirect()->route('transporters.index')->with('success', 'Transporter deleted successfully!');
    }

    /**
     * Get truck owners for API
     */
    public function getTruckOwnersApi()
    {
        try {
            $query = \App\Models\Transporter::where('is_owner', 1);

            // Apply company filter for non-super admin users
            if (auth()->user()->userRole->name !== 'super_admin') {
                $query->where('company_id', auth()->user()->company_id);
            }

            $truckOwners = $query->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json($truckOwners);
        } catch (\Exception $e) {
            \Log::error('Error fetching truck owners: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch truck owners'], 500);
        }
    }

    /**
     * Get agencies for API
     */
    public function getAgenciesApi()
    {
        try {
            $query = \App\Models\Transporter::where('is_owner', 0);

            // Apply company filter for non-super admin users
            if (auth()->user()->userRole->name !== 'super_admin') {
                $query->where('company_id', auth()->user()->company_id);
            }

            $agencies = $query->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json($agencies);
        } catch (\Exception $e) {
            \Log::error('Error fetching agencies: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch agencies'], 500);
        }
    }

    //
}
