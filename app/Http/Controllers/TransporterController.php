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
        $query = \App\Models\Transporter::with(['company','city', 'bankAccounts' => function($q) {
            $q->select('transporter_id', 'bulk_payment_id');
        }])->where('is_owner', 0); // Default to show only Agencies

        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('pan_number', 'like', "%{$searchValue}%");
                 
            });
        }
        
        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Name
                            $query->where('name', 'like', "%{$searchValue}%");
                            break;
                        case 2: // PAN Number
                            $query->where('pan_number', 'like', "%{$searchValue}%");
                            break;
                        case 3: // Location
                            $query->whereHas('city', function($q) use ($searchValue) {
                                $q->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 4: // Contact Number
                            $query->where('mobile_number', 'like', "%{$searchValue}%");
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
                    $query->orderBy('id', $orderDirection);
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                case 2: // PAN Number
                    $query->orderBy('pan_number', $orderDirection);
                    break;
                case 3: // Location
                    $query->orderByHas('city', function($q) use ($orderDirection) {
                        $q->orderBy('name', $orderDirection);
                    });
                    break;
                case 4: // Contact Number
                    $query->orderBy('mobile_number', $orderDirection);
                    break;

                case 5: // Declaration Given
                    $query->orderBy('declaration_given', $orderDirection);
                    break;
                
                   
                case 6: // Advance Amount
                    $query->orderBy('advance_amount', $orderDirection);
                    break;
               
                    
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
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
        $transporters = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $transporters->map(function($transporter, $index) use ($start) {
            return [
                'sr_no' => $start + $index + 1,
                'name' => $transporter->name,
                'pan_number' => $transporter->pan_number ?: '-',
                'location' => optional($transporter->city)->name ?: '-',
                'contact_number' => $transporter->mobile_number ?: '-',
                'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
                
                'advance_amount' => number_format($transporter->advance_amount ?: 0, 2),

                // 'actions' => $this->getActionButtons($transporter)
                'actions' => auth()->user()->userRole->name === 'admin'
                        ? $this->getActionButtons($transporter)
                        : ''  // empty string for non-admins
            ];
     
      return $row;
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($transporter)
    {
        $buttons = '<div class="d-flex gap-1">';
        
        // Bank account button
        if($transporter->bankAccounts->isEmpty() || $transporter->bankAccounts->first()->bulk_payment_id === null) {
            $bankUrl = route('transporter_bank_accounts.create', ['transporter_id' => $transporter->id]);
            $buttons .= '<a href="' . $bankUrl . '" class="btn btn-sm btn-info" title="Add Bank Account">';
            $buttons .= '<i class="bi bi-bank"></i></a>';
        } else {
            $bankUrl = route('transporter_bank_accounts.index', ['transporter_id' => $transporter->id]);
            $buttons .= '<a href="' . $bankUrl . '" class="btn btn-sm btn-success" title="View Bank Accounts">';
            $buttons .= '<i class="bi bi-bank"></i></a>';
        }

        // $bankUrl = route('transporter_bank_accounts.create', ['transporter_id' => $transporter->id]);
        // $buttons .= '<a href="' . $bankUrl . '" class="btn btn-sm btn-info" title="Add Bank Account">';
        // $buttons .= '<i class="bi bi-bank"></i></a>';
        

        
        // Edit button
        $editUrl = route('transporters.edit', $transporter->id);
        $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info">';
        $buttons .= '<i class="bi bi-pencil"></i></a>';
        
        // Delete button
        $deleteUrl = route('transporters.destroy', $transporter->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this transporter?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';
        
        $buttons .= '</div>';
        return $buttons;
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
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_type' => 'nullable|in:composition,regular',
            'is_owner' => 'nullable|boolean',
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
            ],
        ]);
    //    dd($validated);
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        if (!isset($validated['is_owner'])) {
            $validated['is_owner'] = 0;
        }
        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
       
        \App\Models\Transporter::create($validated);
       
        return redirect()->route('transporters.index')->with('success', 'Transporter created successfully!');
    }

    public function edit(\App\Models\Transporter $transporter)
    {
        $companies = \App\Models\Company::all();
        // Filter trucks to show only those belonging to this transporter
        $trucks = \App\Models\Truck::where('transporter_id', $transporter->id)->get();
        $countries = \App\Models\Country::all();
        $states = $transporter->country_id ? \App\Models\State::where('country_id', $transporter->country_id)->get() : collect();
        $cities = $transporter->state_id ? \App\Models\City::where('state_id', $transporter->state_id)->get() : collect();
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
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_type' => 'nullable|in:composition,regular',
            'is_owner' => 'nullable|boolean',
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
            ],
        ]); 
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }

        if (!isset($validated['is_owner'])) {
            $validated['is_owner'] = 0;
        }
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
