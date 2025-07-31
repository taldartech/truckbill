@extends('layouts.app')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
@endpush

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Company Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('company.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Companies List</h3>
        </div>
        <div class="card-body">
            <table id="companiesTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Name</th>
                        <th>GST Number</th>
                        <th>PAN Number</th>
                        <th>GST Scheme</th>
                        <th>GST Type</th>
                        <th>Address</th>
                        <th>Contact Person</th>
                        <th>Contact Number</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
            </table>
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
    $('#companiesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("company.index") }}',
            type: 'GET'
        },
        columns: [
            {
                data: 'id',
                name: 'id',
                width: '80px',
                className: 'text-center'
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'gst_number',
                name: 'gst_number',
                width: '150px'
            },
            {
                data: 'pan_number',
                name: 'pan_number',
                width: '120px'
            },
            {
                data: 'gst_scheme',
                name: 'gst_scheme',
                width: '120px'
            },
            {
                data: 'gst_type',
                name: 'gst_type',
                width: '100px'
            },
            {
                data: 'address',
                name: 'address'
            },
            {
                data: 'contact_person',
                name: 'contact_person',
                width: '150px'
            },
            {
                data: 'contact_number',
                name: 'contact_number',
                width: '130px'
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                width: '120px',
                className: 'text-center'
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12 col-md-2"B><"col-sm-12 col-md-10"p>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'excel',
                className: 'btn btn-secondary btn-sm',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel'
            }
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            zeroRecords: "No matching records found",
            emptyTable: "No data available in table",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        responsive: true,
        autoWidth: false,
        stateSave: true,
        searchDelay: 500,
        scrollX: true,
        initComplete: function() {
            // Add individual column search
            this.api().columns([1, 2, 3, 4, 5, 6, 7, 8]).every(function() {
                var column = this;
                var title = column.header().textContent;
                
                // Create input element
                var input = $('<input type="text" class="form-control form-control-sm" placeholder="Search ' + title + '" />')
                    .appendTo($(column.header()))
                    .on('keyup change clear', function() {
                        if (column.search() !== this.value) {
                            column.search(this.value).draw();
                        }
                    });
            });
        }
    });
    
    // Handle delete confirmation
    $(document).on('submit', 'form[action*="destroy"]', function(e) {
        if (!confirm('Are you sure you want to delete this company?')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush
