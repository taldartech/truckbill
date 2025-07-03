<?php

namespace App\Http\Controllers;

use App\Models\Consignee;
use Illuminate\Http\Request;

class ConsigneeController extends Controller
{
    public function index()
    {
        $consignees = Consignee::where('company_id', auth()->user()->company_id)
            ->with(['city', 'state', 'country'])
            ->orderBy('name')
            ->paginate(20);
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
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'contact_number' => 'required|string|max:20',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        
        $consignee = Consignee::create($validated);
        return redirect()->route('consignees.index')
            ->with('success', 'Consignee created successfully!');
    }



    public function edit(Consignee $consignee)
    {
        $countries = \App\Models\Country::all();
        $states = $consignee->country_id ? \App\Models\State::where('country_id', $consignee->country_id)->get() : collect();
        $cities = $consignee->state_id ? \App\Models\City::where('state_id', $consignee->state_id)->get() : collect();
        return view('consignees.edit', compact('consignee', 'countries', 'states', 'cities'));
    }

    public function update(Request $request, Consignee $consignee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'contact_number' => 'required|string|max:20',
        ]);

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
        $consignee = Consignee::with(['city', 'state'])->find($id);

        if ($consignee) {
            return response()->json([
                'contact_number' => $consignee->contact_number,
                'city_id' => $consignee->city_id,
                'state_id' => $consignee->state_id,
                'city_name' => $consignee->city ? $consignee->city->name : '',
                'state_name' => $consignee->state ? $consignee->state->name : ''
            ]);
        }

        return response()->json(['error' => 'Consignee not found'], 404);
    }
}
