<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::where('company_id', auth()->user()->company_id)->latest()->paginate(20);
        return view('customers.index', compact('customers'));
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
