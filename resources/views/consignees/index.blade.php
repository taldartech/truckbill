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
            <h3 class="card-title mb-0">Consignees / Consignors / Customers</h3>
            <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">

                <a href="{{ route('consignees.create') }}" class="btn app-index-header-btn" aria-label="Create consignee">
                    <i class="fas fa-plus"></i> Create
                </a>
                <button type="button"
               class="btn app-index-header-btn"
               data-bs-toggle="modal"
               data-bs-target="#consigneeBulkUploadModal">
               <i class="fas fa-upload"></i> Bulk Upload
               </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="consigneesTable" class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Sr. No</th>
                            <th>Name</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Country</th>
                            <th>Contact Number</th>
                            @if(auth()->check() && auth()->user()->canActAsAdmin())
                            <th class="app-index-actions-cell" style="width: 120px;">Actions</th>
                            @endif
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Consignee Details Modal -->
<div class="modal fade fb-pay-modal" id="consigneeDetailsModal" tabindex="-1" aria-labelledby="consigneeDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="consigneeDetailsModalLabel">
                    <i class="bi bi-person-lines-fill"></i> Consignee Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="consigneeDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Close
                </button>
                @if (Auth::user()->canActAsAdmin())
                  <a href="#" id="editConsigneeBtn" class="btn btn-primary" style="display: none;">
                      <i class="bi bi-pencil-square"></i> Edit Consignee
                  </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Consignee Delete Modal -->
<div class="modal fade" id="consigneeDeleteModal" tabindex="-1"
    aria-labelledby="consigneeDeleteModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="consigneeDeleteModalLabel">
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

                <p>Are you sure you want to delete this consignee?</p>

                <p>
                    <strong>Name:</strong>
                    <span id="deleteConsigneeName">-</span>
                </p>

                <p>
                    <strong>Contact Number:</strong>
                    <span id="deleteConsigneeContact">-</span>
                </p>

                <p>
                    <strong>City:</strong>
                    <span id="deleteConsigneeCity">-</span>
                </p>

                <p>
                    <strong>State:</strong>
                    <span id="deleteConsigneeState">-</span>
                </p>

                <p>
                    <strong>Country:</strong>
                    <span id="deleteConsigneeCountry">-</span>
                </p>

                <p>
                    <strong>Address:</strong>
                    <span id="deleteConsigneeAddress">-</span>
                </p>

                <div class="mb-3 mt-3">

                    <label for="deleteConsigneeRemark" class="form-label">
                        <strong>
                            Remark <span class="text-danger">*</span>
                        </strong>
                    </label>

                    <textarea
                        id="deleteConsigneeRemark"
                        class="form-control"
                        rows="4"
                        placeholder="Enter reason for deletion"></textarea>

                </div>

                <div id="deleteConsigneeRemarkError"
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
                    id="confirmConsigneeDelete">
                    <i class="bi bi-trash"></i>
                    Confirm Delete
                </button>

            </div>
        </div>
    </div>
</div>


