@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title">Add New Invoice</h3>
        </div>
        <form action="{{ route('invoices.store') }}" method="POST" class="needs-validation" novalidate="">
            @csrf
            @php
                $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
                $userCompanyId = auth()->user()->company_id ?? null;
            @endphp
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="company_id" class="form-label">Company *</label>
                        @if($canEditCompany)
                            <select class="form-select" id="company_id" name="company_id" required>
                                <option value="">Select</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a company.</div>
                        @else
                            <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
                            <div class="form-control-plaintext">
                                {{ $companies->firstWhere('id', $userCompanyId)->name ?? '' }}
                            </div>
                        @endif
                        @error('company_id') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Manual Invoice #</label>
                        <input type="text" name="manual_invoice_number" class="form-control" value="{{ old('manual_invoice_number') }}">
                        @error('manual_invoice_number') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Invoice Type *</label>
                        <select name="invoice_type" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Forward" {{ old('invoice_type') == 'Forward' ? 'selected' : '' }}>Forward</option>
                            <option value="Reverse" {{ old('invoice_type') == 'Reverse' ? 'selected' : '' }}>Reverse</option>
                        </select>
                        @error('invoice_type') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                        <div class="invalid-feedback">Please select an invoice type.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Generated Date *</label>
                        <input type="date" name="generated_date" class="form-control" required value="{{ old('generated_date') }}">
                        @error('generated_date') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                        <div class="invalid-feedback">Please select a date.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        @error('customer_id') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Amount *</label>
                        <input type="number" name="total_amount" class="form-control" step="0.01" min="0" required value="{{ old('total_amount') }}">
                        @error('total_amount') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                        <div class="invalid-feedback">Please enter a valid amount.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Status *</label>
                        <select name="payment_status" class="form-select" required>
                            <option value="">Select</option>
                            <option value="pending" {{ old('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="partial" {{ old('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                        @error('payment_status') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                        <div class="invalid-feedback">Please select a payment status.</div>
                    </div>
                    <div class="col-md-8" id="memoDiv" style="display: none;">
                        <label class="form-label">Memos</label>
                        <select name="memo_ids[]" class="form-select" id="memoSelect" multiple>
                            <!-- options will be loaded dynamically -->
                        </select>
                        <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple.</small>
                        @error('memo_ids') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-info" type="submit">Submit form</button>
                <a href="{{ route('invoices.index') }}" class="btn btn-info">Back to List</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (() => {
        'use strict';

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation');

        // Loop over them and prevent submission
        Array.from(forms).forEach((form) => {
            form.addEventListener(
                'submit',
                (event) => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                },
                false,
            );
        });
    })();

    // Memo loading script
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
