@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
@endpush

@section('content')
<div class="container px-0 mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between gap-2 mb-3">
        <div class="col-sm-6"><h3 class="mb-0">Branch Management</h3></div>
        <a href="{{ route('branches.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Add Branch
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Branches List</h3>
        </div>

        <div class="card-body">
            <table id="branchesTable" class="table table-bordered table-striped" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 80px;">SR No.</th>
                        <th>Branch Name</th>
                        <th>Company</th>
                        <th>Contact Number</th>
                        <th>Address</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>

                    <!-- ✅ Search Row -->
                    <tr>
                        <th></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Branch"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Company"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Contact"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Address"></th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {

    var table = $('#branchesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("branches.index") }}',
        orderCellsTop: true,
        fixedHeader: true,

        columns: [
            {
                data: null, render: function (data, type, row, meta) {
                    return meta.row + 1; // This will show the index + 1 as the SR number
                },
                width: '80px',
                className: 'text-center'
            },
            {
                data: 'branch_name',
                name: 'branch_name'
            },
            {
                data: 'company.name',
                name: 'company.name',
                render: function(data) { return data ?? 'N/A'; }
            },
            { data: 'branch_contact_number', name: 'branch_contact_number', defaultContent: 'N/A' },
            { data: 'branch_address', name: 'branch_address', defaultContent: 'N/A' },
            { data: 'action', name: 'action', orderable: false, searchable: false, width: '120px', className: 'text-center' }
        ],

        order: [[0, 'desc']],
        pageLength: 10,
        scrollX: true,
    });

    // ✅ Column-wise Search Functional
    table.columns().every(function(index) {
        $('thead tr:eq(1) th:eq(' + index + ') input').on('keyup change', function() {
            table.column(index).search(this.value).draw();
        });
    });

});
</script>
@endpush
