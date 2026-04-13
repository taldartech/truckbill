@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="col-sm-6">
            <h3 class="mb-0">Company Bank Details</h3>
        </div>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Company Bank Details List</h3>
            </div>
            <div class="card-body">
                <table id="companyBankDetailsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Bank</th>
                            <th>Account Number</th>
                            <th>IFSC</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>{{ $company->id }}</td>
                                <td>{{ $company->name }}</td>
                                <td>{{ $company->bank ?? '-' }}</td>
                                <td>{{ $company->account_number ?? '-' }}</td>
                                <td>{{ $company->ifsc ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('company_bank_details.edit', $company->id) }}"
                                        class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
