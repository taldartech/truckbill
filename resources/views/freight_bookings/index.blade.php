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

    {{-- <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('freight_bookings.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div> --}}

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Freight Bookings List</h3>
        </div>
        <div class="card-body">
            <table id="freightBookingsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 80px;">Sr. No</th>
                        <th>LR Number</th>
                        <th>Voucher No</th>
                        <th>TR Rate</th>
                        <th>TR Amount</th>
                        <th>Advance</th>
                        <th>Balance</th>
                        <th>GR Date</th>
                        <th>Delivered</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded by DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- LR Details Modal -->
<div class="modal fade" id="lrDetailsModal" tabindex="-1" aria-labelledby="lrDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lrDetailsModalLabel">LR Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="lrDetailsContent">
                <!-- Content will be loaded dynamically -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Floating action buttons column -->
        <div class="col-md-2">
            <div class="floating-buttons sticky-top pt-4">
                <div class="mb-3">
                    <button id="bulk-payment-btn" class="btn btn-success mb-2" style="display: none;" data-bs-toggle="modal" data-bs-target="#bulkPaymentModal">
                        <i class="fas fa-money-bill-wave me-1"></i> Bulk Payment
                    </button>
                </div>
                <div class="mb-3">
                    <button id="voucher-payment-btn" class="btn btn-info mb-2" style="display: none;" data-bs-toggle="modal" data-bs-target="#voucherPaymentModal">
                        <i class="fas fa-receipt me-1"></i> Voucher Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="voucherPaymentModal" tabindex="-1" aria-labelledby="voucherPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Voucher Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="voucherPaymentBody">
                <!-- Dynamic content will be inserted here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" onclick="submitVoucherPayment()">Submit Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Payment Modal -->
<div class="modal fade" id="bulkPaymentModal" tabindex="-1" aria-labelledby="bulkPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="bulkPaymentBody">
                <!-- Dynamic content will be inserted here -->
            </div>
            <div class="modal-footer">
                <a href="#" id="editFreightBookingBtn" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
<style>
    /* Modal styles */
    .lr-details-container {
        padding: 1.5rem;
    }
    .lr-details-section {
        margin-bottom: 1.5rem;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .lr-details-section h5 {
        background-color: #f1f8ff;
        padding: 0.75rem 1.25rem;
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #0d6efd;
        border-bottom: 1px solid #e9ecef;
    }
    .lr-details-content {
        padding: 1.25rem;
    }
    .lr-details-row {
        display: flex;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed #e9ecef;
    }
    .lr-details-row:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .lr-details-label {
        width: 200px;
        font-weight: 500;
        color: #6c757d;
        flex-shrink: 0;
    }
    .lr-details-value {
        flex: 1;
        color: #212529;
        word-break: break-word;
    }
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    .badge.bg-warning { color: #000; }
    
    /* Animation effects */
    .wavy-animation {
        animation: waveMove 2s ease-in-out infinite;
        display: inline-block;
    }
    .wavy-animation:nth-child(1) { animation-delay: 0s; }
    .wavy-animation:nth-child(2) { animation-delay: 0.3s; }
    @keyframes waveMove {
        0%   { transform: translateY(0); }
        50%  { transform: translateY(-5px); }
        100% { transform: translateY(0); }
    }
    
    /* Floating buttons */
    .floating-buttons button {
        display: block;
        width: 100%;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    .floating-buttons button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }
    
    /* Table links */
    .table-link {
        color: inherit !important;
        text-decoration: none !important;
        cursor: pointer;
    }
    .table-link:hover {
        text-decoration: underline !important;
    }
    
    /* Improved card styling */
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    }
    
    .card-header {
        background: linear-gradient(to right, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #e9ecef;
        padding: 15px 20px;
        border-radius: 10px 10px 0 0 !important;
    }
    
    .card-body {
        padding: 20px;
    }
    
    /* Button styling */
    .btn-info, .btn-success, .btn-primary, .btn-warning {
        transition: all 0.2s ease;
    }
    
    .btn-info:hover, .btn-success:hover, .btn-primary:hover, .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    /* Table improvements */
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    
    /* Tooltip enhancement */
    [data-bs-toggle="tooltip"] {
        position: relative;
    }
</style>

@push('scripts')
<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdn.datatables.net/searchpanes/2.2.0/js/dataTables.searchPanes.min.js"></script>
<script src="https://cdn.datatables.net/searchpanes/2.2.0/js/searchPanes.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

<style>
    .clickable {
        cursor: pointer;
        color: #0d6efd;
        text-decoration: underline;
    }
    .clickable:hover {
        text-decoration: none;
    }
    .memo-assigned {
        background-color: #fff3cd !important; /* Light yellow background for rows with memo */
    }
    .memo-tooltip {
        position: relative;
        display: inline-block;
        margin-left: 5px;
    }
    .memo-tooltip .tooltip-text {
        visibility: hidden;
        width: 200px;
        background-color: #555;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        margin-left: -100px;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .memo-tooltip:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }
</style>

<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // DataTable initialization
    var table = $('#freight-bookings-table').DataTable({
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
                orderable: false,
                searchable: false,
                width: '5%',
                className: 'text-center',
                render: function(data, type, row) {
                    return `<input type="checkbox" class="row-checkbox" value="${row.id}" 
                        data-lr="${row.lr_number || ''}" 
                        data-truck="${row.truck_number || ''}" 
                        data-balance="${row.balance || 0}">`;
                }
            },
            {
                data: 'id',
                name: 'id',
                width: '50px',
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    return `<span class="table-link" onclick="showFreightBookingDetails(${data})">${data}</span>`;
                }
            },
            {
                data: 'lr_number',
                name: 'lr_number',
                className: 'align-middle',
                render: function(data, type, row) {
                    if (type === 'display' && data) {
                        return `<span class="table-link" onclick="showFreightBookingDetails(${row.id})">${data}</span>`;
                    }
                    return data || '-';
                }
            },
            {
                data: 'voucher_number',
                name: 'voucher_number',
                className: 'align-middle',
                render: function(data, type, row) {
                    return data || '-';
                }
            },
            {
                data: 'tr_rate',
                name: 'tr_rate',
                className: 'text-end align-middle',
                render: function(data, type, row) {
                    if (type === 'display' && data) {
                        return '₹' + parseFloat(data).toFixed(2);
                    }
                    return data || 0;
                }
            },
            {
                data: 'tr_amount',
                name: 'tr_amount',
                className: 'text-end align-middle',
                render: function(data, type, row) {
                    if (type === 'display' && data) {
                        return '₹' + parseFloat(data).toFixed(2);
                    }
                    return data || 0;
                }
            },
            {
                data: 'advance',
                name: 'advance',
                className: 'text-end align-middle',
                render: function(data, type, row) {
                    if (type === 'display' && data) {
                        return '₹' + parseFloat(data).toFixed(2);
                    }
                    return data || 0;
                }
            },
            {
                data: 'balance',
                name: 'balance',
                className: 'text-end align-middle',
                render: function(data, type, row) {
                    if (type === 'display') {
                        const balance = parseFloat(row.tr_amount || 0) - parseFloat(row.advance || 0);
                        return '₹' + balance.toFixed(2);
                    }
                    return data || 0;
                }
            },
            {
                data: 'gr_date',
                name: 'gr_date',
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    if (type === 'display' && data) {
                        // Format date as dd-mm-yyyy
                        const date = new Date(data);
                        const day = date.getDate().toString().padStart(2, '0');
                        const month = (date.getMonth() + 1).toString().padStart(2, '0');
                        const year = date.getFullYear();
                        return `${day}-${month}-${year}`;
                    }
                    return data || '-';
                }
            },
            {
                data: 'is_delivered',
                name: 'is_delivered',
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    if (type === 'display') {
                        if (data == 1) {
                            return '<span class="badge bg-success">Yes</span>';
                        } else {
                            return '<span class="badge bg-warning">No</span>';
                        }
                    }
                    return data;
                }
            },
            {
                data: 'is_cancelled',
                name: 'is_cancelled',
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    if (type === 'display') {
                        if (data == 1) {
                            return '<span class="badge bg-danger">Yes</span>';
                        } else {
                            return '<span class="badge bg-secondary">No</span>';
                        }
                    }
                    return data;
                }
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                width: '120px',
                className: 'text-center align-middle'
            }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-md-5"i><"col-md-7"p>>',
        buttons: [
            {
                extend: 'excel',
                className: 'btn btn-sm btn-outline-secondary',
                text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                exportOptions: {
                    columns: ':visible:not(:first-child):not(:last-child)'
                },
                title: 'Freight Bookings - ' + new Date().toLocaleDateString()
            }
        ],
        language: {
            search: "<i class='fas fa-search'></i> _INPUT_",
            searchPlaceholder: "Search records...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            zeroRecords: "No matching records found",
            emptyTable: "No data available",
            paginate: {
                first: "<i class='fas fa-angle-double-left'></i>",
                last: "<i class='fas fa-angle-double-right'></i>",
                next: "<i class='fas fa-angle-right'></i>",
                previous: "<i class='fas fa-angle-left'></i>"
            }
        },
        responsive: true,
        autoWidth: false,
        stateSave: true,
        stateDuration: 60 * 60 * 24 * 7, // 1 week
        searchDelay: 350,
        scrollX: true,
        drawCallback: function() {
            // Initialize tooltips after each draw
            $('[data-bs-toggle="tooltip"]').tooltip();
            updatePaymentButtonsVisibility();
        }
    });
    
    // Apply column search
    $('#filterrow th input.column-search').on('keyup change', function() {
        const index = $(this).closest('th').index();
        table.column(index).search(this.value).draw();
    });
    
    // Apply select filters
        $('#select-all').prop('checked', allChecked && $('.row-checkbox').length > 0);
        updatePaymentButtonsVisibility();
    });
    
    // Function to update payment buttons visibility
    function updatePaymentButtonsVisibility() {
        const checkedCount = $('.row-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#bulk-payment-btn, #voucher-payment-btn').show();
        } else {
            $('#bulk-payment-btn, #voucher-payment-btn').hide();
        }
    }
    
    // Delete confirmation
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this freight booking?')) {
            $(this).closest('form').submit();
        }
    });
    
    // Voucher payment button click handler
    $('#voucher-payment-btn').on('click', function(e) {
        e.preventDefault();
        handleVoucherPayment();
    });
    
    // Bulk payment button click handler
    $('#bulk-payment-btn').on('click', function(e) {
        e.preventDefault();
        handleBulkPayment();
    });
});
</script>
<script>

