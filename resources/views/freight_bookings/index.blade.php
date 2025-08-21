@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Freight Bookings Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Freight Bookings List</h3>
            <div class="d-flex gap-2">
                <button id="bulk-payment-btn" class="btn btn-success" style="display: none;" data-bs-toggle="modal" data-bs-target="#bulkPaymentModal">
                    <i class="bi bi-cash-stack"></i> Bulk Payment
                </button>
                <button id="voucher-payment-btn" class="btn btn-info" style="display: none;" data-bs-toggle="modal" data-bs-target="#voucherPaymentModal">
                    <i class="bi bi-receipt"></i> Voucher Payment
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Search and Filter Section -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <form method="GET" action="{{ route('freight_bookings.index') }}">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search LR Number, Voucher Number...">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            @if(request('search'))
                                <a href="{{ route('freight_bookings.index') }}" class="btn btn-outline-danger">
                                    <i class="bi bi-x"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                <div class="col-md-8 text-end">
                    <small class="text-muted">
                        Showing {{ $bookings->firstItem() ?? 0 }} to {{ $bookings->lastItem() ?? 0 }} of {{ $bookings->total() }} results
                    </small>
                </div>
            </div>

            @if(request('search'))
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Search results for: <strong>"{{ request('search') }}"</strong>
                    <a href="{{ route('freight_bookings.index') }}" class="btn btn-sm btn-outline-primary ms-2">Clear Search</a>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all"></th>
                            <th style="width: 80px;">Sr. No</th>
                            <th>FR Number</th>
                            <th>LR Number</th>
                            <th>Voucher No</th>
                            <th>Source City</th>
                            <th>Destination City</th>
                            <th>Truck Number</th>
                            <th class="text-end">TR Rate</th>
                            {{-- <th class="text-end">TR Amount</th> --}}
                            <th class="text-end">Advance</th>
                            <th class="text-end">Balance</th>
                            <th class="text-center">GR Date</th>
                            {{-- <th class="text-center">Delivered</th> --}}
                            {{-- <th class="text-center">Cancelled</th> --}}
                            <th class="text-center" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $index => $booking)
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="row-checkbox" value="{{ $booking->id }}" 
                                           data-lr="{{ $booking->lr ? $booking->lr->lr_number : '' }}" 
                                           data-truck="{{ $booking->lr && $booking->lr->truck ? $booking->lr->truck->truck_number : '' }}" 
                                           data-balance="{{ $booking->balance_amount ?? 0 }}" 
                                           data-voucher_number="{{ $booking->voucher_number ?? '' }}"
                                           data-freight-booking-id="{{ $booking->id }}"
                                           data-transporter="{{ $booking->transporter ? $booking->transporter->name : '' }}"
                                           data-lr-transporter="{{ $booking->lr && $booking->lr->transporter ? $booking->lr->transporter->name : '' }}">
                                </td>
                                <td class="text-center">{{ $bookings->firstItem() + $index }}</td>
                                <td><a href="{{ route('freight_bookings.show', $booking->id) }}" class="text-primary text-decoration-underline">{{ $booking->id ? $booking->id : '-' }}</a></td>
                                <td>{{ $booking->lr ? $booking->lr->lr_number : '-' }}</td>
                                <td>{{ $booking->voucher_number ?? '-' }}</td>
                                <td>{{ $booking->lr && $booking->lr->source_city ? $booking->lr->source_city : '-' }}</td>
                                <td>{{ $booking->lr && $booking->lr->destination_city ? $booking->lr->destination_city : '-' }}</td>
                                <td>{{ $booking->lr && $booking->lr->truck ? $booking->lr->truck->truck_number : '-' }}</td>
                                <td class="text-end">₹{{ number_format($booking->tr_fr_rate ?? 0, 2) }}</td>
                                {{-- <td class="text-end">₹{{ number_format($booking->tr_amount ?? 0, 2) }}</td> --}}
                                <td class="text-end">₹{{ number_format($booking->advance_amount ?? 0, 2) }}</td>
                                <td class="text-end">₹{{ number_format($booking->balance_amount ?? 0, 2) }}</td>
                                <td class="text-center">
                                    {{ $booking->lr && $booking->lr->gr_date ? date('d-m-Y', strtotime($booking->lr->gr_date)) : '-' }}
                                </td>
                                {{-- <td class="text-center">
                                    @if($booking->is_delivered == '1')
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-warning">No</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($booking->is_cancelled == '1')
                                        <span class="badge bg-danger">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                {{--  --}}
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('freight_bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('freight_bookings.edit', $booking->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4"></i>
                                        <p class="mt-2">No freight bookings found</p>
                                        @if(request('search'))
                                            <p>Try adjusting your search criteria</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($bookings->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $bookings->appends(request()->query())->links() }}
                </div>
            @endif
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
        
    </div>
</div>

<!-- Voucher Payment Modal -->
<div class="modal fade" id="voucherPaymentModal" tabindex="-1" aria-labelledby="voucherPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voucherPaymentModalLabel">💳 Voucher Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="voucherPaymentBody">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitVoucherPayment()">💰 Process Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Individual Voucher Payment Modal -->
<div class="modal fade" id="individualPaymentModal" tabindex="-1" aria-labelledby="individualPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="individualPaymentModalLabel">💳 Individual Voucher Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="individualPaymentBody">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="submitIndividualPayment">💰 Process Payment</button>
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
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="bulkPaymentForm" class="btn btn-success">Process Bulk Payment</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all checkbox functionality
    $('#select-all').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', isChecked);
        updatePaymentButtonsVisibility();
    });

    // Individual checkbox change
    $(document).on('change', '.row-checkbox', function() {
        updatePaymentButtonsVisibility();
        
        // Update select-all checkbox state
        const totalCheckboxes = $('.row-checkbox').length;
        const checkedCheckboxes = $('.row-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#select-all').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#select-all').prop('indeterminate', true);
        }
    });

    function updatePaymentButtonsVisibility() {
        const checkedBoxes = $('.row-checkbox:checked');
        
        if (checkedBoxes.length > 0) {
            $('#bulk-payment-btn, #voucher-payment-btn').show();
        } else {
            $('#bulk-payment-btn, #voucher-payment-btn').hide();
        }
    }

    // Voucher Payment Modal
    $('#voucher-payment-btn').on('click', function() {
        const checkedBoxes = $('.row-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            alert('Please select at least one freight booking.');
            return;
        }

        let tableBody = '';
        let totalAmount = 0;

        checkedBoxes.each(function() {
            const checkbox = $(this);
            const lrNumber = checkbox.data('lr');
            const truckNumber = checkbox.data('truck');
            const balance = parseFloat(checkbox.data('balance')) || 0;
            const voucherNumber = checkbox.data('voucher_number');

            totalAmount += balance;

            tableBody += `
                <tr>
                    <td>${lrNumber}</td>
                    <td>${truckNumber}</td>
                    <td>${voucherNumber}</td>
                    <td class="text-end">₹${balance.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                    <td>
                        <input type="number" class="form-control payment-amount" 
                               data-id="${checkbox.val()}" 
                               value="${balance}" 
                               min="0" 
                               max="${balance}" 
                               step="0.01">
                    </td>
                </tr>
            `;
        });

        const modalContent = `
            <form id="voucherPaymentForm" method="POST" action="{{ route('freight_bookings.voucher_payment') }}">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Payment Type</label>
                        <select name="payment_type_id" class="form-select" required>
                            <option value="">Select Payment Type</option>
                            @foreach($paymentTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Mode</label>
                        <select name="payment_mode_id" class="form-select" required>
                            <option value="">Select Payment Mode</option>
                            @foreach($paymentModes as $mode)
                                <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>LR Number</th>
                                <th>Truck Number</th>
                                <th>Voucher Number</th>
                                <th class="text-end">Balance Amount</th>
                                <th class="text-end">Payment Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableBody}
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th colspan="4" class="text-end">Total Payment:</th>
                                <th class="text-end" id="totalPaymentAmount">₹${totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>
            </form>
        `;

        $('#voucherPaymentBody').html(modalContent);
    });

    // Bulk Payment Modal
    $('#bulk-payment-btn').on('click', function() {
        const checkedBoxes = $('.row-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            alert('Please select at least one freight booking.');
            return;
        }

        let tableBody = '';
        let totalAmount = 0;

        checkedBoxes.each(function() {
            const checkbox = $(this);
            const lrNumber = checkbox.data('lr');
            const truckNumber = checkbox.data('truck');
            const balance = parseFloat(checkbox.data('balance')) || 0;
            const voucherNumber = checkbox.data('voucher_number');

            totalAmount += balance;

            tableBody += `
                <tr>
                    <td>${lrNumber}</td>
                    <td>${truckNumber}</td>
                    <td>${voucherNumber}</td>
                    <td class="text-end">₹${balance.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                    <input type="hidden" name="freight_booking_ids[]" value="${checkbox.val()}">
                    <input type="hidden" name="amounts[]" value="${balance}">
                </tr>
            `;
        });

        const modalContent = `
            <form id="bulkPaymentForm" method="POST" action="{{ route('freight_bookings.bulk_payment') }}">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Payment Type</label>
                        <select name="payment_type_id" class="form-select" required>
                            <option value="">Select Payment Type</option>
                            @foreach($paymentTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Mode</label>
                        <select name="payment_mode_id" class="form-select" required>
                            <option value="">Select Payment Mode</option>
                            @foreach($paymentModes as $mode)
                                <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>LR Number</th>
                                <th>Truck Number</th>
                                <th>Voucher Number</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableBody}
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th colspan="3" class="text-end">Total Amount:</th>
                                <th class="text-end">₹${totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>
            </form>
        `;

        $('#bulkPaymentBody').html(modalContent);
    });

    // Payment amount change handler
    $(document).on('input', '.payment-amount', function() {
        let total = 0;
        $('.payment-amount').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#totalPaymentAmount').text('₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2}));
    });
});
</script>
@endpush

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
    var table = $('#freightBookingsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("freight_bookings.datatable") }}',
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                alert('DataTables error: ' + error + '. Check console for details.');
            },
            success: function(data) {
                console.log('DataTables success:', data);
            }
        },
        columns: [
            {
                data: 'checkbox',
                name: 'checkbox',
                orderable: false,
                searchable: false,
                width: '5%',
                className: 'text-center'
            },
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false,
                width: '50px',
                className: 'text-center align-middle'
            },
            {
                data: 'fr_number',
                name: 'fr_number',
                className: 'align-middle'
            },
            {
                data: 'lr_number',
                name: 'lr_number',
                className: 'align-middle'
            },
            {
                data: 'voucher_number',
                name: 'voucher_number',
                className: 'align-middle'
            },
            {
                data: 'source_city',
                name: 'source_city',
                className: 'align-middle'
            },
            {
                data: 'destination_city',
                name: 'destination_city',
                className: 'align-middle'
            },
            {
                data: 'truck_number',
                name: 'truck_number',
                className: 'align-middle'
            },
            {
                data: 'tr_rate_formatted',
                name: 'tr_rate_formatted',
                className: 'text-end align-middle',
                orderable: false
            },
           // {
                // data: 'tr_amount_formatted',
                // name: 'tr_amount_formatted',
                // className: 'text-end align-middle',
                // orderable: false
           // },
            {
                data: 'advance_formatted',
                name: 'advance_formatted',
                className: 'text-end align-middle',
                orderable: false
            },
            {
                data: 'balance_formatted',
                name: 'balance_formatted',
                className: 'text-end align-middle',
                orderable: false
            },
            {
                data: 'gr_date_formatted',
                name: 'gr_date_formatted',
                className: 'text-center align-middle',
                orderable: false
            },
            // {
            //     data: 'is_delivered_badge',
            //     name: 'is_delivered_badge',
            //     className: 'text-center align-middle',
            //     orderable: false
            // },
            // {
            //     data: 'is_cancelled_badge',
            //     name: 'is_cancelled_badge',
            //     className: 'text-center align-middle',
            //     orderable: false
            // },
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
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100, "All"]],
        dom: 'Bfrtip',
        buttons: [
            'excel'
        ],
        responsive: true,
        drawCallback: function() {
            updatePaymentButtonsVisibility();
        }
    });
    
    // Apply column search
    $('#filterrow th input.column-search').on('keyup change', function() {
        const index = $(this).closest('th').index();
        table.column(index).search(this.value).draw();
    });
    
    // Select all checkbox handler
    $('#select-all').on('change', function() {
        const checked = this.checked;
        $('.row-checkbox').prop('checked', checked);
        updatePaymentButtonsVisibility();
    });
    
    // Individual checkbox handler
    $(document).on('change', '.row-checkbox', function() {
        const allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length;
        $('#select-all').prop('checked', allChecked);
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

        // Add transporter details section
        const firstTransporter = checkboxes[0].getAttribute('data-transporter');
        const firstLrTransporter = checkboxes[0].getAttribute('data-lr-transporter');
        modalBody.innerHTML += `
            <div class="alert alert-primary mb-3">
                <h6 class="mb-2"><i class="fas fa-truck"></i> Transporter Details</h6>
                <div class="row">
                    
                    <div class="col-md-4">
                        <strong>LR Transporter:</strong> ${firstLrTransporter || 'N/A'}
                    </div>
                    <div class="col-md-4">
                        <strong>Truck Number:</strong> ${firstTruck || 'N/A'}
                    </div>
                </div>
            </div>
        `;

    // Fetch and display payment history first
    fetchPaymentHistory(Array.from(checkboxes).map(cb => cb.value)).then(paymentHistory => {
        if (paymentHistory && paymentHistory.length > 0) {
            let paymentRows = '';
            paymentHistory.forEach(payment => {
                paymentRows += `
                    <tr>
                        <td>${new Date(payment.created_at).toLocaleDateString()}</td>
                        <td>${payment.freight_booking_id || 'N/A'}</td>
                        <td>${payment.freight_booking?.lr?.lr_number || 'N/A'}</td>
                        <td>${payment.payment_type}</td>
                        <td>${payment.payment_mode}</td>
                        <td class="text-end">₹${parseFloat(payment.amount).toFixed(2)}</td>
                    </tr>
                `;
            });
            
            modalBody.innerHTML += `
                <div class="alert alert-info mb-3">
                    <h6 class="mb-2"><i class="fas fa-history"></i> Previous Payments</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>FR No.</th>
                                    <th>LR Number</th>
                                    <th>Type</th>
                                    <th>Mode</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${paymentRows}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
        
        // Continue with original layout - show payment form in original SweetAlert style
        const lrRows = lrData.map((item, index) => {
            const voucherNumber = checkboxes[index].getAttribute('data-voucher_number');
            const freightBookingId = checkboxes[index].getAttribute('data-freight-booking-id');
            return `
                <tr>
                    <td style="text-align: left; padding: 8px; border: 1px solid #ddd;">${freightBookingId || 'N/A'}</td>
                    <td style="text-align: left; padding: 8px; border: 1px solid #ddd;">${item.lrNumber || 'N/A'}</td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #ddd;">${firstTruck || 'N/A'}</td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #ddd;">${voucherNumber || 'N/A'}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #ddd;">₹${item.balance.toFixed(2)}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #ddd;">
                        <input type="number" step="0.01" min="0" max="${item.balance}" 
                               style="width: 80px; text-align: right; border: 1px solid #ccc; padding: 2px;" 
                               class="allocated-input" data-lr="${item.lrNumber}" 
                               onchange="updateTotalPayment()" value="${item.balance.toFixed(2)}">
                    </td>
                </tr>
            `;
        }).join('');
        
        modalBody.innerHTML += `
            <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th style="text-align: left; padding: 8px; border: 1px solid #ddd;">FR NO.</th>
                        <th style="text-align: left; padding: 8px; border: 1px solid #ddd;">LR NUMBER</th>
                        <th style="text-align: center; padding: 8px; border: 1px solid #ddd;">TRUCK NUMBER</th>
                        <th style="text-align: center; padding: 8px; border: 1px solid #ddd;">VOUCHER NUMBER</th>
                        <th style="text-align: right; padding: 8px; border: 1px solid #ddd;">BALANCE AMOUNT</th>
                        <th style="text-align: right; padding: 8px; border: 1px solid #ddd;">PAYMENT AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    ${lrRows}
                </tbody>
            </table>
            
            <div style="background-color: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <strong>TOTAL PAYMENT: ₹<span id="totalPaymentAmount">${lrData.reduce((sum, item) => sum + item.balance, 0).toFixed(2)}</span></strong>
            </div>
            
            <div style="margin: 15px 0;">
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Payment Type *</label>
                        <select id="paymentType" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                            <option value="">Select Payment Type</option>
                            <option value="advance">Advance</option>
                            <option value="balance">Balance</option>
                            <option value="full">Full Payment</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Payment Mode *</label>
                        <select id="paymentMode" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                            <option value="">Select Payment Mode</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="upi">UPI</option>
                            <option value="neft">NEFT</option>
                            <option value="rtgs">RTGS</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Reference Transaction ID</label>
                    <input type="text" id="referenceId" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Enter reference/transaction ID (optional)">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Remarks</label>
                    <textarea id="paymentRemarks" style="width: 100%; height: 60px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Enter any remarks..."></textarea>
                </div>
            </div>
        `;
        
        // Show the modal
        $('#voucherPaymentModal').modal('show');
    }).catch(error => {
        console.error('Error fetching payment history:', error);
        // Continue with original layout even if history fetch fails
        const lrRows = lrData.map((item, index) => {
            const voucherNumber = checkboxes[index].getAttribute('data-voucher_number');
            const freightBookingId = checkboxes[index].getAttribute('data-freight-booking-id');
            return `
                <tr>
                    <td style="text-align: left; padding: 8px; border: 1px solid #ddd;">${freightBookingId || 'N/A'}</td>
                    <td style="text-align: left; padding: 8px; border: 1px solid #ddd;">${item.lrNumber || 'N/A'}</td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #ddd;">${firstTruck || 'N/A'}</td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #ddd;">${voucherNumber || 'N/A'}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #ddd;">₹${item.balance.toFixed(2)}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #ddd;">
                        <input type="number" step="0.01" min="0" max="${item.balance}" 
                               style="width: 80px; text-align: right; border: 1px solid #ccc; padding: 2px;" 
                               class="allocated-input" data-lr="${item.lrNumber}" 
                               onchange="updateTotalPayment()" value="${item.balance.toFixed(2)}">
                    </td>
                </tr>
            `;
        }).join('');
        
        modalBody.innerHTML += `
            <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th style="text-align: left; padding: 8px; border: 1px solid #ddd;">FR NO.</th>
                        <th style="text-align: left; padding: 8px; border: 1px solid #ddd;">LR NUMBER</th>
                        <th style="text-align: center; padding: 8px; border: 1px solid #ddd;">TRUCK NUMBER</th>
                        <th style="text-align: center; padding: 8px; border: 1px solid #ddd;">VOUCHER NUMBER</th>
                        <th style="text-align: right; padding: 8px; border: 1px solid #ddd;">BALANCE AMOUNT</th>
                        <th style="text-align: right; padding: 8px; border: 1px solid #ddd;">PAYMENT AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    ${lrRows}
                </tbody>
            </table>
            
            <div style="background-color: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <strong>TOTAL PAYMENT: ₹<span id="totalPaymentAmount">${lrData.reduce((sum, item) => sum + item.balance, 0).toFixed(2)}</span></strong>
            </div>
            
            <div style="margin: 15px 0;">
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Payment Type *</label>
                        <select id="paymentType" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                            <option value="">Select Payment Type</option>
                            <option value="advance">Advance</option>
                            <option value="balance">Balance</option>
                            <option value="full">Full Payment</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Payment Mode *</label>
                        <select id="paymentMode" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                            <option value="">Select Payment Mode</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="upi">UPI</option>
                            <option value="neft">NEFT</option>
                            <option value="rtgs">RTGS</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Reference Transaction ID</label>
                    <input type="text" id="referenceId" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Enter reference/transaction ID (optional)">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Remarks</label>
                    <textarea id="paymentRemarks" style="width: 100%; height: 60px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Enter any remarks..."></textarea>
                </div>
            </div>
        `;
        
        // Show the modal
        $('#voucherPaymentModal').modal('show');
    });
}

// Individual voucher payment handler
function handleIndividualVoucherPayment(button) {
    const freightBookingId = button.data('id');
    const lrNumber = button.data('lr');
    const voucherNumber = button.data('voucher_number');
    const balance = parseFloat(button.data('balance'));
    const truckNumber = button.data('truck');
    
    const modalBody = document.getElementById('individualPaymentBody');
    modalBody.innerHTML = '';
    
    // Show freight booking details
    modalBody.innerHTML = `
        <div class="lr-details-section">
            <h5>Freight Booking Details</h5>
            <div class="lr-details-content">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <span><strong>LR Number:</strong></span>
                            <span>${lrNumber}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><strong>Voucher Number:</strong></span>
                            <span>${voucherNumber}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <span><strong>Truck Number:</strong></span>
                            <span>${truckNumber}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><strong>Balance Amount:</strong></span>
                            <span class="text-success"><strong>₹${balance.toFixed(2)}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="lr-details-section mt-3">
            <h5>Payment Details</h5>
            <div class="lr-details-content">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="individual_payment_type" class="form-label">Payment Type *</label>
                        <select name="payment_type" id="individual_payment_type" class="form-select" required>
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
                        <label for="individual_payment_mode" class="form-label">Payment Mode *</label>
                        <select name="payment_mode" id="individual_payment_mode" class="form-select" required>
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
                        <label for="individual_reference_transaction_id" class="form-label">Reference Transaction ID</label>
                        <input type="text" class="form-control" name="reference_transaction_id" id="individual_reference_transaction_id">
                    </div>
                    <div class="col-md-6">
                        <label for="individual_remark" class="form-label">Remark</label>
                        <input type="text" class="form-control" name="remark" id="individual_remark">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="individualTotalAmountInput" class="form-label">Payment Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" class="form-control" name="total_amount" id="individualTotalAmountInput" value="${balance.toFixed(2)}" max="${balance}" required>
                        </div>
                        <div class="invalid-feedback">Please enter a valid payment amount.</div>
                        <small class="text-muted">Maximum amount: ₹${balance.toFixed(2)}</small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Store freight booking ID for submission
    $('#individualPaymentModal').data('freight-booking-id', freightBookingId);
    
    // Show the modal
    $('#individualPaymentModal').modal('show');
}

// Handle individual payment submission
$('#submitIndividualPayment').on('click', function() {
    const freightBookingId = $('#individualPaymentModal').data('freight-booking-id');
    const submitBtn = this;
    const originalBtnText = submitBtn.innerHTML;
    
    // Validate form
    const paymentType = document.getElementById('individual_payment_type').value;
    const paymentMode = document.getElementById('individual_payment_mode').value;
    const totalAmount = document.getElementById('individualTotalAmountInput').value;
    
    if (!paymentType || !paymentMode || !totalAmount || parseFloat(totalAmount) <= 0) {
        alert('Please fill in all required fields with valid values.');
        return;
    }
    
    // Show loading state
    submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Processing...';
    submitBtn.disabled = true;
    
    // Prepare payment data
    const paymentData = {
        freight_booking_ids: [freightBookingId],
        payment_type: paymentType,
        payment_mode: paymentMode,
        total_amount: parseFloat(totalAmount),
        reference_transaction_id: document.getElementById('individual_reference_transaction_id').value,
        remark: document.getElementById('individual_remark').value,
        allocated_amounts: {
            [freightBookingId]: parseFloat(totalAmount)
        },
        _token: '{{ csrf_token() }}'
    };
    
    // Submit payment
    fetch('{{ route("voucher_payments.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(paymentData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            $('#individualPaymentModal').modal('hide');
            // Show success message
            alert(data.message || 'Payment processed successfully!');
            // Refresh the table
            $('#freightBookingsTable').DataTable().ajax.reload();
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
});

// Function to fetch payment history for selected freight bookings
function fetchPaymentHistory(freightBookingIds) {
    return fetch('{{ route("freight_bookings.payment_history") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            freight_booking_ids: freightBookingIds
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            return data.payments;
        } else {
            throw new Error(data.message || 'Failed to fetch payment history');
        }
    });
}

// Function to update total payment amount
function updateTotalPayment() {
    const allocatedInputs = document.querySelectorAll('.allocated-input');
    let total = 0;
    allocatedInputs.forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    const totalElement = document.getElementById('totalPaymentAmount');
    if (totalElement) {
        totalElement.textContent = total.toFixed(2);
    }
}

// Function to submit voucher payment
function submitVoucherPayment() {
    // Get form values
    const paymentType = document.getElementById('paymentType')?.value;
    const paymentMode = document.getElementById('paymentMode')?.value;
    const referenceId = document.getElementById('referenceId')?.value;
    const remarks = document.getElementById('paymentRemarks')?.value;
    
    // Validate required fields
    if (!paymentType) {
        alert('Please select a payment type');
        return;
    }
    
    if (!paymentMode) {
        alert('Please select a payment mode');
        return;
    }
    
    // Get selected freight booking IDs
    const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    
    // Get allocated amounts
    const allocatedInputs = document.querySelectorAll('.allocated-input');
    const paymentAmounts = Array.from(allocatedInputs).map(input => parseFloat(input.value) || 0);
    
    // Calculate total amount
    const totalAmount = paymentAmounts.reduce((sum, amount) => sum + amount, 0);
    
    if (totalAmount <= 0) {
        alert('Please enter valid payment amounts');
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('payment_type', paymentType);
    formData.append('payment_mode', paymentMode);
    formData.append('total_amount', totalAmount.toFixed(2));
    formData.append('payment_date', new Date().toISOString().split('T')[0]);
    
    if (referenceId) formData.append('reference_number', referenceId);
    if (remarks) formData.append('notes', remarks);
    
    // Add booking IDs and payment amounts
    selectedIds.forEach((id, index) => {
        formData.append('booking_ids[]', id);
        formData.append('payment_amounts[]', paymentAmounts[index] || 0);
    });
    
    // Add CSRF token
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    // Show loading state
    const submitBtn = document.querySelector('#voucherPaymentModal .btn-success');
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
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // Hide modal
        $('#voucherPaymentModal').modal('hide');
        
        if (data.success) {
            alert(data.message || 'Payment processed successfully!');
            // Refresh the page or table
            location.reload();
        } else {
            alert(data.message || 'Error processing payment. Please try again.');
        }
    })
    .catch(error => {
        console.error('Payment error:', error);
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


