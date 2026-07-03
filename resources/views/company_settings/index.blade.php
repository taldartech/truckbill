@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h5 mb-0 fw-semibold">Company settings</h1>
            <p class="text-muted small mb-0">Voucher headers and PDF layouts per company.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Company</th>
                        <th>Short Name</th>
                        <th>PAN Number</th>
                        <th class="text-end">Action</th>
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
                            <td class="text-end">
                                <a href="{{ route('company_settings.edit', $company) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i>
                                    <span class="d-none d-sm-inline">Edit</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No companies found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
