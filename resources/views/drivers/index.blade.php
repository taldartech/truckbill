@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Driver Management</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="GET" action="{{ route('drivers.index') }}" class="row g-3 mb-3">
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Name" value="{{ request('name') }}">
        </div>
        <div class="col-md-3">
            <input type="text" name="mobile" class="form-control" placeholder="Mobile" value="{{ request('mobile') }}">
        </div>
        <div class="col-md-3">
            <input type="text" name="license_number" class="form-control" placeholder="License Number" value="{{ request('license_number') }}">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary ms-2">Reset</a>
            <a href="{{ route('drivers.create') }}" class="btn btn-primary ms-2">Add New Driver</a>
        </div>
    </form>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>License Number</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($drivers as $driver)
                <tr>
                    <td>{{ $driver->id }}</td>
                    <td>{{ $driver->name }}</td>
                    <td>{{ $driver->mobile }}</td>
                    <td>{{ $driver->license_number }}</td>
                    <td>{{ $driver->address }}</td>
                    <td>
                        <a href="{{ route('drivers.edit', $driver->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('drivers.destroy', $driver->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No drivers found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
@endsection
