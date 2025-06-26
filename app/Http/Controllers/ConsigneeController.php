<?php

namespace App\Http\Controllers;

use App\Models\Consignee;
use Illuminate\Http\Request;

class ConsigneeController extends Controller
{
    public function index()
    {
        $consignees = Consignee::orderBy('name')->paginate(20);
        return view('consignees.index', compact('consignees'));
    }

    public function create()
    {
        return view('consignees.create');
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

        Consignee::create($validated);
        return redirect()->route('consignees.index')
            ->with('success', 'Consignee created successfully!');
    }

    public function edit(Consignee $consignee)
    {
        return view('consignees.edit', compact('consignee'));
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
