@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-sm-8">
            <h3 class="mb-0">Daily Payments</h3>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Date Selection -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Select Date</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('voucher_payments.daily') }}">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="ms-3">
                            <strong>Selected Date:</strong> {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments List -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Voucher Payments - {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</h3>
            <small class="text-muted">Total: {{ $payments->total() }} payments</small>
        </div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Voucher Number</th>
                        <th>LR Number</th>
                        <th>FR Number</th>
                        <th>Transporter</th>
                        <th>Truck No</th>
                        <th>Source</th>
                        <th>Destination</th>
                        <th>Payment Type</th>
                        <th>Payment Mode</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr class="align-middle">
                        <td>{{ $payment->id }}</td>
                        <td>
                            @if($payment->voucher_number)
                                {{ $payment->voucher_number }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->freight_booking && $payment->freight_booking->lr)
                                {{ $payment->freight_booking->lr->lr_number }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->freight_booking)
                                {{ $payment->freight_booking->id }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->freight_booking && $payment->freight_booking->transporter)
                                @if($payment->freight_booking->transporter->is_owner)
                                    <i class="bi bi-truck"></i> {{ $payment->freight_booking->transporter->name }}
                                @else
                                    <i class="bi bi-building"></i> {{ $payment->freight_booking->transporter->name }}
                                @endif
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
                                {{ $payment->freight_booking->lr->consignor->city->name }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->consignee && $payment->freight_booking->lr->consignee->city)
                                {{ $payment->freight_booking->lr->consignee->city->name }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->payment_type)
                                {{ ucfirst($payment->payment_type) }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->payment_mode)
                                {{ ucfirst($payment->payment_mode) }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($payment->amount)
                                <strong>₹{{ number_format($payment->amount, 2) }}</strong>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->payment_status)
                                @switch($payment->payment_status)
                                    @case('pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @break
                                    @case('in-progress')
                                        <span class="badge bg-info">In Progress</span>
                                        @break
                                    @case('paid')
                                        <span class="badge bg-success">Paid</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger">Cancelled</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ ucfirst($payment->payment_status) }}</span>
                                @endswitch
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <small>{{ $payment->created_at->setTimezone('Asia/Kolkata')->format('h:i A') }}</small>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionDropdown{{ $payment->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-gear"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="actionDropdown{{ $payment->id }}">
                                    <li><a class="dropdown-item" href="#" onclick="updateStatus({{ $payment->id }}, 'pending')"><i class="bi bi-hourglass text-warning"></i> Mark as Pending</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="updateStatus({{ $payment->id }}, 'in-progress')"><i class="bi bi-arrow-repeat text-info"></i> Mark as In Progress</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="updateStatus({{ $payment->id }}, 'paid')"><i class="bi bi-check-circle text-success"></i> Mark as Paid</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="updateStatus({{ $payment->id }}, 'cancelled')"><i class="bi bi-x-circle text-danger"></i> Mark as Cancelled</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="text-center">
                            <div class="empty-state">
                                <i class="bi bi-calendar-x fa-3x text-muted mb-3"></i>
                                <h5>No Payments Found</h5>
                                <p class="text-muted">No voucher payments were initiated on {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            
            <!-- Pagination -->
            @if($payments->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Summary Card -->
    @if($payments->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Payment Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-primary">{{ $payments->total() }}</h4>
                        <small class="text-muted">Total Payments</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-success">₹{{ number_format($payments->sum('amount'), 2) }}</h4>
                        <small class="text-muted">Total Amount</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-warning">{{ $payments->where('payment_status', 'pending')->count() }}</h4>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-info">{{ $payments->where('payment_status', 'paid')->count() }}</h4>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function updateStatus(paymentId, status) {
        if (confirm('Are you sure you want to update the payment status to ' + status + '?')) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("voucher_payments.update_status") }}';
            
            // CSRF Token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Method field
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'PUT';
            form.appendChild(methodField);
            
            // Payment ID
            const paymentIdField = document.createElement('input');
            paymentIdField.type = 'hidden';
            paymentIdField.name = 'payment_id';
            paymentIdField.value = paymentId;
            form.appendChild(paymentIdField);
            
            // Status
            const statusField = document.createElement('input');
            statusField.type = 'hidden';
            statusField.name = 'status';
            statusField.value = status;
            form.appendChild(statusField);
            
            // Redirect back
            const redirectField = document.createElement('input');
            redirectField.type = 'hidden';
            redirectField.name = 'redirect';
            redirectField.value = window.location.href;
            form.appendChild(redirectField);
            
            // Append to body and submit
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endpush
