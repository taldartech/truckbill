<?php

namespace App\Http\Controllers;

use App\Models\Consignee;
use Illuminate\Http\Request;

class ConsigneeController extends Controller
{
    public function index()
    {
        $consignees = Consignee::where('company_id', auth()->user()->company_id)->orderBy('name')->paginate(20);
        return view('consignees.index', compact('consignees'));
    }

    public function create()
    {
        $countries = \App\Models\Country::all();
        $states = [];
    $cities = [];
    
    if (old('country_id')) {
        $states = \App\Models\State::where('country_id', old('country_id'))->get();
    }
    
    if (old('state_id')) {
        $cities = \App\Models\City::where('state_id', old('state_id'))->get();
    }
    
    return view('consignees.create', compact('countries', 'states', 'cities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['city_name'] = \App\Models\City::find($validated['city_id'])->name;
        $validated['state_name'] = \App\Models\State::find($validated['state_id'])->name;
        $validated['country_name'] = \App\Models\Country::find($validated['country_id'])->name;
        Consignee::create($validated);
        return redirect()->route('consignees.index')
            ->with('success', 'Consignee created successfully!');
    }

    public function edit(Consignee $consignee)
    {
        $countries = \App\Models\Country::all();
        $states = \App\Models\State::where('country_id', $consignee->country_id)->get();
        $cities = \App\Models\City::where('state_id', $consignee->state_id)->get();
        return view('consignees.edit', compact('consignee', 'countries', 'states', 'cities'));
    }

    public function update(Request $request, Consignee $consignee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
        ]);

        $validated['city_name'] = \App\Models\City::find($validated['city_id'])->name;
        $validated['state_name'] = \App\Models\State::find($validated['state_id'])->name;
        $validated['country_name'] = \App\Models\Country::find($validated['country_id'])->name;
        $consignee->update($validated);
        return redirect()->route('consignees.index')
            ->with('success', 'Consignee updated successfully!');
    }

    public function destroy(Consignee $consignee)
    {
        $consignee->delete();
        return redirect()->route('consignees.index')
            ->with('success', 'Consignee deleted successfully!');
    }
    
    public function getDetails($id)
{
    $consignee = Consignee::find($id);

    if ($consignee) {
        return response()->json([
            'contact_number'     => $consignee->contact_number,
            'destination_city'   => $consignee->city,
            'destination_state'  => $consignee->state
        ]);
    }

    return response()->json(null, 404);
}
}
