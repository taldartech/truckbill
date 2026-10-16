@extends('layouts.app')

@push('styles')
    <style>
        @media (max-width: 767.98px) {
            .custom-invoice-page {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
                margin-top: 0.35rem !important;
            }

            /* Page Title & Back Button */
            .custom-invoice-page .page-title {
                font-size: 0.95rem !important;
            }

            .custom-invoice-page .page-title i {
                font-size: 0.95rem !important;
            }

            .custom-invoice-page .header-btn {
                font-size: 0.72rem !important;
                padding: 0.2rem 0.45rem !important;
            }

            /* Cards & Headers */
            .custom-invoice-page .card {
                border-radius: 0.45rem !important;
                margin-bottom: 0.6rem !important;
            }

            .custom-invoice-page .card-header {
                padding: 0.35rem 0.6rem !important;
            }

            .custom-invoice-page .card-title,
            .custom-invoice-page .card-header h6 {
                font-size: 0.78rem !important;
                font-weight: 600 !important;
            }

            .custom-invoice-page .card-body {
                padding: 0.6rem 0.65rem !important;
            }

            .custom-invoice-page .card-footer {
                padding: 0.35rem 0.6rem !important;
            }

            /* Form labels */
            .custom-invoice-page .form-label {
                font-size: 0.74rem !important;
                font-weight: 600 !important;
                margin-bottom: 0.15rem !important;
            }

            /* Form controls, selects, input groups */
            .custom-invoice-page .form-control,
            .custom-invoice-page .form-select {
                font-size: 0.78rem !important;
                padding: 0.25rem 0.45rem !important;
                min-height: 31px !important;
                height: 31px !important;
                border-radius: 0.375rem !important;
                line-height: 1.3 !important;
            }

            .custom-invoice-page input[type="date"].form-control {
                font-size: 0.74rem !important;
                padding: 0.2rem 0.35rem !important;
            }

            .custom-invoice-page .input-group-text {
                font-size: 0.74rem !important;
                padding: 0.25rem 0.45rem !important;
            }

            .custom-invoice-page .input-group .btn {
                font-size: 0.72rem !important;
                padding: 0.25rem 0.5rem !important;
            }

            /* Helper texts and validation errors */
            .custom-invoice-page .text-danger,
            .custom-invoice-page small,
            .custom-invoice-page .form-text,
            .custom-invoice-page .invalid-feedback {
                font-size: 0.67rem !important;
                margin-top: 0.15rem !important;
            }

            /* Customer info section */
            .custom-invoice-page #customerInfoSection .row {
                font-size: 0.74rem !important;
            }

            .custom-invoice-page #customerInfoSection .text-muted {
                font-size: 0.68rem !important;
            }

            /* LR details table */
            .custom-invoice-page #lrDetailsSection table {
                font-size: 0.7rem !important;
            }

            .custom-invoice-page #lrDetailsSection table th,
            .custom-invoice-page #lrDetailsSection table td {
                padding: 0.25rem 0.35rem !important;
                font-size: 0.7rem !important;
            }

            .custom-invoice-page #lrDetailsSection table th {
                width: 100px !important;
            }

            /* Action Buttons (Submit & Cancel) */
            .custom-invoice-page .btn-sm {
                font-size: 0.75rem !important;
                padding: 0.28rem 0.6rem !important;
                border-radius: 0.375rem !important;
            }

            /* Tax section */
            .custom-invoice-page #taxSection .form-label {
                font-size: 0.7rem !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid custom-invoice-page mt-2 mt-md-3 mb-4 px-2 px-md-3">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2 mb-md-3">
                <h5 class="mb-0 fw-bold fs-6 fs-md-5 page-title"><i class="bi bi-file-earmark-plus me-1 me-md-2"></i>Create Custom Invoice</h5>
                <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm header-btn">
                    <i class="bi bi-arrow-left me-1"></i>Back to Invoices
                </a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('custom-invoices.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <!-- Step 1: Invoice type & LR lookup -->
                <div class="card card-outline card-primary shadow-sm mb-3">
                    <div class="card-header py-2 px-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-1-circle me-2"></i>Select type & enter LR</h6>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="row g-2 g-md-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Custom invoice type <span class="text-danger">*</span></label>
                                <select name="custom_invoice" id="custom_invoice_type" class="form-select form-select-sm" required>
                                    <option value="">— Select type —</option>
                                    <option value="rate_difference" {{ old('custom_invoice') == 'rate_difference' ? 'selected' : '' }}>Rate difference of the LR</option>
                                    <option value="shortage_damage" {{ old('custom_invoice') == 'shortage_damage' ? 'selected' : '' }}>Shortage & damage claim</option>
                                </select>
                                @error('custom_invoice')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6" id="lrInputSection" style="display: none;">
                                <label class="form-label small fw-semibold mb-1">LR number <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                    <input type="text" id="lr_number_input" name="lr_number" class="form-control form-control-sm"
                                        placeholder="e.g. LR0000385870" value="{{ old('lr_number') }}" autocomplete="off">
                                    <button type="button" class="btn btn-primary btn-sm" id="fetchLrBtn">
                                        <i class="bi bi-search me-1"></i>Fetch details
                                    </button>
                                </div>
                                @error('lr_number')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer info (after fetch) -->
                <div class="card card-outline card-info shadow-sm mb-3" id="customerInfoSection" style="display: none;">
                    <div class="card-header py-2 px-3 d-flex align-items-center">
                        <i class="bi bi-building me-2"></i>
                        <span class="fw-semibold small">Bill to (from LR)</span>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="row g-2 small">
                            <div class="col-md-4"><span class="text-muted">Name</span><br><span id="customer_name_display" class="fw-medium">—</span></div>
                            <div class="col-md-4"><span class="text-muted">GSTIN</span><br><span id="customer_gst_display">—</span></div>
                            <div class="col-md-4"><span class="text-muted">PAN</span><br><span id="customer_pan_display">—</span></div>
                            <div class="col-md-12 mt-1"><span class="text-muted">Address</span><br><span id="customer_address_display">—</span></div>
                        </div>
                    </div>
                </div>

                <!-- Bill details -->
                <div class="card card-outline card-secondary shadow-sm mb-3" id="invoiceHeaderSection" style="display: none;">
                    <div class="card-header py-2 px-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-2-circle me-2"></i>Bill details</h6>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="row g-2 g-md-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Reference no. (optional)</label>
                                <input type="text" name="manual_invoice_number" class="form-control form-control-sm"
                                    value="{{ old('manual_invoice_number') }}" placeholder="Your reference / printed bill no.">
                                @error('manual_invoice_number')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Bill date <span class="text-danger">*</span></label>
                                <input type="date" name="generated_date" class="form-control form-control-sm"
                                    value="{{ old('generated_date', date('Y-m-d')) }}" required>
                                @error('generated_date')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Due date (optional)</label>
                                <input type="date" name="due_date" class="form-control form-control-sm" value="{{ old('due_date') }}">
                                <small class="text-muted d-block mt-1">Leave blank to use {{ \App\Models\Invoice::DEFAULT_CREDIT_DAYS }} days from the bill date.</small>
                                @error('due_date')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Charge type</label>
                                @php
                                    $invoiceTypeValue = (optional($company)->gst_scheme ?? '') == 'reverse charge' ? 'Reverse' : 'Forward';
                                @endphp
                                <select class="form-select form-select-sm bg-light" disabled aria-label="From company">
                                    <option value="Reverse" {{ $invoiceTypeValue === 'Reverse' ? 'selected' : '' }}>Reverse charge</option>
                                    <option value="Forward" {{ $invoiceTypeValue === 'Forward' ? 'selected' : '' }}>Forward charge</option>
                                </select>
                                <input type="hidden" name="invoice_type" id="invoice_type_input" value="{{ $invoiceTypeValue }}">
                            </div>
                        </div>
                        <input type="hidden" name="customer_id" id="customer_id_input" value="{{ old('customer_id') }}">
                    </div>
                </div>

                <!-- LR details table -->
                <div class="card card-outline card-default shadow-sm mb-3" id="lrDetailsSection" style="display: none;">
                    <div class="card-header py-2 px-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-truck me-2"></i>LR details</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 small">
                                <tbody>
                                    <tr>
                                        <th class="bg-light" style="width: 140px;">LR number</th>
                                        <td id="display_lr_number">—</td>
                                        <th class="bg-light" style="width: 140px;">Truck no.</th>
                                        <td id="display_truck_number">—</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Destination</th>
                                        <td id="display_destination">—</td>
                                        <th class="bg-light">Invoice no.</th>
                                        <td id="display_invoice_number">—</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Invoice date</th>
                                        <td id="display_invoice_date">—</td>
                                        <th class="bg-light">Weight</th>
                                        <td id="display_weight">—</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Amount & type-specific fields -->
                <div class="card card-outline card-default shadow-sm mb-3" id="amountSection" style="display: none;">
                    <div class="card-header py-2 px-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-3-circle me-2"></i>Amount & charges</h6>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="row g-2 g-md-3">
                            <div class="col-6 col-md-4" id="portal_rate_wrapper" style="display: none;">
                                <label class="form-label small fw-semibold mb-1">Portal rate</label>
                                <input type="number" name="portal_rate" id="portal_rate_input" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('portal_rate') }}">
                            </div>
                            <div class="col-6 col-md-4" id="diff_wrapper" style="display: none;">
                                <label class="form-label small fw-semibold mb-1">Diff</label>
                                <input type="number" name="diff" id="diff_input" class="form-control form-control-sm" step="0.01" value="{{ old('diff') }}">
                            </div>
                            <div class="col-6 col-md-4" id="damage_qty_field" style="display: none;">
                                <label class="form-label small fw-semibold mb-1">Damage qty</label>
                                <input type="number" name="damage_qty" id="damage_qty_input" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('damage_qty') }}">
                            </div>
                            <div class="col-6 col-md-4" id="damage_rate_field" style="display: none;">
                                <label class="form-label small fw-semibold mb-1">Damage rate</label>
                                <input type="number" name="damage_rate" id="damage_rate_input" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('damage_rate') }}">
                            </div>
                            <div class="col-6 col-md-4" id="shortage_qty_field" style="display: none;">
                                <label class="form-label small fw-semibold mb-1">Shortage qty</label>
                                <input type="number" name="shortage_qty" id="shortage_qty_input" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('shortage_qty') }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold mb-1">Amount <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="amount_input" class="form-control form-control-sm" step="0.01" min="0" required value="{{ old('amount') }}" placeholder="0.00">
                                @error('amount')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold mb-1">Remark</label>
                                <input type="text" name="remark" class="form-control form-control-sm" value="{{ old('remark') }}" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="card card-outline card-default shadow-sm mb-3" id="summarySection" style="display: none;">
                    <div class="card-body p-2 p-md-3">
                        <div class="row g-2 g-md-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Total amount (base)</label>
                                <input type="number" name="total_amount" id="total_amount_input" class="form-control form-control-sm bg-light" step="0.01" min="0" required readonly
                                    value="{{ old('total_amount') }}">
                                @error('total_amount')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: Tax summary -->
            <div class="col-lg-4">
                <div class="card card-success shadow-sm sticky-top" id="taxSection" style="display: none; top: 1rem;">
                    <div class="card-header py-2 px-3 bg-success text-white">
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-percent me-2"></i>GST (auto)</h6>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-0">SGST</label>
                            <input type="number" name="sgst" id="sgst_input" class="form-control form-control-sm bg-light" step="0.01" min="0" value="{{ old('sgst', '0') }}" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-0">CGST</label>
                            <input type="number" name="cgst" id="cgst_input" class="form-control form-control-sm bg-light" step="0.01" min="0" value="{{ old('cgst', '0') }}" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-0">IGST</label>
                            <input type="number" name="igst" id="igst_input" class="form-control form-control-sm bg-light" step="0.01" min="0" value="{{ old('igst', '0') }}" readonly>
                        </div>
                        <hr class="my-2">
                        <div>
                            <label class="form-label small text-muted mb-0">Net amount</label>
                            <input type="number" name="net_amount" id="net_amount_input" class="form-control form-control-sm fw-bold" step="0.01" min="0" value="{{ old('net_amount') }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2 mt-md-3">
            <div class="col-12">
                <div class="card card-outline card-default">
                    <div class="card-footer d-flex justify-content-between py-2 px-3" id="formFooter">
                        <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-success btn-sm px-3" id="submitBtn" style="display: none;">
                            <i class="bi bi-check2-circle me-1"></i>Create invoice
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customInvoiceType = document.getElementById('custom_invoice_type');
    const lrInputSection = document.getElementById('lrInputSection');
    const lrNumberInput = document.getElementById('lr_number_input');
    const fetchLrBtn = document.getElementById('fetchLrBtn');
    const customerInfoSection = document.getElementById('customerInfoSection');
    const invoiceHeaderSection = document.getElementById('invoiceHeaderSection');
    const lrDetailsSection = document.getElementById('lrDetailsSection');
    const amountSection = document.getElementById('amountSection');
    const summarySection = document.getElementById('summarySection');
    const taxSection = document.getElementById('taxSection');
    const submitBtn = document.getElementById('submitBtn');

    const gstRate = @json($gstSlab ?? 5) / 100;
    const hadValidationErrors = @json($errors->any());
    let customerData = null;
    let companyState = '';
    let customerState = '';

    @if($company && $company->state)
        companyState = '{{ strtolower($company->state->name) }}';
    @endif

    customInvoiceType.addEventListener('change', function() {
        const type = this.value;
        if (type) {
            lrInputSection.style.display = 'block';
            toggleTypeFields(type);
        } else {
            lrInputSection.style.display = 'none';
            hideAllSections();
        }
    });

    function toggleTypeFields(type) {
        const portalRateWrapper = document.getElementById('portal_rate_wrapper');
        const diffWrapper = document.getElementById('diff_wrapper');
        const damageQtyField = document.getElementById('damage_qty_field');
        const damageRateField = document.getElementById('damage_rate_field');
        const shortageQtyField = document.getElementById('shortage_qty_field');

        if (type === 'rate_difference') {
            if (portalRateWrapper) portalRateWrapper.style.display = 'block';
            if (diffWrapper) diffWrapper.style.display = 'block';
            damageQtyField.style.display = 'none';
            damageRateField.style.display = 'none';
            shortageQtyField.style.display = 'none';
        } else {
            if (portalRateWrapper) portalRateWrapper.style.display = 'none';
            if (diffWrapper) diffWrapper.style.display = 'none';
            damageQtyField.style.display = 'block';
            damageRateField.style.display = 'block';
            shortageQtyField.style.display = 'block';
        }
    }

    function hideAllSections() {
        customerInfoSection.style.display = 'none';
        invoiceHeaderSection.style.display = 'none';
        lrDetailsSection.style.display = 'none';
        amountSection.style.display = 'none';
        summarySection.style.display = 'none';
        taxSection.style.display = 'none';
        submitBtn.style.display = 'none';
    }

    fetchLrBtn.addEventListener('click', function() {
        const lrNumber = lrNumberInput.value.trim();
        if (!lrNumber) {
            alert('Please enter LR number');
            return;
        }
        fetchLRDetails(lrNumber, { restoreFromValidation: false });
    });

    function fetchLRDetails(lrNumber, options = {}) {
        const { restoreFromValidation = false } = options;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        fetchLrBtn.disabled = true;
        fetchLrBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Fetching...';

        fetch(`{{ route('custom-invoices.lr-by-number') }}?lr_number=${encodeURIComponent(lrNumber)}`, {
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            fetchLrBtn.disabled = false;
            fetchLrBtn.innerHTML = '<i class="bi bi-search me-1"></i>Fetch details';
            if (data.success) {
                customerData = data.customer;
                displayCustomerInfo(data.customer);
                displayLRDetails(data.lr_details, lrNumber, restoreFromValidation);
                customerState = data.customer.state_name || '';
                invoiceHeaderSection.style.display = 'block';
                lrDetailsSection.style.display = 'block';
                amountSection.style.display = 'block';
                summarySection.style.display = 'block';
                taxSection.style.display = 'block';
                submitBtn.style.display = 'inline-block';
                document.getElementById('customer_id_input').value = data.customer.id;
                const amountInput = document.getElementById('amount_input');
                if (amountInput.value) {
                    calculateGST();
                }
            } else {
                alert(data.message || 'LR number not found');
            }
        })
        .catch(error => {
            fetchLrBtn.disabled = false;
            fetchLrBtn.innerHTML = '<i class="bi bi-search me-1"></i>Fetch details';
            console.error('Error:', error);
            alert('Error fetching LR details');
        });
    }

    function displayCustomerInfo(customer) {
        document.getElementById('customer_name_display').textContent = customer.name || '—';
        document.getElementById('customer_gst_display').textContent = customer.gst_number || '—';
        document.getElementById('customer_pan_display').textContent = customer.pan_number || '—';
        document.getElementById('customer_address_display').textContent = customer.address || '—';
        customerInfoSection.style.display = 'block';
    }

    function displayLRDetails(lrDetails, lrNumber, restoreFromValidation = false) {
        document.getElementById('display_lr_number').textContent = lrNumber;
        document.getElementById('display_truck_number').textContent = lrDetails.truck_number || '—';
        document.getElementById('display_destination').textContent = lrDetails.destination_city || '—';
        document.getElementById('display_invoice_number').textContent = lrDetails.invoice_number || '—';
        document.getElementById('display_invoice_date').textContent = lrDetails.gr_date || lrDetails.memo_date || '—';
        document.getElementById('display_weight').textContent = lrDetails.weight ?? '—';
        const type = customInvoiceType.value;
        if (type === 'shortage_damage' && ! restoreFromValidation) {
            if (lrDetails.damage_qty) {
                document.getElementById('damage_qty_input').value = lrDetails.damage_qty;
            }
            if (lrDetails.damage_rate) {
                document.getElementById('damage_rate_input').value = lrDetails.damage_rate;
            }
            if (lrDetails.shortage_qty) {
                document.getElementById('shortage_qty_input').value = lrDetails.shortage_qty;
            }
        }
    }

    function calculateGST() {
        const baseAmount = parseFloat(document.getElementById('amount_input').value) || 0;
        const invoiceType = document.getElementById('invoice_type_input').value;
        document.getElementById('total_amount_input').value = baseAmount.toFixed(2);
        let sgst = 0, cgst = 0, igst = 0, netAmount = baseAmount;
        if (companyState === customerState && companyState !== '') {
            if (invoiceType === 'Forward') {
                sgst = baseAmount * (gstRate / 2);
                cgst = baseAmount * (gstRate / 2);
                netAmount = baseAmount + sgst + cgst;
            } else {
                sgst = baseAmount * (gstRate / 2);
                cgst = baseAmount * (gstRate / 2);
            }
        } else {
            if (invoiceType === 'Forward') {
                igst = baseAmount * gstRate;
                netAmount = baseAmount + igst;
            } else {
                igst = baseAmount * gstRate;
            }
        }
        document.getElementById('sgst_input').value = sgst.toFixed(2);
        document.getElementById('cgst_input').value = cgst.toFixed(2);
        document.getElementById('igst_input').value = igst.toFixed(2);
        document.getElementById('net_amount_input').value = netAmount.toFixed(2);
    }

    document.getElementById('amount_input').addEventListener('input', function() {
        if (customerState) calculateGST();
    });
    lrNumberInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); fetchLrBtn.click(); }
    });

    if (customInvoiceType.value) {
        lrInputSection.style.display = 'block';
        toggleTypeFields(customInvoiceType.value);
    }

    const lrNumForRestore = lrNumberInput.value.trim();
    if (lrNumForRestore !== '' && customInvoiceType.value) {
        fetchLRDetails(lrNumForRestore, { restoreFromValidation: hadValidationErrors });
    }
});
</script>
@endsection
