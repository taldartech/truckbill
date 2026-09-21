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
                                <th style="width: 100px;" class="ps-3">Dr / Cr</th>
                                <th>Ledger Account</th>
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
                                        <option value="">-- Select Ledger --</option>
                                        @foreach($ledgers as $l)
                                            <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->accountGroup?->name }})</option>
                                        @endforeach
                                    </select>
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
                                        <option value="">-- Select Ledger --</option>
                                        @foreach($ledgers as $l)
                                            <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->accountGroup?->name }})</option>
                                        @endforeach
                                    </select>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 2;

    // Type buttons
    document.querySelectorAll('.vch-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.vch-type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('selectedVoucherType').value = this.dataset.type;
        });
    });

    // Keyboard shortcuts: F4-F7, Ctrl+F8, Ctrl+F9, Ctrl+Enter
    window.addEventListener('keydown', function(e) {
        if (e.key === 'F5') { e.preventDefault(); document.querySelector('[data-type="payment"]')?.click(); }
        if (e.key === 'F6') { e.preventDefault(); document.querySelector('[data-type="receipt"]')?.click(); }
        if (e.key === 'F7') { e.preventDefault(); document.querySelector('[data-type="journal"]')?.click(); }
        if (e.key === 'F4') { e.preventDefault(); document.querySelector('[data-type="contra"]')?.click(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'F8') { e.preventDefault(); document.querySelector('[data-type="credit_note"]')?.click(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'F9') { e.preventDefault(); document.querySelector('[data-type="debit_note"]')?.click(); }
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
                            row0Ledger.selectedIndex = i;
                            break;
                        }
                    }
                }

                if (row1Ledger) {
                    for (let i = 0; i < row1Ledger.options.length; i++) {
                        if (row1Ledger.options[i].text.toLowerCase().includes('bank') || row1Ledger.options[i].text.toLowerCase().includes('cash')) {
                            row1Ledger.selectedIndex = i;
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

    // Add Row
    document.getElementById('addRowBtn')?.addEventListener('click', function() {
        const tbody = document.getElementById('itemsBody');
        const firstRow = tbody.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('input, select').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, `[${rowIndex}]`);
            if (el.tagName === 'INPUT') {
                el.value = '';
            } else if (el.tagName === 'SELECT') {
                if (el.classList.contains('ledger-select') || el.name.includes('cost_centre_id')) {
                    el.selectedIndex = 0;
                }
            }
        });

        tbody.appendChild(newRow);
        rowIndex++;
        attachEvents(newRow);
        updateDeleteButtons();
        calculateBalances();
    });

    function attachEvents(row) {
        row.querySelectorAll('.item-amount, .item-type, .ledger-select').forEach(el => {
            el.addEventListener('input', calculateBalances);
            el.addEventListener('change', calculateBalances);
            el.addEventListener('keyup', calculateBalances);
        });

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
