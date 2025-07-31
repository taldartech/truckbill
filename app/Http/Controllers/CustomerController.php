<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getCustomersData($request);
        }
        
        return view('customers.index');
    }
    
    private function getCustomersData(Request $request)
    {
        $query = Customer::where('company_id', auth()->user()->company_id)
                        ->with(['city', 'state', 'country']);
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('gst_number', 'like', "%{$searchValue}%")
                  ->orWhere('pan_number', 'like', "%{$searchValue}%")
                  ->orWhere('address', 'like', "%{$searchValue}%")
                  ->orWhere('contact_person', 'like', "%{$searchValue}%")
                  ->orWhere('contact_number', 'like', "%{$searchValue}%");
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
                        case 2: // GST Number
                            $query->where('gst_number', 'like', "%{$searchValue}%");
                            break;
                        case 3: // PAN Number
                            $query->where('pan_number', 'like', "%{$searchValue}%");
                            break;
                        case 4: // Address
                            $query->where('address', 'like', "%{$searchValue}%");
                            break;
                        case 5: // Contact Person
                            $query->where('contact_person', 'like', "%{$searchValue}%");
                            break;
                        case 6: // Contact Number
                            $query->where('contact_number', 'like', "%{$searchValue}%");
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
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                case 2: // GST Number
                    $query->orderBy('gst_number', $orderDirection);
                    break;
                case 3: // PAN Number
                    $query->orderBy('pan_number', $orderDirection);
                    break;
                case 4: // Address
                    $query->orderBy('address', $orderDirection);
                    break;
                case 5: // Contact Person
                    $query->orderBy('contact_person', $orderDirection);
                    break;
                case 6: // Contact Number
                    $query->orderBy('contact_number', $orderDirection);
                    break;
                default:
                    $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }
        
        // Get total count before pagination
        $totalRecords = Customer::where('company_id', auth()->user()->company_id)->count();
        $filteredRecords = $query->count();
        
        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $customers = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $customers->map(function($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'gst_number' => $customer->gst_number ?: '-',
                'pan_number' => $customer->pan_number ?: '-',
                'address' => $customer->address ?: '-',
                'contact_person' => $customer->contact_person ?: '-',
                'contact_number' => $customer->contact_number ?: '-',
                'actions' => $this->getActionButtons($customer)
            ];
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($customer)
    {
        $editUrl = route('customers.edit', $customer->id);
        $deleteUrl = route('customers.destroy', $customer->id);
        
        return '
            <div class="d-flex gap-1">
                <a href="' . $editUrl . '" class="btn btn-sm btn-info">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this customer?\')">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        ';
    }

    public function create()
    {
        $countries = \App\Models\Country::all();
        $states = \App\Models\State::all();
            $cities = \App\Models\City::all();
        return view('customers.create', compact('countries', 'states', 'cities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'address' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
                'nullable',
                'digits:10',
            ],
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
        ]);
        
        // Log the validated data
        \Log::info('Customer creation data:', $validated);
        
        // Create customer with explicit location IDs
        $customer = Customer::create([
            'name' => $validated['name'],
            'gst_number' => $validated['gst_number'],
            'pan_number' => $validated['pan_number'],
            'address' => $validated['address'],
            'contact_person' => $validated['contact_person'],
            'contact_number' => $validated['contact_number'],
            'country_id' => $validated['country_id'],
            'state_id' => $validated['state_id'],
            'city_id' => $validated['city_id'],
            'company_id' => auth()->user()->company_id
        ]);
        
        // Log the created customer
        \Log::info('Created customer:', [
            'id' => $customer->id,
            'country_id' => $customer->country_id,
            'state_id' => $customer->state_id,
            'city_id' => $customer->city_id
        ]);
        
        return redirect()->route('customers.index')->with('success', 'Customer added successfully.');
    }

    public function getDetails($id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Log the foreign key values for debugging
        \Log::info('Customer details:', [
            'id' => $id,
            'city_id' => $customer->city_id,
            'state_id' => $customer->state_id
        ]);

        return response()->json([
            'contact_number' => $customer->contact_number,
            'city_id'        => $customer->city_id,
            'state_id'       => $customer->state_id
        ]);
    }


    public function edit(Customer $customer)
    {
        $countries = \App\Models\Country::all();
        $states = $customer->country_id ? \App\Models\State::where('country_id', $customer->country_id)->get() : collect();
        $cities = $customer->state_id ? \App\Models\City::where('state_id', $customer->state_id)->get() : collect();
        return view('customers.edit', compact('customer', 'countries', 'states', 'cities'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
           'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'address' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
                'nullable',
                'digits:10',
            ],
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
        ]);
        
        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
