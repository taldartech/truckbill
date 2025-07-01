@extends('layouts.app')

@section('content')
<div class="container mt-4">
                <h2>Transporter Management</h2>
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form method="GET" action="{{ route('company.index') }}" class="row g-3 mb-0">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="Company Name" value="{{ request('name') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="gst_number" class="form-control" placeholder="GST Number" value="{{ request('gst_number') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="pan_number" class="form-control" placeholder="PAN Number" value="{{ request('pan_number') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-start">
                        <button type="submit" class="btn btn-secondary me-2">Search</button>
                        <a href="{{ route('company.index') }}" class="btn btn-outline-secondary me-2">Reset</a>
                        <a href="{{ route('company.create') }}" class="btn btn-primary">Add New Company</a>
                    </div>
                </form>
    </form>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
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
                <tr>
                  <td>{{ $company->id }}</td>
                  <td>{{ $company->name }}</td>
                  <td>{{ $company->gst_number }}</td>
                  <td>{{ $company->pan_number }}</td>
                  <td>{{ $company->gst_scheme }}</td>
                  <td>{{ $company->gst_type }}</td>
                  <td>{{ $company->address }}</td>
                  <td>{{ $company->contact_person }}</td>
                  <td>{{ $company->contact_number }}</td>
                  <td>
                    <a href="{{ route('company.edit', $company->id) }}" class="btn btn-sm btn-warning me-1"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('company.destroy', $company->id) }}" method="POST" style="display:inline-block;">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash-alt"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="10" class="text-center">No companies found.</td></tr>
              @endforelse
            </tbody>
          </table>
    </div>

    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

@endsection
