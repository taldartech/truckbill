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
    <div class="col-sm-6"><h3 class="mb-0">Freight Bookings Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" id="VocherPaymentBtn" 
            class="btn btn-lg btn-primary d-none"
            onclick="handleVoucherPayment()"
            style="position: fixed; bottom: 50px; right: 20px; z-index: 1050;">
            <i class="bi bi-cash-coin"></i> Payment
        </button>
        <button type="button" class="btn btn-info mt-1" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Filter
        </button>
        <a href="{{ route('freight_bookings.create') }}" class="btn btn-info mt-1 me-2">
            <i class="bi bi-plus"></i> Add New
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Freight Bookings List</h3>
        </div>
        <div class="card-body">
            <table id="freightBookingsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllCheckbox"></th>
                        <th style="width: 80px;">ID</th>
                        <th>LR No</th>
                        {{-- <th>Voucher No</th> --}}
                        <th>Source</th>
                        <th>Destination</th>
                        <th>TR Rate</th>
                        <th>TR Amount</th>
                        <th>Advance</th>
                        <th>Balance</th>
                        <th>GR Date</th>
                        <th>Delivered?</th>
                        <th>Cancelled?</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- Freight Booking Details Modal -->
<div class="modal fade" id="freightBookingDetailsModal" tabindex="-1" aria-labelledby="freightBookingDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Freight Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="freightBookingDetailsContent">
                <!-- Details will be loaded here dynamically -->
            </div>
        </div>
    </div>
</div>

@endsection
<style>
    .wavy-animation {
        animation: waveMove 2s ease-in-out infinite;
        display: inline-block;
    }

    .wavy-animation:nth-child(1) {
        animation-delay: 0s;
    }

    .wavy-animation:nth-child(2) {
        animation-delay: 0.3s;
    }

    @keyframes waveMove {
        0%   { transform: translateY(0); }
        50%  { transform: translateY(-5px); }
        100% { transform: translateY(0); }
    }

    .lr-details-container {
        padding: 20px;
    }

    .lr-details-section {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }

    .lr-details-section h5 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #495057;
    }

    .lr-details-row {
        display: flex;
        margin-bottom: 10px;
    }

    .lr-details-label {
        width: 150px;
        font-weight: 600;
        color: #6c757d;
    }

    .lr-details-value {
        flex: 1;
        color: #495057;
    }

    .table-link {
        color: inherit !important;
        text-decoration: none !important;
        cursor: pointer;
    }

    .table-link:hover {
        text-decoration: underline !important;
    }
