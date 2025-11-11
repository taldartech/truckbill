@extends('layouts.app')

@section('content')
{{-- Yeh styling block aapke Consignee code se liya gaya hai --}}
<style>
/* Prevent form expansion on validation errors */
.form-control, .form-select {
    min-height: 38px;
    box-sizing: border-box;
}

.text-danger {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    word-wrap: break-word;
}

/* Prevent card from expanding */
.card {
    overflow: hidden;
}

/* Consistent spacing for form groups */
.mb-3 {
    margin-bottom: 1rem !important;
}
</style>

{{-- Yeh layout (card) aapke Consignee code se liya gaya hai --}}
<div class="container-fluid px-0">
    <div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
        <div class="card-header">
            {{-- Title aapke Invoice code se --}}
            <div class="card-title h4">Edit Invoice</div>
        </div>
        
        {{-- Form aapke Invoice code se --}}
        <form method="POST" action="{{ route('invoices.update', $invoice->id) }}">
            @csrf
            @method('PUT')
            
            {{-- Card-body wrapper --}}
            <div class="card-body">
                
                {{-- ALIGNMENT FIX: Sabhi columns mein col-12 add kiya gaya hai --}}
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="company" class="form-label">Company *</label>
                        <input type="text" class="form-control" disabled id="company"
                            value="{{ $invoice->company ? $invoice->company->name : 'N/A' }}">
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="invoice_type" class="form-label">Invoice Type *</label>
                        <input type="text" class="form-control" disabled id="invoice_type"
                            value="{{ ucfirst($invoice->invoice_type) }}">
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        {{-- ID FIX: Duplicate ID "generated_date" ko change kiya hai --}}
                        <label for="generated_date_display" class="form-label">Generated Date *</label>
                        <input type="date" id="generated_date_display" class="form-control" disabled
                            value="{{ old('generated_date', $invoice->generated_date) }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="customer" class="form-label">Customer *</label>
                        <input type="text" class="form-control" disabled id="customer"
                            value="{{ $invoice->customer_id ? $invoice->consignee->name : 'N/A' }}">
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="total_amount" class="form-label">Total Amount *</label>
                        <input type="number" id="total_amount" class="form-control" disabled
                            value="{{ $invoice->total_amount }}">
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="memos" class="form-label">Memos</label>
                        <input type="text" id="memos" class="form-control" disabled
                            value="{{ implode(',', $invoice->memo_ids) }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="form-label">Manual Invoice #</label>
                        <input type="text" placeholder="Invoice Number" id="manual_invoice_number" name="manual_invoice_number"
                            class="form-control @error('manual_invoice_number') is-invalid @enderror"
                            value="{{ old('manual_invoice_number', $invoice->manual_invoice_number) }}" required>
                        
                        <div id="manual-invoice-number-feedback"></div>
                        
                        @error('manual_invoice_number') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="generated_date" class="form-label">Generated Date *</label>
                        <input type="date" name="generated_date" class="form-control" required id="generated_date"
                            @if ($invoice->payment_status != 'pending') disabled @endif
                            value="{{ old('generated_date', $invoice->generated_date) }}">
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="payment_status" class="form-label">Payment Status *</label>
                        <select name="payment_status" class="form-select" required id="payment_status">
                            @if ($invoice->payment_status == 'pending')
                                <option value="pending"
                                    {{ old('payment_status', $invoice->payment_status) == 'pending' ? 'selected' : '' }}>Pending
                                </option>
                            @endif
                            @if ($invoice->payment_status != 'paid')
                                <option value="partial"
                                    {{ old('payment_status', $invoice->payment_status) == 'partial' ? 'selected' : '' }}>
                                    Partial
                                </option>
                            @endif
                            <option value="paid"
                                {{ old('payment_status', $invoice->payment_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                </div>
                
            </div>
            
            {{-- Card footer Consignee file jaisa --}}
            <div class="card-footer">
                {{-- Buttons aapke Invoice file se (icons add kar diye hain) --}}
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Update
                </button>
                <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
{{-- SCRIPT SYNTAX FIX: Nested @section aur extra <script> tag hata diya hai --}}
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