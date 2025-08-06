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
        <a href="{{ route('freight_bookings.create') }}" class="btn btn-info">
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
                        <th style="width: 80px;">ID</th>
                        <th>LR No</th>
                        {{-- <th>Voucher No</th> --}}
                        <th>Unloading Point</th>
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
});
</script>
<script>
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
                        <h5>Freight Booking Info</h5>
                        <div class="lr-details-row"><strong>LR Number:</strong> ${lr?.lr_number || '-'}</div>
                        <div class="lr-details-row"><strong>TR Freight Rate:</strong> ₹${fb.tr_fr_rate ? Number(fb.tr_fr_rate).toFixed(2) : '0.00'}</div>
                        <div class="lr-details-row"><strong>TR Amount:</strong> ₹${fb.tr_amount ? Number(fb.tr_amount).toFixed(2) : '0.00'}</div>
                        <div class="lr-details-row"><strong>Advance:</strong> ₹${fb.advance_amount ? Number(fb.advance_amount).toFixed(2) : '0.00'}</div>
                        <div class="lr-details-row"><strong>Balance:</strong> ₹${fb.balance_amount ? Number(fb.balance_amount).toFixed(2) : '0.00'}</div>
                        <div class="lr-details-row"><strong>TDS Amount:</strong> ₹${fb.tds_amount ? Number(fb.tds_amount).toFixed(2) : '0.00'}</div>
                        <div class="lr-details-row"><strong>Good Type:</strong> ${fb.good_type || '-'}</div>
                        <div class="lr-details-row"><strong>Delivered?</strong>   ${String(fb.is_delivered) === '1' ? 'Yes' : 'No'}</div>
                        <div class="lr-details-row"><strong>Cancelled?</strong> ${String(fb.is_cancelled) === '1' ? 'Yes' : 'No'}</div>
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
