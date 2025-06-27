@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Transporter Management</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="GET" action="{{ route('transporters.index') }}" class="row g-3 mb-3">
        <div class="col-md-2">
            <input type="text" name="name" class="form-control" placeholder="Name" value="{{ request('name') }}">
        </div>
        <div class="col-md-2">
            <input type="text" name="gst_number" class="form-control" placeholder="GST Number" value="{{ request('gst_number') }}">
        </div>
        <div class="col-md-2">
            <select name="gst_type" class="form-select">
                <option value="">All GST Types</option>
                <option value="composition" {{ request('gst_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                <option value="regular" {{ request('gst_type') == 'regular' ? 'selected' : '' }}>Regular</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="pan_number" class="form-control" placeholder="PAN Number" value="{{ request('pan_number') }}">
        </div>
        <div class="col-md-2">
            <select name="is_owner" class="form-select">
                <option value="">All Types</option>
                <option value="1" {{ request('is_owner') === '1' ? 'selected' : '' }}>Owner</option>
                <option value="0" {{ request('is_owner') === '0' ? 'selected' : '' }}>Agency</option>
            </select>
        </div>
        @php
    $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
    $userCompanyId = auth()->user()->company_id ?? null;
@endphp
<div class="col-md-2">
    @if($canEditCompany)
        <select name="company_id" class="form-select">
            <option value="">All Companies</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" {{ request('company_id', $userCompanyId) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
            @endforeach
        </select>
    @else
        <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
        <div class="form-control-plaintext">
            {{ $companies->firstWhere('id', $userCompanyId)->name ?? '' }}
        </div>
    @endif
</div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="{{ route('transporters.index') }}" class="btn btn-outline-secondary ms-2">Reset</a>
            <a href="{{ route('transporters.create') }}" class="btn btn-primary ms-2">Add New Transporter</a>
        </div>
    </form>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>GST Number</th>
                <th>GST Type</th>
                <th>PAN Number</th>
                <th>Type</th>
                <th>Declaration Given</th>
                <th>Total Trucks</th>
                <th>Advance Amount</th>
                <th>Company</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transporters as $transporter)
                <tr>
                    <td>{{ $transporter->id }}</td>
                    <td>{{ $transporter->name }}</td>
                    <td>{{ $transporter->gst_number }}</td>
                    <td>{{ ucfirst($transporter->gst_type) }}</td>
                    <td>{{ $transporter->pan_number }}</td>
                    <td>{{ $transporter->is_owner ? 'Owner' : 'Agency' }}</td>
                    <td>{{ $transporter->declaration_given ? 'Yes' : 'No' }}</td>
                    <td>{{ $transporter->total_trucks }}</td>
                    <td>{{ number_format($transporter->advance_amount, 2) }}</td>
                    <td>{{ $transporter->company ? $transporter->company->name : '-' }}</td>
                    <td>
                        <a href="{{ route('transporters.edit', $transporter->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('transporters.destroy', $transporter->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center">No transporters found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
@endsection
