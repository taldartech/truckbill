@extends('layouts.app')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
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

    <div class="card app-index-list-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Branch Management</h3>
            <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('branches.create') }}" class="btn app-index-header-btn" aria-label="Add branch">
                    <i class="fas fa-plus"></i> Add Branch
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="branchesTable" class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">SR No.</th>
                            <th>Branch Name</th>
                            <th>Company</th>
                            <th>Contact Number</th>
                            <th>Address</th>
                            <th class="app-index-actions-cell" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="branchDeleteModal" tabindex="-1" aria-labelledby="branchDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="branchDeleteModalLabel">
                    Delete Branch
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                </button>
            </div>

            <form id="branchDeleteForm" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-body">

                    <p class="mb-3">
                        Are you sure you want to delete this branch?
                    </p>

                    <div class="mb-2">
                        <strong>Branch Name:</strong>
                        <span id="deleteBranchName">-</span>
                    </div>

                    <div class="mb-2">
                        <strong>Company:</strong>
                        <span id="deleteBranchCompany">-</span>
                    </div>

                   

                    <div class="mb-3">
                        <strong>Head Office:</strong>
                        <span id="deleteBranchHeadOffice">-</span>
                    </div>

                    <div class="mb-3">
                        <label for="deleteBranchRemark" class="form-label">
                            Delete Remark <span class="text-danger">*</span>
                        </label>

                        <textarea
                            class="form-control"
                            id="deleteBranchRemark"
                            name="delete_remark"
                            rows="3"
                            required
                            maxlength="1000"
                            placeholder="Enter reason for deleting this branch"></textarea>

                        <div id="deleteBranchRemarkError"
                             class="text-danger mt-1"
                             style="display:none;">
                            Delete remark is required.
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-danger"
                            id="confirmBranchDelete">
                        Delete
                    </button>
                </div>

            </form>
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
    $('#branchesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("branches.index") }}',
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
                render: function(data, type, row) {
                    return (row.company && row.company.name) ? row.company.name : 'N/A';
                },
                width: '150px'
            },
            {
                data: 'branch_contact_number',
                name: 'branch_contact_number',
                defaultContent: 'N/A',
                width: '150px'
            },
            {
                data: 'branch_address',
                name: 'branch_address',
                defaultContent: 'N/A'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                width: '120px',
                className: 'app-index-actions-cell'
            }
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
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ branches",
            infoEmpty: "Showing 0 to 0 of 0 branches",
            infoFiltered: "(filtered from _MAX_ total)",
            zeroRecords: "No branches found.",
            emptyTable: "No branches found.",
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
        scrollX: true
    });

    // Handle delete confirmation
    $(document).on('click', '.branch-delete-btn', function () {

    const button = $(this);

    const branchId = button.data('branch-id');
    const branchName = button.data('branch-name');
    const company = button.data('branch-company');
    const headOffice = button.data('branch-head-office');

    $('#deleteBranchName').text(branchName || '-');
    $('#deleteBranchCompany').text(company || '-');
    $('#deleteBranchHeadOffice').text(headOffice || '-');

    $('#deleteBranchRemark').val('');
    $('#deleteBranchRemarkError').hide();

    $('#branchDeleteForm').attr(
        'action',
        '/branches/' + branchId
    );
});
$('#branchDeleteForm').on('submit', function (e) {

    const remark = $('#deleteBranchRemark').val().trim();

    if (!remark) {
        e.preventDefault();

        $('#deleteBranchRemarkError').show();
        $('#deleteBranchRemark').focus();

        return false;
    }

    $('#deleteBranchRemarkError').hide();
});
});
</script>
@endpush
