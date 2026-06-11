@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Transporter Masters</h3></div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('master.transporter-masters.create') }}" class="btn btn-brand-primary">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Global Transporter Master Records</h3>
        </div>
        <div class="card-body">
            <table id="transporterMastersTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>PAN</th>
                        <th>GST</th>
                        <th>PAN Active</th>
                        <th>Location</th>
                        <th>Linked</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
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
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '110px', className: 'text-center' }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12 col-md-12"B>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
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
