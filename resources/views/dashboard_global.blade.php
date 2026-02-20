@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
    <div class="content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="mb-1">Super Admin Dashboard</h3>
                    <p class="text-muted mb-0">Select a company to enter its admin panel, or manage companies and users from here.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Companies</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">
                                <a href="{{ route('company.index') }}" class="btn btn-primary">
                                    <i class="bi bi-building"></i> Company List
                                </a>
                                <span class="ms-2 text-muted">Go to company listing to enter a company panel or manage companies.</span>
                            </p>
                            @if ($companies->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>GST Number</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($companies as $company)
                                                <tr>
                                                    <td>{{ $company->id }}</td>
                                                    <td>{{ $company->name }}</td>
                                                    <td>{{ $company->gst_number ?? '-' }}</td>
                                                    <td>
                                                        <a href="{{ route('company.enter', $company) }}" class="btn btn-sm btn-success">Enter</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">No companies registered yet. <a href="{{ route('company.create') }}">Register a company</a>.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