function handleVoucherPayment() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const modalBody = document.getElementById('voucherPaymentBody');
    
    modalBody.innerHTML = '';

    if (checkboxes.length === 0) {
        alert("Please select at least one Freight Booking.");
        return;
    }

    // Check if any LR has no voucher number
    const hasNoVoucher = Array.from(checkboxes).some(cb => cb.getAttribute('data-voucher_number') === '0');
    if (hasNoVoucher) {
        alert("Please create Freight Booking first for selected Lorry Receipts.");
        return;
    }

    // Check for LRs with zero balance and collect their numbers
    const zeroBalanceLRs = Array.from(checkboxes).filter(cb => {
        const balance = parseFloat(cb.getAttribute('data-balance') || 0);
        return balance <= 0;
    }).map(cb => cb.getAttribute('data-lr'));

    if (zeroBalanceLRs.length > 0) {
        const message = `The following Lorry Receipts have zero balance. No payment is required:\n${zeroBalanceLRs.join(', ')}`;
        alert(message);
        return;
    }

    // Collect truck numbers
    const truckNumbers = Array.from(checkboxes).map(cb => cb.getAttribute('data-truck'));
    const firstTruck = truckNumbers[0];
    const allSameTruck = truckNumbers.every(truck => truck === firstTruck);

    if (!allSameTruck) {
        alert("Selected Freight Bookings belong to different Truck Numbers. Please select bookings with the same truck.");
        return;
    }

    // Header: show truck number once at top-right
    modalBody.innerHTML += `
        <div class="lr-details-section">
            <h5>Selected Freight Bookings</h5>
            <div class="lr-details-content">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge bg-info fs-6">Truck Number: ${firstTruck || 'N/A'}</div>
                    <div class="badge bg-secondary fs-6">${checkboxes.length} booking(s) selected</div>
                </div>
            </div>
        </div>
    `;

    // Collect LR data for allocation
    const lrData = Array.from(checkboxes).map(checkbox => ({
        lrNumber: checkbox.getAttribute('data-lr'),
        balance: parseFloat(checkbox.getAttribute('data-balance') || 0)
    }));

    // Show LR Number + balance amount and voucher number in a table format
    modalBody.innerHTML += `
        <div class="lr-details-section mt-3">
            <h5>Payment Allocation</h5>
            <div class="lr-details-content">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>LR Number</th>
                                <th>Voucher Number</th>
                                <th class="text-end">Balance Amount</th>
                                <th class="text-end">Allocated Amount</th>
                            </tr>
                        </thead>
                        <tbody>
    `;

    lrData.forEach((item, index) => {
        const voucherNumber = checkboxes[index].getAttribute('data-voucher_number');
        modalBody.querySelector('tbody').innerHTML += `
            <tr>
                <td>${index + 1}</td>
                <td>${item.lrNumber || 'N/A'}</td>
                <td>${voucherNumber || 'N/A'}</td>
                <td class="text-end">₹${item.balance.toFixed(2)}</td>
                <td class="text-end">₹<span class="allocated-amount" data-lr="${item.lrNumber}">0.00</span></td>
            </tr>
            <input type="hidden" class="allocated-input" data-lr="${item.lrNumber}" name="allocated_amounts[${index}]" value="0">
        `;
    });

    modalBody.innerHTML += `
                    </table>
                </div>
            </div>
        </div>
    `;

    // Add payment type, payment mode, and optional fields
    modalBody.innerHTML += `
        <div class="lr-details-section mt-3">
            <h5>Payment Details</h5>
            <div class="lr-details-content">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="payment_type" class="form-label">Payment Type *</label>
                        <select name="payment_type" id="payment_type" class="form-select" required>
                            <option value="">Select Payment Type</option>
                            @foreach($paymentTypes as $paymentType)
                                <option value="{{ $paymentType->name }}">
                                    {{ $paymentType->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select a payment type.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_mode" class="form-label">Payment Mode *</label>
                        <select name="payment_mode" id="payment_mode" class="form-select" required>
                            <option value="">Select Payment Mode</option>
                            @foreach($paymentModes as $paymentMode)
                                <option value="{{ $paymentMode->name }}">
                                    {{ $paymentMode->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select a payment mode.</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="reference_transaction_id" class="form-label">Reference Transaction ID</label>
                        <input type="text" class="form-control" name="reference_transaction_id" id="reference_transaction_id">
                    </div>
                    <div class="col-md-6">
                        <label for="remark" class="form-label">Remark</label>
                        <input type="text" class="form-control" name="remark" id="remark">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="totalAmountInput" class="form-label">Enter Payment Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" id="totalAmountInput" name="total_amount" placeholder="Enter Amount" required>
                        </div>
                        <div class="form-text text-muted">Maximum: ₹<span id="maxBalanceDisplay">0.00</span></div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div>
                            <input type="hidden" id="totalAllocatedAmount" name="amount_given" value="0">
                            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                            <div class="alert alert-info mb-0 p-2">
                                <i class="fas fa-info-circle me-1"></i> Amount will be automatically allocated to bookings
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Calculate the max total balance amount
    const maxTotalBalance = lrData.reduce((sum, item) => sum + item.balance, 0);
    document.getElementById('maxBalanceDisplay').textContent = maxTotalBalance.toFixed(2);

    // Add allocation logic
    const totalAmountInput = document.getElementById('totalAmountInput');
    totalAmountInput.addEventListener('input', () => {
        let enteredAmount = parseFloat(totalAmountInput.value || 0);

        // Validation: prevent amount greater than total balance
        if (enteredAmount > maxTotalBalance) {
            alert(`Payment amount cannot be greater than total balance ₹${maxTotalBalance.toFixed(2)}`);
            enteredAmount = maxTotalBalance;
            totalAmountInput.value = enteredAmount.toFixed(2);
        }

        let remaining = parseFloat(totalAmountInput.value || 0);
        let totalAllocated = 0;

        lrData.forEach((item) => {
            let allocate = 0;
            if (remaining > 0) {
                if (remaining >= item.balance) {
                    allocate = item.balance;
                    remaining -= item.balance;
                } else {
                    allocate = remaining;
                    remaining = 0;
                }
            }

            totalAllocated += allocate;

            const span = document.querySelector(`.allocated-amount[data-lr="${item.lrNumber}"]`);
            const hiddenInput = document.querySelector(`.allocated-input[data-lr="${item.lrNumber}"]`);
            if (span && hiddenInput) {
                span.textContent = allocate.toFixed(2);
                hiddenInput.value = allocate.toFixed(2);
            }
        });

        // Update hidden total allocated input
        document.getElementById('totalAllocatedAmount').value = totalAllocated.toFixed(2);
    });

    const modal = new bootstrap.Modal(document.getElementById('voucherPaymentModal'));
    modal.show();
}

function updateBulkPaymentTotal() {
    const selectedCheckboxes = document.querySelectorAll('.bulk-payment-checkbox:checked');
    let total = 0;
    
    selectedCheckboxes.forEach(cb => {
        total += parseFloat(cb.getAttribute('data-balance') || 0);
    });
    
    document.getElementById('bulkPaymentTotal').textContent = total.toFixed(2);
}

function submitBulkPayment() {
    // Get form values
    const paymentType = document.getElementById('bulkPaymentType').value;
    const paymentMode = document.getElementById('bulkPaymentMode').value;
    const paymentDate = document.getElementById('bulkPaymentDate').value;
    const referenceNumber = document.getElementById('bulkPaymentReference').value;
    const notes = document.getElementById('bulkPaymentNotes').value;
    
    // Validate required fields
    if (!paymentType || !paymentMode || !paymentDate) {
        alert('Please fill all required fields.');
        return;
    }
    
    // Get selected bookings
    const selectedCheckboxes = document.querySelectorAll('.bulk-payment-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one booking for payment.');
        return;
    }
    
    // Prepare booking IDs and amounts
    const bookingIds = [];
    const paymentAmounts = [];
    
    selectedCheckboxes.forEach(cb => {
        bookingIds.push(cb.value);
        paymentAmounts.push(parseFloat(cb.getAttribute('data-balance') || 0));
    });
    
    // Update hidden fields
    document.getElementById('bulkPaymentIds').value = JSON.stringify(bookingIds);
    document.getElementById('bulkPaymentAmounts').value = JSON.stringify(paymentAmounts);
    
    // Show processing state
    const submitBtn = document.getElementById('bulkPaymentSubmitBtn');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    
    // Submit form via AJAX
    $.ajax({
        url: '{{ route("freight_bookings.bulk_payment") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            payment_type: paymentType,
            payment_mode: paymentMode,
            payment_date: paymentDate,
            reference_number: referenceNumber,
            notes: notes,
            booking_ids: bookingIds,
            payment_amounts: paymentAmounts
        },
        success: function(response) {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('bulkPaymentModal')).hide();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message || 'Bulk payment processed successfully.',
                confirmButtonText: 'OK'
            }).then(() => {
                // Refresh the table
                $('#freightBookingsTable').DataTable().ajax.reload();
            });
        },
        error: function(xhr) {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            
            // Show error message
            const errorMessage = xhr.responseJSON?.message || 'An error occurred while processing the payment.';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage,
                confirmButtonText: 'OK'
            });
        }
    });
}
// Add event listener for the Bulk Payment button
document.getElementById('bulkPaymentBtn').addEventListener('click', handleBulkPayment);

// Add event listener for the Bulk Payment submit button
document.getElementById('bulkPaymentSubmitBtn').addEventListener('click', submitBulkPayment);

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

$(document).ready(function() {
    // Initialize DataTable
    console.log('Initializing DataTable...');
    var table = $('#freightBookingsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("freight_bookings.datatable") }}',
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                // Show error message to user
                $('#freightBookingsTable').before(
                    '<div class="alert alert-danger">Error loading data. Please check console for details.</div>'
                );
            }
        },
        columns: [
            { 
                data: 'DT_RowIndex', 
                name: 'DT_RowIndex', 
                orderable: false, 
                searchable: false 
            },
            { 
                data: 'lr_number', 
                name: 'lr_number',
                render: function(data, type, row) {
                    return '<span class="clickable lr-number" data-id="' + row.id + '">' + data + '</span>';
                }
            },
            { 
                data: 'voucher_number', 
                name: 'voucher_number' 
            },
            { 
                data: 'tr_rate_formatted', 
                name: 'tr_rate_formatted',
                orderable: false
            },
            { 
                data: 'tr_amount_formatted', 
                name: 'tr_amount_formatted',
                orderable: false
            },
            { 
                data: 'advance_formatted', 
                name: 'advance_formatted',
                orderable: false
            },
            { 
                data: 'balance_formatted', 
                name: 'balance_formatted',
                orderable: false
            },
            { 
                data: 'gr_date_formatted', 
                name: 'gr_date_formatted',
                orderable: false
            },
            { 
                data: 'is_delivered_badge', 
                name: 'is_delivered_badge',
                orderable: false
            },
            { 
                data: 'actions', 
                name: 'actions', 
                orderable: false, 
                searchable: false,
                className: 'text-center'
            },
        ],
        order: [[0, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print', 'colvis'
        ],
        pageLength: 25,
        responsive: true,
        createdRow: function(row, data, dataIndex) {
            if (data.memo_id) {
                $(row).addClass('memo-assigned');
            }
        },
        initComplete: function(settings, json) {
            console.log('DataTables initialization complete');
            console.log('Data received:', json);
        }
    });

    // Handle click on LR number to show details
    $(document).on('click', '.lr-number', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        showFreightBookingDetails(id);
    });

    // Function to show freight booking details in modal
    function showFreightBookingDetails(id) {
        const modal = new bootstrap.Modal(document.getElementById('lrDetailsModal'));
        const contentDiv = document.getElementById('lrDetailsContent');
        const editBtn = document.getElementById('editFreightBookingBtn');

        // Show loading state
        contentDiv.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;

        // Fetch freight booking details
        fetch(`/api/freight-bookings/${id}/details`)
            .then(response => response.json())
            .then(fb => {
                let detailsHtml = `
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-md-6"><strong>LR Number:</strong></div>
                                        <div class="col-md-6">${fb.lr_no || 'N/A'}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6"><strong>Voucher No:</strong></div>
                                        <div class="col-md-6">${fb.voucher_no || 'N/A'}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6"><strong>GR Date:</strong></div>
                                        <div class="col-md-6">${fb.gr_date || 'N/A'}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6"><strong>Status:</strong></div>
                                        <div class="col-md-6">
                                            <span class="badge bg-${fb.is_cancelled ? 'danger' : (fb.is_delivered ? 'success' : 'secondary')}">
                                                ${fb.is_cancelled ? 'Cancelled' : (fb.is_delivered ? 'Delivered' : 'In Transit')}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Financial Information -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Financial Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-md-6"><strong>TR Rate:</strong></div>
                                        <div class="col-md-6">${fb.tr_rate || '0.00'}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6"><strong>TR Amount:</strong></div>
                                        <div class="col-md-6">${fb.tr_amount || '0.00'}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6"><strong>Advance:</strong></div>
                                        <div class="col-md-6">${fb.advance || '0.00'}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6"><strong>Balance:</strong></div>
                                        <div class="col-md-6">${fb.balance || '0.00'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

            contentDiv.innerHTML = detailsHtml;
            
            // Set edit button URL
            if (editBtn) {
                editBtn.href = `/freight_bookings/${id}/edit`;
            }
            
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching Freight Booking details:', error);
            contentDiv.innerHTML = '<div class="alert alert-danger">Error loading details. Please try again.</div>';
            modal.show();
        });
}

