@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<div class="container px-0 mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between gap-2 mb-3">
        <h3 class="mb-0">Company Management</h3>
        <a href="{{ route('company.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4 class="mb-0">Companies List</h4>
        </div>
        <div class="card-body">

            <table id="companiesTable" class="table table-striped table-bordered w-100 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>GST Number</th>
                        <th>PAN Number</th>
                        <th>GST Scheme</th>
                        <th>GST Type</th>
                        <th>Address</th>
                        <th>Contact Person</th>
                        <th>Contact Number</th>
                        <th>Actions</th>
                    </tr>
                    <tr class="filters">
                        <th></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Name"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search GST"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search PAN"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Scheme"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Type"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Address"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Person"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Number"></th>
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
<script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    let table = $('#companiesTable').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader: true,
        scrollX: true,
        ajax: {
            url: '{{ route("company.index") }}',
            type: 'GET'
        },
        columns: [
            { data: 'id', name: 'id', className: 'text-center' },
            { data: 'name', name: 'name' },
            { data: 'gst_number', name: 'gst_number' },
            { data: 'pan_number', name: 'pan_number' },
            { data: 'gst_scheme', name: 'gst_scheme' },
            { data: 'gst_type', name: 'gst_type' },
            { data: 'address', name: 'address' },
            { data: 'contact_person', name: 'contact_person' },
            { data: 'contact_number', name: 'contact_number' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'desc']],
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-5"i><"col-md-7"p>>'
    });

    // ✅ Column Filters
    table.columns().every(function(index) {
        $('thead tr.filters th:eq(' + index + ') input').on('keyup change', () => {
            this.search($('thead tr.filters th:eq(' + index + ') input').val()).draw();
        });
    });
});
</script>
@endpush