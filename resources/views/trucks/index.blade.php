@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Truck Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Always visible container --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('transporters.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> create
        </a>
    </div>

    {{-- Hidden filter form --}}
    <form method="GET" action="{{ route('trucks.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        {{-- Filter checkboxes --}}
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="truck_number" id="filter_truck_number" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_truck_number">Truck Number</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="truck_type" id="filter_truck_type" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_truck_type">Truck Type</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="driver_id" id="filter_driver" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_driver">Driver</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="transporter_id" id="filter_transporter" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_transporter">Transporter</label>
                </div>
            </div>
        </div>

        {{-- Conditionally visible filter fields --}}
        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-2 d-none" id="field_truck_number">
                <label class="form-label">Truck Number</label>
                <input type="text" name="truck_number" class="form-control" value="{{ request('truck_number') }}">
            </div>
            <div class="col-md-2 d-none" id="field_truck_type">
                <label class="form-label">Truck Type</label>
                <select name="truck_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Open" {{ request('truck_type') == 'Open' ? 'selected' : '' }}>Open</option>
                    <option value="Closed" {{ request('truck_type') == 'Closed' ? 'selected' : '' }}>Closed</option>
                    <option value="Container" {{ request('truck_type') == 'Container' ? 'selected' : '' }}>Container</option>
                    <option value="Trailer" {{ request('truck_type') == 'Trailer' ? 'selected' : '' }}>Trailer</option>
                    <option value="Tanker" {{ request('truck_type') == 'Tanker' ? 'selected' : '' }}>Tanker</option>
                    <option value="Refrigerated" {{ request('truck_type') == 'Refrigerated' ? 'selected' : '' }}>Refrigerated</option>
                    <option value="Flatbed" {{ request('truck_type') == 'Flatbed' ? 'selected' : '' }}>Flatbed</option>
                    <option value="Tipper" {{ request('truck_type') == 'Tipper' ? 'selected' : '' }}>Tipper</option>
                    <option value="Other" {{ request('truck_type') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="col-md-2 d-none" id="field_driver_id">
                <label class="form-label">Driver</label>
                <select name="driver_id" class="form-select">
                    <option value="">All Drivers</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->mobile }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-none" id="field_transporter_id">
                <label class="form-label">Transporter</label>
                <select name="transporter_id" class="form-select">
                    <option value="">All Transporters</option>
                    @foreach($transporters as $transporter)
                        <option value="{{ $transporter->id }}" {{ request('transporter_id') == $transporter->id ? 'selected' : '' }}>{{ $transporter->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Form action buttons --}}
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('trucks.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Trucks List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Truck Number</th>
                        <th>Truck Type</th>
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
                            <td>{{ $truck->registration_date }}</td>
                            <td>{{ $truck->transporter ? $truck->transporter->name : '-' }}</td>
                            <td>{{ $truck->driver ? $truck->driver->name : '-' }}</td>
                            <td>
                                <a href="{{ route('trucks.edit', $truck->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('trucks.destroy', $truck->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No trucks found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            
        </div>
        
    </div>
    {{-- <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a> --}}
</div>

<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        document.getElementById(fieldId).classList.toggle('d-none');
    }
</script>
@endsection
