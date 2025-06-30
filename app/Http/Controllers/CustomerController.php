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
        return view('customers.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gst_number' => 'nullable|string|max:255',
            'pan_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => 'required|string|max:20',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
        ]);
        
        $validated['company_id'] = auth()->user()->company_id;
        Customer::create($validated);
        return redirect()->route('customers.index')->with('success', 'Customer added successfully.');
    }

    public function getDetails($id)
    {
        $customer = Customer::find($id);
    
        if ($customer) {
            return response()->json([
                'contact_number' => $customer->contact_number,
                'source_city'    => $customer->city,
                'source_state'   => $customer->state
            ]);
        }
    
        return response()->json(null, 404);
    }


    public function edit(Customer $customer)
    {
        $countries = \App\Models\Country::all();
        return view('customers.edit', compact('customer', 'countries'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gst_number' => 'nullable|string|max:255',
            'pan_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => 'required|string|max:20',
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
