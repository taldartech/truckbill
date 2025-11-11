@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">

    {{-- This small CSS addition helps the new search inputs fit perfectly --}}
    <style>
        #transportersTable thead th {
            vertical-align: middle;
        }
        #search-filter-row th {
            padding: 0.5rem;
        }
        #search-filter-row .form-control-sm {
            width: 100%;
        }
    </style>
@endpush

@section('content')
    <div class="container px-0 mt-4">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="d-flex justify-content-between gap-2 mb-3">
            <h3 class="mb-0">Transporter Management</h3>
            <a href="{{ route('transporters.create') }}" class="btn btn-info">
                <i class="bi bi-plus"></i> Create
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Transporters List</h3>
            </div>
            <div class="card-body">
                {{-- 
                  The HTML table remains clean. 
                  The JavaScript will now create the new search row dynamically.
                --}}
                <table id="transportersTable" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        {{-- This is the main header row --}}
                        <tr>
                            <th style="width: 80px;">Sr. No</th>
                            <th>Name</th>
                            <th>Short Name</th>
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th>Advance Amount</th>
                            @if (auth()->check() && auth()->user()->userRole->name === 'admin')
                                <th style="width: 120px;">Actions</th>
                            @endif
                        </tr>
                        {{-- The JavaScript will dynamically insert the new search row here --}}
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="transporterDetailsModal" tabindex="-1" aria-labelledby="transporterDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
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
                    @if (Auth::user()->userRole->name === 'admin')
                        <button type="button" class="btn btn-primary" id="editTransporterBtn" style="display: none;">
                            <i class="bi bi-pencil"></i> Edit Transporter
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="advancePaymentModal" tabindex="-1" aria-labelledby="advancePaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
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
                            <label for="advance_amount" class="form-label">Advance Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" id="advance_amount"
                                    name="advance_amount" required min="0.01" placeholder="Enter amount">
                            </div>
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
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Add Payment
                        </button>
                    </div>
                </form>
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
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/dataTables.searchPanes.min.js"></script>
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/searchPanes.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#transportersTable').DataTable({
                processing: true,
                serverSide: true,

                // --- SCROLL SETTINGS (Unchanged) ---
                scrollY: '500px', // Vertical scroll
                scrollCollapse: true,
                scrollX: true, // Horizontal scroll

                ajax: {
                    url: '{{ route('transporters.index') }}',
                    type: 'GET'
                },
                columns: [{
                        data: 'sr_no',
                        name: 'sr_no',
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
                        data: 'short_name',
                        name: 'short_name',
                        width: '120px'
                    },
                    {
                        data: 'contact_person',
                        name: 'contact_person',
                        width: '150px'
                    },
                    {
                        data: 'contact_number',
                        name: 'contact_number',
                        width: '140px'
                    },
                    {
                        data: 'advance_amount',
                        name: 'advance_amount',
                        width: '120px',
                        className: 'text-end',
                        // === CHANGE 1: Removed 'searchable: false' ===
                    },
                    @if (auth()->check() && auth()->user()->userRole->name === 'admin')
                        {
                            data: 'actions',
                            name: 'actions',
                            orderable: false,
                            searchable: false,
                            width: '120px',
                            className: 'text-center'
                        }
                    @endif
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [{
                    extend: 'excel',
                    className: 'btn btn-secondary btn-sm',
                    text: '<i class="bi bi-file-earmark-excel"></i> Excel'
                }],
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
                autoWidth: false,
                stateSave: true,
                searchDelay: 500,

                // =========================================================================
                // === THIS IS THE MODIFIED SECTION FOR THE SEPARATE SEARCH ROW ===
                // =========================================================================
                initComplete: function() {
                    var api = this.api();

                    // --- 1. Clone the header row and append it ---
                    var header = $(api.table().header());
                    var filterRow = $(header.find('tr').get(0).outerHTML).appendTo(header);
                    filterRow.attr('id', 'search-filter-row');

                    // --- 2. Clear out the content of the new row's cells ---
                    filterRow.find('th').each(function() {
                        $(this).html('');
                    });

                    // --- 3. Define which columns are searchable ---
                    // (Indexes: 0=Sr, 1=Name, 2=Short, 3=Contact, 4=Number, 5=Advance, 6=Actions)
                    
                    // === CHANGE 2: Added index '5' for Advance Amount ===
                    var searchableColumns = [1, 2, 3, 4, 5];

                    // --- 4. Loop over all columns and add inputs to the new row ---
                    api.columns().every(function() {
                        var col = this;
                        var index = col.index();

                        // Find the <th> in our new filter row
                        var th = $(filterRow.find('th').eq(index));

                        // Only add an input if this column is in our searchable list
                        if (searchableColumns.includes(index)) {
                            var title = $(header.find('tr:first th').eq(index)).text();
                            var input = $(
                                    '<input type="text" class="form-control form-control-sm" placeholder="Search ' +
                                    title + '" />')
                                .appendTo(th)
                                .on('keyup change clear', function() {
                                    if (col.search() !== this.value) {
                                        col.search(this.value).draw();
                                    }
                                })
                                // Stop click from sorting the column
                                .on('click', function(e) {
                                    e.stopPropagation();
                                });
                        }
                    });
                }
            });
            // ================= END OF MODIFIED SECTION ===================================


            // Handle delete confirmation
            $(document).on('submit', 'form[action*="destroy"]', function(e) {
                if (!confirm('Are you sure you want to delete this transporter?')) {
                    e.preventDefault();
                    return false;
                }
            });

            // Handle transporter name click to show details
            $(document).on('click', '.transporter-name-link', function(e) {
                e.preventDefault();
                var transporterId = $(this).data('id');

                // Show modal with loading state
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

                // Fetch transporter details
                $.ajax({
                    url: '/transporters/' + transporterId,
                    type: 'GET',
                    success: function(data) {
                        var detailsHtml = `
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
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
                                                <td><span class="badge ${data.is_owner === 'Truck Owner' ? 'bg-success' : 'bg-info'}">${data.is_owner}</span></td>
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
                                                <td><span class="badge ${data.pan_active === 'Yes' ? 'bg-success' : 'bg-warning'}">${data.pan_active}</span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Declaration Given:</td>
                                                <td><span class="badge ${data.declaration_given === 'Yes' ? 'bg-success' : 'bg-warning'}">${data.declaration_given}</span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">TDS Percentage:</td>
                                                <td><span class="badge bg.warning text-dark">${data.tds_percentage}%</span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Advance Amount:</td>
                                                <td class="fw-bold text-success">${data.advance_amount}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6 mb-3 mb-md-0">
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

                        // Add advance payments section if there are any payments
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
                                detailsHtml += `
                        <tr>
                            <td class="fw-bold text-success">${payment.amount}</td>
                            <td>${payment.payment_date}</td>
                            <td class="text-muted">${payment.remark || '-'}</td>
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
                    error: function(xhr, status, error) {
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

            // Handle advance payment button click
            $(document).on('click', '.advance-payment-btn', function(e) {
                e.preventDefault();
                var transporterId = $(this).data('transporter-id');
                var transporterName = $(this).data('transporter-name');

                // Reset form and populate data
                $('#advancePaymentForm')[0].reset();
                $('#transporter_id').val(transporterId);
                $('#transporter_name_display').val(transporterName);

                // Set today's date as default
                var today = new Date().toISOString().split('T')[0];
                $('#payment_date').val(today);

                // Clear any previous validation states
                $('#advancePaymentForm .form-control').removeClass('is-invalid is-valid');
                $('#advancePaymentForm .invalid-feedback').text('');

                // Show modal
                $('#advancePaymentModal').modal('show');
            });

            // Handle advance payment form submission
            $('#advancePaymentForm').on('submit', function(e) {
                e.preventDefault();
                //  confirm before submitting
                let advanceAmount = $('#advance_amount').val();
                if (!confirm('Are you sure you want to add an advance payment of ₹' + advanceAmount +
                        '?')) {
                    return false;
                }


                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var originalBtnText = submitBtn.html();

                // Show loading state
                submitBtn.prop('disabled', true).html(
                    '<i class="bi bi-hourglass-split"></i> Processing...');

                // Clear previous validation states
                form.find('.form-control').removeClass('is-invalid is-valid');
                form.find('.invalid-feedback').text('');

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        // Show success message
                        $('#advancePaymentModal').modal('hide');

                        // Show success alert
                        $('body').prepend(`
                        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-check-circle"></i>
                            <strong>Success!</strong> Advance payment of ₹${$('#advance_amount').val()} has been added successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);

                        // Auto-hide alert after 5 seconds
                        setTimeout(function() {
                            $('.alert-success').fadeOut();
                        }, 5000);

                        // Reload DataTable to show updated data
                        $('#transportersTable').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            // Validation errors
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                var input = $('#' + field);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(messages[
                                    0]);
                            });
                        } else {
                            // General error
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
                        // Reset button state
                        submitBtn.prop('disabled', false).html(originalBtnText);
                    }
                });
            });

            // Reset form when modal is hidden
            $('#advancePaymentModal').on('hidden.bs.modal', function() {
                $('#advancePaymentForm')[0].reset();
                $('#advancePaymentForm .form-control').removeClass('is-invalid is-valid');
                $('#advancePaymentForm .invalid-feedback').text('');
            });
        });
    </script>
@endpush