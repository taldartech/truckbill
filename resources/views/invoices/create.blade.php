@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Add Invoice</h2>
    <form method="POST" action="{{ route('invoices.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Manual Invoice #</label>
                <input type="text" name="manual_invoice_number" class="form-control" value="{{ old('manual_invoice_number') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Company *</label>
                <select name="company_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Invoice Type *</label>
                <select name="invoice_type" class="form-select" required>
                    <option value="Forward" {{ old('invoice_type') == 'Forward' ? 'selected' : '' }}>Forward</option>
                    <option value="Reverse" {{ old('invoice_type') == 'Reverse' ? 'selected' : '' }}>Reverse</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Generated Date *</label>
                <input type="date" name="generated_date" class="form-control" required value="{{ old('generated_date') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Customer *</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Total Amount *</label>
                <input type="number" name="total_amount" class="form-control" step="0.01" min="0" required value="{{ old('total_amount') }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Payment Status *</label>
                <select name="payment_status" class="form-select" required>
                    <option value="pending" {{ old('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ old('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            {{-- <div class="col-md-8 mb-3" id="memoDiv" style="display: {{ old('customer_id') ? 'block' : 'none' }};">
                <label class="form-label">Memos</label>
                <select name="memo_ids[]" class="form-select" multiple>
                    @foreach($memos as $memo)
                        <option value="{{ $memo['id'] }}" {{ (collect(old('memo_ids'))->contains($memo['id'])) ? 'selected' : '' }}>{{ $memo['memo_number'] }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple.</small>
            </div> --}}
            <div class="col-md-8 mb-3" id="memoDiv" style="display: none;">
                <label class="form-label">Memos</label>
                <select name="memo_ids[]" class="form-select" id="memoSelect" multiple>
                    <!-- options will be loaded dynamically -->
                </select>
                <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple.</small>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('invoices.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>

    {{-- <script>
        document.addEventListener('DOMContentLoaded', function() {
            const customerSelect = document.querySelector('select[name="customer_id"]');
            const memoDiv = document.getElementById('memoDiv');

            customerSelect.addEventListener('change', function() {
                memoDiv.style.display = this.value ? 'block' : 'none';
            });
        });
    </script> --}}
    
    
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("🚀 Script loaded");
        const customerSelect = document.querySelector('select[name="customer_id"]');
        const memoDiv = document.getElementById('memoDiv');
        const memoSelect = document.getElementById('memoSelect');

        customerSelect.addEventListener('change', function () {
            const customerId = this.value;
            console.log("📦 Customer selected:", customerId);
            memoDiv.style.display = customerId ? 'block' : 'none';
            memoSelect.innerHTML = ''; // clear old options

            if (customerId) {
                fetch(`/invoices/memos/${customerId}`, {
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("✅ AJAX response:", data);
                    memoSelect.innerHTML = ''; // Clear existing options
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(memo => {
                            const option = document.createElement('option');
                            option.value = memo.id;
                            option.text = memo.memo_number;
                            option.selected = memo.selected;
                            memoSelect.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.value = '';
                        option.text = 'No memos found';
                        option.disabled = true;
                        memoSelect.appendChild(option);
                    }
                })
                .catch(error => {
                    console.error("❌ AJAX fetch error:", error);
                    memoSelect.innerHTML = '<option value="">Error loading memos</option>';
                });
            }
        });
    });
</script>
@endsection
