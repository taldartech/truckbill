@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <style>
        /* Mobile-responsive adjustments for Create Invoice */
        @media (max-width: 767.98px) {
            /* Headers and cards */
            .app-create-page .app-index-list-card > .card-header {
                padding: 0.45rem 0.65rem !important;
            }

            .app-create-page .app-index-list-card > .card-header .card-title,
            .app-create-page .card-header h3.card-title {
                font-size: 0.875rem !important;
            }

            .app-create-page .app-index-card-toolbar .btn,
            .app-create-page .app-index-header-btn {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.5rem !important;
            }

            .app-create-page .app-form-section__header {
                padding: 0.35rem 0.65rem !important;
            }

            .app-create-page .app-form-section__header h5,
            .app-create-page .app-form-section__header .app-form-section__title {
                font-size: 0.78rem !important;
            }

            .app-create-page .app-form-section__body {
                padding: 0.5rem 0.65rem !important;
            }

            /* Labels and validation feedback */
            .app-create-page .form-label {
                font-size: 0.72rem !important;
                font-weight: 600 !important;
                margin-bottom: 0.15rem !important;
            }

            .app-create-page .valid-feedback,
            .app-create-page .invalid-feedback,
            .app-create-page .text-danger,
            .app-create-page #manual-invoice-number-feedback {
                font-size: 0.7rem !important;
            }

            /* Form inputs, selects & buttons */
            .app-create-page .form-control,
            .app-create-page .form-select {
                font-size: 0.78rem !important;
                padding: 0.25rem 0.45rem !important;
                min-height: 32px !important;
                height: 32px !important;
                border-radius: 0.375rem !important;
                line-height: 1.3 !important;
            }

            .app-create-page input[type="date"].form-control {
                font-size: 0.75rem !important;
                padding: 0.2rem 0.35rem !important;
            }

            .app-create-page .select2-container .select2-selection--single {
                font-size: 0.78rem !important;
                min-height: 32px !important;
                height: 32px !important;
                border-radius: 0.375rem !important;
            }

            .app-create-page .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                font-size: 0.78rem !important;
                line-height: 30px !important;
                padding-left: 0.45rem !important;
                padding-right: 1.25rem !important;
            }

            .app-create-page .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
                height: 30px !important;
                width: 1.5rem !important;
            }

            #customerMemoRow {
                align-items: flex-end !important;
            }

            #showMemoOptions {
                font-size: 0.68rem !important;
                padding: 2px 8px !important;
                border-radius: 4px !important;
                height: 26px !important;
                min-height: 26px !important;
                line-height: 1 !important;
                width: auto !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                white-space: nowrap !important;
            }

            /* Step 1 Memos list */
            #memoOptionsContainer .app-form-section__title {
                font-size: 0.75rem !important;
            }

            #memoCheckboxes .form-check-label {
                font-size: 0.75rem !important;
            }

            #memoCheckboxes .form-check-input {
                width: 0.85rem !important;
                height: 0.85rem !important;
            }

            /* Selected Information summary */
            .app-create-page #selectedInfo {
                font-size: 0.75rem !important;
            }

            .app-create-page #selectedInfo strong {
                font-size: 0.75rem !important;
            }

            .app-create-page #selectedInfo span {
                font-size: 0.75rem !important;
            }

            /* Action buttons bar */
            .app-create-page .app-form-actions {
                margin-top: 0.5rem !important;
            }

            .app-create-page .app-form-actions .card-body {
                padding: 0.35rem 0.6rem !important;
            }

            .app-create-page .app-form-actions .btn {
                font-size: 0.78rem !important;
                padding: 0.28rem 0.6rem !important;
                border-radius: 0.375rem !important;
            }

            /* Selected LR Details container and Table */
            #lrDetailsContainer .card-title {
                font-size: 0.8rem !important;
            }

            #lrDetailsContainer .card-header {
                padding: 0.35rem 0.6rem !important;
            }

            #lrDetailsContainer .card-header h5.card-title {
                font-size: 0.75rem !important;
            }

            #lrDetailsContainer .table {
                font-size: 0.72rem !important;
                margin-bottom: 0 !important;
            }

            #lrDetailsContainer .table th {
                font-size: 0.7rem !important;
                padding: 0.25rem 0.35rem !important;
                font-weight: 600 !important;
                white-space: nowrap !important;
            }

            #lrDetailsContainer .table td {
                font-size: 0.72rem !important;
                padding: 0.25rem 0.35rem !important;
                white-space: nowrap !important;
            }

            #lrDetailsContainer .card-body p {
                font-size: 0.72rem !important;
                margin-bottom: 0.15rem !important;
            }

            #lrDetailsContainer .card-body h5 {
                font-size: 0.78rem !important;
                margin-bottom: 0 !important;
            }
        }

        /* Tablet adjustments */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .app-create-page .form-label {
                font-size: 0.78rem !important;
            }

            .app-create-page .form-control,
            .app-create-page .form-select {
                font-size: 0.8125rem !important;
                min-height: 34px !important;
                height: 34px !important;
                padding: 0.28rem 0.55rem !important;
            }

            .app-create-page .select2-container .select2-selection--single {
                font-size: 0.8125rem !important;
                min-height: 34px !important;
                height: 34px !important;
            }

            .app-create-page .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                font-size: 0.8125rem !important;
                line-height: 32px !important;
            }

            #lrDetailsContainer .table th,
            #lrDetailsContainer .table td {
                font-size: 0.78rem !important;
                padding: 0.3rem 0.45rem !important;
            }
        }

        /* Laptop & Desktop adjustments (>= 768px) */
        @media (min-width: 768px) {
            #customerMemoRow {
                align-items: flex-start !important;
            }
            #customerMemoRow .form-label {
                margin-top: 0 !important;
                margin-bottom: 0.5rem !important;
                line-height: 1.5 !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container mt-2 pt-1 pb-5 app-index-page app-create-page">
        {{-- Step 1: Select customer & memo --}}
        <div id="preFormContainer">
            <div class="card app-index-list-card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-receipt me-1"></i> Create Invoice
                    </h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <a href="{{ route('invoices.index') }}" class="btn app-index-header-btn" aria-label="Back to invoices">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="app-form-section">
                        <div class="app-form-section__header">
                            <h5 class="app-form-section__title">
                                <i class="bi bi-person-check"></i> Select Customer & Memo / POD
                            </h5>
                        </div>
                        <div class="app-form-section__body">
                            <div class="row g-2" id="customerMemoRow">
                                <div class="col col-md-6">
                                    <label class="form-label">Customer *</label>
                                    <select id="preCustomerSelect" style="width:100%" required>
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-auto col-md-6">
                                    <label class="form-label d-none d-md-block">Memo ID / POD</label>
                                    <div>
                                        {{-- <input type="text" id="preMemoId" class="form-control" placeholder="Optional"> --}}
                                        <button class="btn btn-secondary btn-sm text-nowrap" type="button" id="showMemoOptions">Show Memos / POD</button>
                                    </div>
                                </div>
                            </div>
                            <div id="memoOptionsContainer" style="display: none; margin-top: 15px;">
                                <div class="app-form-section mb-0">
                                    <div class="app-form-section__header app-form-section__header--muted">
                                        <h5 class="app-form-section__title mb-0">Available Memos / POD</h5>
                                    </div>
                                    <div class="app-form-section__body">
                                        <div id="memoCheckboxes"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-form-actions card">
                <div class="card-body d-flex justify-content-end p-2 p-sm-3">
                    <button type="button" class="btn btn-brand-primary btn-sm w-auto px-4" id="showInvoiceFormBtn" disabled>
                        Next <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Step 2: Invoice form --}}
        <div id="mainInvoiceForm" style="display: none;">
            <form action="{{ route('invoices.store') }}" method="POST" class="needs-validation" id="invoiceCreateForm" novalidate="">
                @csrf
                @php
                    $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
                    $userCompanyId = auth()->user()->company_id ?? null;
                @endphp

                <div class="card app-index-list-card mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="bi bi-receipt-cutoff me-1"></i> Add New Invoice
                        </h3>
                        <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                            <a href="{{ route('invoices.index') }}" class="btn app-index-header-btn" aria-label="Back to invoices">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="app-form-section">
                            <div class="app-form-section__header">
                                <h5 class="app-form-section__title">
                                    <i class="bi bi-info-circle"></i> Selected Information
                                </h5>
                            </div>
                            <div class="app-form-section__body">
                                <div id="selectedInfo">
                                    <div class="row g-2">
                                        <div class="col-6 col-md-6">
                                            <strong>Customer:</strong> <span id="selectedCustomerName"></span>
                                        </div>
                                        <div class="col-6 col-md-6">
                                            <strong>Location:</strong> <span id="selectedLocation" name="location"></span>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <strong>Memo(s):</strong> <span id="selectedMemoIds"></span>
                                        </div>
                                    </div>
                                    <div id="selectedLrNumbers" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="app-form-section">
                            <div class="app-form-section__header">
                                <h5 class="app-form-section__title">
                                    <i class="bi bi-file-earmark-text"></i> Invoice Details
                                </h5>
                            </div>
                            <div class="app-form-section__body">
                                <div class="row g-2">
                                    <div class="col-12 col-md-4">
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
                                    <div class="col-6 col-md-4">
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

                                    <div class="col-6 col-md-4">
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
                                    <div class="col-5 col-sm-6 col-md-4">
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
                        </div>
                    </div>
                </div>

                <input type="hidden" name="sgst" id="sgstInput" value="0">
                <input type="hidden" name="cgst" id="cgstInput" value="0">
                <input type="hidden" name="igst" id="igstInput" value="0">
                <input type="hidden" name="net_amount" id="netAmountInput" value="0">
                <input type="hidden" name="base_amount" id="baseAmountInput" value="0">

                <div class="app-form-actions card">
                    <div class="card-body d-flex flex-row flex-nowrap justify-content-end align-items-center gap-2 p-2 p-sm-3">
                        <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm flex-fill flex-sm-grow-0 text-nowrap w-auto">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </a>
                        <button class="btn btn-brand-primary btn-sm flex-fill flex-sm-grow-0 text-nowrap w-auto" type="submit">
                            <i class="bi bi-check2-circle me-1"></i> Generate invoice
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- LR Details Container --}}
        <div id="lrDetailsContainer" class="mt-3" style="display: none;">
            <div class="card app-index-list-card mb-3">
                <div class="card-header py-2">
                    <h3 class="card-title mb-0 fs-6 fw-bold">
                        <i class="bi bi-truck me-1"></i> Selected LR Details
                    </h3>
                </div>
                <div class="card-body p-2 p-sm-3">
                    <div class="app-form-section mb-0">
                        <div class="app-form-section__body">
                            <div id="lrDetailsContent"></div>
                        </div>
                    </div>
                </div>
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
                    const lrParams = new URLSearchParams();
                    memoIds.forEach(id => lrParams.append('memo_ids[]', id));

                    fetch(`/invoices/lrs?${lrParams.toString()}`, {
                            cache: 'no-store',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`Failed to load LR numbers (${response.status})`);
                            }

                            return response.json();
                        })
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
                                const memoLrs = Array.isArray(data[memoId]) ? data[memoId] : [];
                                if (memoLrs.length > 0) {
                                    lrCount += memoLrs.length;
                                    const totalAmount = memoLrs.reduce((sum, lr) => sum +
                                        parseFloat(lr.amount || 0), 0);
                                    grandTotalAmount += totalAmount;
                                    tableHtml += `
                                        <div class="card mb-3 border">
                                            <div class="card-header py-2 bg-light">
                                                <h5 class="card-title mb-0 fs-6 fw-bold">Memo ${memoId} Details</h5>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-sm table-hover align-middle text-nowrap mb-0 small">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>LR Number</th>
                                                                <th>Source City</th>
                                                                <th>Destination City</th>
                                                                <th>Transporter</th>
                                                                <th>Consignor</th>
                                                                <th>Consignee</th>
                                                                <th>GR Date</th>
                                                                <th>Truck Number</th>
                                                                <th class="text-end">Weight</th>
                                                                <th class="text-end">Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${memoLrs.map(lr => `
                                                                <tr>
                                                                    <td class="fw-semibold text-brand-primary">${lr.lr_number}</td>
                                                                    <td>${lr.source_city || '—'}</td>
                                                                    <td>${lr.destination_city || '—'}</td>
                                                                    <td>${lr.transporter || '—'}</td>
                                                                    <td>${lr.consignor || '—'}</td>
                                                                    <td>${lr.consignee || '—'}</td>
                                                                    <td>${lr.gr_date || '—'}</td>
                                                                    <td>${lr.truck_number || '—'}</td>
                                                                    <td class="text-end">${lr.weight || '—'}</td>
                                                                    <td class="text-end fw-semibold">${lr.amount || '0.00'}</td>
                                                                </tr>
                                                            `).join('')}
                                                            <tr class="table-light fw-bold">
                                                                <td colspan="9" class="text-end">Total Amount:</td>
                                                                <td class="text-end">${totalAmount.toFixed(2)}</td>
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
                                    <div class="card border-dark mb-2">
                                        <div class="card-body p-2 text-end small">
                                            <h5 class="mb-0 fs-6 fw-bold">Grand Total Amount: ${grandTotalAmount.toFixed(2)}</h5>
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
                                                        <div class="card border-dark mb-2">
                                                            <div class="card-body p-2 text-end small">
                                                                <p class="mb-1" name="sgst">SGST (${gst_slab / 2}%): ${sgst.toFixed(2)}</p>
                                                                <p class="mb-1" name="cgst">CGST (${gst_slab / 2}%): ${cgst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fs-6 fw-bold" name="net_amount">Net Total: ${netTotal.toFixed(2)}</h5>
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
                                                        <div class="card border-dark mb-2">
                                                            <div class="card-body p-2 text-end small">
                                                                <p class="mb-1" name="sgst">SGST (${gst_slab/2}%): ${sgst.toFixed(2)}</p>
                                                                <p class="mb-1" name="cgst">CGST (${gst_slab/2}%): ${cgst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fs-6 fw-bold" name="net_amount"> Total: ${netTotal.toFixed(2)}</h5>
                                                                <p class="mb-1" name="gst">Tax under RCM: ${gst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fs-6 fw-bold">Net Total: ${grandTotalAmount.toFixed(2)}</h5>
                                                            </div>
                                                        </div>
                                                    `;
                                                    const totalAmountInput = document.querySelector(
                                                        'input[name="total_amount"]');
                                                    if (totalAmountInput) {
                                                        totalAmountInput.value = grandTotalAmount
                                                            .toFixed(2);
                                                    }
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
                                                        <div class="card border-dark mb-2">
                                                            <div class="card-body p-2 text-end small">
                                                                <p class="mb-1" name="igst">IGST (${gst_slab}%): ${igst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fs-6 fw-bold" name="net_amount">Net Total: ${netTotal.toFixed(2)}</h5>
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
                                                        <div class="card border-dark mb-2">
                                                            <div class="card-body p-2 text-end small">
                                                                <p class="mb-1" name="igst">IGST (${gst_slab}%): ${igst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fs-6 fw-bold" name="net_amount"> Total: ${netTotal.toFixed(2)}</h5>
                                                                <p class="mb-1" name="gst">Tax under RCM: ${igst.toFixed(2)}</p>
                                                                <h5 class="mb-0 fs-6 fw-bold">Net Total: ${grandTotalAmount.toFixed(2)}</h5>
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
                                            `<div class="w-100">${tableHtml}</div>`;

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
                                <div class="w-100">
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
