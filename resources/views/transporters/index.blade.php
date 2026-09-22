@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')

  @if (session('bulk_upload_errors') && count(session('bulk_upload_errors')) > 0)
        <div class="container mt-3">
            <div class="alert alert-danger">
                <strong>Bulk Upload Errors:</strong>

                <ul class="mb-0 mt-2">
                    @foreach (session('bulk_upload_errors') as $error)
                        <li>
                            <strong>Row {{ $error['row'] }}:</strong>
                            {{ $error['error'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif


    <div class="container mt-4 app-index-page app-index-page--standard">
        @if (session('success'))
            <div class="mb-3">
                <span class="badge text-bg-dark border app-index-flash">
                    <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
                </span>
            </div>
        @endif

        <div class="card app-index-list-card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">Transporter Management</h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('transporters.create') }}" class="btn app-index-header-btn" aria-label="Create transporter">
                        <i class="fas fa-plus"></i> Create
                    </a>
                    <a href="{{ route('reports.transporter-master-export', ['type' => 'transporter']) }}" class="btn app-index-header-btn" aria-label="Export transporters">
                        <i class="fas fa-file-excel"></i> Export
                    </a>
              

               <button type="button"
                class="btn app-index-header-btn"
                data-bs-toggle="modal"
                data-bs-target="#transporterBulkUploadModal">
               <i class="fas fa-upload"></i> Bulk Upload
                </button>
            </div>
        </div> 

            <div class="card-body">
                <div class="table-responsive">
                    <table id="transportersTable" class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Short Name</th>
                                <th>Beneficiary ID</th>
                                <th>PAN Number</th>
                                <th>Contact Person</th>
                                <th>Contact Number</th>
                                <th class="text-end">Book Balance</th>
                                @if (auth()->check() && auth()->user()->canActAsAdmin())
                                    <th class="app-index-actions-cell" style="width: 120px;">Actions</th>
                                @endif
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Transporter Details Modal -->
    <div class="modal fade fb-pay-modal" id="transporterDetailsModal" tabindex="-1" aria-labelledby="transporterDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-brand-primary text-white">
                    <h5 class="modal-title" id="transporterDetailsModalLabel">
                        <i class="bi bi-truck"></i> Transporter Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" id="transporterDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading transporter details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    @if (Auth::user()->canActAsAdmin())
                        <button type="button" class="btn btn-edit" id="editTransporterBtn" style="display: none;">
                            <i class="bi bi-pencil"></i> Edit Transporter
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Advance Payment Modal -->
    <div class="modal fade fb-pay-modal" id="advancePaymentModal" tabindex="-1" aria-labelledby="advancePaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-brand-primary text-white">
                    <h5 class="modal-title" id="advancePaymentModalLabel">
                        <i class="bi bi-currency-rupee"></i> Add Advance Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="advancePaymentForm" method="POST" action="{{ route('advance-payments.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="transporter_id" name="transporter_id">

                        <div class="mb-3">
                            <label for="transporter_name_display" class="form-label">Transporter Name</label>
                            <input type="text" class="form-control" id="transporter_name_display" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="advance_amount" class="form-label">Book Balance *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" id="advance_amount"
                                    name="advance_amount" required placeholder="Use + to add, - to deduct">
                            </div>
                            <small class="text-muted">Enter positive amount to add balance, negative amount to deduct balance.</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date *</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="remark" class="form-label">Remark</label>
                            <textarea class="form-control" id="remark" name="remark" rows="3"
                                placeholder="Enter any remarks or notes (optional)"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-brand-primary">
                            <i class="bi bi-check-lg"></i> Add Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="transporterDeleteModal" tabindex="-1"
    aria-labelledby="transporterDeleteModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="transporterDeleteModalLabel">
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

                <p>Are you sure you want to delete this transporter?</p>

                <p>
                    <strong>Transporter Name:</strong>
                    <span id="deleteTransporterName"></span>
                </p>

                <p>
                    <strong>PAN Number:</strong>
                    <span id="deleteTransporterPan"></span>
                </p>

                <div class="mb-3 mt-3">
                    <label for="deleteTransporterRemark" class="form-label">
                        <strong>
                            Remark <span class="text-danger">*</span>
                        </strong>
                    </label>

                    <textarea
                        id="deleteTransporterRemark"
                        class="form-control"
                        rows="4"
                        placeholder="Enter reason for deletion"></textarea>
                </div>

                <div id="deleteTransporterRemarkError"
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
                    id="confirmTransporterDelete">
                    <i class="bi bi-trash"></i>
                    Confirm Delete
                </button>

            </div>

        </div>
    </div>
</div>
@endsection

@include('shared.bulk-upload-modal', [
    'modalId' => 'transporterBulkUploadModal',
    'title' => 'Bulk Upload Transporters',
    'templateUrl' => route('transporters.bulk-upload-template'),
    'uploadUrl' => route('transporters.bulk-upload'),
    'recordName' => 'transporters',
    'columns' => [
        ['name' => 'PAN Number', 'format' => 'PAN number', 'required' => true],
        ['name' => 'Transport Name', 'format' => 'Name', 'required' => true],
        ['name' => 'Mobile Number', 'format' => '10-digit number', 'required' => true],
        ['name' => 'PAN Active', 'format' => 'Yes / No', 'required' => true],
        ['name' => 'Country', 'format' => 'Country name', 'required' => true],
        ['name' => 'State', 'format' => 'State name', 'required' => true],
        ['name' => 'City', 'format' => 'City name', 'required' => true],
        ['name' => 'Declaration Given', 'format' => 'Yes / No', 'required' => true],
        ['name' => 'TDS Percentage', 'format' => 'Number', 'required' => true],
        ['name' => 'Email', 'format' => 'Email address', 'required' => false],
        ['name' => 'GST Number', 'format' => 'GST number', 'required' => false],
        ['name' => 'Contact Person Name', 'format' => 'Name', 'required' => false],
        ['name' => 'Address', 'format' => 'Text', 'required' => false],
        ['name' => 'TDS Adjustment Remark', 'format' => 'Text', 'required' => false],
        ['name' => 'Short Name', 'format' => 'Short Name', 'required' => false],
        ['name' => 'Advance Amount', 'format' => 'Amount', 'required' => false],
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
            $('#transportersTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('transporters.index') }}',
                    type: 'GET'
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'short_name',
                        name: 'short_name',
                    },
                    {
                        data: 'beneficiary_id',
                        name: 'beneficiary_id',
                    },
                    {
                        data: 'pan_number',
                        name: 'pan_number',
                    },
                    {
                        data: 'contact_person',
                        name: 'contact_person',
                    },
                    {
                        data: 'contact_number',
                        name: 'contact_number',
                    },
                    {
                        data: 'advance_amount',
                        name: 'advance_amount',
                        className: 'text-end'
                    },
                    @if (auth()->check() && auth()->user()->canActAsAdmin())
                        {
                            data: 'actions',
                            name: 'actions',
                            orderable: false,
                            searchable: false,
                            className: 'app-index-actions-cell'
                        }
                    @endif
                ],
                order: [
                    [0, 'asc']
                ],
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                dom: '<"app-index-dt-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2"<"d-flex flex-wrap align-items-center gap-2"l><"d-flex flex-wrap align-items-center gap-2"fB>>' +
                    't' +
                    '<"app-index-partload-pagination d-flex flex-wrap align-items-center justify-content-between gap-2"<"app-index-partload-pagination__summary"i><"app-index-partload-pagination__nav"p>>',
                buttons: [{
                    extend: 'excel',
                    className: 'btn btn-secondary btn-sm',
                    text: '<i class="bi bi-file-earmark-excel"></i> Excel'
                }],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_",
                    info: "Showing _START_ to _END_ of _TOTAL_ transporters",
                    infoEmpty: "Showing 0 to 0 of 0 transporters",
                    infoFiltered: "(filtered from _MAX_ total)",
                    zeroRecords: "No transporters found.",
                    emptyTable: "No transporters found.",
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
                stateLoadParams: function(settings, data) {
                    data.order = [
                        [0, 'asc']
                    ];
                },
                searchDelay: 500
            });

           // Open Transporter Delete Modal
               $(document).on('click', '.transporter-delete-btn', function() {

               const button = $(this);
               const form = button.closest('.transporter-delete-form');

              const transporterName =
              button.data('transporter-name') || '-';

              const transporterPan =
              button.data('transporter-pan') || '-';

              $('#deleteTransporterName').text(transporterName);
              $('#deleteTransporterPan').text(transporterPan);
 
              $('#deleteTransporterRemark').val('');
              $('#deleteTransporterRemarkError').addClass('d-none');

              $('#confirmTransporterDelete').data('form', form);
           });

           // Open Transporter Delete Modal
         $(document).on('click', '.transporter-delete-btn', function() {

         const button = $(this);
         const form = button.closest('.transporter-delete-form');

         const transporterName = button.attr('data-transporter-name') || '-';
         const transporterPan = button.attr('data-transporter-pan') || '-';

         $('#deleteTransporterName').text(transporterName);
         $('#deleteTransporterPan').text(transporterPan);

         $('#deleteTransporterRemark').val('');
         $('#deleteTransporterRemarkError').addClass('d-none');

         $('#confirmTransporterDelete').data('form', form);
       });


         // Confirm Transporter Delete
          $(document).on('click', '#confirmTransporterDelete', function() {

        const remark = $('#deleteTransporterRemark').val().trim();
        const form = $('#confirmTransporterDelete').data('form');

      if (!remark) {

        $('#deleteTransporterRemarkError')
            .removeClass('d-none')
            .text('Please enter a remark before deleting.');

          return;
       }

      $('#deleteTransporterRemarkError').addClass('d-none');

      form.find('input[name="delete_remark"]').remove();

      $('<input>', {
        type: 'hidden',
        name: 'delete_remark',
        value: remark
       }).appendTo(form);

         form[0].submit();
        });

            $(document).on('click', '.transporter-name-link', function(e) {
                e.preventDefault();
                var transporterId = $(this).data('id');

                $('#transporterDetailsModal').modal('show');
                $('#transporterDetailsContent').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading transporter details...</p>
            </div>
        `);
                $('#editTransporterBtn').hide();

                $.ajax({
                    url: '/transporters/' + transporterId,
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
                                            <td class="fw-bold">Short Name:</td>
                                            <td>${data.short_name || 'Not provided'}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Contact Person:</td>
                                            <td>${data.contact_person_name}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Mobile Number:</td>
                                            <td>${data.mobile_number}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Type:</td>
                                            <td><span class="badge ${data.is_owner === 'Truck Owner' ? 'bg-brand-primary' : 'bg-info'}">${data.is_owner}</span></td>
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
                                        <tr>
                                            <td class="fw-bold">PAN Active:</td>
                                            <td><span class="badge ${data.pan_active === 'Yes' ? 'bg-brand-primary' : 'bg-warning'}">${data.pan_active}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Declaration Given:</td>
                                            <td><span class="badge ${data.declaration_given === 'Yes' ? 'bg-brand-primary' : 'bg-warning'}">${data.declaration_given}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">TDS Percentage:</td>
                                            <td><span class="badge bg-warning text-dark">${data.tds_percentage}%</span></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Book Balance:</td>
                                            <td class="fw-bold text-brand-primary">${data.advance_amount}</td>
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

                        if (data.advance_payments && data.advance_payments.length > 0) {
                            detailsHtml += `
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-currency-rupee"></i> Recent Advance Payments (Last 10)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 15%;">Amount</th>
                                                        <th style="width: 20%;">Payment Date</th>
                                                        <th style="width: 35%;">Remark</th>
                                                        <th style="width: 30%;">Added On</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                    `;

                            data.advance_payments.forEach(function(payment) {
                                const parsedAmount = parseFloat(String(payment.amount).replace(/[^0-9.-]/g, ''));
                                const amountClass = !isNaN(parsedAmount) && parsedAmount < 0 ? 'text-danger' : 'text-brand-primary';
                                detailsHtml += `
                            <tr>
                                <td class="fw-bold ${amountClass}">${payment.amount}</td>
                                <td>${payment.payment_date}</td>
                                <td class="text-muted">${payment.remark}</td>
                                <td class="text-muted small">${payment.created_at}</td>
                            </tr>
                        `;
                            });

                            detailsHtml += `
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                        } else {
                            detailsHtml += `
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-currency-rupee"></i> Recent Advance Payments</h6>
                                    </div>
                                    <div class="card-body text-center text-muted">
                                        <i class="bi bi-inbox display-6"></i>
                                        <p class="mt-2 mb-0">No advance payments found for this transporter.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                        }

                        $('#transporterDetailsContent').html(detailsHtml);
                        $('#editTransporterBtn').show().off('click').on('click', function() {
                            window.location.href = '/transporters/' + data.id + '/edit';
                        });
                    },
                    error: function() {
                        $('#transporterDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Error:</strong> Unable to load transporter details. Please try again.
                    </div>
                `);
                        $('#editTransporterBtn').hide();
                    }
                });
            });

            $(document).on('click', '.advance-payment-btn', function(e) {
                e.preventDefault();
                var transporterId = $(this).data('transporter-id');
                var transporterName = $(this).data('transporter-name');

                $('#advancePaymentForm')[0].reset();
                $('#transporter_id').val(transporterId);
                $('#transporter_name_display').val(transporterName);

                var today = new Date().toISOString().split('T')[0];
                $('#payment_date').val(today);

                $('#advancePaymentForm .form-control').removeClass('is-invalid is-valid');
                $('#advancePaymentForm .invalid-feedback').text('');

                $('#advancePaymentModal').modal('show');
            });

            $('#advancePaymentForm').on('submit', function(e) {
                e.preventDefault();
                let advanceAmount = $('#advance_amount').val();
                if (!confirm('Are you sure you want to submit a book balance entry of ' + advanceAmount +
                        '?')) {
                    return false;
                }

                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var originalBtnText = submitBtn.html();

                submitBtn.prop('disabled', true).html(
                    '<i class="bi bi-hourglass-split"></i> Processing...');

                form.find('.form-control').removeClass('is-invalid is-valid');
                form.find('.invalid-feedback').text('');

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function() {
                        $('#advancePaymentModal').modal('hide');

                        $('body').prepend(`
                    <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <i class="bi bi-check-circle"></i>
                        <strong>Success!</strong> Book balance entry of ${$('#advance_amount').val()} has been saved successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);

                        setTimeout(function() {
                            $('.alert-success').fadeOut();
                        }, 5000);

                        $('#transportersTable').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                var input = $('#' + field);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(messages[0]);
                            });
                        } else {
                            $('body').prepend(`
                        <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Error!</strong> Unable to process advance payment. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);

                            setTimeout(function() {
                                $('.alert-danger').fadeOut();
                            }, 5000);
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalBtnText);
                    }
                });
            });

            $('#advancePaymentModal').on('hidden.bs.modal', function() {
                $('#advancePaymentForm')[0].reset();
                $('#advancePaymentForm .form-control').removeClass('is-invalid is-valid');
                $('#advancePaymentForm .invalid-feedback').text('');
            });

             });

    </script>

     <script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('bulkUploadForm');
    const fileInput = document.getElementById('bulkUploadFile');
    const startUploadBtn = document.getElementById('startUploadBtn');

    const progressContainer =
        document.getElementById('uploadProgressContainer');

    const progressBar =
        document.getElementById('uploadProgressBar');

    const percentage =
        document.getElementById('uploadPercentage');

    const status =
        document.getElementById('uploadStatus');

    const fileName =
        document.getElementById('uploadFileName');

    const message =
        document.getElementById('uploadMessage');

    const cancelButton =
        document.getElementById('cancelUploadBtn');

    const confirmCancelUploadBtn =
        document.getElementById('confirmCancelUploadBtn');

    const cancelUploadModalElement =
        document.getElementById('cancelUploadModal');


    // Stop if required elements are missing
    if (
        !form ||
        !fileInput ||
        !startUploadBtn ||
        !progressContainer ||
        !progressBar ||
        !percentage ||
        !status ||
        !fileName ||
        !message
    ) {
        return;
    }


    let xhr = null;


    /*
    |--------------------------------------------------------------------------
    | FILE SELECT
    |--------------------------------------------------------------------------
    */

    fileInput.addEventListener('change', function () {

        if (!fileInput.files.length) {

            fileName.value = 'No file selected';

            return;
        }


        const file = fileInput.files[0];


        // Only show selected file.
        // Upload does NOT start here.
        fileName.value =
            file.name + ' (' +
            formatFileSize(file.size) +
            ')';


        // Reset previous upload state
        progressContainer.style.display = 'none';

        progressBar.style.width = '0%';

        progressBar.classList.remove('bg-danger');

        progressBar.classList.add('bg-success');

        percentage.textContent = '0%';

        percentage.classList.remove('text-danger');

        percentage.classList.add('text-success');

        status.textContent = '';

        message.textContent = '';

    });


    /*
    |--------------------------------------------------------------------------
    | UPLOAD BUTTON
    |--------------------------------------------------------------------------
    */

    startUploadBtn.addEventListener('click', function () {

        // No file selected
        if (!fileInput.files.length) {

            showUploadError('Please choose a file first.');

            return;
        }


        const file = fileInput.files[0];


        // Check file size
        if (file.size > 10 * 1024 * 1024) {

            showUploadError(
                'File size must not exceed 10 MB.'
            );

            return;
        }


        // Check file extension
        const allowedExtensions = [
            'xlsx',
            'xls',
            'csv'
        ];


        const extension =
            file.name.split('.').pop().toLowerCase();


        if (!allowedExtensions.includes(extension)) {

            showUploadError(
                'Invalid file type. Please select XLSX, XLS or CSV.'
            );

            return;
        }


        const formData = new FormData(form);


        xhr = new XMLHttpRequest();


        /*
        |--------------------------------------------------------------------------
        | SHOW PROGRESS CONTAINER
        |--------------------------------------------------------------------------
        */

        progressContainer.style.display = 'block';


        progressBar.classList.remove('bg-danger');

        progressBar.classList.add('bg-success');

        progressBar.classList.add('progress-bar-striped');

        progressBar.classList.add('progress-bar-animated');


        progressBar.style.width = '0%';

        percentage.textContent = '0%';

        percentage.classList.remove('text-danger');

        percentage.classList.add('text-success');


        status.textContent =
            'Uploading file...';


        message.textContent =
            "Please don't close or refresh this page.";


        // Disable upload button while uploading
        startUploadBtn.disabled = true;


        /*
        |--------------------------------------------------------------------------
        | UPLOAD PROGRESS
        |--------------------------------------------------------------------------
        */

        xhr.upload.addEventListener(
            'progress',
            function (event) {

                if (event.lengthComputable) {

                    const percent = Math.round(
                        (event.loaded / event.total) * 100
                    );


                    progressBar.style.width =
                        percent + '%';


                    percentage.textContent =
                        percent + '%';

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | REQUEST COMPLETED
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener(
            'load',
            function () {

                console.log('STATUS:', xhr.status);

                console.log(
                    'RESPONSE:',
                    xhr.responseText
                );


                let response = {};


                try {

                    response =
                        JSON.parse(xhr.responseText);

                } catch (e) {

                    console.log(
                        'Response is not JSON'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */

                if (
                    xhr.status >= 200 &&
                    xhr.status < 300 &&
                    response.success
                ) {

                    progressBar.style.width =
                        '100%';

                    percentage.textContent =
                        '100%';


                    progressBar.classList.remove(
                        'progress-bar-animated'
                    );


                    status.textContent =
                        'Upload completed successfully!';


                    message.textContent =
                        response.message ||
                        'Transporter records processed successfully.';


                    message.classList.remove(
                        'text-danger'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | SHOW PARTIAL ERRORS IF ANY
                    |--------------------------------------------------------------------------
                    */

                    if (
                        response.errors &&
                        response.errors.length > 0
                    ) {

                        let errorHtml =
                            '<br><strong>Errors:</strong><br>';


                        response.errors.forEach(
                            function (item) {

                                if (
                                    typeof item === 'object' &&
                                    item !== null
                                ) {

                                    errorHtml +=
                                        'Row ' +
                                        item.row +
                                        ': ' +
                                        item.error +
                                        '<br>';

                                } else {

                                    errorHtml +=
                                        String(item) +
                                        '<br>';

                                }

                            }
                        );


                        message.innerHTML +=
                            errorHtml;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | REFRESH DATATABLE ONLY
                    |--------------------------------------------------------------------------
                    */

                    if (
                        typeof $ !== 'undefined' &&
                        $.fn.DataTable &&
                        $.fn.DataTable.isDataTable(
                            '#transportersTable'
                        )
                    ) {

                        $('#transportersTable')
                            .DataTable()
                            .ajax
                            .reload(null, false);

                    }


                    // Enable button again
                    startUploadBtn.disabled = false;


                    /*
                    |--------------------------------------------------------------------------
                    | DO NOT HIDE PROGRESS CONTAINER
                    |--------------------------------------------------------------------------
                    |
                    | It stays visible so user can see:
                    | success message / errors.
                    |
                    */

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | SERVER / VALIDATION ERROR
                    |--------------------------------------------------------------------------
                    */

                    let errorMessage =
                        response.message || '';


                    if (response.errors) {

                        if (
                            Array.isArray(
                                response.errors
                            )
                        ) {

                            errorMessage =
                                response.errors
                                    .map(function (item) {

                                        if (
                                            typeof item === 'object' &&
                                            item !== null
                                        ) {

                                            return (
                                                'Row ' +
                                                item.row +
                                                ': ' +
                                                item.error
                                            );

                                        }

                                        return String(item);

                                    })
                                    .join('<br>');


                        } else if (
                            typeof response.errors === 'object'
                        ) {

                            let errors = [];


                            Object.values(
                                response.errors
                            ).forEach(
                                function (error) {

                                    if (
                                        Array.isArray(error)
                                    ) {

                                        errors =
                                            errors.concat(
                                                error
                                            );

                                    } else {

                                        errors.push(
                                            error
                                        );

                                    }

                                }
                            );


                            errorMessage =
                                errors.join('<br>');


                        } else {

                            errorMessage =
                                String(
                                    response.errors
                                );

                        }

                    }


                    showUploadError(
                        errorMessage ||
                        'Something went wrong while uploading.'
                    );


                    startUploadBtn.disabled = false;

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | NETWORK ERROR
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener(
            'error',
            function () {

                showUploadError(
                    'Network or server error occurred.'
                );


                startUploadBtn.disabled = false;

            }
        );


        /*
        |--------------------------------------------------------------------------
        | UPLOAD ABORTED
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener(
            'abort',
            function () {

                progressContainer.style.display =
                    'block';


                progressBar.classList.remove(
                    'progress-bar-animated'
                );


                status.textContent =
                    'Upload cancelled';


                message.textContent =
                    'The file upload was cancelled.';


                startUploadBtn.disabled =
                    false;

            }
        );


        /*
        |--------------------------------------------------------------------------
        | OPEN REQUEST
        |--------------------------------------------------------------------------
        */

        xhr.open(
            'POST',
            form.action,
            true
        );


        /*
        |--------------------------------------------------------------------------
        | CSRF TOKEN
        |--------------------------------------------------------------------------
        */

        const csrfToken =
            document.querySelector(
                'meta[name="csrf-token"]'
            );


        if (csrfToken) {

            xhr.setRequestHeader(
                'X-CSRF-TOKEN',
                csrfToken.getAttribute('content')
            );

        }


        /*
        |--------------------------------------------------------------------------
        | AJAX REQUEST
        |--------------------------------------------------------------------------
        */

        xhr.setRequestHeader(
            'X-Requested-With',
            'XMLHttpRequest'
        );


        xhr.setRequestHeader(
            'Accept',
            'application/json'
        );


        /*
        |--------------------------------------------------------------------------
        | SEND FILE
        |--------------------------------------------------------------------------
        */

        xhr.send(formData);

    });


    /*
    |--------------------------------------------------------------------------
    | CANCEL BUTTON
    |--------------------------------------------------------------------------
    |
    | Cancel button first opens confirmation modal.
    |
    */

    if (cancelButton) {

        cancelButton.addEventListener(
            'click',
            function () {

                /*
                |--------------------------------------------------------------------------
                | If upload is running
                |--------------------------------------------------------------------------
                */

                if (
                    xhr &&
                    xhr.readyState !== XMLHttpRequest.DONE
                ) {

                    showCancelConfirmation();

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | If upload hasn't started
                |--------------------------------------------------------------------------
                */

                if (fileInput.files.length) {

                    showCancelConfirmation();

                    return;

                }


                // Nothing selected
                resetUpload();

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRM CANCEL
    |--------------------------------------------------------------------------
    */

    if (confirmCancelUploadBtn) {

        confirmCancelUploadBtn.addEventListener(
            'click',
            function () {

                /*
                |--------------------------------------------------------------------------
                | Abort active upload
                |--------------------------------------------------------------------------
                */

                if (
                    xhr &&
                    xhr.readyState !== XMLHttpRequest.DONE
                ) {

                    xhr.abort();

                    xhr = null;

                }


                /*
                |--------------------------------------------------------------------------
                | Reset everything
                |--------------------------------------------------------------------------
                */

                resetUpload();


                /*
                |--------------------------------------------------------------------------
                | Close confirmation modal
                |--------------------------------------------------------------------------
                */

                if (
                    cancelUploadModalElement &&
                    typeof bootstrap !== 'undefined'
                ) {

                    const modal =
                        bootstrap.Modal.getInstance(
                            cancelUploadModalElement
                        );

                    if (modal) {

                        modal.hide();

                    }

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | SHOW CANCEL CONFIRMATION
    |--------------------------------------------------------------------------
    */

    function showCancelConfirmation() {

        if (
            cancelUploadModalElement &&
            typeof bootstrap !== 'undefined'
        ) {

            const modal =
                bootstrap.Modal.getOrCreateInstance(
                    cancelUploadModalElement
                );

            modal.show();

        } else {

            // Fallback if Bootstrap modal is unavailable
            const confirmCancel =
                confirm(
                    'Are you sure you want to cancel this upload?'
                );


            if (confirmCancel) {

                if (
                    xhr &&
                    xhr.readyState !== XMLHttpRequest.DONE
                ) {

                    xhr.abort();

                    xhr = null;

                }

                resetUpload();

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | SHOW UPLOAD ERROR
    |--------------------------------------------------------------------------
    */

    function showUploadError(errorMessage) {

        progressContainer.style.display =
            'block';


        progressBar.classList.remove(
            'bg-success'
        );


        progressBar.classList.add(
            'bg-danger'
        );


        progressBar.classList.remove(
            'progress-bar-animated'
        );


        progressBar.style.width =
            '100%';


        percentage.classList.remove(
            'text-success'
        );


        percentage.classList.add(
            'text-danger'
        );


        percentage.textContent =
            '100%';


        status.textContent =
            'Upload completed with errors!';


        message.innerHTML =
            errorMessage ||
            'Some rows could not be imported.';


        message.classList.add(
            'text-danger'
        );


        message.style.whiteSpace =
            'pre-line';


        startUploadBtn.disabled =
            false;

    }


    /*
    |--------------------------------------------------------------------------
    | RESET UPLOAD
    |--------------------------------------------------------------------------
    */

    function resetUpload() {

        if (
            xhr &&
            xhr.readyState !== XMLHttpRequest.DONE
        ) {

            xhr.abort();

        }


        xhr = null;


        fileInput.value = '';


        fileName.value =
            'No file selected';


        progressContainer.style.display =
            'none';


        progressBar.style.width =
            '0%';


        progressBar.classList.remove(
            'bg-danger'
        );


        progressBar.classList.add(
            'bg-success'
        );


        progressBar.classList.remove(
            'progress-bar-animated'
        );


        percentage.textContent =
            '0%';


        percentage.classList.remove(
            'text-danger'
        );


        percentage.classList.add(
            'text-success'
        );


        status.textContent =
            '';


        message.textContent =
            '';


        startUploadBtn.disabled =
            false;

    }

    const bulkUploadModal = document.getElementById('bulkUploadModal');

if (bulkUploadModal) {
    bulkUploadModal.addEventListener('hidden.bs.modal', function () {
        resetUpload();
    });
}


    /*
    |--------------------------------------------------------------------------
    | FORMAT FILE SIZE
    |--------------------------------------------------------------------------
    */

    function formatFileSize(bytes) {

        if (bytes === 0) {

            return '0 Bytes';

        }


        const sizes = [
            'Bytes',
            'KB',
            'MB',
            'GB'
        ];


        const i = Math.floor(
            Math.log(bytes) /
            Math.log(1024)
        );


        return (
            Math.round(
                bytes /
                Math.pow(1024, i) *
                100
            ) / 100
        ) +
        ' ' +
        sizes[i];

    }

});
</script>


@endpush
