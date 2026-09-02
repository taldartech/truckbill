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

    <div class="card app-index-list-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Goods Type Management</h3>
            <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('master.goods-types.create') }}" class="btn app-index-header-btn" aria-label="Create goods type">
                    <i class="fas fa-plus"></i> Create
                </a>

    <button type="button"
        class="btn app-index-header-btn"
        data-bs-toggle="modal"
        data-bs-target="#goodsTypeBulkUploadModal">
    <i class="fas fa-upload"></i> Bulk Upload
</button>

</div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="goodsTypesTable" class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            @if(auth()->user()->canActAsAdmin())
                            <th class="app-index-actions-cell" style="width: 120px;">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data will be loaded via AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

<!-- Good Type Delete Modal -->
<div class="modal fade" id="goodTypeDeleteModal" tabindex="-1"
    aria-labelledby="goodTypeDeleteModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="goodTypeDeleteModalLabel">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Confirm Delete
                </h5>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>

            <div class="modal-body">

                <p>Are you sure you want to delete this goods type?</p>

                <p>
                    <strong>Name:</strong>
                    <span id="deleteGoodTypeName"></span>
                </p>

                <div class="mb-3 mt-3">
                    <label for="deleteGoodTypeRemark" class="form-label">
                        <strong>
                            Remark <span class="text-danger">*</span>
                        </strong>
                    </label>

                    <textarea
                        id="deleteGoodTypeRemark"
                        class="form-control"
                        rows="4"
                        placeholder="Enter reason for deletion"></textarea>
                </div>

                <div id="deleteGoodTypeRemarkError"
                    class="text-danger small d-none">
                    Delete remark is required.
                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    <i class="bi bi-x"></i>
                    Cancel
                </button>

                <button type="button"
                    class="btn btn-danger"
                    id="confirmGoodTypeDelete">
                    <i class="bi bi-trash"></i>
                    Confirm Delete
                </button>

            </div>

        </div>
    </div>
</div>


@include('shared.bulk-upload-modal', [
    'modalId' => 'goodsTypeBulkUploadModal',
    'title' => 'Bulk Upload Goods Types',
    'templateUrl' => route('master.goods-types.bulk-upload.template'),
    'uploadUrl' => route('master.goods-types.bulk-upload'),
    'recordName' => 'goods types',
       'columns' => [
        ['name' => 'Goods Type Name', 'format' => 'Text', 'required' => true],
    ],
])

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#goodsTypesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("master.goods-types.index") }}',
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
            @if(auth()->user()->canActAsAdmin())
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                width: '120px',
                className: 'app-index-actions-cell'
            }
            @endif
        ],
        order: [[0, 'asc']],
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
        searchDelay: 500
    });

   // Open Good Type Delete Modal
$(document).on('click', '.good-type-delete-btn', function() {

    const button = $(this);
    const form = button.closest('.good-type-delete-form');

    const goodTypeName = button.data('good-type-name') || '-';

    $('#deleteGoodTypeName').text(goodTypeName);

    $('#deleteGoodTypeRemark').val('');
    $('#deleteGoodTypeRemarkError').addClass('d-none');

    $('#confirmGoodTypeDelete').data('form', form);
});


// Confirm Good Type Delete
$(document).on('click', '#confirmGoodTypeDelete', function() {

    const remark = $('#deleteGoodTypeRemark').val().trim();
    const form = $('#confirmGoodTypeDelete').data('form');

    if (!remark) {

        $('#deleteGoodTypeRemarkError')
            .removeClass('d-none')
            .text('Please enter a remark before deleting.');

        return;
    }

    $('#deleteGoodTypeRemarkError').addClass('d-none');

    form.find('input[name="delete_remark"]').remove();

    $('<input>', {
        type: 'hidden',
        name: 'delete_remark',
        value: remark
    }).appendTo(form);

    form[0].submit();
});
});
</script>
@endpush
