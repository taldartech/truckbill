@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Truck Details</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="GET" action="{{ route('trucks.index') }}" class="row g-3 mb-3">
        <div class="col-md-3">
            <input type="text" name="truck_number" class="form-control" placeholder="Truck Number" value="{{ request('truck_number') }}">
        </div>
        <div class="col-md-3">
            <input type="text" name="truck_type" class="form-control" placeholder="Truck Type" value="{{ request('truck_type') }}">
        </div>
        <div class="col-md-3">
            <select name="driver_id" class="form-select">
                <option value="">All Drivers</option>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->mobile }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="transporter_id" class="form-select">
                <option value="">All Transporters</option>
                @foreach($transporters as $transporter)
                    <option value="{{ $transporter->id }}" {{ request('transporter_id') == $transporter->id ? 'selected' : '' }}>{{ $transporter->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="{{ route('trucks.index') }}" class="btn btn-outline-secondary ms-2">Reset</a>
            <a href="{{ route('trucks.create') }}" class="btn btn-primary ms-2">Add New Truck</a>
        </div>
    </form>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Truck Number</th>
                <th>Truck Type</th>
                <th>Driver Name</th>
                <th>Registration Date</th>
                <th>Transporter</th>
                <th>Driver</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trucks as $truck)
                <tr>
                    <td>{{ $truck->id }}</td>
                    <td>{{ $truck->truck_number }}</td>
                    <td>{{ $truck->truck_type }}</td>
                    <td>{{ $truck->driver_name }}</td>
                    <td>{{ $truck->registration_date }}</td>
                    <td>{{ $truck->transporter ? $truck->transporter->name : '-' }}</td>
                    <td>{{ $truck->driver ? $truck->driver->name : '-' }}</td>
                    <td>
                        <a href="{{ route('trucks.edit', $truck->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('trucks.destroy', $truck->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No trucks found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
@endsection
