<?php
namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::with('state', 'state.country')->get();
        return view('master.cities', compact('cities'));
    }

    public function create()
    {
        $states = State::with('country')->get();
        return view('master.cities-form', compact('states'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
        ]);

        City::create($validated);
        return redirect()->route('master.cities.index')->with('success', 'City created successfully');
    }

    public function edit(City $city)
    {
        $states = State::with('country')->get();
        return view('master.cities-form', compact('city', 'states'));
    }

    public function update(Request $request, City $city)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
        ]);

        $city->update($validated);
        return redirect()->route('master.cities.index')->with('success', 'City updated successfully');
    }

    public function destroy(City $city)
    {
        $city->delete();
        return redirect()->route('master.cities.index')->with('success', 'City deleted successfully');
    }
}
