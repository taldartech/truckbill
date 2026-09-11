@extends('layouts.app')

@section('title', 'Ledger Statement (Account Book) - ' . $company->name)

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            border-color: var(--brand-border) !important;
            font-size: 0.875rem !important;
            min-height: 38px;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.ledger-statement') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted mb-1">Select Ledger Account <span class="text-danger">*</span></label>
                    <select name="ledger_id" class="form-select form-select-sm select2" required>
                        <option value="">-- Choose Ledger Account --</option>
                        @foreach($ledgers as $l)
                            <option value="{{ $l->id }}" {{ (string)$selectedLedgerId === (string)$l->id ? 'selected' : '' }}>
                                {{ $l->name }} ({{ $l->accountGroup?->name ?? 'Primary' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm flex-grow-1">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('accounting.ledger-statement') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($statement)
    <!-- Summary Header Cards -->
    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; border: 1px solid var(--brand-border) !important; background: #fff;">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <span class="badge mb-1" style="background-color: var(--brand-soft-primary); color: var(--brand-primary); font-weight: 600;">
                        {{ $statement['ledger']->accountGroup?->name ?? 'General Ledger' }}
                    </span>
                    <h4 class="fw-bold mb-1" style="color: var(--brand-text-primary);">{{ $statement['ledger']->name }}</h4>
                    <div class="small text-muted">
                        @if($statement['ledger']->gstin) GSTIN: <code>{{ $statement['ledger']->gstin }}</code> @endif
                        @if($statement['ledger']->pan_number) | PAN: <code>{{ $statement['ledger']->pan_number }}</code> @endif
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row text-center g-2">
                        <div class="col-6 col-sm-3">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block small">Opening Balance</small>
                                <span class="fw-bold text-dark">{{ $statement['opening_balance']['formatted'] }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="border rounded p-2" style="background: var(--brand-soft-success);">
                                <small class="text-muted d-block small">Period Debits (+)</small>
                                <span class="fw-bold" style="color: var(--brand-success);">₹{{ number_format($statement['total_debit'], 2) }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="border rounded p-2" style="background: var(--brand-soft-danger);">
                                <small class="text-muted d-block small">Period Credits (-)</small>
                                <span class="fw-bold" style="color: var(--brand-danger);">₹{{ number_format($statement['total_credit'], 2) }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="border rounded p-2" style="background: var(--brand-active-bg); border-color: var(--brand-primary) !important;">
                                <small class="text-muted d-block small">Closing Balance</small>
                                <span class="fw-bold" style="color: var(--brand-primary-dark);">{{ $statement['closing_balance']['formatted'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card app-index-list-card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-book-open me-2"></i>Account Statement Transactions
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <button onclick="window.print()" class="btn app-index-header-btn">
                    <i class="bi bi-printer"></i> Print Statement
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table app-index-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3 text-center" style="width: 100px;">Date</th>
                            <th style="width: 100px;">Vch Type</th>
                            <th style="width: 140px;">Vch No</th>
                            <th>Particulars / Opposing Ledger</th>
                            <th class="text-end" style="width: 140px;">Debit (₹)</th>
                            <th class="text-end" style="width: 140px;">Credit (₹)</th>
                            <th class="text-end pe-3" style="width: 170px;">Running Balance (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Opening Balance Row -->
                        <tr style="background-color: var(--brand-table-header-bg);">
                            <td class="ps-3 text-center small text-muted">{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                            <td colspan="3"><span class="fw-bold text-dark">*** Opening Balance Brought Forward ***</span></td>
                            <td class="text-end fw-semibold" style="color: var(--brand-success);">
                                {{ $statement['opening_balance']['type'] === 'Dr' ? number_format($statement['opening_balance']['amount'], 2) : '-' }}
                            </td>
                            <td class="text-end fw-semibold" style="color: var(--brand-danger);">
                                {{ $statement['opening_balance']['type'] === 'Cr' ? number_format($statement['opening_balance']['amount'], 2) : '-' }}
                            </td>
                            <td class="text-end pe-3 fw-bold" style="color: var(--brand-primary);">
                                {{ $statement['opening_balance']['formatted'] }}
                            </td>
                        </tr>

                        <!-- Period Transactions -->
                        @forelse($statement['rows'] as $row)
                        <tr>
                            <td class="ps-3 text-center small">{{ $row['date'] }}</td>
                            <td>
                                @php
                                    $badgeStyle = match(strtolower($row['voucher_type'])) {
                                        'sales' => 'background-color: var(--brand-soft-success); color: var(--brand-success);',
                                        'purchase' => 'background-color: var(--brand-soft-danger); color: var(--brand-danger);',
                                        'payment' => 'background-color: var(--brand-soft-warning); color: var(--brand-warning);',
                                        'receipt' => 'background-color: var(--brand-soft-info); color: var(--brand-info);',
                                        'contra' => 'background-color: var(--brand-soft-secondary); color: var(--brand-text-muted);',
                                        default => 'background-color: var(--brand-soft-primary); color: var(--brand-primary);'
                                    };
                                @endphp
                                <span class="badge border" style="{{ $badgeStyle }} font-weight: 600;">{{ $row['voucher_type'] }}</span>
                            </td>
                            <td>
                                <a href="{{ route('accounting.vouchers.show', $row['voucher_id']) }}" class="text-decoration-none fw-semibold">
                                    {{ $row['voucher_number'] }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $row['particulars'] }}</div>
                                @if($row['narration'])
                                    <small class="text-muted d-block text-truncate" style="max-width: 350px;">{{ $row['narration'] }}</small>
                                @endif
                                @if($row['cost_centre'])
                                    <span class="badge border small mt-1" style="background-color: var(--brand-soft-info); color: var(--brand-info);"><i class="bi bi-truck me-1"></i>{{ $row['cost_centre'] }}</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold" style="color: var(--brand-success);">
                                {{ $row['debit'] ? '₹'.number_format($row['debit'], 2) : '-' }}
                            </td>
                            <td class="text-end fw-semibold" style="color: var(--brand-danger);">
                                {{ $row['credit'] ? '₹'.number_format($row['credit'], 2) : '-' }}
                            </td>
                            <td class="text-end pe-3 fw-bold" style="color: var(--brand-primary);">
                                ₹{{ number_format($row['running_balance'], 2) }} {{ $row['running_balance_type'] }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No transactions recorded for this ledger during the selected period.
                            </td>
                        </tr>
                        @endforelse

                        <!-- Totals & Closing Balance Row -->
                        <tr class="fw-bold" style="background-color: var(--brand-table-header-bg); border-top: 2px solid var(--brand-border);">
                            <td colspan="4" class="text-end pe-3 text-uppercase small">Total Period Activity:</td>
                            <td class="text-end" style="color: var(--brand-success);">₹{{ number_format($statement['total_debit'], 2) }}</td>
                            <td class="text-end" style="color: var(--brand-danger);">₹{{ number_format($statement['total_credit'], 2) }}</td>
                            <td class="text-end pe-3 fw-bold" style="color: var(--brand-primary-dark);">{{ $statement['closing_balance']['formatted'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="card shadow-sm border-0 py-5 text-center text-muted" style="border-radius: 12px; border: 1px solid var(--brand-border) !important;">
        <i class="bi bi-search fs-1 mb-2 text-muted"></i>
        <h5 class="fw-semibold text-dark">Select a ledger account above to view its statement.</h5>
        <small class="text-muted">You can inspect Customer ledgers, Transporter accounts, Fuel Pumps, Bank registers, or Expenses.</small>
    </div>
    @endif
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
    </script>
@endpush
