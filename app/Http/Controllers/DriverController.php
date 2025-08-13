<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Driver::query();
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('mobile')) {
            $query->where('mobile', 'like', '%' . $request->mobile . '%');
        }
        if ($request->filled('license_number')) {
            $query->where('license_number', 'like', '%' . $request->license_number . '%');
        }
        $drivers = $query->orderByDesc('created_at')->get();
        return view('drivers.index', compact('drivers'));
    }

    public function getDriversApi()
    {
        $drivers = \App\Models\Driver::select('id', 'name', 'mobile')
            ->orderBy('name')
            ->get();
        
        return response()->json($drivers);
    }

    public function create()
    {
        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'mobile' => [
                'nullable',
                'digits:10',
            ],
            'license_number' => [
                'nullable',
                'string',
            ],
            'address' => 'nullable|string|max:255',
        ]);
        
        $driver = \App\Models\Driver::create($validated);
        
        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Driver created successfully!',
                'driver' => $driver
            ]);
        }
        
        return redirect()->route('drivers.index')->with('success', 'Driver created successfully!');
    }

    public function edit(\App\Models\Driver $driver)
    {
        return view('drivers.edit', compact('driver'));
    }

    public function update(Request $request, \App\Models\Driver $driver)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'mobile' => [
                'nullable',
                'digits:10',
            ],
             'license_number' => [
                            'nullable',
                            'string',
                            
                          
                        ],
            'address' => 'nullable|string|max:255',
        ]);
        $driver->update($validated);
        return redirect()->route('drivers.index')->with('success', 'Driver updated successfully!');
    }

    public function destroy(\App\Models\Driver $driver)
    {
        $driver->delete();
        return redirect()->route('drivers.index')->with('success', 'Driver deleted successfully!');
    }

    //
}
