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
            <div class="d-flex flex-wrap gap-2">
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
                                    <div id="balanceAlert" class="alert alert-warning py-1 px-2 mb-0 small" style="border-radius: 8px;">
                                        Difference: <span id="diffDisplay" class="fw-bold">₹0.00</span>
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

    // Keyboard shortcuts: F4, F5, F6, F7, Ctrl+Enter
    window.addEventListener('keydown', function(e) {
        if (e.key === 'F5') { e.preventDefault(); document.querySelector('[data-type="payment"]').click(); }
        if (e.key === 'F6') { e.preventDefault(); document.querySelector('[data-type="receipt"]').click(); }
        if (e.key === 'F7') { e.preventDefault(); document.querySelector('[data-type="journal"]').click(); }
        if (e.key === 'F4') { e.preventDefault(); document.querySelector('[data-type="contra"]').click(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            const saveBtn = document.getElementById('saveVoucherBtn');
            if (!saveBtn.disabled) { saveBtn.click(); }
        }
    });

    // Add Row
    document.getElementById('addRowBtn').addEventListener('click', function() {
        const tbody = document.getElementById('itemsBody');
        const firstRow = tbody.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('input, select').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, `[${rowIndex}]`);
            if (el.tagName === 'INPUT') el.value = '';
        });

        newRow.querySelector('.delete-row-btn').disabled = false;
        tbody.appendChild(newRow);
        rowIndex++;
        attachEvents(newRow);
        calculateBalances();
    });

    function attachEvents(row) {
        row.querySelectorAll('.item-amount, .item-type').forEach(el => {
            el.addEventListener('input', calculateBalances);
            el.addEventListener('change', calculateBalances);
        });

        row.querySelector('.delete-row-btn').addEventListener('click', function() {
            if (document.querySelectorAll('.item-row').length > 2) {
                row.remove();
                calculateBalances();
            }
        });
    }

    document.querySelectorAll('.item-row').forEach(row => attachEvents(row));

    function calculateBalances() {
        let debits = 0;
        let credits = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const type = row.querySelector('.item-type').value;
            const amount = parseFloat(row.querySelector('.item-amount').value) || 0;
            if (type === 'debit') debits += amount;
            else credits += amount;
        });

        const diff = Math.abs(debits - credits);
        document.getElementById('totalDebitDisplay').textContent = '₹' + debits.toFixed(2);
        document.getElementById('totalCreditDisplay').textContent = '₹' + credits.toFixed(2);
        document.getElementById('diffDisplay').textContent = '₹' + diff.toFixed(2);

        const alertBox = document.getElementById('balanceAlert');
        const saveBtn = document.getElementById('saveVoucherBtn');

        if (diff < 0.01 && debits > 0) {
            alertBox.className = 'alert alert-success py-1 px-2 mb-0 small';
            alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Voucher is perfectly balanced!';
            saveBtn.disabled = false;
        } else {
            alertBox.className = 'alert alert-warning py-1 px-2 mb-0 small';
            alertBox.innerHTML = 'Difference: <span class="fw-bold">₹' + diff.toFixed(2) + '</span> (Debits must equal Credits)';
            saveBtn.disabled = true;
        }
    }

    calculateBalances();
});
</script>
@endsection
