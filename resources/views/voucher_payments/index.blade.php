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
        <a href="{{ route('voucher_payments.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
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
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Voucher Payments List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Voucher #</th>
                        <th>Payment Type</th>
                        <th>Payment Mode</th>
                        <th>Transaction ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr class="align-middle">
                            <td>{{ $payment->id }}</td>
                            <td><strong>{{ $payment->voucher->voucher_number }}</strong></td>
                            <td>{{ ucfirst($payment->payment_type) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</td>
                            <td>{{ $payment->transaction_id }}</td>
                            <td class="text-success fw-bold">₹{{ number_format($payment->amount, 2) }}</td>
                            <td>{!! $payment->getPaymentStatusBadge() !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('voucher_payments.edit', $payment) }}" class="btn btn-sm btn-info">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('voucher_payments.destroy', $payment) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">
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
        if (checkbox.checked) {
            field.classList.remove('d-none');
        } else {
            field.classList.add('d-none');
            const input = field.querySelector('input, select');
            if (input) input.value = '';
        }
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
</script>
@endsection
