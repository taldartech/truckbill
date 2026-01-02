@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="col-sm-6">
            <h3 class="mb-0">Voucher Template</h3>
        </div>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Voucher Templates List</h3>
            </div>
            <div class="card-body">
                <table id="voucherTemplatesTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            <tr>
                                <td>{{ $company->id }}</td>
                                <td>{{ $company->name }}</td>
                                <td>{{ $company->template_name ?? 'N/A' }}</td>
                                <td>{{ Str::limit($company->template_address ?? 'N/A', 50) }}</td>
                                <td>
                                    <a href="{{ route('voucher_templates.edit', $company) }}"
                                        class="btn btn-outline-primary btn-sm" title="Edit Template">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <i class="bi bi-inbox"></i> No companies found. Please create a company first.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
