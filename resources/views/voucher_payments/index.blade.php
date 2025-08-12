@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Voucher Payments Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Always visible container --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        {{-- <a href="{{ route('voucher_payments.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a> --}}
    </div>

    {{-- Hidden filter form --}}
    <form method="GET" action="{{ route('voucher_payments.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        {{-- Filter checkboxes --}}
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="voucher_number" id="filter_voucher" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_voucher">Voucher Number</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="payment_type" id="filter_payment_type" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_payment_type">Payment Type</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="payment_mode" id="filter_payment_mode" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_payment_mode">Payment Mode</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="transaction_id" id="filter_transaction" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_transaction">Transaction ID</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="status" id="filter_status" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_status">Status</label>
                </div>
            </div>
        </div>

        {{-- Conditionally visible filter fields --}}
        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-3 d-none" id="field_voucher_number">
                <label class="form-label">Voucher Number</label>
                <input type="text" name="voucher_number" class="form-control" value="{{ request('voucher_number') }}">
            </div>
            <div class="col-md-3 d-none" id="field_payment_type">
                <label class="form-label">Payment Type</label>
                <select name="payment_type" class="form-select">
                    <option value="">All</option>
                    <option value="advance" {{ request('payment_type') == 'advance' ? 'selected' : '' }}>Advance</option>
                    <option value="final" {{ request('payment_type') == 'final' ? 'selected' : '' }}>Final</option>
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_payment_mode">
                <label class="form-label">Payment Mode</label>
                <select name="payment_mode" class="form-select">
                    <option value="">All</option>
                    <option value="cash" {{ request('payment_mode') == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank_transfer" {{ request('payment_mode') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="online_transfer" {{ request('payment_mode') == 'online_transfer' ? 'selected' : '' }}>Online Transfer</option>
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_transaction_id">
                <label class="form-label">Transaction ID</label>
                <input type="text" name="transaction_id" class="form-control" value="{{ request('transaction_id') }}">
            </div>
            <div class="col-md-3 d-none" id="field_status">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
        </div>

        {{-- Form action buttons --}}
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('voucher_payments.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    {{-- Main content card --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        
        @if(auth()->user()->userRole->name === 'admin')
        <div>
            <button type="button" id="bulkUpdateBtn" 
                    class="btn btn-lg btn-primary d-none"
                    onclick="showBulkUpdateModal()"
                    style="position: fixed; bottom: 20px; right: 20px; z-index: 1050;">
                <i class="bi bi-cash-coin"></i> Bulk Update
            </button>
        </div>
        @endif
    </div>

    <!-- Bulk Update Modal -->
    <div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkUpdateModalLabel">Bulk Update Payment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bulkUpdateForm">
                        <div class="mb-3">
                            <label for="bulkPaymentStatus" class="form-label">Select New Payment Status *</label>
                            <select name="payment_status" id="bulkPaymentStatus" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="in-progress">In-Progress</option>
                                <option value="paid">Paid</option>
                            </select>
                            <div class="invalid-feedback">Please select a payment status.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitBulkUpdate()">Update Status</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Voucher Payments List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        @if(auth()->user()->userRole->name === 'admin')
                        <th style="width: 30px">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select-all" onchange="toggleAllChecks(this.checked)">
                            </div>
                        </th>
                        @endif
                        <th style="width: 10px">ID</th>
                        <th>Voucher #</th>
                        <th>Customer</th>
                        <th>Truck No</th>
                        <th>Source</th>
                        <th>Destination</th>
                        <th>Payment Type</th>
                        <th>Payment Mode</th>
                        <th>Amount</th>
                        <th>Status</th>
                        @if(auth()->check() && auth()->user()->userRole->name === 'admin')
                        <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr class="align-middle">
                            @if(auth()->user()->userRole->name === 'admin')
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input row-check" type="checkbox" value="{{ $payment->id }}" data-payment-id="{{ $payment->id }}">
                                </div>
                            </td>
                            @endif
                            <td>{{ $payment->id }}</td>
                            <td><strong>{{ $payment->voucher_number }}</strong></td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->customer)
                                    <span class="badge bg-info">{{ $payment->freight_booking->lr->customer->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->truck)
                                    <span class="badge bg-primary">{{ $payment->freight_booking->lr->truck->truck_number }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->consignor && $payment->freight_booking->lr->consignor->city)
                                    <span class="badge bg-success">{{ $payment->freight_booking->lr->consignor->city->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->consignee && $payment->freight_booking->lr->consignee->city)
                                    <span class="badge bg-warning">{{ $payment->freight_booking->lr->consignee->city->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ ucfirst($payment->payment_type) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</td>
                            <td class="text-success fw-bold">₹{{ number_format($payment->amount, 2) }}</td>
                            <td>{!! $payment->getPaymentStatusBadge() !!}</td>
                            @if(auth()->check() && auth()->user()->userRole->name === 'admin')
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('voucher_payments.edit', $payment) }}?lorry_receipt_id={{ $payment->freight_booking ? $payment->freight_booking->lr_id : '' }}&freight_booking_id={{ $payment->freight_booking_id }}" class="btn btn-sm btn-info" data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Edit Payment">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('voucher_payments.destroy', $payment) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Delete Payment" onclick="return confirm('Delete this payment?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="empty-state">
                                    <i class="bi bi-box"></i>
                                    <p class="mt-2">No payments found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $payments->links() }}
        </div>
    </div>
</div>

<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        const field = document.getElementById(fieldId);
        const input = field.querySelector('input, select');

        if (checkbox.checked) {
            field.classList.remove('d-none');
        } else {
            field.classList.add('d-none');
            if (input) input.value = '';
        }
    }

    // Handle select all/deselect all
    function toggleAllChecks(checked) {
        const checkboxes = document.querySelectorAll('.row-check');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
    }

    // Get selected payment IDs
    function getSelectedPayments() {
        const checkboxes = document.querySelectorAll('.row-check:checked');
        return Array.from(checkboxes).map(checkbox => checkbox.value);
    }

    // Initialize filter fields based on URL parameters
    document.addEventListener('DOMContentLoaded', function() {
        // Get all filter checkboxes
        const checkboxes = document.querySelectorAll('.form-check-input');
        
        // Check each checkbox based on URL parameters
        checkboxes.forEach(checkbox => {
            const value = checkbox.value;
            const fieldId = 'field_' + value;
            const field = document.getElementById(fieldId);
            const urlValue = new URLSearchParams(window.location.search).get(value);
            
            if (urlValue) {
                checkbox.checked = true;
                field.classList.remove('d-none');
            }
        });
    });

    // Update bulk update button visibility
    function updateBulkButtonVisibility() {
        const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
        const selectedCount = document.querySelectorAll('.row-check:checked').length;
        bulkUpdateBtn.classList.toggle('d-none', selectedCount === 0);
    }

    // Show bulk update modal
    function showBulkUpdateModal() {
        const selectedPayments = getSelectedPayments();
        if (selectedPayments.length === 0) {
            alert('Please select at least one payment to update');
            return;
        }

        const modal = new bootstrap.Modal(document.getElementById('bulkUpdateModal'));
        modal.show();
    }

    // Submit bulk update
    async function submitBulkUpdate() {
        const selectedPayments = getSelectedPayments();
        const newStatus = document.getElementById('bulkPaymentStatus').value;

        if (!newStatus) {
            alert('Please select a payment status');
            return;
        }

        if (!confirm(`Are you sure you want to update ${selectedPayments.length} payment(s) to status: ${newStatus}?`)) {
            return;
        }

        try {
            const response = await fetch('{{ route('voucher_payments.bulkUpdateStatus') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    _token: '{{ csrf_token() }}',
                    payment_ids: selectedPayments,
                    new_status: newStatus
                })
            });

            const result = await response.json();

            if (response.ok) {
                alert('Payment status updated successfully');
                window.location.reload();
            } else {
                alert(result.message || 'Failed to update payment status');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while updating payment status');
        }
    }
</script>
    <script>
        // Toggle all checkboxes
        function toggleAllChecks(checked) {
            document.querySelectorAll('.row-check').forEach(checkbox => {
                checkbox.checked = checked;
            });
            updateBulkUpdateButton();
        }

        // Update bulk update button visibility based on selected rows
        function updateBulkUpdateButton() {
            const selectedChecks = document.querySelectorAll('.row-check:checked');
            const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
            
            if (selectedChecks.length > 0) {
                bulkUpdateBtn.classList.remove('d-none');
            } else {
                bulkUpdateBtn.classList.add('d-none');
            }
        }

        // Show bulk update modal
        function showBulkUpdateModal() {
            const selectedChecks = document.querySelectorAll('.row-check:checked');
            if (selectedChecks.length === 0) {
                alert('Please select at least one payment to update');
                return;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('bulkUpdateModal'));
            modal.show();
        }

        // Submit bulk update
        async function submitBulkUpdate() {
            const form = document.getElementById('bulkUpdateForm');
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const selectedChecks = document.querySelectorAll('.row-check:checked');
            const paymentIds = Array.from(selectedChecks).map(checkbox => checkbox.value);
            const newStatus = document.getElementById('bulkPaymentStatus').value;

            try {
                const response = await fetch('{{ route('voucher_payments.bulkUpdateStatus') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        payment_ids: paymentIds,
                        new_status: newStatus
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    alert('Payment statuses updated successfully');
                    window.location.reload();
                } else {
                    alert(result.message || 'Failed to update payment statuses');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating payment statuses');
            }
        }

        // Add event listeners to individual checkboxes
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.row-check').forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkUpdateButton);
            });
        });
    </script>
@endsection
