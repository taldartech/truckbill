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
    <div class="col-sm-6"><h3 class="mb-0">Truck Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Trucks List</h3>
        </div>
        <div class="card-body">
            <table id="trucksTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Truck Number</th>
                        <th>Truck Type</th>
                        <th>Registration Date</th>
                        <th>Truck Owner</th>
                        <th>Driver</th>
                        @if(auth()->check() && auth()->user()->userRole->name === 'admin')
                        <th style="width: 120px;">Actions</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Truck Details Modal -->
<div class="modal fade" id="truckDetailsModal" tabindex="-1" aria-labelledby="truckDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="truckDetailsModalLabel">
                    <i class="bi bi-truck"></i> Truck Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="truckDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading truck details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                
            </div>
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
    $('#trucksTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("trucks.index") }}',
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
                data: 'truck_number',
                name: 'truck_number',
                render: function(data, type, row) {
                    return '<a href="#" class="truck-number-link text-primary fw-bold" data-id="' + row.id + '">' + data + '</a>';
                }
            },
            {
                data: 'truck_type',
                name: 'truck_type',
                width: '120px'
            },
            {
                data: 'registration_date',
                name: 'registration_date',
                width: '130px'
            },
            {
                data: 'transporter',
                name: 'transporter'
            },
            {
                data: 'driver',
                name: 'driver'
            },
            @if(auth()->check() && auth()->user()->userRole->name === 'admin')
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
            this.api().columns([1, 2, 3, 4, 5]).every(function() {
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
    
    // Handle delete confirmation
    $(document).on('submit', 'form[action*="destroy"]', function(e) {
        if (!confirm('Are you sure you want to delete this truck?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Handle truck number click to show details
    $(document).on('click', '.truck-number-link', function(e) {
        e.preventDefault();
        var truckId = $(this).data('id');
        
        // Show modal with loading state
        $('#truckDetailsModal').modal('show');
        $('#truckDetailsContent').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading truck details...</p>
            </div>
        `);
        $('#editTruckBtn').hide();
        
        // Fetch truck details
        $.ajax({
            url: '/trucks/' + truckId,
            type: 'GET',
            success: function(data) {
                var detailsHtml = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-truck"></i> Truck Information</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2"><strong>Truck Number:</strong> <span class="badge bg-primary">${data.truck_number}</span></p>
                                    <p class="mb-2"><strong>Truck Type:</strong> ${data.truck_type}</p>
                                    <p class="mb-2"><strong>Registration Date:</strong> ${data.registration_date || 'Not specified'}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-person-badge"></i> Truck Owner Information</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2"><strong>Name:</strong> ${data.transporter.name}</p>
                                    <p class="mb-2"><strong>Short Name:</strong> ${data.transporter.short_name || 'N/A'}</p>
                                    <p class="mb-2"><strong>Mobile:</strong> ${data.transporter.mobile_number}</p>
                                    <p class="mb-2"><strong>GST Number:</strong> ${data.transporter.gst_number || 'N/A'}</p>
                                    <p class="mb-2"><strong>PAN Number:</strong> ${data.transporter.pan_number || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-person-circle"></i> Driver Information</h6>
                                </div>
                                <div class="card-body">
                                    ${data.driver ? `
                                        <p class="mb-2"><strong>Name:</strong> ${data.driver.name}</p>
                                        <p class="mb-2"><strong>Mobile:</strong> ${data.driver.mobile}</p>
                                        <p class="mb-2"><strong>License Number:</strong> ${data.driver.license_number || 'N/A'}</p>
                                    ` : '<p class="text-muted">No driver assigned</p>'}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-geo-alt"></i> Address Information</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2"><strong>Address:</strong> ${data.transporter.address || 'Not specified'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
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
                
                $('#truckDetailsContent').html(detailsHtml);
                
                // Show edit button and set up click handler
                $('#editTruckBtn').show().off('click').on('click', function() {
                    window.location.href = '/trucks/' + data.id + '/edit';
                });
            },
            error: function(xhr) {
                $('#truckDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Error!</strong> Unable to load truck details. Please try again.
                    </div>
                `);
            }
        });
    });
});
</script>
@endpush