</script>

<script>
function handleBulkPayment() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const modalBody = document.getElementById('bulkPaymentBody');
    
    modalBody.innerHTML = '';

    if (checkboxes.length === 0) {
        alert("Please select at least one Freight Booking.");
        return;
    }

    // Check for bookings with zero balance and collect their numbers
    const zeroBalanceBookings = Array.from(checkboxes).filter(cb => {
        const balance = parseFloat(cb.getAttribute('data-balance') || 0);
        return balance <= 0;
    }).map(cb => cb.getAttribute('data-lr'));

    if (zeroBalanceBookings.length > 0) {
        const message = `The following Bookings have zero balance. No payment is required:\n${zeroBalanceBookings.join(', ')}`;
        alert(message);
        return;
    }

    // Collect truck numbers
    const truckNumbers = Array.from(checkboxes).map(cb => cb.getAttribute('data-truck'));
    const firstTruck = truckNumbers[0];
    const allSameTruck = truckNumbers.every(truck => truck === firstTruck);

    if (!allSameTruck) {
        alert("Selected Freight Bookings belong to different Truck Numbers. Please select bookings with the same truck.");
        return;
    }

    // Header: show truck number once at top-right
    modalBody.innerHTML += `
        <div class="lr-details-section">
            <h5>Selected Freight Bookings</h5>
            <div class="lr-details-content">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge bg-info fs-6">Truck Number: ${firstTruck || 'N/A'}</div>
                    <div class="badge bg-secondary fs-6">${checkboxes.length} booking(s) selected</div>
                </div>
            </div>
        </div>
    `;

    // Collect LR data for allocation
    const lrData = Array.from(checkboxes).map(checkbox => ({
        id: checkbox.value,
        lrNumber: checkbox.getAttribute('data-lr'),
        balance: parseFloat(checkbox.getAttribute('data-balance') || 0)
    }));

    // Show LR Number + balance amount and voucher number in a table format
    modalBody.innerHTML += `
        <div class="lr-details-section mt-3">
            <h5>Payment Allocation</h5>
            <div class="lr-details-content">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th width="40px"><input type="checkbox" id="bulk-select-all" checked></th>
                                <th>LR Number</th>
                                <th class="text-end">Balance Amount</th>
                                <th class="text-end">Payment Amount</th>
                            </tr>
                        </thead>
                        <tbody id="bulkPaymentTableBody">
    `;

    lrData.forEach((item, index) => {
        modalBody.querySelector('#bulkPaymentTableBody').innerHTML += `
            <tr>
                <td class="text-center">
                    <input type="checkbox" class="bulk-row-checkbox" data-id="${item.id}" data-balance="${item.balance}" checked>
                </td>
                <td>${item.lrNumber || 'N/A'}</td>
                <td class="text-end">₹${item.balance.toFixed(2)}</td>
                <td class="text-end">
                    <input type="number" class="form-control form-control-sm bulk-payment-amount" 
                        data-id="${item.id}" value="${item.balance.toFixed(2)}" 
                        min="0" max="${item.balance}" step="0.01">
                </td>
            </tr>
        `;
    });

    modalBody.innerHTML += `
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total Payment:</th>
                                <th class="text-end">₹<span id="bulkPaymentTotal">0.00</span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    `;

    // Add payment type, payment mode, and optional fields
    modalBody.innerHTML += `
        <div class="lr-details-section mt-3">
            <h5>Payment Details</h5>
            <div class="lr-details-content">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="bulk_payment_type" class="form-label">Payment Type *</label>
                        <select name="payment_type" id="bulk_payment_type" class="form-select" required>
                            <option value="">Select Payment Type</option>
                            @foreach($paymentTypes as $paymentType)
                                <option value="{{ $paymentType->name }}">
                                    {{ $paymentType->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select a payment type.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="bulk_payment_mode" class="form-label">Payment Mode *</label>
                        <select name="payment_mode" id="bulk_payment_mode" class="form-select" required>
                            <option value="">Select Payment Mode</option>
                            @foreach($paymentModes as $paymentMode)
                                <option value="{{ $paymentMode->name }}">
                                    {{ $paymentMode->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select a payment mode.</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="bulk_payment_date" class="form-label">Payment Date *</label>
                        <input type="date" class="form-control" id="bulk_payment_date" name="payment_date" 
                            required value="{{ date('Y-m-d') }}">
                        <div class="invalid-feedback">Please select a payment date.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="bulk_reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="bulk_reference_number" name="reference_number">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="bulk_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="bulk_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                
                <input type="hidden" id="bulk_total_amount" name="total_amount" value="0">
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            </div>
        </div>
    `;

    // Add event listeners for checkboxes and amount inputs
    setTimeout(() => {
        // Select all checkbox
        const bulkSelectAll = document.getElementById('bulk-select-all');
        bulkSelectAll.addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('.bulk-row-checkbox').forEach(cb => {
                cb.checked = checked;
            });
            updateBulkPaymentTotal();
        });

        // Individual checkboxes
        document.querySelectorAll('.bulk-row-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkPaymentTotal);
        });

        // Amount inputs
        document.querySelectorAll('.bulk-payment-amount').forEach(input => {
            input.addEventListener('input', function() {
                const max = parseFloat(this.getAttribute('max'));
                if (parseFloat(this.value) > max) {
                    this.value = max;
                }
                updateBulkPaymentTotal();
            });
        });

        // Calculate initial total
        updateBulkPaymentTotal();
    }, 100);

    const modal = new bootstrap.Modal(document.getElementById('bulkPaymentModal'));
    modal.show();
}

