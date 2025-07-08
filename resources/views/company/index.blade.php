@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Company Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('company.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    <form method="GET" action="{{ route('company.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="name" id="filter_name" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_name">Name</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="gst_number" id="filter_gst" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_gst">GST Number</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="pan_number" id="filter_pan" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_pan">PAN Number</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="gst_scheme" id="filter_scheme" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_scheme">GST Scheme</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="gst_type" id="filter_type" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_type">GST Type</label>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-2 d-none" id="field_name">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ request('name') }}">
            </div>
            <div class="col-md-2 d-none" id="field_gst_number">
                <label class="form-label">GST Number</label>
                <input type="text" name="gst_number" class="form-control" value="{{ request('gst_number') }}">
            </div>
            <div class="col-md-2 d-none" id="field_pan_number">
                <label class="form-label">PAN Number</label>
                <input type="text" name="pan_number" class="form-control" value="{{ request('pan_number') }}">
            </div>
            <div class="col-md-2 d-none" id="field_gst_scheme">
                <label class="form-label">GST Scheme</label>
                <select name="gst_scheme" class="form-select">
                    <option value="">All</option>
                    <option value="reverse charge" {{ request('gst_scheme') == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                    <option value="forward charge" {{ request('gst_scheme') == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                </select>
            </div>
            <div class="col-md-2 d-none" id="field_gst_type">
                <label class="form-label">GST Type</label>
                <select name="gst_type" class="form-select">
                    <option value="">All</option>
                    <option value="composition" {{ request('gst_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                    <option value="regular" {{ request('gst_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                </select>
            </div>
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('company.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Companies List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Name</th>
                        <th>GST Number</th>
                        <th>PAN Number</th>
                        <th>GST Scheme</th>
                        <th>GST Type</th>
                        <th>Address</th>
                        <th>Contact Person</th>
                        <th>Contact Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr class="align-middle">
                            <td>{{ $company->id }}</td>
                            <td>{{ $company->name }}</td>
                            <td>{{ $company->gst_number }}</td>
                            <td>{{ $company->pan_number }}</td>
                            <td>{{ $company->gst_scheme }}</td>
                            <td>{{ $company->gst_type }}</td>
                            <td>{{ $company->address }}</td>
                            <td>{{ $company->contact_person }}</td>
                            <td>{{ $company->contact_number }}</td>
                            <td class="d-flex align-items-center">
                                <a href="{{ route('company.edit', $company->id) }}" class="btn btn-sm btn-info me-2" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('company.destroy', $company->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center">No companies found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.toggle('d-none');
        }
    }
</script>
@endsection
