<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Truck;
use App\Models\Country;
use App\Models\State;
use App\Models\City;

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
            $query->where(function($q) use ($searchValue) {
                $q->where('transporters.name', 'like', "%{$searchValue}%")
                  ->orWhere('transporters.pan_number', 'like', "%{$searchValue}%")
                  ->orWhere('transporters.mobile_number', 'like', "%{$searchValue}%")
                  ->orWhereHas('company', function($companyQuery) use ($searchValue) {
                      $companyQuery->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('city', function($cityQuery) use ($searchValue) {
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
                        case 1: // Name
                            $query->where('transporters.name', 'like', "%{$searchValue}%");
                            break;
                        case 2: // PAN Number
                            $query->where('transporters.pan_number', 'like', "%{$searchValue}%");
                            break;
                        case 3: // Location
                            $query->whereHas('city', function($q) use ($searchValue) {
                                $q->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 4: // Contact Number
                            $query->where('transporters.mobile_number', 'like', "%{$searchValue}%");
                            break;
                        case 5: // Declaration Given
                            if (strtolower($searchValue) === 'yes') {
                                $query->where('transporters.declaration_given', 1);
                            } elseif (strtolower($searchValue) === 'no') {
                                $query->where('transporters.declaration_given', 0);
                            }
                            break;
                        case 7: // Company
                            $query->whereHas('company', function($q) use ($searchValue) {
                                $q->where('name', 'like', "%{$searchValue}%");
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
                case 0: // Sr. No (ID)
                    $query->orderBy('transporters.id', $orderDirection);
                    break;
                case 1: // Name
                    $query->orderBy('transporters.name', $orderDirection);
                    break;
                case 2: // PAN Number
                    $query->orderBy('transporters.pan_number', $orderDirection);
                    break;
                case 3: // Location
                    $query->leftJoin('cities', 'cities.id', '=', 'transporters.city_id')
                          ->orderBy('cities.name', $orderDirection)
                          ->select('transporters.*');
                    break;
                case 4: // Contact Number
                    $query->orderBy('transporters.mobile_number', $orderDirection);
                    break;
                case 5: // Declaration Given
                    $query->orderBy('transporters.declaration_given', $orderDirection);
                    break;
                case 6: // Advance Amount
                    $query->orderBy('transporters.advance_amount', $orderDirection);
                    break;
                case 7: // Company
                    $query->leftJoin('companies', 'companies.id', '=', 'transporters.company_id')
                          ->orderBy('companies.name', $orderDirection)
                          ->select('transporters.*');
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
        $data = $transporters->map(function($transporter, $index) use ($start) {
            return [
                'sr_no' => $start + $index + 1,
                'name' => '<a href="javascript:void(0)" class="text-primary fw-bold transporter-name-link" data-id="' . $transporter->id . '" style="cursor: pointer; text-decoration: none;">' . ($transporter->name ?: '-') . '</a>',
                'pan_number' => $transporter->pan_number ?: '-',
                'location' => optional($transporter->city)->name ?: '-',
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
            ->map(function($payment) {
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
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'
            ],
            'pan_number' => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'unique:transporters,pan_number'
            ],
            'gst_type' => 'nullable|in:composition,regular',
            'is_owner' => 'nullable|boolean',
            'advance_amount' => 'nullable|numeric',
            'pan_active' => 'required|boolean',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'country_id' => 'nullable|exists:countries,id',
            'mobile_number' => [
                'required',
                'digits:10',
                'unique:transporters,mobile_number'
            ],
            'short_name' => 'nullable|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
        ], [
            'pan_number.unique' => 'This PAN number is already associated with another transporter and cannot be used.',
            'mobile_number.unique' => 'This mobile number is already associated with another transporter.',
            
        ]);
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
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'
            ],
            'pan_number' => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'unique:transporters,pan_number,' . $transporter->id
            ],
            'gst_type' => 'nullable|in:composition,regular',
            'is_owner' => 'nullable|boolean',
            'advance_amount' => 'nullable|numeric',
            'pan_active' => 'required|boolean',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'country_id' => 'nullable|exists:countries,id',
            'mobile_number' => [
                'required',
                'digits:10',
                \Illuminate\Validation\Rule::unique('transporters', 'mobile_number')->ignore($transporter->id)
            ],
            'short_name' => 'nullable|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',

        ], [
            'pan_number.unique' => 'This PAN number is already associated with another transporter and cannot be used.',
            'mobile_number.unique' => 'This mobile number is already associated with another transporter.',
        ]); 
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
        $transporter->update($validated);
        return redirect()->route('transporters.index')->with('success', 'Transporter updated successfully!');
    }

    public function destroy(\App\Models\Transporter $transporter)
    {
        $transporter->delete();
        return redirect()->route('transporters.index')->with('success', 'Transporter deleted successfully!');
    }

    //
}
