@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
@endpush

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
                        <select id="preCustomerSelect" style="width:100%" required>
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Memo ID</label>
                        <div class="input-group">
                            {{-- <input type="text" id="preMemoId" class="form-control" placeholder="Optional"> --}}
                            <button class="btn btn-secondary" type="button" id="showMemoOptions">Show
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
        <form action="{{ route('invoices.store') }}" method="POST" class="needs-validation" id="invoiceCreateForm" novalidate="">
            @csrf
            @php
                $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
                $userCompanyId = auth()->user()->company_id ?? null;
            @endphp
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Reference No (Optional)</label>
                        <input type="text" placeholder="Invoice Number" id="manual_invoice_number"
                            name="manual_invoice_number"
                            class="form-control @error('manual_invoice_number') is-invalid @enderror"
                            value="{{ old('manual_invoice_number') }}">

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
                            <option value="Forward"
                                {{ ($company->gst_scheme ?? '') == 'forward charge' ? 'selected' : '' }}>Forward</option>
                            <option value="Reverse"
                                {{ ($company->gst_scheme ?? '') == 'reverse charge' ? 'selected' : '' }}>Reverse</option>
                        </select>
                        <!-- Hidden input to actually submit the value -->
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
            <input type="hidden" name="sgst" id="sgstInput" value="0">
            <input type="hidden" name="cgst" id="cgstInput" value="0">
            <input type="hidden" name="igst" id="igstInput" value="0">
            <input type="hidden" name="net_amount" id="netAmountInput" value="0">
            <input type="hidden" name="base_amount" id="baseAmountInput" value="0">

            <div class="card-footer">
                <button class="btn btn-brand-primary" type="submit">
                    <i class="bi bi-check2-circle me-1"></i>Generate invoice
                </button>
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
        document.addEventListener('DOMContentLoaded', function() {
            const invoiceCustomerPrefill = @json($invoiceCustomerSelectPrefill);
            const consigneeSearchUrl = @json(route('api.consignees.search'));

            function initPreCustomerSelect2() {
                if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
                    setTimeout(initPreCustomerSelect2, 50);

                    return;
                }

                const $sel = $('#preCustomerSelect');
                $sel.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Search customer…',
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: consigneeSearchUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1,
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || [],
                                pagination: {
                                    more: Boolean(data.pagination && data.pagination.more),
                                },
                            };
                        },
                        cache: true,
                    },
                });

                if (invoiceCustomerPrefill && invoiceCustomerPrefill.id) {
                    const opt = new Option(invoiceCustomerPrefill.text, String(invoiceCustomerPrefill.id), true,
                        true);
                    $sel.append(opt).trigger('change');
                    window.invoiceCreatePendingCustomerId = String(invoiceCustomerPrefill.id);
                    if (typeof window.invoiceCreateLoadCustomerMemos === 'function') {
                        window.invoiceCreateLoadCustomerMemos(window.invoiceCreatePendingCustomerId);
                    }
                }
            }

            initPreCustomerSelect2();
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
            const customerSelect = document.querySelector('select[name="customer_id"]');
            const memoDiv = document.getElementById('memoDiv');
            const memoSelect = document.getElementById('memoSelect');

            // Legacy guard: this page now uses hidden #prefilledCustomerId instead of
            // a visible select[name="customer_id"] in the main form.
            if (!customerSelect || !memoDiv || !memoSelect) {
                return;
            }

            customerSelect.addEventListener('change', function() {
                const customerId = this.value;
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

    <script>
        // Manual Invoice Number uniqueness validation
        const gst_slab = @json($company->gst_slab);
        const companyState = @json($company->state ? strtolower($company->state->name) : '');
        const manualInvoiceInput = document.getElementById('manual_invoice_number');
        const manualInvoiceFeedback = document.getElementById('manual-invoice-number-feedback');
        let manualInvoiceTimeout;

        function checkManualInvoiceUniqueness(manualNumber) {
            if (!manualNumber) {
                manualInvoiceFeedback.innerHTML = '<small class="text-muted">Optional field</small>';
                manualInvoiceInput.classList.remove('is-valid', 'is-invalid');
                manualInvoiceInput.setCustomValidity('');
                return;
            }

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
                    manualInvoiceFeedback.innerHTML =
                        '<small class="text-warning">⚠️ Unable to check availability</small>';
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
            const showMemoOptionsBtn = document.getElementById('showMemoOptions');
            const memoOptionsContainer = document.getElementById('memoOptionsContainer');
            const memoCheckboxes = document.getElementById('memoCheckboxes');

            if (!showInvoiceFormBtn || !mainFormContainer || !preFormContainer || !preCustomerSelect ||
                !showMemoOptionsBtn || !memoOptionsContainer || !memoCheckboxes) {
                return;
            }

            function resetMemoSelection() {
                memoCheckboxes.innerHTML = '';
                memoOptionsContainer.style.display = 'none';
                showInvoiceFormBtn.disabled = true;
            }

            function bindMemoCheckboxState() {
                memoCheckboxes.querySelectorAll('.memo-checkbox').forEach(cb => {
                    cb.addEventListener('change', function() {
                        const anyChecked = memoCheckboxes.querySelectorAll('.memo-checkbox:checked').length > 0;
                        showInvoiceFormBtn.disabled = !anyChecked;
                    });
                });
            }

            function loadCustomerMemos(customerId) {
                if (!customerId) {
                    resetMemoSelection();

                    return;
                }

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
                            showInvoiceFormBtn.disabled = true; // keep disabled until user selects
                            bindMemoCheckboxState();
                        } else {
                            resetMemoSelection();
                        }
                    })
                    .catch(error => {
                        console.error("Error loading memos:", error);
                        resetMemoSelection();
                    });
            }

            window.invoiceCreateLoadCustomerMemos = loadCustomerMemos;

            // Load memos when customer is selected
            if (typeof $ !== 'undefined') {
                $('#preCustomerSelect')
                    .off('.invoiceCreateMemos')
                    .on('change.invoiceCreateMemos select2:select.invoiceCreateMemos', function() {
                        loadCustomerMemos($(this).val());
                    });
            } else {
                preCustomerSelect.addEventListener('change', function() {
                    loadCustomerMemos(this.value);
                });
            }

            const initialCustomerId = window.invoiceCreatePendingCustomerId || preCustomerSelect.value ||
                (typeof $ !== 'undefined' ? $('#preCustomerSelect').val() : '');
            if (initialCustomerId) {
                loadCustomerMemos(initialCustomerId);
            }

            // Show/Hide memo options when button is clicked
            showMemoOptionsBtn.addEventListener('click', function() {
                memoOptionsContainer.style.display = memoOptionsContainer.style.display === 'none' ?
                    'block' : 'none';
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
                            let lrCount = 0;
                            // Prefill customer select field when form is shown
                            showInvoiceFormBtn.addEventListener('click', function() {
                                const selectedCustomerName = document.getElementById(
                                    'selectedCustomerName').textContent;
                                const customerSelect = document.querySelector(
                                    'select[name="customer_id"]');

                                // Find the option that matches the selected customer name
                                const selectedOption = Array.from(customerSelect.options).find(
                                    option =>
                                    option.text === selectedCustomerName
                                );

                                if (selectedOption) {
                                    customerSelect.value = selectedOption.value;
                                    customerSelect.dispatchEvent(new Event('change'));
                                }
                            });

                            for (const memoId in data) {
                                if (data[memoId].length > 0) {
                                    lrCount += data[memoId].length;
                                    const totalAmount = data[memoId].reduce((sum, lr) => sum +
                                        parseFloat(lr.amount || 0), 0);
                                    grandTotalAmount += totalAmount;
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
                                                        <tr>
                                                        <td colspan="7" class="text-end fw-bold">Total Amount:</td>
                                                        <td class="fw-bold">${totalAmount.toFixed(2)}</td>
                                                        <td colspan="2"></td>
                                                    </tr>
                                                    </tbody>

                                                </table>
                                            </div>
                                        </div>
                                    `;
                                }
                            }

                            if (tableHtml !== '') {
                                tableHtml += `
                                    <div class="card border-dark">
                                        <div class="card-body text-end">
                                            <h5 class="mb-0 fw-bold">Grand Total Amount: ${grandTotalAmount.toFixed(2)}</h5>
                                        </div>
                                    </div>
                                `;

                                const invoiceTypeInput = document.querySelector(
                                    'input[name="invoice_type"]');
                                const customerId = document.getElementById('prefilledCustomerId')
                                    .value; // get selected customer

                                // Fetch state again before calculating taxes
                                fetch(`/api/consignees/${customerId}`, {
                                        cache: 'no-store',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        const state = data.state?.toLowerCase() || 'n/a';
                                        document.getElementById('selectedLocation').textContent =
                                            data.state || 'N/A';

                                        if (invoiceTypeInput) {
                                            document.getElementById('baseAmountInput').value = grandTotalAmount.toFixed(2);
                                            const invoiceType = invoiceTypeInput.value.trim()
                                                .toLowerCase();

                                            if (companyState && state === companyState) {

                                                if (invoiceType === "forward") {
                                                    const sgst = grandTotalAmount * (gst_slab /
                                                        100) / 2;
                                                    const cgst = grandTotalAmount * (gst_slab /
                                                        100) / 2;
                                                    const netTotal = grandTotalAmount + sgst + cgst;

                                                    document.getElementById('sgstInput').value =
                                                        sgst.toFixed(2);
                                                    document.getElementById('cgstInput').value =
                                                        cgst.toFixed(2);
                                                    document.getElementById('igstInput').value = 0;
                                                    document.getElementById('netAmountInput')
                                                        .value = netTotal.toFixed(2);

                                                    tableHtml += `
                                                        <div class="card border-dark">
                                                            <div class="card-body text-end">
                                                                <p class="mb-1" name="sgst">SGST (${gst_slab / 2}%): ${sgst.toFixed(2)}</p>
                                                                <p class="mb-1" name="cgst">CGST (${gst_slab / 2}%): ${cgst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" name="net_amount">Net Total: ${netTotal.toFixed(2)}</h5>
                                                            </div>
                                                        </div>
                                                    `;
                                                    document.querySelector(
                                                            'input[name="total_amount"]').value =
                                                        netTotal.toFixed(2);
                                                } else {
                                                    const sgst = grandTotalAmount * gst_slab / 200;
                                                    const cgst = grandTotalAmount * gst_slab / 200;
                                                    const gst = grandTotalAmount * gst_slab / 100; // if IGST applies under RCM
                                                    const netTotal = grandTotalAmount + gst;

                                                    document.getElementById('sgstInput').value =
                                                        sgst.toFixed(2);
                                                    document.getElementById('cgstInput').value =
                                                        cgst.toFixed(2);
                                                    document.getElementById('igstInput').value = 0;
                                                    document.getElementById('netAmountInput')
                                                        .value = netTotal.toFixed(2);

                                                    tableHtml += `
                                                        <div class="card border-dark">
                                                            <div class="card-body text-end">
                                                                <p class="mb-1" name="sgst">SGST (${gst_slab/2}%): ${sgst.toFixed(2)}</p>
                                                                        <p class="mb-1" name="cgst">CGST (${gst_slab/2}%): ${cgst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" name="net_amount"> Total: ${netTotal.toFixed(2)}</h5>

                                                                <p class="mb-1" name="gst">Tax under RCM: ${gst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" >Net Total: ${grandTotalAmount.toFixed(2)}</h5>

                                                            </div>
                                                        </div>
                                                    `;
                                                    const totalAmountInput = document.querySelector(
                                                        'input[name="total_amount"]');
                                                    if (totalAmountInput) {
                                                        totalAmountInput.value = grandTotalAmount
                                                            .toFixed(2);
                                                    }
                                                    // document.querySelector('input[name="total_amount"]').value = grandTotalAmount.toFixed(2);
                                                }
                                            } else {
                                                if (invoiceType === "forward") {
                                                    const igst = grandTotalAmount * gst_slab / 100;
                                                    const netTotal = grandTotalAmount + igst;

                                                    document.getElementById('sgstInput').value = 0;
                                                    document.getElementById('cgstInput').value = 0;
                                                    document.getElementById('igstInput').value =
                                                        igst.toFixed(2);
                                                    document.getElementById('netAmountInput')
                                                        .value = netTotal.toFixed(2);

                                                    tableHtml += `
                                                        <div class="card border-dark">
                                                            <div class="card-body text-end">
                                                                <p class="mb-1" name="igst">IGST (${gst_slab}%): ${igst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" name="net_amount">Net Total: ${netTotal.toFixed(2)}</h5>
                                                            </div>
                                                        </div>
                                                    `;
                                                    document.querySelector(
                                                            'input[name="total_amount"]').value =
                                                        netTotal.toFixed(2);
                                                } else {
                                                    const igst = grandTotalAmount * gst_slab / 100; // RCM
                                                    const netTotal = grandTotalAmount + igst;

                                                    document.getElementById('sgstInput').value = 0;
                                                    document.getElementById('cgstInput').value = 0;
                                                    document.getElementById('igstInput').value =
                                                        igst.toFixed(2);
                                                    document.getElementById('netAmountInput')
                                                        .value = netTotal.toFixed(2);

                                                    tableHtml += `
                                                        <div class="card border-dark">
                                                            <div class="card-body text-end">
                                                                <p class="mb-1" name="igst">IGST (${gst_slab}%): ${igst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold" name="net_amount"> Total: ${netTotal.toFixed(2)}</h5>
                                                                <p class="mb-1" name="gst">Tax under RCM: ${igst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fw-bold">Net Total: ${grandTotalAmount.toFixed(2)}</h5>

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

                                        window.invoiceCreateMeta = {
                                            lrCount: lrCount,
                                            memoCount: selectedMemos.length,
                                        };
                                    })
                                    .catch(err => {
                                        console.error("Error fetching customer state for tax:",
                                            err);
                                    });
                            }

                            document.getElementById('lrDetailsContainer').style.display = 'block';

                            // Update the LR details content
                            document.getElementById('lrDetailsContent').innerHTML = `
                                <div class="row">
                                    ${tableHtml}
                                </div>
                            `;

                            window.invoiceCreateMeta = {
                                lrCount: lrCount,
                                memoCount: selectedMemos.length,
                            };
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

                fetch(`/api/consignees/${selectedCustomerId}`, {
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

                window.invoiceCreateMeta = {
                    lrCount: 0,
                    memoCount: selectedMemos.length,
                };
            });
        });
    </script>

    @include('invoices.partials.confirmation-assets')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const invoiceCreateForm = document.getElementById('invoiceCreateForm');
            if (!invoiceCreateForm) {
                return;
            }

            const gstSlab = @json((int) ($company->gst_slab ?? 5));

            function getTaxLines() {
                const invoiceType = (document.querySelector('input[name="invoice_type"]')?.value || '').toLowerCase();
                const baseAmount = parseFloat(document.getElementById('baseAmountInput')?.value || '0') || 0;
                const sgst = parseFloat(document.getElementById('sgstInput')?.value || '0') || 0;
                const cgst = parseFloat(document.getElementById('cgstInput')?.value || '0') || 0;
                const igst = parseFloat(document.getElementById('igstInput')?.value || '0') || 0;
                const lines = [];

                if (sgst > 0) {
                    lines.push({ label: `SGST (${gstSlab / 2}%)`, amount: sgst });
                }
                if (cgst > 0) {
                    lines.push({ label: `CGST (${gstSlab / 2}%)`, amount: cgst });
                }
                if (igst > 0) {
                    lines.push({ label: `IGST (${gstSlab}%)`, amount: igst });
                }

                const netAmount = parseFloat(document.getElementById('netAmountInput')?.value || '0') || 0;
                if (invoiceType === 'reverse' && (sgst > 0 || cgst > 0 || igst > 0)) {
                    lines.push({
                        label: 'Tax under RCM',
                        amount: sgst + cgst + igst,
                        muted: true,
                    });
                }

                return { lines, baseAmount, netAmount };
            }

            invoiceCreateForm.addEventListener('submit', function(event) {
                if (!invoiceCreateForm.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    invoiceCreateForm.classList.add('was-validated');
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                invoiceCreateForm.classList.add('was-validated');

                const tax = getTaxLines();
                const invoiceType = document.querySelector('input[name="invoice_type"]')?.value || '';
                const totalAmount = parseFloat(document.querySelector('input[name="total_amount"]')?.value || '0') || 0;
                const meta = window.invoiceCreateMeta || { lrCount: 0, memoCount: 0 };
                const confirmationData = {
                    dialogTitle: 'Confirm invoice generation',
                    title: 'Review invoice before generating',
                    confirmText: 'Generate invoice',
                    customer: document.getElementById('prefilledCustomerName')?.value
                        || document.getElementById('selectedCustomerName')?.textContent
                        || '—',
                    generatedDate: document.querySelector('input[name="generated_date"]')?.value || '',
                    reference: document.getElementById('manual_invoice_number')?.value || '',
                    memos: document.getElementById('selectedMemoIds')?.textContent || '—',
                    lrMeta: meta.lrCount
                        ? `${meta.lrCount} LR(s) across ${meta.memoCount || 0} memo(s)`
                        : '—',
                    invoiceType: invoiceType,
                    subtotal: tax.baseAmount,
                    taxLines: tax.lines,
                    totalAmount: totalAmount,
                    netAmount: tax.netAmount,
                    totalLabel: invoiceType.toLowerCase() === 'forward' ? 'Net total' : 'Invoice total',
                };

                if (window.InvoiceConfirmation) {
                    window.InvoiceConfirmation.confirmInvoiceSummary(confirmationData).then(function(confirmed) {
                        if (confirmed) {
                            invoiceCreateForm.submit();
                        }
                    });
                } else if (window.confirm('Generate this invoice?')) {
                    invoiceCreateForm.submit();
                }
            });
        });
    </script>
@endsection
