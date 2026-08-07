@extends('layouts.app')

@section('content')
<div class="container mt-4 app-index-page app-index-page--standard">
    @if (session('success'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
            </span>
        </div>
    @endif

    <div class="card app-index-list-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Company Settings</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Company</th>
                            <th>Short Name</th>
                            <th>PAN Number</th>
                            <th class="app-index-actions-cell text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $company->name }}</div>
                                </td>
                                <td>
                                    <div class="text-muted">{{ $company->short_name }}</div>
                                </td>
                                <td>
                                    <div class="text-nowrap">{{ $company->pan_number }}</div>
                                </td>
                                <td class="app-index-actions-cell text-end">
                                    <a href="{{ route('company_settings.edit', $company) }}" class="btn btn-sm btn-edit">
                                        <i class="bi bi-pencil-square"></i>
                                        <span class="d-none d-sm-inline">Edit</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr class="app-index-empty-row">
                                <td colspan="4" class="text-center text-muted py-4">No companies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
