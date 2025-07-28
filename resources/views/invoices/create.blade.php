@extends('layouts.app')

@section('content')
<div class="container mt-4" id="preFormContainer">
    <div class="card card-warning card-outline" style="width: 50%; margin: auto;">
        <div class="card-header">
            <h3 class="card-title">Select Customer & Memo to Proceed</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Customer *</label>
                    <select id="preCustomerSelect" class="form-select" required>
                        <option value="">Select</option>
                        @foreach($consignees as $consignee)
                            <option value="{{ $consignee->id }}">{{ $consignee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Memo ID</label>
                    <div class="input-group">
                        {{-- <input type="text" id="preMemoId" class="form-control" placeholder="Optional"> --}}
                        <button class="btn btn-outline-secondary" type="button" id="showMemoOptions">Show Memos</button>
                    </div>
                </div>
            </div>
            <div id="memoOptionsContainer" style="display: none; margin-top: 15px;">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Available Memos</h6>
                    </div>
                    <div class="card-body">
                        <div id="memoCheckboxes"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-center">
            <button type="button" class="btn btn-primary" id="showInvoiceFormBtn">OK</button>
        </div>
    </div>
</div>


<div class="container mt-4" id="mainInvoiceForm" style="display: none;">
    <div class="card card-info card-outline" style="width: 70%;align-items: center;margin: auto;">
        <div class="card-header">
            <h3 class="card-title">Add New Invoice</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <div class="alert alert-info mb-4">
                        <h5>Selected Information</h5>
                        <div id="selectedInfo">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Customer:</strong> <span id="selectedCustomerName"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Memo(s):</strong> <span id="selectedMemoIds"></span>
                                </div>
                            </div>
                            <div id="selectedLrNumbers" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
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
                    {{-- <div class="col-md-4">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($consignees as $consignee)
                                <option value="{{ $consignee->id }}" {{ old('customer_id') == $consignee->id ? 'selected' : '' }}>{{ $consignee->name }}</option>
                            @endforeach
                        </select>
                        @error('customer_id') 
                            <div class="text-danger">{{ $message }}</div> 
                        @enderror
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div> --}}
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

    <!-- LR Details Container -->
    <div class="container mt-4" id="lrDetailsContainer" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Selected LR Details</h3>
            </div>
            <div class="card-body">
                <div id="lrDetailsContent"></div>
            </div>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const showInvoiceFormBtn = document.getElementById('showInvoiceFormBtn');
        const mainFormContainer = document.getElementById('mainInvoiceForm');
        const preFormContainer = document.getElementById('preFormContainer');
        const preCustomerSelect = document.getElementById('preCustomerSelect');
        const preMemoId = document.getElementById('preMemoId');
        const showMemoOptionsBtn = document.getElementById('showMemoOptions');
        const memoOptionsContainer = document.getElementById('memoOptionsContainer');
        const memoCheckboxes = document.getElementById('memoCheckboxes');

        // Load memos when customer is selected
        preCustomerSelect.addEventListener('change', function() {
            const customerId = this.value;
            if (!customerId) {
                memoOptionsContainer.style.display = 'none';
                return;
            }

            fetch(`/invoices/memos/${customerId}`, {
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                memoCheckboxes.innerHTML = '';
                if (data && data.length > 0) {
                    data.forEach(memo => {
                        const checkbox = document.createElement('div');
                        checkbox.className = 'form-check mb-2';
                        checkbox.innerHTML = `
                            <input class="form-check-input" type="checkbox" value="${memo.id}" id="memo_${memo.id}">
                            <label class="form-check-label" for="memo_${memo.id}">
                                ${memo.memo_number}
                            </label>
                        `;
                        memoCheckboxes.appendChild(checkbox);
                    });
                    memoOptionsContainer.style.display = 'block';
                } else {
                    memoOptionsContainer.style.display = 'none';
                }
            })
            .catch(error => {
                console.error("Error loading memos:", error);
                memoOptionsContainer.style.display = 'none';
            });
        });

        // Show/Hide memo options when button is clicked
        showMemoOptionsBtn.addEventListener('click', function() {
            memoOptionsContainer.style.display = memoOptionsContainer.style.display === 'none' ? 'block' : 'none';
        });

        showInvoiceFormBtn.addEventListener('click', function () {
            const selectedCustomerId = preCustomerSelect.value;

            if (!selectedCustomerId) {
                alert("Please select a customer first.");
                return;
            }

            // Get customer name
            const selectedCustomer = Array.from(preCustomerSelect.options)
                .find(option => option.value === selectedCustomerId);
            const customerName = selectedCustomer ? selectedCustomer.text : '';

            // Get selected memos and their LR numbers
            const selectedMemos = Array.from(memoCheckboxes.querySelectorAll('input[type="checkbox"]:checked'))
                .map(cb => {
                    const label = document.querySelector(`label[for="memo_${cb.value}"]`);
                    return {
                        id: cb.value,
                        number: label ? label.textContent.trim() : ''
                    };
                });

            // Update display
            document.getElementById('selectedCustomerName').textContent = customerName;
            document.getElementById('selectedMemoIds').textContent = 
                selectedMemos.map(memo => memo.number).join(', ');

            // Fetch LR numbers for selected memos
            const memoIds = selectedMemos.map(memo => memo.id);
            if (memoIds.length > 0) {
                fetch(`/invoices/lrs/${memoIds.join(',')}`, {
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
    // Create a table for LR details
    let tableHtml = '';
    for (const memoId in data) {
        if (data[memoId].length > 0) {
            tableHtml += `
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Memo ${memoId} Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>LR Number</th>
                                    <th>Source City</th>
                                    <th>Destination City</th>
                                    <th>Transporter</th>
                                    <th>Consignor</th>
                                    <th>Consignee</th>
                                    <th>GR Date</th>
                                    <th>Amount</th>
                                    <th>Truck Number</th>
                                    <th>Weight</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data[memoId].map(lr => `
                                    <tr>
                                        <td>${lr.lr_number}</td>
                                        <td>${lr.source_city}</td>
                                        <td>${lr.destination_city}</td>
                                        <td>${lr.transporter}</td>
                                        <td>${lr.consignor}</td>
                                        <td>${lr.consignee}</td>
                                        <td>${lr.gr_date}</td>
                                        <td>${lr.amount}</td>
                                        <td>${lr.truck_number}</td>
                                        <td>${lr.weight}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
    }

    // Show the LR details container
    document.getElementById('lrDetailsContainer').style.display = 'block';
    
    // Update the LR details content
    document.getElementById('lrDetailsContent').innerHTML = `
        <div class="row">
            ${tableHtml}
        </div>
    `;
})

                .catch(error => {
                    console.error("Error fetching LR numbers:", error);
                    document.getElementById('selectedLrNumbers').innerHTML = `
                        <div class="row">
                            <div class="col-12">
                                <strong>LR Numbers:</strong> Error loading LR numbers
                            </div>
                        </div>
                    `;
                });
            }

            // Set value in the main invoice form's customer select
            const customerSelect = document.querySelector('select[name="customer_id"]');
            if (customerSelect) {
                customerSelect.value = selectedCustomerId;
                customerSelect.dispatchEvent(new Event('change')); // trigger memo load
            }

            // Get selected memos and set them in the main form
            if (selectedMemos.length > 0) {
                const memoSelect = document.getElementById('memoSelect');
                memoSelect.innerHTML = '';
                selectedMemos.forEach(memo => {
                    const option = document.createElement('option');
                    option.value = memo.id;
                    option.selected = true;
                    memoSelect.appendChild(option);
                });
            }

            // Hide pre-form, show main form
            preFormContainer.style.display = 'none';
            mainFormContainer.style.display = 'block';
        });
    });
</script>

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
