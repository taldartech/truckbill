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
    <div class="col-sm-6"><h3 class="mb-0">Lorry Receipts</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" id="VocherPaymentBtn" 
            class="btn btn-lg btn-primary d-none"
            onclick="handleVoucherPayment()"
            style="position: fixed; bottom: 100px; right: 20px; z-index: 1050;">
            <i class="bi bi-cash-coin"></i> Payment
        </button>
        <button type="button" id="bulkPaymentBtn" 
            class="btn btn-lg btn-primary d-none"
            onclick="handleBulkPayment()"
            style="position: fixed; bottom: 20px; right: 20px; z-index: 1050;">
            <i class="bi bi-cash-coin"></i> Memo Update
        </button>
        <a href="{{ route('lorry_receipts.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Lorry Receipts List</h3>
        </div>
        <div class="card-body">
            <table id="lorryReceiptsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        
                        <th style="width: 60px;">Sr. No</th>
                        <th>LR Number</th>
                        <th>Customer</th>
                        <th>Consignor</th>
                        <th>Consignee</th>
                        <th>Status</th>
                        <th>GR Date</th>
                        <th>Truck Number</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Voucher Payment Modal -->
<div class="modal fade" id="voucherPaymentModal" tabindex="-1" aria-labelledby="voucherPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voucherPaymentModalLabel">Voucher Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="voucherPaymentForm" method="POST" action="{{ route('lorry_receipts.index') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="voucher_number" class="form-label">Voucher Number</label>
                                <input type="text" class="form-control" id="voucher_number" name="voucher_number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_type" class="form-label">Payment Type</label>
                                <select class="form-control" id="payment_type" name="payment_type" required>
                                    <option value="">Select Payment Type</option>
                                    @foreach($paymentTypes ?? [] as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_mode" class="form-label">Payment Mode</label>
                                <select class="form-control" id="payment_mode" name="payment_mode" required>
                                    <option value="">Select Payment Mode</option>
                                    @foreach($paymentModes ?? [] as $mode)
                                        <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select class="form-control" id="payment_status" name="payment_status" required>
                            <option value="">Select Status</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <input type="hidden" id="selected_lr_numbers" name="lr_numbers[]">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="voucherPaymentForm" class="btn btn-primary">Process Payment</button>
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
    var table = $('#lorryReceiptsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("lorry_receipts.index") }}',
            type: 'GET'
        },
        columns: [
            {
                data: 'sr_no',
                name: 'sr_no',
                width: '60px',
                className: 'text-center',
                orderable: false,
                searchable: false
            },
            {
                data: 'lr_number',
                name: 'lr_number'
            },
            {
                data: 'customer_name',
                name: 'customer_name'
            },
            {
                data: 'consignor_name',
                name: 'consignor_name'
            },
            {
                data: 'consignee_name',
                name: 'consignee_name'
            },
            {
                data: 'status_badge',
                name: 'delivery_status',
                width: '100px',
                className: 'text-center'
            },
            {
                data: 'gr_date_formatted',
                name: 'gr_date',
                width: '100px',
                className: 'text-center'
            },
            {
                data: 'truck_number',
                name: 'truck_number',
                width: '120px'
            },
            {
                data: 'no_of_units',
                name: 'no_of_units',
                width: '80px',
                className: 'text-center'
            },
            {
                data: 'amount_formatted',
                name: 'amount',
                width: '100px',
                className: 'text-end'
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
        order: [[1, 'desc']],
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
            this.api().columns([1, 2, 3, 4, 5, 7]).every(function() {
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
        if (!confirm('Are you sure you want to delete this lorry receipt?')) {
            e.preventDefault();
            return false;
        }
    });

    // Voucher payment functionality (placeholder)
    function handleVoucherPayment() {
        // Get selected rows
        var selectedRows = table.rows('.selected').data();
        if (selectedRows.length === 0) {
            alert('Please select at least one lorry receipt for payment.');
            return;
        }
        
        // Show voucher payment modal
        $('#voucherPaymentModal').modal('show');
    }

    // Bulk payment functionality (placeholder)
    function handleBulkPayment() {
        // Get selected rows
        var selectedRows = table.rows('.selected').data();
        if (selectedRows.length === 0) {
            alert('Please select at least one lorry receipt for memo update.');
            return;
        }
        
        // Handle bulk payment logic here
        console.log('Bulk payment for', selectedRows.length, 'items');
    }

    // Make functions global
    window.handleVoucherPayment = handleVoucherPayment;
    window.handleBulkPayment = handleBulkPayment;
});
</script>
@endpush
