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
                            <th>Name</th>
                            <th>Company Name</th>
                            <th>Address</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($company as $company)
                            <tr>
                                <td>{{ $company->id }}</td>
                                <td>{{ $company->name }}</td>
                                <td>{{ $company->template_name }}</td>
                                <td>{{ $company->template_address }}</td>
                                <td>
                                    <a href="{{ route('voucher_templates.edit', $company->id) }}"
                                        class="btn btn-outline-primary btn-s"><i class="fa fa-edit"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
