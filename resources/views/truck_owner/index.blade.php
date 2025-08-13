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
    <div class="col-sm-6"><h3 class="mb-0">Truck Owner Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('truck_owners.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Truck Owners List</h3>
        </div>
        <div class="card-body">
            <table id="truckOwnersTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 80px;">Sr. No</th>
                        <th>Name</th>
                        {{-- <th>GST Number</th> --}}
                        {{-- <th>GST Type</th> --}}
                        <th>PAN Number</th>
                        <th>Location</th>
                        <th>Contact Number</th>
                        <th>Declaration Given</th>
                        {{-- <th>Total Trucks</th> --}}
                        <th>Advance Amount</th>
                        {{-- <th>Company</th> --}}
                        @if(auth()->check() && auth()->user()->userRole->name === 'admin')
                        <th style="width: 120px;">Actions</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Truck Owner Details Modal -->
<div class="modal fade" id="truckOwnerDetailsModal" tabindex="-1" aria-labelledby="truckOwnerDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="truckOwnerDetailsModalLabel">
                    <i class="bi bi-truck"></i> Truck Owner Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="truckOwnerDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading truck owner details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editTruckOwnerBtn" style="display: none;">
                    <i class="bi bi-pencil"></i> Edit Truck Owner
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Advance Payment Modal -->
<div class="modal fade" id="advancePaymentModal" tabindex="-1" aria-labelledby="advancePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="advancePaymentModalLabel">
                    <i class="bi bi-currency-rupee"></i> Add Advance Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="advancePaymentForm" method="POST" action="{{ route('advance-payments.store') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="transporter_id" name="transporter_id">
                    
                    <div class="mb-3">
                        <label for="transporter_name_display" class="form-label">Truck Owner Name</label>
                        <input type="text" class="form-control" id="transporter_name_display" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="advance_amount" class="form-label">Advance Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" required min="0.01" placeholder="Enter amount">
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
                        <textarea class="form-control" id="remark" name="remark" rows="3" placeholder="Enter any remarks or notes (optional)"></textarea>
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
    $('#truckOwnersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("truck_owners.index") }}',
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        },
        columns: [
            {
                data: 'sr_no',
                name: 'sr_no',
                width: '80px',
                className: 'text-center',
                orderable: false,
                searchable: false
            },
            {
                data: 'name',
                name: 'name',
                render: function(data, type, row) {
                    return data; // Allow HTML rendering for clickable links
                }
            },
            {
                data: 'pan_number',
                name: 'pan_number',
                width: '120px'
            },

            {
    data: 'location',
    name: 'location',
    width: '150px'
},
{
    data: 'contact_number',
    name: 'contact_number',
    width: '140px'
},
            {
                data: 'declaration_given',
                name: 'declaration_given',
                width: '120px',
                className: 'text-center'
            },
            
            {
                data: 'advance_amount',
                name: 'advance_amount',
                width: '120px',
                className: 'text-end'
            }@if(auth()->check() && auth()->user()->userRole->name === 'admin'),
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
        initComplete: function() {
            // Add individual column search
            this.api().columns([1, 2, 3, 4]).every(function() {
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
    
    // Handle delete confirmation with AJAX
    $(document).on('submit', 'form[action*="destroy"]', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this truck owner? This action can be undone.')) {
            return false;
        }
        
        var form = $(this);
        var url = form.attr('action');
        var token = form.find('input[name="_token"]').val();
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                '_token': token,
                '_method': 'DELETE'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('body').prepend(`
                        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-check-circle"></i>
                            <strong>Success!</strong> ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    // Auto-hide alert after 5 seconds
                    setTimeout(function() {
                        $('.alert-success').fadeOut();
                    }, 5000);
                    
                    // Reload DataTable to show updated data
                    $('#truckOwnersTable').DataTable().ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                // Show error message
                $('body').prepend(`
                    <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Error!</strong> Unable to delete truck owner. Please try again.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                setTimeout(function() {
                    $('.alert-danger').fadeOut();
                }, 5000);
            }
        });
    });

    // Handle truck owner name click to show details
    $(document).on('click', '.truck-owner-name-link', function(e) {
        e.preventDefault();
        var truckOwnerId = $(this).data('id');
        
        // Show modal with loading state
        $('#truckOwnerDetailsModal').modal('show');
        $('#truckOwnerDetailsContent').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading truck owner details...</p>
            </div>
        `);
        $('#editTruckOwnerBtn').hide();
        
        // Fetch truck owner details
        $.ajax({
            url: '/truck_owners/' + truckOwnerId,
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
                                            <td>${data.contact_person_name || 'Not provided'}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Mobile Number:</td>
                                            <td>${data.mobile_number}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Type:</td>
                                            <td><span class="badge bg-success">${data.is_owner}</span></td>
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
                                            <td class="fw-bold">Total Trucks:</td>
                                            <td class="fw-bold text-info">${data.total_trucks}</td>
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
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-geo-alt"></i> Location Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-bold">Address:</td>
                                            <td>${data.address}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">City:</td>
                                            <td>${data.city}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">State:</td>
                                            <td>${data.state}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Country:</td>
                                            <td>${data.country}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Company:</td>
                                            <td>${data.company}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-truck"></i> Registered Trucks</h6>
                                </div>
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    ${data.trucks && data.trucks.length > 0 ? 
                                        `<div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Truck No.</th>
                                                        <th>Type</th>
                                                        <th>Driver</th>
                                                        <th>Mobile</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.trucks.map(truck => `
                                                        <tr>
                                                            <td class="fw-bold">${truck.truck_number}</td>
                                                            <td>${truck.truck_type}</td>
                                                            <td>${truck.driver_name}</td>
                                                            <td>${truck.driver_mobile}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>` 
                                        : '<p class="text-muted text-center">No trucks registered</p>'
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                    ${data.advance_payments && data.advance_payments.length > 0 ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-currency-rupee"></i> Recent Advance Payments</h6>
                                    </div>
                                    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Amount</th>
                                                        <th>Payment Date</th>
                                                        <th>Remark</th>
                                                        <th>Added On</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.advance_payments.map(payment => `
                                                        <tr>
                                                            <td class="fw-bold text-success">${payment.amount}</td>
                                                            <td>${payment.payment_date}</td>
                                                            <td>${payment.remark}</td>
                                                            <td class="text-muted">${payment.created_at}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Record Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Created:</strong> ${data.created_at}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Last Updated:</strong> ${data.updated_at}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#truckOwnerDetailsContent').html(detailsHtml);
                
                // Show edit button and set up click handler
                $('#editTruckOwnerBtn').show().off('click').on('click', function() {
                    window.location.href = '/truck_owners/' + data.id + '/edit';
                });
            },
            error: function(xhr) {
                $('#truckOwnerDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Error:</strong> Unable to load truck owner details. Please try again.
                    </div>
                `);
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
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var originalBtnText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
        
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
                $('#truckOwnersTable').DataTable().ajax.reload(null, false);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        var input = $('#' + field);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
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
