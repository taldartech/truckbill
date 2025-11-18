@extends('layouts.app')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col-sm-6">
                <h3 class="mb-0">
                    Payment History
                    @if ($company)
                        - {{ $company->name }}
                    @endif
                </h3>
            </div>
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

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payment Transactions</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="paymentsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>SR No.</th>
                                <th>Payment Date</th>
                                <th>Amount</th>
                                @if (!$company)
                                    <th>Company</th>
                                @endif
                                <th>Payment Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
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
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/dataTables.searchPanes.min.js"></script>
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/searchPanes.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

    <script>
        $(document).ready(function() {
            const companyId = '{{ $company ? $company->id : '' }}';
            $('#paymentsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('payment.history') }}',
                    type: 'GET',
                    data: {
                        company_id: companyId
                    }
                },
                columns: [{
                        data: 'sr_no',
                        name: 'sr_no',
                        className: 'text-center',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount',
                        className: 'text-end'
                    },
                    @if (!$company)
                        {
                            data: 'company_name',
                            name: 'company_name',
                            orderable: false,
                            searchable: false
                        },
                    @endif {
                        data: 'payment_type',
                        name: 'payment_type',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                lengthMenu: [10, 25, 50, 100],
                pageLength: 10,
                searching: false,
                responsive: true,
                autoWidth: false,
            });
        });
    </script>
@endpush
