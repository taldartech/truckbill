<?php

namespace App\Http\Controllers;

use App\Models\TruckOwner;
use App\Models\Transporter;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Truck;
use App\Models\Driver;
use Illuminate\Http\Request;
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
        $query = \App\Models\Transporter::with(['company', 'city', 'bankAccounts' => function($q) {
            $q->select('transporter_id', 'bulk_payment_id');
        }])->where('is_owner', 1); // Show only truck owners

        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('transporters.name', 'like', "%{$searchValue}%")
                  ->orWhere('transporters.pan_number', 'like', "%{$searchValue}%")
                  ->orWhere('transporters.mobile_number', 'like', "%{$searchValue}%")
                  ->orWhereHas('city', function($cityQuery) use ($searchValue) {
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
                        case 2: // PAN Number
                            $query->where('transporters.pan_number', 'like', "%{$searchValue}%");
                            break;
                        case 3: // Mobile Number
                            $query->where('transporters.mobile_number', 'like', "%{$searchValue}%");
                            break;
                        case 4: // City
                            $query->whereHas('city', function($cityQuery) use ($searchValue) {
                                $cityQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 5: // Company
                            $query->whereHas('company', function($companyQuery) use ($searchValue) {
                                $companyQuery->where('name', 'like', "%{$searchValue}%");
                            });
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
                case 2: // PAN Number
                    $query->orderBy('transporters.pan_number', $orderDir);
                    break;
                case 3: // Mobile Number
                    $query->orderBy('transporters.mobile_number', $orderDir);
                    break;
                case 4: // City
                    $query->join('cities', 'transporters.city_id', '=', 'cities.id')
                          ->orderBy('cities.name', $orderDir)
                          ->select('transporters.*');
                    break;
                case 5: // Company
                    $query->join('companies', 'transporters.company_id', '=', 'companies.id')
                          ->orderBy('companies.name', $orderDir)
                          ->select('transporters.*');
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
            $data[] = [
                'sr_no' => $start + $index + 1,
                'name' => $truckOwner->name ?? 'N/A',
                'pan_number' => $truckOwner->pan_number ?? 'N/A',
                'mobile_number' => $truckOwner->mobile_number ?? 'N/A',
                'city' => $truckOwner->city->name ?? 'N/A',
                'company' => $truckOwner->company->name ?? 'N/A',
                'actions' => $this->getActionButtons($truckOwner)
            ];
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    private function getActionButtons($truckOwner)
    {
        $buttons = '';
        
        // Bank Account Management Button
        $hasBankAccount = $truckOwner->bankAccounts && $truckOwner->bankAccounts->count() > 0;
        if ($hasBankAccount) {
            $buttons .= '<a href="' . route('truck_owner_bank_accounts.index') . '?company_id=' . $truckOwner->company_id . '" class="btn btn-success btn-sm me-1" title="Edit Bank Account">
                <i class="fas fa-university"></i>
            </a>';
        } else {
            $buttons .= '<a href="' . route('truck_owner_bank_accounts.index') . '?company_id=' . $truckOwner->company_id . '" class="btn btn-warning btn-sm me-1" title="Add Bank Account">
                <i class="fas fa-plus"></i>
            </a>';
        }
        
        // Edit Button
        $buttons .= '<a href="' . route('truck_owners.edit', $truckOwner->id) . '" class="btn btn-primary btn-sm me-1" title="Edit">
            <i class="fas fa-edit"></i>
        </a>';
        
        // Delete Button
        $buttons .= '<button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(' . $truckOwner->id . ')" title="Delete">
            <i class="fas fa-trash"></i>
        </button>';
        
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
                'unique:transporters,gst_number'
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'unique:transporters,pan_number'
            ],
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0',
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
            'short_name' => ['nullable', 'string', 'max:255', 'unique:transporters,short_name'],
            'contact_person_name' => 'nullable|string|max:255',
        ], [
            'pan_number.unique' => 'This PAN number is already registered with another truck owner or transporter.',
            'gst_number.unique' => 'This GST number is already registered with another truck owner or transporter.',
            'mobile_number.unique' => 'This mobile number is already registered with another truck owner or transporter.',
        ]);
        
        // Always set truck owner specific fields (system controlled, not user input)
        $validated['advance_amount'] = 0; // Truck owners always have 0 advance amount
        $validated['is_owner'] = 1; // Always true for truck owners (this differentiates from transporters)
        
        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
        
        \App\Models\Transporter::create($validated);
        return redirect()->route('truck_owners.index')->with('success', 'Truck Owner created successfully!');
    }

    public function show(\App\Models\Transporter $transporter)
    {
        return view('truck_owner.show', compact('transporter'));
    }

    public function edit(\App\Models\Transporter $transporter)
    {
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
                Rule::unique('transporters', 'gst_number')->ignore($transporter->id)
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                Rule::unique('transporters', 'pan_number')->ignore($transporter->id)
            ],
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0',
            'advance_amount' => 'nullable|numeric',
            'pan_active' => 'required|boolean',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'country_id' => 'nullable|exists:countries,id',
            'mobile_number' => [
                'required',
                'digits:10',
                Rule::unique('transporters', 'mobile_number')->ignore($transporter->id)
            ],
            'short_name' => ['nullable', 'string', 'max:255', 'unique:transporters,short_name'],
            'contact_person_name' => 'nullable|string|max:255',
        ], [
            'pan_number.unique' => 'This PAN number is already registered with another truck owner or transporter.',
            'gst_number.unique' => 'This GST number is already registered with another truck owner or transporter.',
            'mobile_number.unique' => 'This mobile number is already registered with another truck owner or transporter.',
        ]);
        
        // Always set truck owner specific fields (system controlled, not user input)
        $validated['advance_amount'] = 0; // Truck owners always have 0 advance amount
        $validated['is_owner'] = 1; // Always true for truck owners (this differentiates from transporters)
        
        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
        
        $transporter->update($validated);
        return redirect()->route('truck_owners.index')->with('success', 'Truck Owner updated successfully!');
    }

    public function destroy(\App\Models\Transporter $transporter)
    {
        $transporter->delete();
        return redirect()->route('truck_owners.index')->with('success', 'Truck Owner deleted successfully!');
    }
}
