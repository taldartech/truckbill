@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Customers</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('customers.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> create
        </a>
    </div>

    <form method="GET" action="{{ route('customers.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        {{-- Filter checkboxes --}}
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="gst_number" id="filter_gst" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_gst">GST Number</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="pan_number" id="filter_pan" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_pan">PAN Number</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="contact_person" id="filter_contact_person" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_contact_person">Contact Person</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="contact_number" id="filter_contact_number" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_contact_number">Contact Number</label>
                </div>
            </div>
        </div>

        {{-- Conditionally visible filter fields --}}
        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-3 d-none" id="field_gst_number">
                <label class="form-label">GST Number</label>
                <input type="text" name="gst_number" class="form-control" value="{{ request('gst_number') }}">
            </div>
            <div class="col-md-3 d-none" id="field_pan_number">
                <label class="form-label">PAN Number</label>
                <input type="text" name="pan_number" class="form-control" value="{{ request('pan_number') }}">
            </div>
            <div class="col-md-3 d-none" id="field_contact_person">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" value="{{ request('contact_person') }}">
            </div>
            <div class="col-md-3 d-none" id="field_contact_number">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control" value="{{ request('contact_number') }}">
            </div>
        </div>

        {{-- Form action buttons --}}
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Customers List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>GST</th>
                            <th>PAN</th>
                            <th>Address</th>
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->id }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->gst_number }}</td>
                            <td>{{ $customer->pan_number }}</td>
                            <td>{{ $customer->address }}</td>
                            <td>{{ $customer->contact_person }}</td>
                            <td>{{ $customer->contact_number }}</td>
                            <td>
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="empty-state">
                                    <i class="bi bi-box"></i>
                                    <p class="mt-2">No customers found.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $customers->links() }}
        </div>
    </div>
    {{-- <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a> --}}
</div>

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
        
        // Check if each field has a value in the URL
        checkboxes.forEach(checkbox => {
            const fieldId = 'field_' + checkbox.value;
            const field = document.getElementById(fieldId);
            const input = field.querySelector('input, select');
            
            if (input && input.value) {
                checkbox.checked = true;
                field.classList.remove('d-none');
            } else {
                checkbox.checked = false;
                field.classList.add('d-none');
            }
        });
    });
</script>
@endsection
