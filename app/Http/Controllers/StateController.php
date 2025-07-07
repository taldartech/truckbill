<?php
namespace App\Http\Controllers;

use App\Models\State;
use App\Models\Country;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index()
    {
        $countries = Country::all();
        $states = State::with('country')->get();
        return view('master.states', compact('states', 'countries'));
    }

    public function create()
    {
        $countries = Country::all();
        return view('master.states-form', compact('countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'country_id' => 'required|exists:countries,id',
        ]);

        State::create($validated);
        return redirect()->route('master.states.index')->with('success', 'State created successfully');
    }

    public function edit(State $state)
    {
        $countries = Country::all();
        return view('master.states-form', compact('state', 'countries'));
    }

    public function update(Request $request, State $state)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'country_id' => 'required|exists:countries,id',
        ]);

        $state->update($validated);
        return redirect()->route('master.states.index')->with('success', 'State updated successfully');
    }

    public function show(State $state)
    {
        return response()->json($state);
    }

    public function destroy(State $state)
    {
        $state->delete();
        return redirect()->route('master.states.index')->with('success', 'State deleted successfully');
    }
}
