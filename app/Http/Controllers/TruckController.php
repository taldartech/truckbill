<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TruckController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Truck::with(['transporter', 'driver']);
        if ($request->filled('truck_number')) {
            $query->where('truck_number', 'like', '%' . $request->truck_number . '%');
        }
        if ($request->filled('truck_type')) {
            $query->where('truck_type', 'like', '%' . $request->truck_type . '%');
        }
        if ($request->filled('driver_name')) {
            $query->where('driver_name', 'like', '%' . $request->driver_name . '%');
        }
        if ($request->filled('transporter_id')) {
            $query->where('transporter_id', $request->transporter_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        $trucks = $query->orderByDesc('created_at')->get();
        $transporters = \App\Models\Transporter::all();
        $drivers = \App\Models\Driver::all();
        return view('trucks.index', compact('trucks', 'transporters', 'drivers'));
    }

    public function create()
    {
        $transporters = \App\Models\Transporter::all();
        $drivers = \App\Models\Driver::all();
        return view('trucks.create', compact('transporters', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'truck_number' => 'required|string|max:50',
            'truck_type' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:drivers,id',
            'registration_date' => 'required|date',
        ]);
        \App\Models\Truck::create($validated);
        return redirect()->route('trucks.index')->with('success', 'Truck created successfully!');
    }

    public function edit(\App\Models\Truck $truck)
    {
        $transporters = \App\Models\Transporter::all();
        $drivers = \App\Models\Driver::all();
        return view('trucks.edit', compact('truck', 'transporters', 'drivers'));
    }

    public function update(Request $request, \App\Models\Truck $truck)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'truck_number' => 'required|string|max:50',
            'truck_type' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:drivers,id',
            'registration_date' => 'required|date',
        ]);
        $truck->update($validated);
        return redirect()->route('trucks.index')->with('success', 'Truck updated successfully!');
    }

    public function destroy(\App\Models\Truck $truck)
    {
        $truck->delete();
        return redirect()->route('trucks.index')->with('success', 'Truck deleted successfully!');
    }

    public function report(Request $request)
    {
        $query = \App\Models\Truck::with(['transporter', 'driver']);

        if ($request->filled('truck_type')) {
            $query->where('truck_type', $request->truck_type);
        }
        if ($request->filled('transporter_id')) {
            $query->where('transporter_id', $request->transporter_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('registration_date_from')) {
            $query->whereDate('registration_date', '>=', $request->registration_date_from);
        }
        if ($request->filled('registration_date_to')) {
            $query->whereDate('registration_date', '<=', $request->registration_date_to);
        }
        $trucks = $query->orderByDesc('created_at')->get();
        $total = $trucks->count();
        $transporters = \App\Models\Transporter::all();
        $drivers = \App\Models\Driver::all();
        $truckTypes = [
            'Open', 'Closed', 'Container', 'Trailer', 'Tanker', 'Refrigerated', 'Flatbed', 'Tipper', 'Other'
        ];
        return view('trucks.report', compact('trucks', 'total', 'transporters', 'drivers', 'truckTypes'));
    }
}