@include('shared.bulk-upload-modal', [
    'modalId' => 'consigneeBulkUploadModal',
    'title' => 'Bulk Upload Consignees',
    'uploadUrl' => route('consignees.bulkUpload'),
    'templateUrl' => route('consignees.bulkUploadTemplate'),
    'recordName' => 'consignees',

    'columns' => [
        ['name' => 'Consignee Name', 'format' => 'Text', 'required' => true],
        ['name' => 'Contact Number', 'format' => '10-digit number', 'required' => true],
        ['name' => 'Country', 'format' => 'Country name', 'required' => true],
        ['name' => 'State', 'format' => 'State name', 'required' => true],
        ['name' => 'City', 'format' => 'City name', 'required' => true],
        ['name' => 'Email', 'format' => 'Valid email address', 'required' => false],
        ['name' => 'GST Number', 'format' => 'GST number', 'required' => false],
        ['name' => 'PAN Number', 'format' => 'PAN number', 'required' => false],
        ['name' => 'Address', 'format' => 'Text', 'required' => false],
    ],
])
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
    $('#consigneesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("consignees.index") }}',
            type: 'GET'
        },
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                width: '80px',
                className: 'text-center',
                orderable: false,
                searchable: false
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'city',
                name: 'city',
                width: '120px'
            },
            {
                data: 'state',
                name: 'state',
                width: '120px'
            },
            {
                data: 'country',
                name: 'country',
                width: '120px'
            },
            {
                data: 'contact_number',
                name: 'contact_number',
                width: '130px'
            },
            @if(auth()->check() && auth()->user()->canActAsAdmin())
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
            info: "Showing _START_ to _END_ of _TOTAL_ consignees",
            infoEmpty: "Showing 0 to 0 of 0 consignees",
            infoFiltered: "(filtered from _MAX_ total)",
            zeroRecords: "No consignees found.",
            emptyTable: "No consignees found.",
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

    // Handle consignee name click to show details
    $(document).on('click', '.consignee-name-link', function(e) {
        e.preventDefault();
        var consigneeId = $(this).data('id');

        // Show modal with loading state
        $('#consigneeDetailsModal').modal('show');
        $('#consigneeDetailsContent').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading consignee details...</p>
            </div>
        `);
        $('#editConsigneeBtn').hide();

        // Fetch consignee details
        $.ajax({
            url: '/api/consignees/' + consigneeId,
            type: 'GET',
            success: function(data) {
                var detailsHtml = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-person-circle"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-bold">Name:</td>
                                            <td>${data.name}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Contact Number:</td>
                                            <td>${data.contact_number}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-file-text"></i> Business Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-bold">GST Number:</td>
                                            <td>${data.gst_number}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">PAN Number:</td>
                                            <td>${data.pan_number}</td>
                                        </tr>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-geo-alt"></i> Location Details</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-bold">Country:</td>
                                            <td>${data.country}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">State:</td>
                                            <td>${data.state}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">City:</td>
                                            <td>${data.city}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Address:</td>
                                            <td>${data.address}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Additional Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-bold">Created:</td>
                                            <td>${data.created_at}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Last Updated:</td>
                                            <td>${data.updated_at}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#consigneeDetailsContent').html(detailsHtml);
                $('#editConsigneeBtn').show().attr('href', '/consignees/' + consigneeId + '/edit');
            },
            error: function(xhr, status, error) {
                $('#consigneeDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Error!</strong> Failed to load consignee details. Please try again.
                    </div>
                `);
                $('#editConsigneeBtn').hide();
            }
        });
    });
    // Handle Consignee Delete Button
$(document).on('click', '.consignee-delete-btn', function() {

    const button = $(this);
    const form = button.closest('.consignee-delete-form');

    const consigneeName = button.data('consignee-name') || '-';
    const contactNumber = button.data('consignee-contact') || '-';
    const city = button.data('consignee-city') || '-';
    const state = button.data('consignee-state') || '-';
    const country = button.data('consignee-country') || '-';
    const address = button.data('consignee-address') || '-';

    $('#deleteConsigneeName').text(consigneeName);
    $('#deleteConsigneeContact').text(contactNumber);
    $('#deleteConsigneeCity').text(city);
    $('#deleteConsigneeState').text(state);
    $('#deleteConsigneeCountry').text(country);
    $('#deleteConsigneeAddress').text(address);

    $('#deleteConsigneeRemark').val('');
    $('#deleteConsigneeRemarkError').addClass('d-none');

    $('#confirmConsigneeDelete').data('form', form);
});


// Confirm Consignee Delete
$(document).on('click', '#confirmConsigneeDelete', function() {

    const remark = $('#deleteConsigneeRemark').val().trim();
    const form = $('#confirmConsigneeDelete').data('form');

    if (!remark) {

        $('#deleteConsigneeRemarkError')
            .removeClass('d-none')
            .text('Please enter a remark before deleting.');

        return;
    }

    $('#deleteConsigneeRemarkError').addClass('d-none');

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
