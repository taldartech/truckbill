@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">

    {{-- This CSS helps the new search inputs fit perfectly --}}
    <style>
        #truckOwnersTable thead th {
            vertical-align: middle;
        }
        /* This new row will hold the search boxes */
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
        {{-- FIXED: Removed invalid col-sm-6 wrapper --}}
        
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="d-flex justify-content-between gap-2 mb-3">
              <h3 class="mb-0">Truck Owner Management</h3>
      
            <a href="{{ route('truck_owners.create') }}" class="btn btn-info">
                <i class="bi bi-plus"></i> Create
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Truck Owners List</h3>
            </div>
            <div class="card-body">
                {{-- FIXED: Added style="width:100%" for scrollX --}}
                <table id="truckOwnersTable" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Short Name</th>
                            <th>PAN Number</th>
                            <th>Location</th>
                            <th>Contact Number</th>
                            <th>Declaration Given</th>
                            <th>Advance Amount</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                        {{-- The JavaScript will dynamically insert the new search row here --}}
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="truckOwnerDetailsModal" tabindex="-1" aria-labelledby="truckOwnerDetailsModalLabel"
        aria-hidden="true">
        
        {{-- FIXED: Added modal-dialog-scrollable for mobile scrolling --}}
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="truckOwnerDetailsModalLabel">
                        <i class="bi bi-truck"></i> Truck Owner Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
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
                    @if (Auth::check() && Auth::user()->userRole->name === 'admin')
                        <button type="button" class="btn btn-primary" id="editTruckOwnerBtn" style="display: none;">
                            <i class="bi bi-pencil"></i> Edit Truck Owner
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
                            <label for="transporter_name_display" class="form-label">Truck Owner Name</label>
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

    <div class="modal fade" id="addTruckModal" tabindex="-1" aria-labelledby="addTruckModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addTruckModalLabel">
                        <i class="bi bi-truck"></i> Add New Truck
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="addTruckForm" method="POST" action="{{ route('trucks.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="modal_transporter_id" name="transporter_id">

                        <div class="row mb-3">
                            {{-- RESPONSIVE FIX: col-12 col-md-6 --}}
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <label for="modal_truck_owner" class="form-label">Truck Owner *</label>
                                <input type="text" class="form-control" id="modal_truck_owner" disabled>
                            </div>
                            {{-- RESPONSIVE FIX: col-12 col-md-6 --}}
                            <div class="col-12 col-md-6">
                                <label for="modal_truck_number" class="form-label">Truck Number *</label>
                                <input type="text" class="form-control" placeholder="e.g., MH12AB1234 or GJ01CD5678"
                                    id="modal_truck_number" name="truck_number" required>
                                <div class="invalid-feedback">Please enter a valid truck number (6-15 characters).</div>
                                <small class="form-text text-muted">Format: State code + digits + letters + digits (e.g.,
                                    MH12AB1234)</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            {{-- RESPONSIVE FIX: col-12 col-md-6 --}}
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <label for="modal_truck_type" class="form-label">Truck Type *</label>
                                <select class="form-select" id="modal_truck_type" name="truck_type" required>
                                    <option value="">Select</option>
                                    <option value="Open">Open</option>
                                    <option value="Closed">Closed</option>
                                    <option value="Container">Container</option>
                                    <option value="Trailer">Trailer</option>
                                    <option value="Tanker">Tanker</option>
                                    <option value="Refrigerated">Refrigerated</option>
                                    <option value="Flatbed">Flatbed</option>
                                    <option value="Tipper">Tipper</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div class="invalid-feedback">Please select a truck type.</div>
                            </div>
                            {{-- RESPONSIVE FIX: col-12 col-md-6 --}}
                            <div class="col-12 col-md-6">
                                <label for="modal_driver_id" class="form-label">Driver</label>
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="modal_driver_id" name="driver_id">
                                        <option value="">Select Driver</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="showAddDriverFormModal">
                                        <i class="bi bi-plus-circle"></i> Add New
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            {{-- RESPONSIVE FIX: col-12 col-md-6 --}}
                            <div class="col-12 col-md-6">
                                <label for="modal_registration_date" class="form-label">Registration Date</label>
                                <input type="date" class="form-control" id="modal_registration_date"
                                    name="registration_date">
                                <small class="form-text text-muted">Optional field</small>
                            </div>
                        </div>

                        <div id="addDriverFormModal" style="display: none;">
                            <hr class="my-4">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-person-plus"></i> Add New Driver</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        {{-- RESPONSIVE FIX: col-12 col-md-6 --}}
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label for="new_driver_name_modal" class="form-label">Name *</label>
                                            <input type="text" class="form-control" id="new_driver_name_modal"
                                                name="new_driver_name">
                                            <div class="invalid-feedback">Please enter a name.</div>
                                        </div>
                                        {{-- RESPONSIVE FIX: col-12 col-md-6 --}}
                                        <div class="col-12 col-md-6">
                                            <label for="new_driver_mobile_modal" class="form-label">Mobile *</label>
                                            <input type="text" class="form-control" id="new_driver_mobile_modal"
                                                name="new_driver_mobile" pattern="[0-9]{10}" maxlength="10">
                                            <div class="invalid-feedback">Please enter a valid 10-digit mobile number.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        {{-- RESPONSIVE FIX: col-12 col-md-6 --}}
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label for="new_driver_license_modal" class="form-label">License
                                                Number</label>
                                            <input type="text" class="form-control" id="new_driver_license_modal"
                                                name="new_driver_license">
                                        </div>
                                        {{-- RESPONSIVE FIX: col-12 col-md-6 --}}
                                        <div class="col-12 col-md-6">
                                            <label for="new_driver_address_modal" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="new_driver_address_modal"
                                                name="new_driver_address">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary" id="addDriverBtnModal">
                                            <i class="bi bi-check-circle"></i> Add Driver
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="cancelAddDriverModal">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i>
                                <strong>Note:</strong> The new driver will be automatically selected after creation.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-truck"></i> Add Truck
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
            $('#truckOwnersTable').DataTable({
                processing: true,
                serverSide: true,
                // FIXED: Replaced 'responsive: true' with 'scrollX: true'
                scrollX: true, 
                ajax: {
                    url: '{{ route('truck_owners.index') }}',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name',
                        render: function(data, type, row) {
                            return data; // Allow HTML rendering for clickable links
                        }
                    },
                    {
                        data: 'short_name',
                        name: 'short_name',
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
                    }, {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: '120px',
                        className: 'text-center'
                    }
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
                // == THIS IS THE NEW, CORRECTED initComplete FUNCTION ==
                // =========================================================================
                initComplete: function() {
                    var api = this.api();
                    
                    // --- 1. Clone the header row and append it ---
                    var header = $(api.table().header());
                    // Clone the first row (index 0)
                    var filterRow = $(header.find('tr').get(0).outerHTML).appendTo(header);
                    filterRow.attr('id', 'search-filter-row');

                    // --- 2. Clear out the content of the new row's cells ---
                    filterRow.find('th').each(function() {
                        $(this).html('');
                    });

                    // --- 3. Define which columns are searchable ---
                    // (Indexes: 0=Name, 1=Short, 2=PAN, 3=Location, 4=Contact, 5=Decl, 6=Adv, 7=Act)
                    var searchableColumns = [0, 1, 2, 3, 4, 6]; 

                    // --- 4. Loop over all columns and add inputs to the new row ---
                    api.columns().every(function() {
                        var col = this;
                        var index = col.index();
                        
                        // Find the <th> in our new filter row (filterRow)
                        var th = $(filterRow.find('th').eq(index));

                        // Only add an input if this column is in our searchable list
                        if (searchableColumns.includes(index)) {
                            // Get title from the *original* header row
                            var title = $(header.find('tr:first th').eq(index)).text();
                            var input = $(
                                    '<input type="text" class="form-control form-control-sm" placeholder="Search ' +
                                    title + '" />')
                                .appendTo(th) // Append to the new row's <th>
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
                // ================= END OF MODIFIED SECTION ===================================
            });

            // Handle delete confirmation with AJAX
            $(document).on('submit', 'form[action*="destroy"]', function(e) {
                e.preventDefault();

                if (!confirm(
                        'Are you sure you want to delete this truck owner? This action can be undone.')) {
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
                            $('body').prepend(`
                            <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                                <i class="bi bi-check-circle"></i>
                                <strong>Success!</strong> ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `);
                            setTimeout(function() {
                                $('.alert-success').fadeOut();
                            }, 5000);
                            $('#truckOwnersTable').DataTable().ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
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

                $.ajax({
                    url: '/truck_owners/' + truckOwnerId,
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
                                                <td class="fw-bold">TDS Percentage:</td>
                                                <td><span class="badge bg-warning text-dark">${data.tds_percentage}%</span></td>
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
                            <div class="col-md-6 mb-3 mb-md-0">
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

                $('#advancePaymentForm')[0].reset();
                $('#transporter_id').val(transporterId);
                $('#transporter_name_display').val(transporterName);

                var today = new Date().toISOString().split('T')[0];
                $('#payment_date').val(today);

                $('#advancePaymentForm .form-control').removeClass('is-invalid is-valid');
                $('#advancePaymentForm .invalid-feedback').text('');

                $('#advancePaymentModal').modal('show');
            });

            // Handle advance payment form submission
            $('#advancePaymentForm').on('submit', function(e) {
                e.preventDefault();
                let advanceAmount = $('#advance_amount').val();
                if (!confirm('Are you sure you want to add an advance payment of ₹' + advanceAmount +
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
                    success: function(response) {
                        $('#advancePaymentModal').modal('hide');

                        $('body').prepend(`
                        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-check-circle"></i>
                            <strong>Success!</strong> Advance payment of ₹${$('#advance_amount').val()} has been added successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);

                        setTimeout(function() {
                            $('.alert-success').fadeOut();
                        }, 5000);

                        $('#truckOwnersTable').DataTable().ajax.reload(null, false);
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

            // Reset form when modal is hidden
            $('#advancePaymentModal').on('hidden.bs.modal', function() {
                $('#advancePaymentForm')[0].reset();
                $('#advancePaymentForm .form-control').removeClass('is-invalid is-valid');
                $('#advancePaymentForm .invalid-feedback').text('');
            });

            // Handle Add Truck button click
            $(document).on('click', '.add-truck-btn', function() {
                const transporterId = $(this).data('transporter-id');
                const transporterName = $(this).data('transporter-name');

                $('#modal_transporter_id').val(transporterId);
                $('#modal_truck_owner').val(transporterName);
                loadDrivers();
                $('#addTruckModal').modal('show');
            });

            // Handle Add Truck form submission
            $('#addTruckForm').on('submit', function(e) {
                e.preventDefault();

                $(this).find('.form-control, .form-select').removeClass('is-invalid is-valid');
                $(this).find('.invalid-feedback').hide();

                console.log($(this).serialize());

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        $('body').prepend(`
                        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-check-circle"></i>
                            <strong>Success!</strong> Truck has been added successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                        setTimeout(function() {
                            $('.alert-success').fadeOut();
                        }, 5000);

                        $('#addTruckForm')[0].reset();
                        $('#addTruckModal').modal('hide');
                        $('#truckOwnersTable').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(function(field) {
                                const input = $(`#modal_${field}`);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(errors[field][
                                    0
                                ]).show();
                            });
                        } else {
                            $('body').prepend(`
                            <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Error!</strong> Unable to add truck. Please try again.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `);
                            setTimeout(function() {
                                $('.alert-danger').fadeOut();
                            }, 5000);
                        }
                    }
                });
            });

            // Reset Add Truck form when modal is hidden
            $('#addTruckModal').on('hidden.bs.modal', function() {
                $('#addTruckForm')[0].reset();
                $('#addTruckForm .form-control, #addTruckForm .form-select').removeClass(
                    'is-invalid is-valid');
                $('#addTruckForm .invalid-feedback').hide();
                $('#addDriverFormModal').hide();
                $('#showAddDriverFormModal').show();
            });

            // Show/Hide Add Driver Form in Add Truck Modal
            $('#showAddDriverFormModal').on('click', function() {
                $('#addDriverFormModal').slideDown();
                $(this).hide();
            });

            $('#cancelAddDriverModal').on('click', function() {
                $('#addDriverFormModal').slideUp();
                $('#showAddDriverFormModal').show();
                $('#new_driver_name_modal, #new_driver_mobile_modal, #new_driver_license_modal, #new_driver_address_modal')
                    .val('');
                $('#addDriverFormModal .form-control').removeClass('is-invalid is-valid');
            });

            // Handle new driver addition in add truck modal
            $('#addDriverBtnModal').on('click', function(e) {
                e.preventDefault();

                const driverData = {
                    name: $('#new_driver_name_modal').val().trim(),
                    mobile: $('#new_driver_mobile_modal').val().trim(),
                    license_number: $('#new_driver_license_modal').val().trim(),
                    address: $('#new_driver_address_modal').val().trim(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                let isValid = true;
                if (!driverData.name) {
                    $('#new_driver_name_modal').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#new_driver_name_modal').removeClass('is-invalid').addClass('is-valid');
                }
                if (!driverData.mobile || !/^[0-9]{10}$/.test(driverData.mobile)) {
                    $('#new_driver_mobile_modal').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#new_driver_mobile_modal').removeClass('is-invalid').addClass('is-valid');
                }
                if (!isValid) return;

                const originalText = $(this).html();
                $(this).html('<i class="bi bi-hourglass-split"></i> Adding...').prop('disabled', true);

                $.ajax({
                    url: '{{ route('drivers.store') }}',
                    type: 'POST',
                    data: driverData,
                    success: function(response) {
                        if (response.success) {
                            $('body').prepend(`
                            <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                                <i class="bi bi-check-circle"></i>
                                <strong>Success!</strong> Driver added successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `);
                            $('#new_driver_name_modal, #new_driver_mobile_modal, #new_driver_license_modal, #new_driver_address_modal')
                                .val('');
                            $('#addDriverFormModal .form-control').removeClass(
                                'is-invalid is-valid');
                            $('#addDriverFormModal').slideUp();
                            $('#showAddDriverFormModal').show();

                            loadDriversForModal();
                            setTimeout(() => {
                                $('#modal_driver_id').val(response.driver.id);
                            }, 500);

                            setTimeout(function() {
                                $('.alert-success').fadeOut();
                            }, 5000);
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Unable to add driver. ';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage += Object.values(xhr.responseJSON.errors).flat().join(' ');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage += xhr.responseJSON.message;
                        } else {
                            errorMessage += 'Please try again.';
                        }
                        $('body').prepend(`
                        <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Error!</strong> ${errorMessage}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                        setTimeout(function() {
                            $('.alert-danger').fadeOut();
                        }, 5000);
                    },
                    complete: function() {
                        $('#addDriverBtnModal').html(originalText).prop('disabled', false);
                    }
                });
            });

            // Handle Delete Truck Owner button click
            $(document).on('click', '.delete-truck-owner-btn', function() {
                const truckOwnerId = $(this).data('truck-owner-id');
                const truckOwnerName = $(this).data('truck-owner-name');

                if (confirm(
                        `Are you sure you want to delete truck owner "${truckOwnerName}"? This action cannot be undone.`
                    )) {
                    $.ajax({
                        url: '/truck_owners/' + truckOwnerId,
                        type: 'POST',
                        data: {
                            '_token': $('meta[name="csrf-token"]').attr('content'),
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            $('body').prepend(`
                            <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                                <i class="bi bi-check-circle"></i>
                                <strong>Success!</strong> Truck owner "${truckOwnerName}" has been deleted successfully.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `);
                            setTimeout(function() {
                                $('.alert-success').fadeOut();
                            }, 5000);
                            $('#truckOwnersTable').DataTable().ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            $('body').prepend(`
                            <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Error!</strong> Unable to delete truck owner "${truckOwnerName}". Please try again.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `);
                            setTimeout(function() {
                                $('.alert-danger').fadeOut();
                            }, 5000);
                        }
                    });
                }
            });
        });

        function loadDrivers() {
            $.ajax({
                url: '/api/drivers',
                type: 'GET',
                success: function(drivers) {
                    const driverSelect = $('#modal_driver_id');
                    driverSelect.empty();
                    driverSelect.append('<option value="">Select Driver</option>');
                    drivers.forEach(function(driver) {
                        driverSelect.append(
                            `<option value="${driver.id}">${driver.name} (${driver.mobile})</option>`
                        );
                    });
                },
                error: function() {
                    console.log('Failed to load drivers');
                }
            });
        }
        function loadDriversForModal() {
            loadDrivers();
        }
        function viewTruckDetails(truckId) {
            window.open('/trucks/' + truckId, '_blank');
        }
        function deleteTruck(truckId, truckNumber) {
            if (confirm('Are you sure you want to delete truck "' + truckNumber + '"? This action cannot be undone.')) {
                $.ajax({
                    url: '/trucks/' + truckId,
                    type: 'POST',
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        $('body').prepend(`
                        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-check-circle"></i>
                            <strong>Success!</strong> Truck "${truckNumber}" has been deleted successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                        setTimeout(function() {
                            $('.alert-success').fadeOut();
                        }, 5000);

                        $('#truckOwnerDetailsModal').modal('hide');
                        $('#truckOwnersTable').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        $('body').prepend(`
                        <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Error!</strong> Unable to delete truck "${truckNumber}". Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                        setTimeout(function() {
                            $('.alert-danger').fadeOut();
                        }, 5000);
                    }
                });
            }
        }
    </script>
@endpush