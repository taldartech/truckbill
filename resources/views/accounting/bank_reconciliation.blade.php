@extends('layouts.app')

@section('title', 'Bank Reconciliation Statement - ' . $company->name)

@push('styles')
<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 10mm 10mm;
        }

        .sidebar-overlay,
        .modal-backdrop,
        .loading-overlay,
        .overlay,
        [class*="overlay"],
        [class*="backdrop"],
        .app-sidebar,
        .main-sidebar,
        .sidebar-wrapper,
        .app-header,
        .app-footer,
        .app-index-card-toolbar,
        .btn,
        .btn-group,
        .dropdown,
        .d-print-none,
        .no-print,
        #subscription-access-banner,
        .alert {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            background: transparent !important;
            width: 0 !important;
            height: 0 !important;
            pointer-events: none !important;
        }

        html,
        body,
        body.layout-fixed,
        body.bg-body-tertiary,
        body.sidebar-open,
        .app-wrapper,
        .app-main,
        .app-content,
        .app-content-header,
        .container-fluid,
        .app-index-page--standard {
            background: #ffffff !important;
            background-color: #ffffff !important;
            background-image: none !important;
            color: #000000 !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        .brs-print {
            display: block !important;
            width: 100% !important;
            color: #000000 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 8.5pt !important;
            line-height: 1.35 !important;
        }

        .tally-print-header {
            text-align: center !important;
            margin-bottom: 12px !important;
            color: #000000 !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-company-name {
            font-size: 14pt !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            color: #000000 !important;
            margin-bottom: 2px !important;
        }

        .tally-company-city {
            font-size: 11pt !important;
            font-weight: 400 !important;
            color: #000000 !important;
            margin-bottom: 3px !important;
        }

        .tally-report-title {
            font-size: 12.5pt !important;
            font-weight: 700 !important;
            color: #000000 !important;
            margin-bottom: 2px !important;
        }

        .tally-report-period {
            font-size: 10pt !important;
            font-weight: 400 !important;
            color: #000000 !important;
        }

        .tally-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            background: #ffffff !important;
        }

        .tally-table th, .tally-table td {
            border: 1px solid #000000 !important;
            padding: 4px 6px !important;
            font-size: 8.5pt !important;
            color: #000000 !important;
        }

        .tally-table th {
            background-color: #f5f5f5 !important;
            font-weight: 700 !important;
            text-align: left !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 d-print-none">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('accounting.day-book') }}">Accounting</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bank Reconciliation</li>
                </ol>
            </nav>
            <h2 class="fs-4 fw-bold mb-0 text-dark">
                <i class="bi bi-bank2 me-2" style="color: var(--brand-primary);"></i>Bank Reconciliation Statement (BRS)
            </h2>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> Print Statement
            </button>
            <a href="{{ route('accounting.ledger-statement', $selectedLedgerId ?? '') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-journal-text me-1"></i> View Bank Ledger
            </a>
        </div>
    </div>

    <!-- Filter Bar (Hidden on Print) -->
    <div class="card shadow-sm border-0 mb-3 d-print-none" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.bank-reconciliation') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Select Bank Account</label>
                    <select name="ledger_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @forelse($bankLedgers as $bLedger)
                            <option value="{{ $bLedger->id }}" {{ $selectedLedgerId == $bLedger->id ? 'selected' : '' }}>
                                {{ $bLedger->name }}
                            </option>
                        @empty
                            <option value="">No bank ledgers provisioned</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">As of Date (Statement Date)</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Clearance Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Transactions</option>
                        <option value="uncleared" {{ $statusFilter === 'uncleared' ? 'selected' : '' }}>Uncleared Only</option>
                        <option value="reconciled" {{ $statusFilter === 'reconciled' ? 'selected' : '' }}>Reconciled Only</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-brand-primary btn-sm w-100">
                        <i class="bi bi-filter"></i> Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($reconciliation)
    <!-- Reconciliation Summary KPI Cards (Hidden on Print) -->
    <div class="row g-3 mb-3 d-print-none">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid var(--brand-border) !important; background: #ffffff;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Balance as per Books</span>
                    <i class="bi bi-book text-muted fs-6"></i>
                </div>
                <h4 class="fw-bold mb-1 text-dark" style="font-size: 1.3rem;">
                    ₹{{ number_format(abs($reconciliation['book_balance']), 2) }}
                    <span class="badge {{ $reconciliation['book_balance'] >= 0 ? 'bg-success' : 'bg-danger' }} fs-6 ms-1">
                        {{ $reconciliation['book_balance'] >= 0 ? 'Dr (Favourable)' : 'Cr (Overdraft)' }}
                    </span>
                </h4>
                <span class="small text-muted" style="font-size: 0.78rem;">As of {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid rgba(245,158,11,0.3) !important; background: #fffbeb;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px; color: #b45309;">Uncleared Receipts (-)</span>
                    <i class="bi bi-arrow-down-left text-warning fs-6"></i>
                </div>
                <h4 class="fw-bold mb-1" style="font-size: 1.3rem; color: #b45309;">
                    ₹{{ number_format($reconciliation['uncleared_debits'], 2) }}
                </h4>
                <span class="small" style="font-size: 0.78rem; color: #b45309;">Deposited in books, pending bank credit</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid rgba(59,130,246,0.3) !important; background: #eff6ff;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-primary text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Uncleared Payments (+)</span>
                    <i class="bi bi-arrow-up-right text-primary fs-6"></i>
                </div>
                <h4 class="fw-bold mb-1 text-primary" style="font-size: 1.3rem;">
                    ₹{{ number_format($reconciliation['uncleared_credits'], 2) }}
                </h4>
                <span class="small text-primary" style="font-size: 0.78rem;">Issued in books, pending bank debit</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid rgba(34,197,94,0.35) !important; background: #f0fdf4;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-success text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Balance as per Bank</span>
                    <i class="bi bi-check-circle-fill text-success fs-6"></i>
                </div>
                <h4 class="fw-bold mb-1 text-success" style="font-size: 1.3rem;">
                    ₹{{ number_format(abs($reconciliation['bank_balance']), 2) }}
                    <span class="badge {{ $reconciliation['bank_balance'] >= 0 ? 'bg-success' : 'bg-danger' }} fs-6 ms-1">
                        {{ $reconciliation['bank_balance'] >= 0 ? 'Cr (Passbook)' : 'Dr (Overdraft)' }}
                    </span>
                </h4>
                <span class="small text-success" style="font-size: 0.78rem;">
                    Reconciled: {{ $reconciliation['totals']['reconciled_count'] }} | Uncleared: {{ $reconciliation['totals']['uncleared_count'] }}
                </span>
            </div>
        </div>
    </div>

    <!-- Reconciliation Work Table Card -->
    <div class="card app-index-list-card shadow-sm d-print-none">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h3 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>Bank Transactions for {{ $reconciliation['ledger']->name }}
                </h3>
                <span class="small text-muted">Enter or update the bank clearance date as per your bank passbook / statement.</span>
            </div>
            <div class="app-index-card-toolbar d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAllToday()">
                    <i class="bi bi-calendar-check me-1"></i> Set All Uncleared to Today
                </button>
                <button type="button" class="btn btn-sm btn-brand-primary" onclick="submitReconciliationForm()">
                    <i class="bi bi-save me-1"></i> Save Reconciliation
                </button>
            </div>
        </div>

        <form id="recon-form" method="POST" action="{{ route('accounting.bank-reconciliation.update') }}">
            @csrf
            <input type="hidden" name="ledger_id" value="{{ $reconciliation['ledger']->id }}">

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead style="background-color: var(--brand-table-header-bg);">
                        <tr>
                            <th style="width: 110px;">Date</th>
                            <th style="width: 140px;">Voucher No</th>
                            <th style="width: 100px;">Type</th>
                            <th>Particulars / Opposing Party</th>
                            <th>Narration / Ref</th>
                            <th class="text-end" style="width: 120px;">Receipts (Dr)</th>
                            <th class="text-end" style="width: 120px;">Payments (Cr)</th>
                            <th style="width: 170px;">Bank Date</th>
                            <th class="text-center" style="width: 100px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reconciliation['transactions'] as $idx => $t)
                        <tr>
                            <td class="small">{{ $t['voucher_date'] }}</td>
                            <td>
                                <a href="{{ route('accounting.vouchers.show', $t['voucher_id']) }}" class="fw-semibold text-decoration-none" target="_blank" title="View Voucher">
                                    {{ $t['voucher_number'] }}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.72rem;">
                                    {{ $t['voucher_type'] }}
                                </span>
                            </td>
                            <td class="fw-medium text-dark">{{ $t['particulars'] }}</td>
                            <td class="small text-muted">{{ $t['narration'] ?: ($t['reference_number'] ?: '—') }}</td>
                            <td class="text-end fw-semibold text-success">
                                {{ $t['debit_amount'] > 0 ? '₹' . number_format($t['debit_amount'], 2) : '—' }}
                            </td>
                            <td class="text-end fw-semibold text-danger">
                                {{ $t['credit_amount'] > 0 ? '₹' . number_format($t['credit_amount'], 2) : '—' }}
                            </td>
                            <td>
                                <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $t['id'] }}">
                                <div class="input-group input-group-sm">
                                    <input type="date" 
                                           name="items[{{ $idx }}][reconciliation_date]" 
                                           id="recon-date-{{ $idx }}" 
                                           class="form-control form-control-sm recon-date-input" 
                                           value="{{ $t['reconciliation_date'] }}">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="setSingleToday('recon-date-{{ $idx }}')" title="Set Today">
                                        <i class="bi bi-calendar-event"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($t['is_reconciled'])
                                    <span class="badge bg-success" style="font-size: 0.72rem;">Reconciled</span>
                                @else
                                    <span class="badge bg-warning text-dark" style="font-size: 0.72rem;">Uncleared</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No bank transactions found for the selected date range.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">
                    Showing {{ $reconciliation['transactions']->count() }} transactions for {{ $reconciliation['ledger']->name }}.
                </span>
                <button type="submit" class="btn btn-brand-primary btn-sm px-4">
                    <i class="bi bi-save me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- DEDICATED PRINT CONTAINER (Tally BRS Format) -->
    <div class="d-none d-print-block brs-print">
        <div class="tally-print-header">
            <div class="tally-company-name">{{ $company->name }}</div>
            <div class="tally-report-title">Bank Reconciliation Statement</div>
            <div class="tally-report-period">{{ $reconciliation['ledger']->name }} as at {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</div>
        </div>

        <table class="tally-table mb-3">
            <thead>
                <tr>
                    <th style="width: 75%;">Particulars</th>
                    <th style="width: 25%; text-align: right;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Balance as per Company's Books</strong></td>
                    <td style="text-align: right; font-weight: bold;">
                        {{ number_format(abs($reconciliation['book_balance']), 2) }} {{ $reconciliation['book_balance'] >= 0 ? 'Dr' : 'Cr' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><em>Add:</em> Amounts credited in books but not debited by Bank (Cheques/Payments issued but not presented)</td>
                    <td style="text-align: right;">{{ number_format($reconciliation['uncleared_credits'], 2) }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><em>Less:</em> Amounts debited in books but not credited by Bank (Cheques/Receipts deposited but not cleared)</td>
                    <td style="text-align: right;">({{ number_format($reconciliation['uncleared_debits'], 2) }})</td>
                </tr>
                <tr style="background-color: #f5f5f5; font-weight: bold;">
                    <td><strong>Balance as per Bank Statement</strong></td>
                    <td style="text-align: right; font-weight: bold;">
                        {{ number_format(abs($reconciliation['bank_balance']), 2) }} {{ $reconciliation['bank_balance'] >= 0 ? 'Cr' : 'Dr' }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="font-weight: bold; margin-top: 15px; margin-bottom: 5px;">Uncleared / Outstanding Transaction Details:</div>
        <table class="tally-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 20%;">Voucher No</th>
                    <th style="width: 45%;">Particulars</th>
                    <th style="width: 20%; text-align: right;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $unclearedTxns = $reconciliation['transactions']->where('is_reconciled', false);
                @endphp
                @forelse($unclearedTxns as $ut)
                <tr>
                    <td>{{ $ut['voucher_date'] }}</td>
                    <td>{{ $ut['voucher_number'] }}</td>
                    <td>{{ $ut['particulars'] }} ({{ $ut['type'] === 'debit' ? 'Receipt' : 'Payment' }})</td>
                    <td style="text-align: right;">
                        {{ number_format($ut['debit_amount'] > 0 ? $ut['debit_amount'] : $ut['credit_amount'], 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center;">All transactions are reconciled as of this date.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>

<script>
    function setSingleToday(elementId) {
        const input = document.getElementById(elementId);
        if (input) {
            const today = new Date().toISOString().split('T')[0];
            input.value = today;
        }
    }

    function setAllToday() {
        const today = new Date().toISOString().split('T')[0];
        const inputs = document.querySelectorAll('.recon-date-input');
        inputs.forEach(input => {
            if (!input.value) {
                input.value = today;
            }
        });
    }

    function submitReconciliationForm() {
        document.getElementById('recon-form').submit();
    }
</script>
@endsection
