@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<div class="container mt-4 app-index-page app-index-page--standard">
    @if(session('success'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
            </span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-exclamation-triangle-fill text-danger"></i> {{ session('error') }}
            </span>
        </div>
    @endif

    <div class="card app-index-list-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Transporter Masters</h3>
            <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('master.transporter-masters.create') }}" class="btn app-index-header-btn" aria-label="Create transporter master">
                    <i class="fas fa-plus"></i> Create
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="transporterMastersTable" class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>PAN</th>
                            <th>GST</th>
                            <th>PAN Active</th>
                            <th>Location</th>
                            <th>Linked</th>
                            <th class="app-index-actions-cell" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    $('#transporterMastersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("master.transporter-masters.index") }}',
            type: 'GET'
        },
        columns: [
            { data: 'id', name: 'id', width: '70px', className: 'text-center' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'pan_number', name: 'pan_number', width: '120px' },
            { data: 'gst_number', name: 'gst_number', width: '150px' },
            { data: 'pan_active', name: 'pan_active', width: '100px', className: 'text-center' },
            { data: 'location', name: 'location' },
            { data: 'transporters_count', name: 'transporters_count', width: '90px', className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '110px', className: 'app-index-actions-cell' }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: '<"app-index-dt-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2"<"d-flex flex-wrap align-items-center gap-2"l><"d-flex flex-wrap align-items-center gap-2"fB>>' +
            't' +
            '<"app-index-partload-pagination d-flex flex-wrap align-items-center justify-content-between gap-2"<"app-index-partload-pagination__summary"i><"app-index-partload-pagination__nav"p>>',
        buttons: [
            {
                extend: 'excel',
                className: 'btn btn-secondary btn-sm',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel'
            }
        ],
        responsive: true,
        autoWidth: false,
        stateSave: true,
        searchDelay: 500
    });
});
</script>
@endpush
