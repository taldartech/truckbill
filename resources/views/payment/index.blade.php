@extends('layouts.app')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
    <div class="container mt-4">
        <div class="col-sm-6">
            <h3 class="mb-0">Payment Management</h3>
        </div>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Companies List</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="companiesTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 80px;">SR No.</th>
                                <th>Company Name</th>
                                <th>Plan End Date</th>
                                <th>LR Credits</th>
                                <th>Truck Credits</th>
                                <th>Status</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($companies as $company)
                                <tr>
                                    @php
                                        $isExpired = false;
                                        if ($company->plan_end_date) {
                                            $isExpired = \Carbon\Carbon::parse($company->plan_end_date)->isBefore(
                                                today(),
                                            );
                                        }
                                    @endphp
                                    <th>{{ $loop->iteration }}</th>
                                    <td>{{ $company->name }}</td>
                                    <td data-order="{{ $company->plan_end_date }}">
                                        {{ \Carbon\Carbon::parse($company->plan_end_date)->format('d-m-Y') }}</td>
                                    <td class="text-center">{{ $company->lr_credits ?? 0 }}</td>
                                    <td class="text-center">{{ $company->truck_credits ?? 0 }}</td>
                                    <td class="text-center">
                                        @if ($isExpired)
                                            <span class="badge bg-danger">Expired</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('payment.create', ['company_id' => $company->id]) }}"
                                            class="btn btn-primary btn-sm">Make Payment</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#companiesTable').DataTable({
                responsive: true,
                paging: false,

                "order": [
                    [0, "asc"]
                ]
            });
        });
    </script>
@endpush
