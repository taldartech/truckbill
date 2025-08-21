@extends('layouts.app')

@section('content')
<style>
/* Consistent styling for view pages */
.info-card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1.5rem;
}

.info-card .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #495057;
    min-width: 140px;
}

.info-value {
    color: #212529;
    text-align: right;
    flex: 1;
}

.badge-status {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}
</style>

<div class="card card-info card-outline mb-4" style="max-width: 1200px; margin: 0 auto;">
    <!--begin::Header-->
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="card-title h4">Freight Booking Details - #{{ $freight_booking->id }}</div>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-success me-2" onclick="openVoucherPaymentModal({{ $freight_booking->id }})">
                <i class="bi bi-credit-card"></i> Voucher Payment
            </button>
            <a href="{{ route('freight_bookings.edit', $freight_booking->id) }}" class="btn btn-sm btn-primary me-2">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('freight_bookings.index') }}" class="btn btn-sm btn-outline-dark">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    <!--end::Header-->
    
    <!--begin::Body-->
    <div class="card-body">
        <div class="row">
            <!-- Basic Information -->
            <div class="col-md-6">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">FR Number:</span>
                            <span class="info-value">#{{ $freight_booking->id }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">LR Number:</span>
                            <span class="info-value">{{ $freight_booking->lr ? $freight_booking->lr->lr_number : 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Voucher Number:</span>
                            <span class="info-value">{{ $freight_booking->voucher_number ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Company:</span>
                            <span class="info-value">{{ $freight_booking->company ? $freight_booking->company->name : 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">GR Date:</span>
                            <span class="info-value">{{ $freight_booking->lr && $freight_booking->lr->gr_date ? date('d-m-Y', strtotime($freight_booking->lr->gr_date)) : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="col-md-6">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Financial Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">TR Rate:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->tr_fr_rate ?? 0, 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">TR Amount:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->tr_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Advance:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->advance_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Balance:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->balance_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">TDS Amount:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->tds_amount ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Status Information -->
            <div class="col-md-6">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Status Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Delivered:</span>
                            <span class="info-value">
                                @if($freight_booking->is_delivered)
                                    <span class="badge bg-success badge-status">Yes</span>
                                @else
                                    <span class="badge bg-warning badge-status">No</span>
                                @endif
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cancelled:</span>
                            <span class="info-value">
                                @if($freight_booking->is_cancelled)
                                    <span class="badge bg-danger badge-status">Yes</span>
                                @else
                                    <span class="badge bg-secondary badge-status">No</span>
                                @endif
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">PAN Verified:</span>
                            <span class="info-value">
                                @if($freight_booking->pan_verified)
                                    <span class="badge bg-success badge-status">Yes</span>
                                @else
                                    <span class="badge bg-warning badge-status">No</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="col-md-6">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Additional Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">E-way Bill:</span>
                            <span class="info-value">{{ $freight_booking->eway_bill_number ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Good Type:</span>
                            <span class="info-value">{{ $freight_booking->good_type ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Remark:</span>
                            <span class="info-value">{{ $freight_booking->remark ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Created:</span>
                            <span class="info-value">{{ $freight_booking->created_at ? $freight_booking->created_at->format('d-m-Y H:i') : 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Updated:</span>
                            <span class="info-value">{{ $freight_booking->updated_at ? $freight_booking->updated_at->format('d-m-Y H:i') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($freight_booking->lr)
        <div class="row">
            <div class="col-12">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Related Lorry Receipt Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Customer:</span>
                                    <span class="info-value">{{ $freight_booking->lr->customer ? $freight_booking->lr->customer->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Consignor:</span>
                                    <span class="info-value">{{ $freight_booking->lr->consignor ? $freight_booking->lr->consignor->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Consignee:</span>
                                    <span class="info-value">{{ $freight_booking->lr->consignee ? $freight_booking->lr->consignee->name : 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Source City:</span>
                                    <span class="info-value">{{ $freight_booking->lr->sourceCity ? $freight_booking->lr->sourceCity->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Destination City:</span>
                                    <span class="info-value">{{ $freight_booking->lr->destinationCity ? $freight_booking->lr->destinationCity->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Weight:</span>
                                    <span class="info-value">{{ $freight_booking->lr->weight ?? 'N/A' }} kg</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Truck Number:</span>
                                    <span class="info-value">{{ $freight_booking->lr->truck ? $freight_booking->lr->truck->truck_number : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Transporter:</span>
                                    <span class="info-value">{{ $freight_booking->lr->transporter ? $freight_booking->lr->transporter->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Delivery Status:</span>
                                    <span class="info-value">
                                        @php
                                            $status = $freight_booking->lr->delivery_status ?? 'Pending';
                                            $badgeClass = match($status) {
                                                'Delivered' => 'bg-success',
                                                'Cancelled' => 'bg-danger',
                                                'In-Progress' => 'bg-info',
                                                default => 'bg-warning'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} badge-status">{{ $status }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    <!--end::Body-->
</div>

<!-- Voucher Payment Modal -->
<div class="modal fade" id="voucherPaymentModal" tabindex="-1" aria-labelledby="voucherPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voucherPaymentModalLabel">Voucher Payment - FR #{{ $freight_booking->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Previous Payments Section -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-history"></i> Previous Payments</h6>
                    <div id="previousPaymentsTable">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            Loading payment history...
                        </div>
                    </div>
                </div>

                        <!-- Transporter Details Section -->
                        <div class="alert alert-secondary">
                            <h6><i class="fas fa-truck"></i> Transporter Details</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>FB Transporter:</strong><br>
                                    <span id="fbTransporter">{{ $freight_booking->transporter_name ?? ($freight_booking->transporter ? $freight_booking->transporter->name : 'N/A') }}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>LR Transporter:</strong><br>
                                    <span id="lrTransporter">{{ $freight_booking->lr && $freight_booking->lr->transporter ? $freight_booking->lr->transporter->name : 'N/A' }}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>TDS Percentage:</strong><br>
                                    <span id="tdsPercentage">{{ $freight_booking->transporter_tds_percentage ? $freight_booking->transporter_tds_percentage . '%' : 'N/A' }}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Truck Number:</strong><br>
                                    <span id="truckNumber">{{ $freight_booking->lr && $freight_booking->lr->truck ? $freight_booking->lr->truck->truck_number : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Allocation Section -->
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-money-bill-wave"></i> Payment Allocation</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>FR No.</th>
                                            <th>LR Number</th>
                                            <th>Balance Amount</th>
                                            <th>Payment Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>#{{ $freight_booking->id }}</td>
                                            <td>{{ $freight_booking->lr ? $freight_booking->lr->lr_number : 'N/A' }}</td>
                                            <td>₹{{ number_format($freight_booking->balance_amount ?? 0, 2) }}</td>
                                            <td>
                                                <input type="number" class="form-control allocated-input" 
                                                       data-fb-id="{{ $freight_booking->id }}" 
                                                       step="0.01" min="0" 
                                                       max="{{ $freight_booking->balance_amount ?? 0 }}"
                                                       onchange="updateTotalPayment()">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-2">
                                <strong>Total Payment Amount: ₹<span id="totalPaymentAmount">0.00</span></strong>
                            </div>
                        </div>

                        <!-- Payment Details Section -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="paymentType" class="form-label">Payment Type *</label>
                                    <select class="form-select" id="paymentType" required>
                                        <option value="">Select Payment Type</option>
                                        <option value="advance">Advance</option>
                                        <option value="balance">Balance</option>
                                        <option value="full">Full Payment</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="paymentMode" class="form-label">Payment Mode *</label>
                                    <select class="form-select" id="paymentMode" required>
                                        <option value="">Select Payment Mode</option>
                                        <option value="cash">Cash</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="online">Online Transfer</option>
                                        <option value="upi">UPI</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="referenceId" class="form-label">Reference Transaction ID</label>
                                    <input type="text" class="form-control" id="referenceId" placeholder="Enter reference ID">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="paymentRemarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="paymentRemarks" rows="2" placeholder="Enter remarks"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="submitVoucherPayment()">
                            <i class="fas fa-save"></i> Submit Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Function to open voucher payment modal for single freight booking
        function openVoucherPaymentModal(freightBookingId) {
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('voucherPaymentModal'));
            modal.show();
            
            // Load payment history for this specific freight booking
            loadPaymentHistory([freightBookingId]);
        }

        // Function to load payment history
        function loadPaymentHistory(freightBookingIds) {
            const tableContainer = document.getElementById('previousPaymentsTable');
            
            // Show loading state
            tableContainer.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Loading payment history...
                </div>
            `;
            
            // Make AJAX request to get payment history
            fetch('/freight_bookings/payment-history', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    freight_booking_ids: freightBookingIds
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.payments && data.payments.length > 0) {
                    // Build table HTML
                    let tableHtml = `
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>FR No.</th>
                                        <th>Voucher Number</th>
                                        <th>Amount</th>
                                        <th>Payment Type</th>
                                        <th>Payment Mode</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    data.payments.forEach(payment => {
                        const statusBadge = payment.payment_status === 'paid' ? 'bg-success' : 
                                          payment.payment_status === 'pending' ? 'bg-warning' : 'bg-secondary';
                        
                        tableHtml += `

                <!-- Payment Allocation Section -->
                <div class="alert alert-warning">
                    <h6><i class="fas fa-money-bill-wave"></i> Payment Allocation</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>FR No.</th>
                                    <th>LR Number</th>
                                    <th>Balance Amount</th>
                                    <th>Payment Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#{{ $freight_booking->id }}</td>
                                    <td>{{ $freight_booking->lr ? $freight_booking->lr->lr_number : 'N/A' }}</td>
                                    <td>₹{{ number_format($freight_booking->balance_amount ?? 0, 2) }}</td>
                                    <td>
                                        <input type="number" class="form-control allocated-input" 
                                               data-fb-id="{{ $freight_booking->id }}" 
                                               step="0.01" min="0" 
                                               max="{{ $freight_booking->balance_amount ?? 0 }}"
                                               onchange="updateTotalPayment()">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-2">
                        <strong>Total Payment Amount: ₹<span id="totalPaymentAmount">0.00</span></strong>
                    </div>
                </div>

                <!-- Payment Details Section -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="paymentType" class="form-label">Payment Type *</label>
                            <select class="form-select" id="paymentType" required>
                                <option value="">Select Payment Type</option>
                                <option value="advance">Advance</option>
                                <option value="balance">Balance</option>
                                <option value="full">Full Payment</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="paymentMode" class="form-label">Payment Mode *</label>
                            <select class="form-select" id="paymentMode" required>
                                <option value="">Select Payment Mode</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online Transfer</option>
                                <option value="upi">UPI</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="referenceId" class="form-label">Reference Transaction ID</label>
                            <input type="text" class="form-control" id="referenceId" placeholder="Enter reference ID">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="paymentRemarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="paymentRemarks" rows="2" placeholder="Enter remarks"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitVoucherPayment()">
                    <i class="fas fa-save"></i> Submit Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to open voucher payment modal for single freight booking
function openVoucherPaymentModal(freightBookingId) {
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('voucherPaymentModal'));
    modal.show();
    
    // Load payment history for this specific freight booking
    loadPaymentHistory([freightBookingId]);
}

// Function to load payment history
function loadPaymentHistory(freightBookingIds) {
    const tableContainer = document.getElementById('previousPaymentsTable');
    
    // Show loading state
    tableContainer.innerHTML = `
        <div class="text-center">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            Loading payment history...
        </div>
    `;
    
    // Make AJAX request to get payment history
    fetch('/freight_bookings/payment-history', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            freight_booking_ids: freightBookingIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.payments && data.payments.length > 0) {
            // Build table HTML
            let tableHtml = `
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Payment ID</th>
                                <th>FR No.</th>
                                <th>Voucher Number</th>
                                <th>Amount</th>
                                <th>Payment Type</th>
                                <th>Payment Mode</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.payments.forEach(payment => {
                const statusBadge = payment.payment_status === 'paid' ? 'bg-success' : 
                                  payment.payment_status === 'pending' ? 'bg-warning' : 'bg-secondary';
                
                tableHtml += `
                    <tr>
                        <td>#${payment.id}</td>
                        <td>#${payment.freight_booking_id}</td>
                        <td>${payment.voucher_number || 'N/A'}</td>
                        <td>₹${parseFloat(payment.amount || 0).toFixed(2)}</td>
                        <td>${payment.payment_type || 'N/A'}</td>
                        <td>${payment.payment_mode || 'N/A'}</td>
                        <td>${new Date(payment.created_at).toLocaleDateString('en-IN')}</td>
                        <td><span class="badge ${statusBadge}">${payment.payment_status || 'N/A'}</span></td>
                    </tr>
                `;
            });
            
            tableHtml += `
                        </tbody>
                    </table>
                </div>
            `;
            
            tableContainer.innerHTML = tableHtml;
        } else {
            tableContainer.innerHTML = '<p class="text-muted mb-0">No previous payments found.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading payment history:', error);
        tableContainer.innerHTML = '<p class="text-danger mb-0">Error loading payment history.</p>';
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
    
    // Get freight booking ID and allocated amount
    const allocatedInput = document.querySelector('.allocated-input');
    const freightBookingId = allocatedInput?.getAttribute('data-fb-id');
    const paymentAmount = parseFloat(allocatedInput?.value) || 0;
    
    if (paymentAmount <= 0) {
        alert('Please enter a valid payment amount');
        return;
    }
    
    // Calculate total amount
    const totalAmount = paymentAmount;
    
    // Prepare form data
    const formData = {
        booking_ids: [freightBookingId],
        payment_amounts: [paymentAmount],
        total_amount: totalAmount,
        payment_type: paymentType,
        payment_mode: paymentMode,
        reference_number: referenceId,
        notes: remarks
    };
    
    // Show loading state
    const submitBtn = document.querySelector('#voucherPaymentModal .btn-primary');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
    
    // Submit payment
    fetch('/freight_bookings/voucher-payment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Payment submitted successfully!');
            // Close modal and refresh page
            bootstrap.Modal.getInstance(document.getElementById('voucherPaymentModal')).hide();
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to submit payment'));
        }
    })
    .catch(error => {
        console.error('Error submitting payment:', error);
        alert('Error submitting payment. Please try again.');
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}
</script>
@endsection