</style>

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
    $('#freightBookingsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("freight_bookings.index") }}',
            type: 'GET'
        },
        columns: [
            {
                data: 'checkbox',
                name: 'checkbox',
                width: '20px',
                className: 'text-center',
                render: function(data, type, row) {
                    return `<input type="checkbox" class="rowCheckbox" value="${row.id}">`;
                }
            },
            {
                data: 'id',
                name: 'id',
                width: '80px',
                className: 'text-center',
                render: function(data, type, row) {
                    return `<a href="#" onclick="showFreightBookingDetails(${data})">${data}</a>`;
                }
            },
            {
                data: 'lr_number',
                name: 'lr_number'
            },
            {
                data: 'source_city',
                name: 'source_city'
            },
            {
                data: 'destination_city',
                name: 'destination_city'
            },
           
            {
                data: 'tr_rate_formatted',
                name: 'tr_rate',
                width: '100px',
                className: 'text-end'
            },
            {
                data: 'tr_amount_formatted',
                name: 'tr_amount',
                width: '120px',
                className: 'text-end'
            },
            {
                data: 'advance_formatted',
                name: 'advance',
                width: '100px',
                className: 'text-end'
            },
            {
                data: 'balance_formatted',
                name: 'balance_formatted',
                width: '100px',
                className: 'text-end',
                orderable: false,
                searchable: false
            },
            {
                data: 'gr_date_formatted',
                name: 'gr_date_formatted',
                width: '100px',
                className: 'text-center',
                orderable: false,
                searchable: false
            },
            {
                data: 'is_delivered_badge',
                name: 'is_delivered',
                width: '100px',
                className: 'text-center'
            },
            {
                data: 'is_cancelled_badge',
                name: 'is_cancelled',
                width: '100px',
                className: 'text-center'
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                width: '120px',
                className: 'text-center'
            }
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
        scrollX: true,
        initComplete: function() {
            // Add individual column search
            this.api().columns([1]).every(function() {
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

            // Add select filters for delivered and cancelled columns
            this.api().columns([8, 9]).every(function() {
                var column = this;
                var title = column.header().textContent;
                
                var select = $('<select class="form-control form-control-sm"><option value="">All</option><option value="Yes">Yes</option><option value="No">No</option></select>')
                    .appendTo($(column.header()))
                    .on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });
            });
        }
    });
    
    // Handle delete confirmation
    $(document).on('submit', 'form[action*="destroy"]', function(e) {
        if (!confirm('Are you sure you want to delete this freight booking?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Add select all functionality
    $(document).ready(function() {
        // Select all checkboxes
        $('#selectAllCheckbox').on('click', function() {
            $('.rowCheckbox').prop('checked', this.checked);
            updatePaymentButtonVisibility();
        });

        // Update select all checkbox based on individual checkboxes
        $('.rowCheckbox').on('click', function() {
            const allChecked = $('.rowCheckbox:checked').length === $('.rowCheckbox').length;
            $('#selectAllCheckbox').prop('checked', allChecked);
            updatePaymentButtonVisibility();
        });

        // Update payment button visibility
        function updatePaymentButtonVisibility() {
            const hasChecked = $('.rowCheckbox:checked').length > 0;
            $('#VocherPaymentBtn').removeClass('d-none');
            if (!hasChecked) {
                $('#VocherPaymentBtn').addClass('d-none');
            }
        }

        // Initialize payment button visibility
        updatePaymentButtonVisibility();

        // Also listen for changes on the DataTable's checkboxes
        $(document).on('click', '.rowCheckbox', function() {
            updatePaymentButtonVisibility();
        });
    });
});
</script>
<script>
    // function showFreightBookingDetails(id) {
    //     const modal = new bootstrap.Modal(document.getElementById('freightBookingDetailsModal'));
    //     const contentDiv = document.getElementById('freightBookingDetailsContent');

    //     fetch(`/api/freight-bookings/${id}/details`)
    //         .then(response => response.json())
    //         .then(data => {
    //             const fb = data.freight_booking;
    //             const lr = data.lr;

    //             const html = `
    //                 <div class="lr-details-container">
    //                     <h5>Freight Booking Info</h5>
    //                     <div class="lr-details-row"><strong>LR Number:</strong> ${lr?.lr_number || '-'}</div>
    //                     <div class="lr-details-row"><strong>TR Freight Rate:</strong> ₹${fb.tr_fr_rate ? Number(fb.tr_fr_rate).toFixed(2) : '0.00'}</div>
    //                     <div class="lr-details-row"><strong>TR Amount:</strong> ₹${fb.tr_amount ? Number(fb.tr_amount).toFixed(2) : '0.00'}</div>
    //                     <div class="lr-details-row"><strong>Advance:</strong> ₹${fb.advance_amount ? Number(fb.advance_amount).toFixed(2) : '0.00'}</div>
    //                     <div class="lr-details-row"><strong>Balance:</strong> ₹${fb.balance_amount ? Number(fb.balance_amount).toFixed(2) : '0.00'}</div>
    //                     <div class="lr-details-row"><strong>TDS Amount:</strong> ₹${fb.tds_amount ? Number(fb.tds_amount).toFixed(2) : '0.00'}</div>
    //                     <div class="lr-details-row"><strong>Good Type:</strong> ${fb.good_type || '-'}</div>
    //                     <div class="lr-details-row"><strong>Delivered?</strong>   ${String(fb.is_delivered) === '1' ? 'Yes' : 'No'}</div>
    //                     <div class="lr-details-row"><strong>Cancelled?</strong> ${String(fb.is_cancelled) === '1' ? 'Yes' : 'No'}</div>
    //                 </div>
    //             `;

    //             contentDiv.innerHTML = html;
    //             modal.show();
    //         })
    //         .catch(error => {
    //             console.error('Error fetching Freight Booking details:', error);
    //             contentDiv.innerHTML = '<div class="alert alert-danger">Error loading details. Please try again.</div>';
    //             modal.show();
    //         });
    // }
    function showFreightBookingDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('freightBookingDetailsModal'));
    const contentDiv = document.getElementById('freightBookingDetailsContent');

    fetch(`/api/freight-bookings/${id}/details`)
        .then(response => response.json())
        .then(data => {
            const fb = data.freight_booking;
            const lr = data.lr;

            const html = `
                <div class="lr-details-container">

                    <!-- Trip & Parties -->
                    <div class="lr-details-section">
                        <h5>Trip & Parties</h5>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Transporter</div>
                            <div class="lr-details-value">${lr?.transporter?.name || 'N/A'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Truck</div>
                            <div class="lr-details-value">${lr?.truck?.truck_number || 'N/A'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Customer</div>
                            <div class="lr-details-value">${lr?.consignee?.name || 'N/A'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">LR Number</div>
                            <div class="lr-details-value">${lr?.lr_number || '-'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Delivery Status</div>
                            <div class="lr-details-value">${lr?.delivery_status || '-'}</div>
                        </div>
                    </div>

                    <!-- Route & Dates -->
                    <div class="lr-details-section">
                        <h5>Route & Dates</h5>
                        <div class="lr-details-row">
                            <div class="lr-details-label">GR Date</div>
                            <div class="lr-details-value">${lr?.gr_date || '-'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Source City</div>
                            <div class="lr-details-value">${lr?.source_city || '-'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Source State</div>
                            <div class="lr-details-value">${lr?.source_state || '-'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Destination City</div>
                            <div class="lr-details-value">${lr?.destination_city || '-'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Destination State</div>
                            <div class="lr-details-value">${lr?.destination_state || '-'}</div>
                        </div>
                    </div>

                    <!-- Goods & Charges -->
                    <div class="lr-details-section">
                        <h5>Goods & Charges</h5>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Lane</div>
                            <div class="lr-details-value">${lr?.lane || '-'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">No. of Units</div>
                            <div class="lr-details-value">${lr?.no_of_units || '0'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Amount</div>
                            <div class="lr-details-value">₹${lr?.amount ? Number(lr.amount).toFixed(2) : '0.00'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Good Type</div>
                            <div class="lr-details-value">${fb?.good_type || '-'}</div>
                        </div>
                    </div>

                    <!-- Freight Booking Financials -->
                    <div class="lr-details-section">
                        <h5>Freight Booking Financials</h5>
                        <div class="lr-details-row">
                            <div class="lr-details-label">TR Freight Rate</div>
                            <div class="lr-details-value">₹${fb?.tr_fr_rate ? Number(fb.tr_fr_rate).toFixed(2) : '0.00'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">TR Amount</div>
                            <div class="lr-details-value">₹${fb?.tr_amount ? Number(fb.tr_amount).toFixed(2) : '0.00'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Advance</div>
                            <div class="lr-details-value">₹${fb?.advance_amount ? Number(fb.advance_amount).toFixed(2) : '0.00'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Balance</div>
                            <div class="lr-details-value">₹${fb?.balance_amount ? Number(fb.balance_amount).toFixed(2) : '0.00'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">TDS Amount</div>
                            <div class="lr-details-value">₹${fb?.tds_amount ? Number(fb.tds_amount).toFixed(2) : '0.00'}</div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="lr-details-section">
                        <h5>Status</h5>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Delivered?</div>
                            <div class="lr-details-value">${String(fb?.is_delivered) === '1' ? 'Yes' : 'No'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Cancelled?</div>
                            <div class="lr-details-value">${String(fb?.is_cancelled) === '1' ? 'Yes' : 'No'}</div>
                        </div>
                    </div>

                </div>
            `;

            contentDiv.innerHTML = html;
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching Freight Booking details:', error);
            contentDiv.innerHTML = '<div class="alert alert-danger">Error loading details. Please try again.</div>';
            modal.show();
        });
}

</script>


@endpush