function updateBulkPaymentTotal() {
    let total = 0;
    document.querySelectorAll('.bulk-row-checkbox:checked').forEach(cb => {
        const id = cb.getAttribute('data-id');
        const amountInput = document.querySelector(`.bulk-payment-amount[data-id="${id}"]`);
        if (amountInput) {
            total += parseFloat(amountInput.value || 0);
        }
    });
    document.getElementById('bulkPaymentTotal').textContent = total.toFixed(2);
    document.getElementById('bulk_total_amount').value = total.toFixed(2);
}

function submitBulkPayment() {
    // Validate required fields
    const paymentType = document.getElementById('bulk_payment_type');
    const paymentMode = document.getElementById('bulk_payment_mode');
    const paymentDate = document.getElementById('bulk_payment_date');
    
    // Basic form validation
    let isValid = true;
    if (!paymentType.value) {
        paymentType.classList.add('is-invalid');
        isValid = false;
    } else {
        paymentType.classList.remove('is-invalid');
    }
    
    if (!paymentMode.value) {
        paymentMode.classList.add('is-invalid');
        isValid = false;
    } else {
        paymentMode.classList.remove('is-invalid');
    }
    
    if (!paymentDate.value) {
        paymentDate.classList.add('is-invalid');
        isValid = false;
    } else {
        paymentDate.classList.remove('is-invalid');
    }
    
    // Check if any booking is selected
    const selectedCheckboxes = document.querySelectorAll('.bulk-row-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one booking for payment.');
        return;
    }
    
    // Check if total amount is greater than 0
    const totalAmount = parseFloat(document.getElementById('bulk_total_amount').value || 0);
    if (totalAmount <= 0) {
        alert('Total payment amount must be greater than zero.');
        return;
    }
    
    if (!isValid) {
        alert('Please fill in all required fields.');
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('payment_type', paymentType.value);
    formData.append('payment_mode', paymentMode.value);
    formData.append('payment_date', paymentDate.value);
    formData.append('reference_number', document.getElementById('bulk_reference_number').value || '');
    formData.append('notes', document.getElementById('bulk_notes').value || '');
    formData.append('total_amount', totalAmount);
    
    // Add booking IDs and payment amounts
    const bookingIds = [];
    const paymentAmounts = [];
    
    selectedCheckboxes.forEach(cb => {
        const id = cb.getAttribute('data-id');
        const amountInput = document.querySelector(`.bulk-payment-amount[data-id="${id}"]`);
        if (amountInput && parseFloat(amountInput.value) > 0) {
            bookingIds.push(id);
            paymentAmounts.push(amountInput.value);
        }
    });
    
    // Append arrays to form data
    bookingIds.forEach((id, index) => {
        formData.append(`booking_ids[${index}]`, id);
        formData.append(`payment_amounts[${index}]`, paymentAmounts[index]);
    });
    
    // Show loading indicator
    const submitBtn = document.querySelector('#bulkPaymentModal .modal-footer .btn-success');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    submitBtn.disabled = true;
    
    // Submit the form via AJAX
    fetch('{{ route("freight_bookings.bulk_payment") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('bulkPaymentModal'));
        modal.hide();
        
        if (data.success) {
            // Show success message using SweetAlert2
            Swal.fire({
                title: 'Success!',
                text: data.message || 'Bulk payment processed successfully!',
                icon: 'success',
                confirmButtonText: 'OK'
            });
            
            // Refresh the table
            $('#freight-bookings-table').DataTable().ajax.reload();
        } else {
            // Show error message
            Swal.fire({
                title: 'Error!',
                text: data.message || 'Error processing payment. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error!',
            text: 'An error occurred while processing the payment. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    });
}

function submitVoucherPayment() {
    // Validate required fields
    const paymentType = document.getElementById('payment_type');
    const paymentMode = document.getElementById('payment_mode');
    const totalAmount = document.getElementById('totalAmountInput');
    
    // Basic form validation
    let isValid = true;
    if (!paymentType.value) {
        paymentType.classList.add('is-invalid');
        isValid = false;
    } else {
        paymentType.classList.remove('is-invalid');
    }
    
    if (!paymentMode.value) {
        paymentMode.classList.add('is-invalid');
        isValid = false;
    } else {
        paymentMode.classList.remove('is-invalid');
    }
    
    if (!totalAmount.value || parseFloat(totalAmount.value) <= 0) {
        totalAmount.classList.add('is-invalid');
        isValid = false;
    } else {
        totalAmount.classList.remove('is-invalid');
    }
    
    if (!isValid) {
        alert('Please fill in all required fields.');
        return;
    }
    
    // Get all selected freight booking IDs
    const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    
    // Create form data
    const formData = new FormData();
    formData.append('payment_type', paymentType.value);
    formData.append('payment_mode', paymentMode.value);
    formData.append('total_amount', totalAmount.value);
    formData.append('amount_given', document.getElementById('totalAllocatedAmount').value);
    formData.append('company_id', document.querySelector('input[name="company_id"]').value);
    
    // Add reference ID and remark if provided
    const referenceId = document.getElementById('reference_transaction_id');
    const remark = document.getElementById('remark');
    if (referenceId.value) formData.append('reference_transaction_id', referenceId.value);
    if (remark.value) formData.append('remark', remark.value);
    
    // Add selected IDs
    selectedIds.forEach(id => formData.append('freight_booking_ids[]', id));
    
    // Add allocated amounts
    const allocatedInputs = document.querySelectorAll('.allocated-input');
    allocatedInputs.forEach((input, index) => {
        formData.append(`allocated_amounts[${index}]`, input.value);
        formData.append(`lr_numbers[${index}]`, input.getAttribute('data-lr'));
    });
    
    // Show loading indicator
    const submitBtn = document.querySelector('#voucherPaymentModal .modal-footer .btn-success');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    submitBtn.disabled = true;
    
    // Submit the form via AJAX
    fetch('{{ route("freight_bookings.voucher_payment") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('voucherPaymentModal'));
        modal.hide();
        
        if (data.success) {
            // Show success message
            alert(data.message || 'Payment processed successfully!');
            // Refresh the table
            $('#freight-bookings-table').DataTable().ajax.reload();
        } else {
            // Show error message
            alert(data.message || 'Error processing payment. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the payment. Please try again.');
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    });
}
</script>

@endpush
