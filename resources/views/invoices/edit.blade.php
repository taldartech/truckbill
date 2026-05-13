@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h2>Edit Invoice</h2>
        <form method="POST" action="{{ route('invoices.update', $invoice->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="company" class="form-label">Company *</label>
                    <input type="text" class="form-control" disabled id="company"
                        value="{{ $invoice->company ? $invoice->company->name : 'N/A' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="invoice_type" class="form-label">Invoice Type *</label>
                    <input type="text" class="form-control" disabled id="invoice_type"
                        value="{{ ucfirst($invoice->invoice_type) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="generated_date" class="form-label">Generated Date *</label>
                    <input type="date" id="generated_date" class="form-control" disabled
                        value="{{ old('generated_date', $invoice->generated_date) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="customer" class="form-label">Customer *</label>
                    <input type="text" class="form-control" disabled id="customer"
                        value="{{ $invoice->customer_id ? $invoice->consignee->name : 'N/A' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="total_amount" class="form-label">Total Amount *</label>
                    <input type="number" id="total_amount" class="form-control" disabled
                        value="{{ $invoice->total_amount }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="memos" class="form-label">Memos</label>
                    <input type="text" id="memos" class="form-control" disabled
                        value="{{ implode(',', is_array($invoice->memo_ids) ? $invoice->memo_ids : []) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Manual Invoice #</label>
                    <input type="text" placeholder="Invoice Number" id="manual_invoice_number" name="manual_invoice_number"
                        class="form-control @error('manual_invoice_number') is-invalid @enderror"
                        value="{{ old('manual_invoice_number', $invoice->manual_invoice_number) }}" required>

                    {{-- Add this feedback div --}}
                    <div id="manual-invoice-number-feedback"></div>

                    @error('manual_invoice_number')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="generated_date" class="form-label">Generated Date *</label>
                    <input type="date" name="generated_date" class="form-control" required id="generated_date"
                        @if ($invoice->payment_status != 'pending') disabled @endif
                        value="{{ old('generated_date', $invoice->generated_date) }}">
                </div>
            </div>
            <button type="submit" class="btn btn-brand-primary">Update</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-back ms-2">Back to List</a>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manual Invoice Number uniqueness validation
    const manualInvoiceInput = document.getElementById('manual_invoice_number');
    const manualInvoiceFeedback = document.getElementById('manual-invoice-number-feedback');
    let manualInvoiceTimeout;

    // Add null check for safety
    if (!manualInvoiceInput || !manualInvoiceFeedback) {
        console.error("Manual invoice input or feedback element not found");
        return;
    }

    function checkManualInvoiceUniqueness(manualNumber, invoiceId) {
        manualInvoiceFeedback.innerHTML = '<small class="text-info">🔍 Checking availability...</small>';
        manualInvoiceInput.classList.remove('is-valid', 'is-invalid');

        fetch('/api/check-manual-invoice-number-uniqueness', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                manual_invoice_number: manualNumber,
                invoice_id: invoiceId // Pass current invoice ID to ignore it
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                manualInvoiceFeedback.innerHTML = '<small class="text-success">✅ ' + data.message + '</small>';
                manualInvoiceInput.classList.remove('is-invalid');
                manualInvoiceInput.classList.add('is-valid');
                manualInvoiceInput.setCustomValidity('');
            } else {
                manualInvoiceFeedback.innerHTML = '<small class="text-danger">❌ ' + data.message + '</small>';
                manualInvoiceInput.classList.remove('is-valid');
                manualInvoiceInput.classList.add('is-invalid');
                manualInvoiceInput.setCustomValidity(data.message);
            }
        })
        .catch(error => {
            console.error('Error checking Manual Invoice Number uniqueness:', error);
            manualInvoiceFeedback.innerHTML = '<small class="text-warning">⚠️ Unable to check availability</small>';
            manualInvoiceInput.classList.remove('is-valid', 'is-invalid');
            manualInvoiceInput.setCustomValidity('');
        });
    }

    // Get current invoice ID from the form action URL or hidden field
    const invoiceId = {{ $invoice->id }};

    // Add debounced input listener for Manual Invoice Number
    manualInvoiceInput.addEventListener('input', function() {
        const manualNumber = this.value.trim();
        clearTimeout(manualInvoiceTimeout);
        manualInvoiceTimeout = setTimeout(() => {
            checkManualInvoiceUniqueness(manualNumber, invoiceId);
        }, 500);
    });

    // Check on blur as well
    manualInvoiceInput.addEventListener('blur', function() {
        const manualNumber = this.value.trim();
        if (manualNumber) {
            clearTimeout(manualInvoiceTimeout);
            checkManualInvoiceUniqueness(manualNumber, invoiceId);
        }
    });
});
</script>
@endsection

</script>
