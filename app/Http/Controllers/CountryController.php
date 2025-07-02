<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::all();
        return view('master.countries', compact('countries'));
    }

    public function create()
    {
        return view('master.countries-form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
        ]);

        Country::create($validated);
        return redirect()->route('master.countries.index')->with('success', 'Country created successfully');
    }

    public function edit(Country $country)
    {
        return view('master.countries-form', compact('country'));
    }

    public function update(Request $request, Country $country)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
        ]);

        $country->update($validated);
        return redirect()->route('master.countries.index')->with('success', 'Country updated successfully');
    }

    public function destroy(Country $country)
    {
        $country->delete();
        return redirect()->route('master.countries.index')->with('success', 'Country deleted successfully');
    }
}
