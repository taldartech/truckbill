@extends('layouts.app')

@section('content')

    <style>
        /* Prevent form expansion on validation errors */
        .form-control,
        .form-select {
            min-height: 38px;
            box-sizing: border-box;
        }

        .text-danger {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            word-wrap: break-word;
        }

        /* FIX: Removed this CSS override as it interferes with Bootstrap's grid */
        /*
        .row .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
        */

        /* Prevent card from expanding */
        .card {
            overflow: hidden;
        }

        /* Consistent spacing for form groups */
        .mb-3 {
            margin-bottom: 1rem !important;
        }
    </style>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="container px-0 mt-4" id="preFormContainer">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Select Customer & Memo to Proceed</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer *</label>
                                <select id="preCustomerSelect" style="width:100%" required>
                                    <option value="">Select</option>
                                    @foreach ($consignees as $consignee)
                                        <option value="{{ $consignee->id }}">{{ $consignee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Memo ID</label>
                                <div class="input-group">
                                    {{-- <input type="text" id="preMemoId" class="form-control" placeholder="Optional"> --}}
                                    <button class="btn btn-outline-secondary" type="button" id="showMemoOptions">Show
                                        Memos</button>
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
                        <button type="button" class="btn btn-primary" id="showInvoiceFormBtn" disabled>Next-></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto; display: none;"
        id="mainInvoiceForm">

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
                                    <strong>Location:</strong> <span id="selectedLocation" name="location"></span>
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
                        <label class="form-label">Manual Invoice #</label>
                        <input type="text" placeholder="Invoice Number" id="manual_invoice_number"
                            name="manual_invoice_number"
                            class="form-control @error('manual_invoice_number') is-invalid @enderror"
                            value="{{ old('manual_invoice_number') }}" required>

                        {{-- Add this feedback div --}}
                        <div id="manual-invoice-number-feedback"></div>

                        @error('manual_invoice_number')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Invoice Type *</label>
                        <select class="form-select" disabled>
                            <option value="Forward" {{ ($company->gst_scheme ?? '') == 'forward charge' ? 'selected' : '' }}>
                                Forward</option>
                            <option value="Reverse" {{ ($company->gst_scheme ?? '') == 'reverse charge' ? 'selected' : '' }}>
                                Reverse</option>
                        </select>
                        <input type="hidden" name="invoice_type"
                            @if ($company->gst_scheme == 'reverse charge') value="Reverse" @else value="Forward" @endif>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Generated Date *</label>
                        <input type="date" placeholder="Generated Date" name="generated_date" class="form-control"
                            required value="{{ date('Y-m-d') }}">
                        @error('generated_date')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a date.</div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="form-label">Customer *</label>
                        {{-- <select name="customer_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach ($consignees as $consignee)
                                <option value="{{ $consignee->id }}" {{ old('customer_id') == $consignee->id ? 'selected' : '' }}>{{ $consignee->name }}</option>
                            @endforeach
                        </select> --}}
                        {{-- Hidden input to store selected customer_id --}}
                        <input type="hidden" name="customer_id" id="prefilledCustomerId" value="{{ old('customer_id') }}">


                        <input type="text" id="prefilledCustomerName" class="form-control" readonly required
                            value="{{ old('customer_name') }}">

                        @error('customer_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Amount *</label>
                        <input type="number" placeholder="Total Amount" name="total_amount" class="form-control"
                            step="0.01" min="0" required readonly value="{{ old('total_amount') }}">
                        @error('total_amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please enter a valid amount.</div>
                    </div>
                    <input type="hidden" name="payment_status" value="pending">
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-8" id="memoDiv" style="display: none;">
                        <label class="form-label">Memos</label>
                        <select name="memo_ids[]" class="form-select" id="memoSelect" multiple>
                            </select>
                        <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple.</small>
                        @error('memo_ids')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <input type="hidden" name="sgst" id="sgstInput" value="0">
            <input type="hidden" name="cgst" id="cgstInput" value="0">
            <input type="hidden" name="igst" id="igstInput" value="0">
            <input type="hidden" name="net_amount" id="netAmountInput" value="0">

            <div class="card-footer">
                <button class="btn btn-info" type="submit">Generate Invoice</button>
            </div>
        </form>
    </div>

    <div class="container px-0 mt-4" id="lrDetailsContainer" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Selected LR Details</h3>
            </div>
            <div class="card-body">
                <div id="lrDetailsContent"></div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Choices('#preCustomerSelect', {
                searchEnabled: true, // enable search box
                itemSelectText: '', // hide "press to select" text
                shouldSort: false, // keep original order
                placeholderValue: 'Select Customer',
                searchPlaceholderValue: 'Type to search...',
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

            // This listener is now on the *pre-form* select, but the logic inside
            // was trying to find the *main-form* select.
            // This script block seems to have conflicting logic with the one below.
            // Let's assume the one below is the correct one for the pre-form.
            // This block for 'select[name="customer_id"]' is probably leftover.
            
            // Commenting out this conflicting listener.
            /*
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
            */
        });
    </script>


    <script>
        // Manual Invoice Number uniqueness validation
        const manualInvoiceInput = document.getElementById('manual_invoice_number');
        const manualInvoiceFeedback = document.getElementById('manual-invoice-number-feedback');
        let manualInvoiceTimeout;

        function checkManualInvoiceUniqueness(manualNumber) {
            // ... (rest of the function is fine) ...
            manualInvoiceFeedback.innerHTML = '<small class="text-info">🔍 Checking availability...</small>';
            manualInvoiceInput.classList.remove('is-valid', 'is-invalid');

            fetch('/api/check-manual-invoice-number-uniqueness', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    manual_invoice_number: manualNumber
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

        // Add debounced input listener for Manual Invoice Number
        manualInvoiceInput.addEventListener('input', function() {
            const manualNumber = this.value.trim();
            clearTimeout(manualInvoiceTimeout);
            manualInvoiceTimeout = setTimeout(() => {
                checkManualInvoiceUniqueness(manualNumber);
            }, 500); // 500ms delay
        });

        // Check on blur as well
        manualInvoiceInput.addEventListener('blur', function() {
            const manualNumber = this.value.trim();
            if (manualNumber) {
                clearTimeout(manualInvoiceTimeout);
                checkManualInvoiceUniqueness(manualNumber);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const showInvoiceFormBtn = document.getElementById('showInvoiceFormBtn');
            const mainFormContainer = document.getElementById('mainInvoiceForm');
            const preFormContainer = document.getElementById('preFormContainer');
            const preCustomerSelect = document.getElementById('preCustomerSelect');
            // const preMemoId = document.getElementById('preMemoId'); // This was commented out in HTML
            const showMemoOptionsBtn = document.getElementById('showMemoOptions');
            const memoOptionsContainer = document.getElementById('memoOptionsContainer');
            const memoCheckboxes = document.getElementById('memoCheckboxes');

            // Load memos when customer is selected
            preCustomerSelect.addEventListener('change', function() {
                const customerId = this.value;
                if (!customerId) {
                    memoOptionsContainer.style.display = 'none';
                    showInvoiceFormBtn.disabled = true;
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
                                    <input class="form-check-input memo-checkbox" type="checkbox" value="${memo.id}" id="memo_${memo.id}">
                                    <label class="form-check-label" for="memo_${memo.id}">
                                        ${memo.memo_number}
                                    </label>
                                `;
                                memoCheckboxes.appendChild(checkbox);
                            });
                            // memoOptionsContainer.style.display = 'block';
                            showInvoiceFormBtn.disabled = true; // keep disabled until user selects

                            // Add change listener to each checkbox
                            memoCheckboxes.querySelectorAll('.memo-checkbox').forEach(cb => {
                                cb.addEventListener('change', function() {
                                    const anyChecked = memoCheckboxes.querySelectorAll(
                                        '.memo-checkbox:checked').length > 0;
                                    showInvoiceFormBtn.disabled = !
                                anyChecked; // enable only if at least one checked
                                });
                            });
                        } else {
                            memoCheckboxes.innerHTML = '<p class="text-muted">No available memos found for this customer.</p>';
                            // memoOptionsContainer.style.display = 'block'; // Show the message
                            showInvoiceFormBtn.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error("Error loading memos:", error);
                        memoOptionsContainer.style.display = 'none';
                    });
            });

            // Show/Hide memo options when button is clicked
            showMemoOptionsBtn.addEventListener('click', function() {
                memoOptionsContainer.style.display = memoOptionsContainer.style.display === 'none' ? 'block' :
                    'none';
            });

            showInvoiceFormBtn.addEventListener('click', function() {
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
                const selectedMemos = Array.from(memoCheckboxes.querySelectorAll(
                        'input[type="checkbox"]:checked'))
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
                            let grandTotalAmount = 0;
                            // Prefill customer select field when form is shown
                            /*
                            // This logic is flawed. It's trying to find the *main form* customer select
                            // which is now a readonly input.
                            // The prefill logic is now handled below with the readonly inputs.
                            showInvoiceFormBtn.addEventListener('click', function() {
                                const selectedCustomerName = document.getElementById('selectedCustomerName').textContent;
                                const customerSelect = document.querySelector('select[name="customer_id"]');

                                
                                // Find the option that matches the selected customer name
                                const selectedOption = Array.from(customerSelect.options).find(option => 
                                    option.text === selectedCustomerName
                                );
                                
                                if (selectedOption) {
                                    customerSelect.value = selectedOption.value;
                                    customerSelect.dispatchEvent(new Event('change'));
                                }
                            });
                            */

                            for (const memoId in data) {
                                if (data[memoId].length > 0) {
                                    const totalAmount = data[memoId].reduce((sum, lr) => sum +
                                        parseFloat(lr.amount || 0), 0);
                                    grandTotalAmount += totalAmount;
                                    // FIX: Added <div class="table-responsive">
                                    tableHtml += `
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Memo ${memoId} Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
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
                                                    <tr>
                                                        <td colspan="7" class="text-end fw-bold">Total Amount:</td>
                                                        <td class="fw-bold">${totalAmount.toFixed(2)}</td>
                                                        <td colspan="2"></td>
                                                    </tr>
                                                </tbody>
                                                
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `;
                                }
                            }

                            if (tableHtml !== '') {
                                tableHtml += `
                                <div class="card border-dark">
                                    <div class="card-body text-end">
                                        <h5 class="mb-0 fw-bold">Grand Total Amount: ₹${grandTotalAmount.toFixed(2)}</h5>
                                    </div>
                                </div>
                            `;

                                const invoiceTypeInput = document.querySelector(
                                    'input[name="invoice_type"]');
                                const customerId = document.getElementById('prefilledCustomerId')
                                .value; // get selected customer

                                // Fetch state again before calculating taxes
                                fetch(`/consignees/${customerId}`, {
                                        cache: 'no-store',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        const state = data.state?.toLowerCase() || 'n/a';
                                        document.getElementById('selectedLocation').textContent = data
                                            .state || 'N/A';
                                        console.log("state fetched for tax calculation:", state);

                                        if (invoiceTypeInput) {
                                            const invoiceType = invoiceTypeInput.value.trim()
                                                .toLowerCase();

                                            if (state === "rajasthan") {

                                                if (invoiceType === "forward") {
                                                    const sgst = grandTotalAmount * 0.025;
                                                    const cgst = grandTotalAmount * 0.025;
                                                    const netTotal = grandTotalAmount + sgst + cgst;


                                                    document.getElementById('sgstInput').value = sgst
                                                        .toFixed(2);
                                                    document.getElementById('cgstInput').value = cgst
                                                        .toFixed(2);
                                                    document.getElementById('igstInput').value = 0;
                                                    document.getElementById('netAmountInput').value =
                                                        netTotal.toFixed(2);

                                                    tableHtml += `
                                                        <div class="card border-dark">
                                                            <div class="card-body text-end">
                                                                <p class="mb-1" name="sgst">SGST (2.5%): ₹${sgst.toFixed(2)}</p>
                                                                <p class="mb-1" name="cgst">CGST (2.5%): ₹${cgst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" name="net_amount">Net Total: ₹${netTotal.toFixed(2)}</h5>
                                                            </div>
                                                        </div>
                                                    `;
                                                    document.querySelector(
                                                            'input[name="total_amount"]').value =
                                                        netTotal.toFixed(2);
                                                } else {
                                                    const sgst = grandTotalAmount * 0.025;
                                                    const cgst = grandTotalAmount * 0.025;
                                                    const gst = grandTotalAmount * 0.05; // if IGST applies under RCM
                                                    const netTotal = grandTotalAmount + gst;

                                                    document.getElementById('sgstInput').value = sgst
                                                        .toFixed(2);
                                                    document.getElementById('cgstInput').value = cgst
                                                        .toFixed(2);
                                                    document.getElementById('igstInput').value = 0;
                                                    document.getElementById('netAmountInput').value =
                                                        netTotal.toFixed(2);

                                                    tableHtml += `
                                                        <div class="card border-dark">
                                                            <div class="card-body text-end">
                                                                <p class="mb-1" name="sgst">SGST (2.5%): ₹${sgst.toFixed(2)}</p>
                                                                <p class="mb-1" name="cgst">CGST (2.5%): ₹${cgst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" name="net_amount"> Total: ₹${netTotal.toFixed(2)}</h5>

                                                                <p class="mb-1" name="gst">Tax under RCM: ₹${gst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" >Net Total: ₹${grandTotalAmount.toFixed(2)}</h5>
                                                                
                                                            </div>
                                                        </div>
                                                    `;
                                                    const totalAmountInput = document
                                                        .querySelector(
                                                            'input[name="total_amount"]');
                                                    if (totalAmountInput) {
                                                        totalAmountInput.value = grandTotalAmount
                                                            .toFixed(2);
                                                    }
                                                    // document.querySelector('input[name="total_amount"]').value = grandTotalAmount.toFixed(2);
                                                }
                                            } else {
                                                if (invoiceType === "forward") {
                                                    const igst = grandTotalAmount * 0.05;
                                                    const netTotal = grandTotalAmount + igst;

                                                    document.getElementById('sgstInput').value = 0;
                                                    document.getElementById('cgstInput').value = 0;
                                                    document.getElementById('igstInput').value = igst
                                                        .toFixed(2);
                                                    document.getElementById('netAmountInput').value =
                                                        netTotal.toFixed(2);

                                                    tableHtml += `
                                                        <div class="card border-dark">
                                                            <div class="card-body text-end">
                                                                <p class="mb-1" name="igst">IGST (5%): ₹${igst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" name="net_amount">Net Total: ₹${netTotal.toFixed(2)}</h5>
                                                            </div>
                                                        </div>
                                                    `;
                                                    document.querySelector(
                                                            'input[name="total_amount"]').value =
                                                        netTotal.toFixed(2);
                                                } else {
                                                    const igst = grandTotalAmount * 0.05; // RCM
                                                    const netTotal = grandTotalAmount + igst;

                                                    document.getElementById('sgstInput').value = 0;
                                                    document.getElementById('cgstInput').value = 0;
                                                    document.getElementById('igstInput').value = igst
                                                        .toFixed(2);
                                                    document.getElementById('netAmountInput').value =
                                                        netTotal.toFixed(2);

                                                    tableHtml += `
                                                        <div class="card border-dark">
                                                            <div class="card-body text-end">
                                                                <p class="mb-1" name="igst">IGST (5%): ₹${igst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" name="net_amount"> Total: ₹${netTotal.toFixed(2)}</h5>
                                                                <p class="mb-1" name="gst">Tax under RCM: ₹${igst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold">Net Total: ₹${grandTotalAmount.toFixed(2)}</h5>
                                                                
                                                            </div>
                                                        </div>
                                                    `;
                                                    document.querySelector(
                                                            'input[name="total_amount"]').value =
                                                        grandTotalAmount.toFixed(2);
                                                }
                                            }
                                        }

                                        // Update LR details with tax info
                                        document.getElementById('lrDetailsContent').innerHTML =
                                            `<div class="row">${tableHtml}</div>`;
                                    })
                                    .catch(err => {
                                        console.error("Error fetching customer state for tax:", err);
                                    });
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

                // Prefill hidden and readonly customer fields
                document.getElementById('prefilledCustomerId').value = selectedCustomerId;
                document.getElementById('prefilledCustomerName').value = customerName;

                fetch(`/consignees/${selectedCustomerId}`, {
                        cache: 'no-store',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('selectedLocation').textContent = data.state || 'N/A';
                    })
                    .catch(err => {
                        console.error("Error fetching consignee location:", err);
                        document.getElementById('selectedLocation').textContent = 'N/A';
                    });

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
@endsection