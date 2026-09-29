@extends('layouts.app')

@section('title', 'Create Accounting Voucher - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-create-page app-create-page--wide">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>New Accounting Voucher</h3>
            <small class="text-muted">Direct double-entry voucher entry for {{ $company->name }}</small>
        </div>
        <div>
            <a href="{{ route('accounting.vouchers.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Vouchers
            </a>
        </div>
    </div>

    <!-- Quick Voucher Type Buttons (Tally-style F4-F7) -->
    <div class="card shadow-sm border-0 mb-3" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <label class="form-label small fw-semibold text-muted d-block mb-2">Voucher Type (press F4 - F7 to switch):</label>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn vch-type-btn active" data-type="payment">
                    <span class="badge bg-warning text-dark me-1">F5</span> Payment
                </button>
                <button type="button" class="btn vch-type-btn" data-type="receipt">
                    <span class="badge bg-info text-dark me-1">F6</span> Receipt
                </button>
                <button type="button" class="btn vch-type-btn" data-type="journal">
                    <span class="badge bg-primary text-white me-1">F7</span> Journal
                </button>
                <button type="button" class="btn vch-type-btn" data-type="contra">
                    <span class="badge bg-secondary text-white me-1">F4</span> Contra
                </button>
                <button type="button" class="btn vch-type-btn" data-type="credit_note">
                    <span class="badge bg-danger text-white me-1">Ctrl+F8</span> Credit Note
                </button>
                <button type="button" class="btn vch-type-btn" data-type="debit_note">
                    <span class="badge bg-dark text-white me-1">Ctrl+F9</span> Debit Note
                </button>
                <button type="button" class="btn btn-outline-primary ms-auto" id="btnTdsDepositTemplate">
                    <i class="bi bi-bank me-1"></i> TDS Deposit (Challan ITNS 281)
                </button>
            </div>
        </div>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .vch-type-btn {
            background-color: #fff;
            border: 1px solid var(--brand-border);
            color: var(--brand-text-primary);
            font-weight: 600;
            padding: 0.45rem 1.1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .vch-type-btn:hover {
            background-color: var(--brand-table-header-bg);
            border-color: var(--brand-primary);
        }
        .vch-type-btn.active {
            background: var(--brand-active-bg);
            border-color: var(--brand-primary);
            color: var(--brand-primary-dark);
            box-shadow: 0 0 0 1px var(--brand-primary);
        }
        .select2-container--bootstrap-5 .select2-selection--single {
            min-height: 31px !important;
            height: 31px !important;
            padding: 0.2rem 0.5rem !important;
            font-size: 0.85rem !important;
            border-radius: 6px !important;
            border: 1px solid var(--brand-border) !important;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 23px !important;
            padding-left: 0 !important;
            color: var(--brand-text-primary) !important;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 29px !important;
        }
        .select2-container {
            width: 100% !important;
        }
        .invoice-ref-container {
            background: #f8fafc;
            border-radius: 6px;
            padding: 4px 6px;
            border: 1px dashed #cbd5e1;
        }
    </style>

    <form method="POST" action="{{ route('accounting.vouchers.store') }}" id="voucherForm">
        @csrf
        <input type="hidden" name="voucher_type" id="selectedVoucherType" value="payment">

        <!-- General Details Section -->
        <div class="app-form-section mb-3">
            <div class="app-form-section__header">
                <h5 class="app-form-section__title"><i class="bi bi-info-circle me-1"></i>General Voucher Details</h5>
            </div>
            <div class="app-form-section__body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Voucher Date *</label>
                        <input type="date" name="voucher_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Reference / Cheque #</label>
                        <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="e.g. UTR / Chq / Bill #">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Narration</label>
                        <input type="text" name="narration" class="form-control form-control-sm" placeholder="Voucher remarks / explanation">
                    </div>
                </div>
            </div>
        </div>

        <!-- Govt TDS Challan (ITNS 281) Details Section -->
        <div class="app-form-section mb-3 d-none" id="challan281Section" style="background-color: #f8fafc; border: 1px dashed var(--brand-primary) !important;">
            <div class="app-form-section__header d-flex justify-content-between align-items-center">
                <h5 class="app-form-section__title text-primary"><i class="bi bi-file-earmark-check me-1"></i>Govt TDS Challan (ITNS 281) Details</h5>
                <span class="badge bg-primary text-white">Form 26Q & 3CD Clause 34</span>
            </div>
            <div class="app-form-section__body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">BSR Code (7 Digits)</label>
                        <input type="text" name="challan_bsr_code" id="challan_bsr_code" class="form-control form-control-sm" placeholder="e.g. 0210001" maxlength="20">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Challan Serial No (5 Digits)</label>
                        <input type="text" name="challan_serial_no" id="challan_serial_no" class="form-control form-control-sm" placeholder="e.g. 00123" maxlength="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Challan Tender Date</label>
                        <input type="date" name="challan_tender_date" id="challan_tender_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Major Head</label>
                        <select name="challan_head" id="challan_head" class="form-select form-select-sm">
                            <option value="0021 - Non-Company Deductees (194C)">0021 - Non-Company Deductees</option>
                            <option value="0020 - Company Deductees (194C)">0020 - Company Deductees</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Voucher Line Items Table Section -->
        <div class="app-form-section mb-3">
            <div class="app-form-section__header">
                <h5 class="app-form-section__title"><i class="bi bi-list-check me-1"></i>Double-Entry Particulars (Debits & Credits)</h5>
                <button type="button" class="btn app-index-header-btn" id="addRowBtn">
                    <i class="bi bi-plus-lg me-1"></i>Add Row
                </button>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table app-index-table table-bordered align-middle mb-0" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width: 90px;" class="ps-3">Dr / Cr</th>
                                <th style="min-width: 320px;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Ledger Account *</span>
                                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 rounded-pill shadow-none" id="openQuickAddLedgerBtn" title="Create New Ledger (Alt+C)" style="font-size: 0.75rem;">
                                            <i class="bi bi-plus-circle me-1"></i>New Ledger (Alt+C)
                                        </button>
                                    </div>
                                </th>
                                <th style="width: 170px;" class="text-end">Amount (₹)</th>
                                <th style="width: 220px;">Cost Centre (Truck/Branch)</th>
                                <th>Line Particulars</th>
                                <th style="width: 50px;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Row 1 (Debit by default) -->
                            <tr class="item-row">
                                <td class="ps-3">
                                    <select name="items[0][type]" class="form-select form-select-sm item-type" required>
                                        <option value="debit" selected>Dr</option>
                                        <option value="credit">Cr</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="items[0][ledger_id]" class="form-select form-select-sm ledger-select" required>
                                        <option value="">-- Select Ledger (Type to search) --</option>
                                        @foreach($ledgers as $l)
                                            <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->accountGroup?->name }})</option>
                                        @endforeach
                                    </select>
                                    <div class="invoice-ref-container mt-1 d-none">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white text-muted py-0 px-1" style="font-size: 0.72rem;"><i class="bi bi-receipt me-1"></i>Agnst Bill</span>
                                            <select name="items[0][invoice_id]" class="form-select form-select-sm invoice-ref-select" style="font-size: 0.78rem;">
                                                <option value="">-- Select Pending Invoice (Optional) --</option>
                                            </select>
                                        </div>
                                        <div class="invoice-balance-hint small text-primary fw-semibold mt-1" style="font-size: 0.75rem;"></div>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="items[0][amount]" class="form-control form-control-sm text-end item-amount" placeholder="0.00" required>
                                </td>
                                <td>
                                    <select name="items[0][cost_centre_id]" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        @foreach($costCentres as $cc)
                                            <option value="{{ $cc->id }}">{{ $cc->name }} ({{ ucfirst($cc->category) }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[0][particulars_note]" class="form-control form-control-sm" placeholder="Line memo">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-row-btn" disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Row 2 (Credit by default) -->
                            <tr class="item-row">
                                <td class="ps-3">
                                    <select name="items[1][type]" class="form-select form-select-sm item-type" required>
                                        <option value="debit">Dr</option>
                                        <option value="credit" selected>Cr</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="items[1][ledger_id]" class="form-select form-select-sm ledger-select" required>
                                        <option value="">-- Select Ledger (Type to search) --</option>
                                        @foreach($ledgers as $l)
                                            <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->accountGroup?->name }})</option>
                                        @endforeach
                                    </select>
                                    <div class="invoice-ref-container mt-1 d-none">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white text-muted py-0 px-1" style="font-size: 0.72rem;"><i class="bi bi-receipt me-1"></i>Agnst Bill</span>
                                            <select name="items[1][invoice_id]" class="form-select form-select-sm invoice-ref-select" style="font-size: 0.78rem;">
                                                <option value="">-- Select Pending Invoice (Optional) --</option>
                                            </select>
                                        </div>
                                        <div class="invoice-balance-hint small text-primary fw-semibold mt-1" style="font-size: 0.75rem;"></div>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="items[1][amount]" class="form-control form-control-sm text-end item-amount" placeholder="0.00" required>
                                </td>
                                <td>
                                    <select name="items[1][cost_centre_id]" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        @foreach($costCentres as $cc)
                                            <option value="{{ $cc->id }}">{{ $cc->name }} ({{ ucfirst($cc->category) }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[1][particulars_note]" class="form-control form-control-sm" placeholder="Line memo">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-row-btn" disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end pe-3">Totals:</td>
                                <td class="text-end">
                                    <div>Dr: <span id="totalDebitDisplay" class="fw-bold" style="color: var(--brand-success);">₹0.00</span></div>
                                    <div>Cr: <span id="totalCreditDisplay" class="fw-bold" style="color: var(--brand-danger);">₹0.00</span></div>
                                </td>
                                <td colspan="3">
                                    <div id="balanceAlert" class="alert alert-secondary py-1 px-2 mb-0 small" style="border-radius: 8px;">
                                        <i class="bi bi-info-circle me-1"></i>Enter debit and credit amounts (Debits must equal Credits)
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sticky Actions -->
        <div class="app-form-actions">
            <div class="card-body">
                <a href="{{ route('accounting.vouchers.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-brand-primary px-4" id="saveVoucherBtn" disabled>
                    <i class="bi bi-check2-circle me-1"></i>Post Voucher (Ctrl+Enter)
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Quick Add Ledger Modal (Alt+C) -->
<div class="modal fade" id="quickAddLedgerModal" tabindex="-1" aria-labelledby="quickAddLedgerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 12px;">
            <div class="modal-header bg-gradient-primary text-white py-2 px-3">
                <h5 class="modal-title fs-6 fw-bold" id="quickAddLedgerModalLabel">
                    <i class="bi bi-journal-plus me-2"></i>Quick Create Ledger Account (Alt+C)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickAddLedgerForm">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ledger Name *</label>
                        <input type="text" name="name" id="quickLedgerName" class="form-control form-control-sm" placeholder="e.g. ABC Logistics / Office Expenses" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Under Account Group *</label>
                        <select name="account_group_id" id="quickLedgerGroup" class="form-select form-select-sm" required>
                            <option value="">-- Select Group Head --</option>
                            @foreach($allGroups ?? [] as $group)
                                <option value="{{ $group->id }}">{{ $group->name }} ({{ ucfirst($group->nature) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label small fw-semibold">Opening Balance</label>
                            <input type="number" step="0.01" min="0" name="opening_balance" id="quickLedgerOpeningBal" class="form-control form-control-sm text-end" value="0.00" placeholder="0.00">
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-semibold">Dr / Cr</label>
                            <select name="opening_balance_type" id="quickLedgerOpeningType" class="form-select form-select-sm">
                                <option value="debit">Dr (Debit)</option>
                                <option value="credit">Cr (Credit)</option>
                            </select>
                        </div>
                    </div>
                    <div id="quickLedgerErrorAlert" class="alert alert-danger py-1 px-2 small d-none mb-0"></div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary btn-sm" id="btnSaveQuickLedger">
                        <i class="bi bi-check2-circle me-1"></i>Create Ledger
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 2;
    let activeRowForLedger = null;

    // Track active row for Quick Add Ledger
    $(document).on('focusin click', '.item-row', function() {
        activeRowForLedger = this;
    });

    // Initialize Select2 on any element
    function initSelect2(element) {
        if (!window.jQuery || !window.jQuery.fn.select2) return;
        $(element).select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Select Ledger (Type to search) --',
            allowClear: false
        });
    }

    // Initialize all existing ledger selects
    $('.ledger-select').each(function() {
        initSelect2(this);
    });

    // Check pending invoices when ledger is selected
    function checkPendingInvoicesForLedger(row, ledgerId) {
        const $container = $(row).find('.invoice-ref-container');
        const $select = $(row).find('.invoice-ref-select');
        const $hint = $(row).find('.invoice-balance-hint');

        if (!ledgerId) {
            $container.addClass('d-none');
            $select.html('<option value="">-- Select Pending Invoice (Optional) --</option>');
            $hint.text('');
            return;
        }

        $.ajax({
            url: `/accounting/ledgers/${ledgerId}/pending-invoices`,
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.is_customer && res.invoices && res.invoices.length > 0) {
                    let options = '<option value="">-- Agnst Invoice # (Optional) --</option>';
                    res.invoices.forEach(inv => {
                        options += `<option value="${inv.id}" data-balance="${inv.remaining_amount}">${inv.label}</option>`;
                    });
                    $select.html(options);
                    $container.removeClass('d-none');
                } else {
                    $container.addClass('d-none');
                    $select.html('<option value="">-- Select Pending Invoice (Optional) --</option>');
                    $hint.text('');
                }
            },
            error: function() {
                $container.addClass('d-none');
            }
        });
    }

    // When invoice is chosen, show balance hint and optionally prefill
    $(document).on('change', '.invoice-ref-select', function() {
        const $row = $(this).closest('.item-row');
        const $opt = $(this).find(':selected');
        const bal = parseFloat($opt.data('balance') || 0);
        const $hint = $row.find('.invoice-balance-hint');
        if ($opt.val() && bal > 0) {
            $hint.html(`<i class="bi bi-receipt me-1"></i>Current Pending: <strong>₹${bal.toFixed(2)}</strong>`);
            const $amt = $row.find('.item-amount');
            if (!$amt.val() || parseFloat($amt.val()) === 0) {
                $amt.val(bal.toFixed(2));
                calculateBalances();
            }
        } else {
            $hint.text('');
        }
    });

    // Quick Add Ledger Modal triggers
    $('#openQuickAddLedgerBtn').on('click', function() {
        openQuickAddLedger();
    });

    function openQuickAddLedger() {
        $('#quickLedgerErrorAlert').addClass('d-none').text('');
        $('#quickAddLedgerForm')[0].reset();
        const modal = new bootstrap.Modal(document.getElementById('quickAddLedgerModal'));
        modal.show();
        setTimeout(() => document.getElementById('quickLedgerName')?.focus(), 400);
    }

    // Submit Quick Add Ledger Form via AJAX
    $('#quickAddLedgerForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btnSaveQuickLedger');
        const $err = $('#quickLedgerErrorAlert');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        $err.addClass('d-none').text('');

        const formData = $(this).serialize();

        $.ajax({
            url: "{{ route('accounting.ledgers.store') }}",
            method: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Create Ledger');
                if (res.success && res.ledger) {
                    const newOpt = new Option(res.ledger.text, res.ledger.id, false, false);
                    $('.ledger-select').each(function() {
                        $(this).append($(newOpt).clone());
                    });

                    // Select on active row if any
                    if (activeRowForLedger) {
                        const $activeSel = $(activeRowForLedger).find('.ledger-select');
                        $activeSel.val(res.ledger.id).trigger('change');
                        $(activeRowForLedger).find('.item-amount').focus();
                    }

                    bootstrap.Modal.getInstance(document.getElementById('quickAddLedgerModal'))?.hide();
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Create Ledger');
                let errMsg = 'Failed to create ledger. Please check input.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                $err.removeClass('d-none').html(errMsg);
            }
        });
    });

    // Type buttons
    document.querySelectorAll('.vch-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.vch-type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('selectedVoucherType').value = this.dataset.type;
        });
    });

    // Keyboard shortcuts: F4-F7, Ctrl+F8, Ctrl+F9, Ctrl+Enter, Alt+C
    window.addEventListener('keydown', function(e) {
        if (e.key === 'F5') { e.preventDefault(); document.querySelector('[data-type="payment"]')?.click(); }
        if (e.key === 'F6') { e.preventDefault(); document.querySelector('[data-type="receipt"]')?.click(); }
        if (e.key === 'F7') { e.preventDefault(); document.querySelector('[data-type="journal"]')?.click(); }
        if (e.key === 'F4') { e.preventDefault(); document.querySelector('[data-type="contra"]')?.click(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'F8') { e.preventDefault(); document.querySelector('[data-type="credit_note"]')?.click(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'F9') { e.preventDefault(); document.querySelector('[data-type="debit_note"]')?.click(); }
        if (e.altKey && (e.key === 'c' || e.key === 'C')) {
            e.preventDefault();
            openQuickAddLedger();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            const saveBtn = document.getElementById('saveVoucherBtn');
            if (saveBtn && !saveBtn.disabled) {
                e.preventDefault();
                saveBtn.click();
            }
        }
    });

    // TDS Deposit Template Shortcut
    const tdsTemplateBtn = document.getElementById('btnTdsDepositTemplate');
    if (tdsTemplateBtn) {
        tdsTemplateBtn.addEventListener('click', function() {
            const paymentBtn = document.querySelector('[data-type="payment"]');
            if (paymentBtn) paymentBtn.click();

            const challanSec = document.getElementById('challan281Section');
            if (challanSec) challanSec.classList.remove('d-none');

            const narrationInput = document.querySelector('input[name="narration"]');
            if (narrationInput && !narrationInput.value) {
                narrationInput.value = 'TDS u/s 194C deposit to Govt of India via Challan ITNS 281';
            }

            const rows = document.querySelectorAll('#itemsBody .item-row');
            if (rows.length >= 2) {
                const row0Type = rows[0].querySelector('.item-type');
                const row0Ledger = rows[0].querySelector('select[name*="[ledger_id]"]');
                const row1Type = rows[1].querySelector('.item-type');
                const row1Ledger = rows[1].querySelector('select[name*="[ledger_id]"]');

                if (row0Type) row0Type.value = 'debit';
                if (row1Type) row1Type.value = 'credit';

                if (row0Ledger) {
                    for (let i = 0; i < row0Ledger.options.length; i++) {
                        if (row0Ledger.options[i].text.toLowerCase().includes('tds u/s 194c payable')) {
                            $(row0Ledger).val(row0Ledger.options[i].value).trigger('change');
                            break;
                        }
                    }
                }

                if (row1Ledger) {
                    for (let i = 0; i < row1Ledger.options.length; i++) {
                        if (row1Ledger.options[i].text.toLowerCase().includes('bank') || row1Ledger.options[i].text.toLowerCase().includes('cash')) {
                            $(row1Ledger).val(row1Ledger.options[i].value).trigger('change');
                            break;
                        }
                    }
                }
            }
            calculateBalances();
        });
    }

    // Update delete button states based on row count
    function updateDeleteButtons() {
        const rows = document.querySelectorAll('.item-row');
        rows.forEach(r => {
            const btn = r.querySelector('.delete-row-btn');
            if (btn) {
                btn.disabled = (rows.length <= 2);
            }
        });
    }

    // Add Row with auto-suggested balancing difference and type
    document.getElementById('addRowBtn')?.addEventListener('click', function() {
        const $tbody = $('#itemsBody');
        const $firstRow = $tbody.find('.item-row:first');

        // Calculate current unallocated difference to auto-suggest in new row
        let curDebits = 0;
        let curCredits = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const t = row.querySelector('.item-type')?.value || 'debit';
            const a = parseFloat(row.querySelector('.item-amount')?.value || 0) || 0;
            if (t === 'debit') curDebits += a;
            else curCredits += a;
        });
        curDebits = Math.round(curDebits * 100) / 100;
        curCredits = Math.round(curCredits * 100) / 100;
        const curDiff = Math.round((curDebits - curCredits) * 100) / 100;

        let suggestedType = 'credit';
        let suggestedAmount = '';
        if (curDiff > 0.001) {
            suggestedType = 'credit';
            suggestedAmount = curDiff.toFixed(2);
        } else if (curDiff < -0.001) {
            suggestedType = 'debit';
            suggestedAmount = Math.abs(curDiff).toFixed(2);
        } else {
            const lastType = document.querySelector('.item-row:last-child .item-type')?.value;
            suggestedType = lastType === 'debit' ? 'credit' : 'debit';
        }

        // Clone row and clean up select2
        const $newRow = $firstRow.clone();
        $newRow.find('.select2-container').remove();
        $newRow.find('.invoice-ref-container').addClass('d-none');
        $newRow.find('.invoice-ref-select').html('<option value="">-- Select Pending Invoice (Optional) --</option>');
        $newRow.find('.invoice-balance-hint').text('');

        const $ledgerSelect = $newRow.find('.ledger-select');
        $ledgerSelect.removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();

        $newRow.find('input, select').each(function() {
            this.name = this.name.replace(/\[\d+\]/, `[${rowIndex}]`);
            if (this.tagName === 'INPUT') {
                if ($(this).hasClass('item-amount')) {
                    this.value = suggestedAmount;
                    if (suggestedAmount) {
                        this.dataset.autoFilled = 'true';
                    } else {
                        delete this.dataset.autoFilled;
                    }
                    delete this.dataset.userEdited;
                } else {
                    this.value = '';
                }
            } else if (this.tagName === 'SELECT') {
                if ($(this).hasClass('item-type')) {
                    this.value = suggestedType;
                } else if ($(this).hasClass('ledger-select') || this.name.includes('cost_centre_id') || $(this).hasClass('invoice-ref-select')) {
                    this.selectedIndex = 0;
                }
            }
        });

        $tbody.append($newRow);
        rowIndex++;

        initSelect2($newRow.find('.ledger-select'));
        attachEvents($newRow[0]);
        updateDeleteButtons();
        calculateBalances();

        // Focus the ledger select of the new row for fast keyboard workflow
        setTimeout(() => {
            $newRow.find('.ledger-select').select2('open');
        }, 100);
    });

    // Auto-suggest opposite row amount in a 2-line voucher
    function autoSuggestOppositeRow(changedInput) {
        changedInput.dataset.userEdited = 'true';
        const rows = document.querySelectorAll('.item-row');
        if (rows.length === 2) {
            const thisRow = changedInput.closest('.item-row');
            const otherRow = rows[0] === thisRow ? rows[1] : rows[0];
            const otherInput = otherRow.querySelector('.item-amount');

            if (otherInput && otherInput.dataset.userEdited !== 'true') {
                const val = parseFloat(changedInput.value) || 0;
                if (val > 0) {
                    otherInput.value = val.toFixed(2);
                    otherInput.dataset.autoFilled = 'true';
                } else {
                    otherInput.value = '';
                    delete otherInput.dataset.autoFilled;
                }

                // If both rows have the same Dr/Cr type, flip the other row to balance
                const thisType = thisRow.querySelector('.item-type')?.value;
                const otherTypeSelect = otherRow.querySelector('.item-type');
                if (thisType && otherTypeSelect && thisType === otherTypeSelect.value) {
                    otherTypeSelect.value = (thisType === 'debit') ? 'credit' : 'debit';
                }
            }
        }
    }

    // Auto-suggest balancing amount on focusing an empty amount field
    function handleAmountFocus(input) {
        const val = parseFloat(input.value) || 0;
        if (val === 0 && input.dataset.userEdited !== 'true') {
            const row = input.closest('.item-row');
            const curType = row.querySelector('.item-type')?.value || 'debit';

            let otherDebits = 0;
            let otherCredits = 0;
            document.querySelectorAll('.item-row').forEach(r => {
                if (r !== row) {
                    const t = r.querySelector('.item-type')?.value || 'debit';
                    const a = parseFloat(r.querySelector('.item-amount')?.value || 0) || 0;
                    if (t === 'debit') otherDebits += a;
                    else otherCredits += a;
                }
            });

            let needed = (curType === 'debit') ? (otherCredits - otherDebits) : (otherDebits - otherCredits);
            needed = Math.round(needed * 100) / 100;

            if (needed > 0.001) {
                input.value = needed.toFixed(2);
                input.dataset.autoFilled = 'true';
                calculateBalances();
                setTimeout(() => input.select(), 50);
            }
        }
    }

    function attachEvents(row) {
        const $sel = $(row).find('.ledger-select');

        $sel.on('change select2:select', function() {
            checkPendingInvoicesForLedger(row, this.value);
            calculateBalances();
        });

        const amountInput = row.querySelector('.item-amount');
        if (amountInput) {
            amountInput.addEventListener('input', function() {
                autoSuggestOppositeRow(this);
                calculateBalances();
            });
            amountInput.addEventListener('focus', function() {
                handleAmountFocus(this);
            });
            amountInput.addEventListener('change', calculateBalances);
            amountInput.addEventListener('keyup', calculateBalances);
        }

        const typeSelect = row.querySelector('.item-type');
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                calculateBalances();
            });
        }

        const delBtn = row.querySelector('.delete-row-btn');
        if (delBtn) {
            delBtn.addEventListener('click', function() {
                if (document.querySelectorAll('.item-row').length > 2) {
                    row.remove();
                    updateDeleteButtons();
                    calculateBalances();
                }
            });
        }
    }

    document.querySelectorAll('.item-row').forEach(row => attachEvents(row));

    function calculateBalances() {
        let debits = 0;
        let credits = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const typeEl = row.querySelector('.item-type');
            const amountEl = row.querySelector('.item-amount');
            const type = typeEl ? typeEl.value : 'debit';
            const amount = parseFloat(amountEl ? amountEl.value : 0) || 0;
            if (type === 'debit') debits += amount;
            else credits += amount;
        });

        // Round to 2 decimal places to avoid floating point imprecision
        debits = Math.round(debits * 100) / 100;
        credits = Math.round(credits * 100) / 100;
        const diff = Math.round(Math.abs(debits - credits) * 100) / 100;

        const totalDebitDisplay = document.getElementById('totalDebitDisplay');
        const totalCreditDisplay = document.getElementById('totalCreditDisplay');
        if (totalDebitDisplay) totalDebitDisplay.textContent = '₹' + debits.toFixed(2);
        if (totalCreditDisplay) totalCreditDisplay.textContent = '₹' + credits.toFixed(2);

        const alertBox = document.getElementById('balanceAlert');
        const saveBtn = document.getElementById('saveVoucherBtn');

        // Circular Entry Detection: Check if the same ledger is selected on both Debit and Credit
        let hasDuplicateLedger = false;
        let duplicateLedgerName = '';
        const ledgerSideMap = {};

        document.querySelectorAll('.item-row').forEach(row => {
            const sel = row.querySelector('.ledger-select');
            const ledgerId = sel ? sel.value : '';
            const type = row.querySelector('.item-type')?.value || 'debit';
            if (ledgerId) {
                if (!ledgerSideMap[ledgerId]) {
                    ledgerSideMap[ledgerId] = {
                        name: sel.options[sel.selectedIndex]?.text || 'Ledger',
                        debit: false,
                        credit: false
                    };
                }
                ledgerSideMap[ledgerId][type] = true;
                if (ledgerSideMap[ledgerId].debit && ledgerSideMap[ledgerId].credit) {
                    hasDuplicateLedger = true;
                    duplicateLedgerName = ledgerSideMap[ledgerId].name;
                }
            }
        });

        if (hasDuplicateLedger) {
            if (alertBox) {
                alertBox.className = 'alert alert-danger py-1 px-2 mb-0 small';
                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i><strong>Circular Entry Warning:</strong> Same ledger ("' + duplicateLedgerName + '") cannot be selected for both Debit and Credit.';
            }
            if (saveBtn) saveBtn.disabled = true;
            return;
        }

        if (debits === 0 && credits === 0) {
            if (alertBox) {
                alertBox.className = 'alert alert-secondary py-1 px-2 mb-0 small';
                alertBox.innerHTML = '<i class="bi bi-info-circle me-1"></i>Enter debit and credit amounts (Debits must equal Credits)';
            }
            if (saveBtn) saveBtn.disabled = true;
        } else if (diff < 0.01 && debits > 0) {
            if (alertBox) {
                alertBox.className = 'alert alert-success py-1 px-2 mb-0 small';
                alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i><strong>Voucher is perfectly balanced!</strong> (Dr = Cr)';
            }
            if (saveBtn) saveBtn.disabled = false;
        } else {
            const hint = debits > credits ? 'Debits exceed Credits' : 'Credits exceed Debits';
            if (alertBox) {
                alertBox.className = 'alert alert-warning py-1 px-2 mb-0 small';
                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Difference: <span class="fw-bold">₹' + diff.toFixed(2) + '</span> (' + hint + ')';
            }
            if (saveBtn) saveBtn.disabled = true;
        }
    }

    // Optional: add submit handler to prevent duplicate submission
    const form = document.getElementById('voucherForm');
    if (form) {
        form.addEventListener('submit', function() {
            const saveBtn = document.getElementById('saveVoucherBtn');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Posting Voucher...';
            }
        });
    }

    calculateBalances();
});
</script>
@endsection
