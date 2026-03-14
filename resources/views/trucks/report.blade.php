@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Truck Report</h2>
    <form method="GET" action="{{ route('trucks.report') }}" class="row g-3 mb-3">
        <div class="col-md-2">
            <select name="truck_type" class="form-select">
                <option value="">All Types</option>
                @foreach($truckTypes ?? [] as $type)
                    <option value="{{ $type->name }}" {{ request('truck_type') == $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="transporter_id" class="form-select">
                <option value="">All Transporters</option>
                @foreach($transporters as $transporter)
                    <option value="{{ $transporter->id }}" {{ request('transporter_id') == $transporter->id ? 'selected' : '' }}>{{ $transporter->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="driver_id" class="form-select">
                <option value="">All Drivers</option>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="registration_date_from" class="form-control" placeholder="From Date" value="{{ request('registration_date_from') }}">
        </div>
        <div class="col-md-2">
            <input type="date" name="registration_date_to" class="form-control" placeholder="To Date" value="{{ request('registration_date_to') }}">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="{{ route('trucks.report') }}" class="btn btn-outline-secondary ms-2">Reset</a>
        </div>
    </form>
    <div class="mb-3">
        <strong>Total Trucks: {{ $total }}</strong>
    </div>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Truck Number</th>
                <th>Truck Type</th>
                <th>Transporter</th>
                <th>Driver</th>
                <th>Registration Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trucks as $truck)
                <tr>
                    <td>{{ $truck->id }}</td>
                    <td>{{ $truck->truck_number }}</td>
                    <td>{{ $truck->truck_type }}</td>
                    <td>{{ $truck->transporter ? $truck->transporter->name : '-' }}</td>
                    <td>{{ $truck->driver ? $truck->driver->name : '-' }}</td>
                    <td>{{ $truck->registration_date }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No trucks found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
@endsection
