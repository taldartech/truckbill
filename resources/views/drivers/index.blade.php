@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Drivers</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('drivers.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> create
        </a>
    </div>

    <form method="GET" action="{{ route('drivers.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        {{-- Filter checkboxes --}}
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="name" id="filter_name" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_name">Name</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="mobile" id="filter_mobile" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_mobile">Mobile</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="license_number" id="filter_license_number" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_license_number">License Number</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="address" id="filter_address" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_address">Address</label>
                </div>
            </div>
        </div>

        {{-- Conditionally visible filter fields --}}
        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-3 d-none" id="field_name">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ request('name') }}">
            </div>
            <div class="col-md-3 d-none" id="field_mobile">
                <label class="form-label">Mobile</label>
                <input type="text" name="mobile" class="form-control" value="{{ request('mobile') }}">
            </div>
            <div class="col-md-3 d-none" id="field_license_number">
                <label class="form-label">License Number</label>
                <input type="text" name="license_number" class="form-control" value="{{ request('license_number') }}">
            </div>
            <div class="col-md-3 d-none" id="field_address">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="{{ request('address') }}">
            </div>
        </div>

        {{-- Form action buttons --}}
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Drivers List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
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
    {{-- <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a> --}}
</div>
@endsection

<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        const field = document.getElementById(fieldId);
        if (checkbox.checked) {
            field.classList.remove('d-none');
        } else {
            field.classList.add('d-none');
            const input = field.querySelector('input, select');
            if (input) input.value = '';
        }
    }

    // Initialize filter fields based on URL parameters
    document.addEventListener('DOMContentLoaded', function() {
        // Get all filter checkboxes
        const checkboxes = document.querySelectorAll('.form-check-input');
        
        // Check each checkbox based on URL parameters
        checkboxes.forEach(checkbox => {
            const value = checkbox.value;
            const fieldId = 'field_' + value;
            const field = document.getElementById(fieldId);
            const urlValue = new URLSearchParams(window.location.search).get(value);
            
            if (urlValue) {
                checkbox.checked = true;
                field.classList.remove('d-none');
            }
        });
    });
</script>
