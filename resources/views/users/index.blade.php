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

    <div class="d-flex justify-content-between mb-3">
        <h3 class="mb-0">User Management</h3>
        <a href="{{ route('users.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4 class="mb-0">Users List</h4>
        </div>
        <div class="card-body table-responsive">
            <table id="usersTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Company</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>

                <!-- ✅ Column Filters Added Here -->
                <thead class="filters">
                    <tr>
                        <th></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Name"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Email"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Role"></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Search Company"></th>
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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {

    var table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('users.index') }}",
            type: "GET"
        },

        // ✅ IMPORTANT — No dropdown in mobile
        responsive: false,
        scrollX: true,

        columns: [
            { data: 'id', name: 'id', className: 'text-center' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'role', name: 'role' },
            { data: 'company', name: 'company' },
            { data: 'actions', name: 'actions', className: 'text-center', orderable: false, searchable: false }
        ],

        order: [[0, 'desc']],
        pageLength: 10,
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-5"i><"col-md-7"p>>',

        initComplete: function () {
            // ✅ Apply individual search
            this.api().columns().every(function(index) {
                let input = $('.filters input').eq(index);
                if (input.length) {
                    input.on('keyup change', () => {
                        this.search(input.val()).draw();
                    });
                }
            });
        }
    });

    // ✅ Delete confirmation
    $(document).on('submit', 'form[action*="destroy"]', function(e) {
        if (!confirm('Are you sure you want to delete this user?')) {
            e.preventDefault();
        }
    });

});
</script>
@endpush
