@extends('layouts.app')

@section('title', 'Accounting Vouchers - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.vouchers.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Voucher Type</label>
                    <select name="voucher_type" class="form-select form-select-sm">
                        <option value="">All Voucher Types</option>
                        <option value="sales" {{ request('voucher_type') === 'sales' ? 'selected' : '' }}>Sales (F8)</option>
                        <option value="purchase" {{ request('voucher_type') === 'purchase' ? 'selected' : '' }}>Purchase (F9)</option>
                        <option value="payment" {{ request('voucher_type') === 'payment' ? 'selected' : '' }}>Payment (F5)</option>
                        <option value="receipt" {{ request('voucher_type') === 'receipt' ? 'selected' : '' }}>Receipt (F6)</option>
                        <option value="journal" {{ request('voucher_type') === 'journal' ? 'selected' : '' }}>Journal (F7)</option>
                        <option value="contra" {{ request('voucher_type') === 'contra' ? 'selected' : '' }}>Contra (F4)</option>
                        <option value="credit_note" {{ request('voucher_type') === 'credit_note' ? 'selected' : '' }}>Credit Note (Ctrl+F8)</option>
                        <option value="debit_note" {{ request('voucher_type') === 'debit_note' ? 'selected' : '' }}>Debit Note (Ctrl+F9)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm flex-grow-1">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('accounting.vouchers.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Vouchers Card -->
    <div class="card app-index-list-card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-journal-text me-2"></i>Accounting Vouchers ({{ $company->name }})
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <a href="{{ route('accounting.vouchers.create') }}" class="btn app-index-header-btn">
                    <i class="bi bi-plus-circle"></i> Create Voucher
                </a>
                <a href="{{ route('accounting.day-book') }}" class="btn app-index-header-btn">
                    <i class="bi bi-calendar3"></i> Day Book View
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table app-index-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width: 110px;">Date</th>
                            <th style="width: 140px;">Voucher #</th>
                            <th style="width: 110px;">Type</th>
                            <th>Debit Ledgers</th>
                            <th>Credit Ledgers</th>
                            <th class="text-end" style="width: 150px;">Amount (₹)</th>
                            <th class="text-center pe-3" style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $v)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $v->voucher_date->format('d-m-Y') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $v->voucher_number }}</span></td>
                            <td>
                                @php
                                    $badgeStyle = match(strtolower($v->voucher_type)) {
                                        'sales' => 'background-color: var(--brand-soft-success); color: var(--brand-success);',
                                        'purchase' => 'background-color: var(--brand-soft-danger); color: var(--brand-danger);',
                                        'payment' => 'background-color: var(--brand-soft-warning); color: var(--brand-warning);',
                                        'receipt' => 'background-color: var(--brand-soft-info); color: var(--brand-info);',
                                        'contra' => 'background-color: var(--brand-soft-secondary); color: var(--brand-text-muted);',
                                        'credit_note' => 'background-color: #fee2e2; color: #dc2626;',
                                        'debit_note' => 'background-color: #fef3c7; color: #b45309;',
                                        default => 'background-color: var(--brand-soft-primary); color: var(--brand-primary);'
                                    };
                                @endphp
                                <span class="badge border" style="{{ $badgeStyle }} font-weight: 600;">{{ ucwords(str_replace('_', ' ', $v->voucher_type)) }}</span>
                            </td>
                            <td>
                                @foreach($v->items->where('type', 'debit') as $deb)
                                    <div class="small py-0.5">
                                        <span class="fw-bold" style="color: var(--brand-success);">Dr:</span>
                                        <span class="fw-semibold text-dark">{{ $deb->ledger?->name }}</span>
                                        <span class="text-muted">(₹{{ number_format($deb->amount, 2) }})</span>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                @foreach($v->items->where('type', 'credit') as $crd)
                                    <div class="small py-0.5">
                                        <span class="fw-bold" style="color: var(--brand-danger);">Cr:</span>
                                        <span class="fw-semibold text-dark">{{ $crd->ledger?->name }}</span>
                                        <span class="text-muted">(₹{{ number_format($crd->amount, 2) }})</span>
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-end fw-bold" style="color: var(--brand-primary);">₹{{ number_format($v->total_amount, 2) }}</td>
                            <td class="text-center pe-3">
                                <a href="{{ route('accounting.vouchers.show', $v->id) }}" class="btn btn-sm btn-secondary py-1 px-2" title="View Double-Entry Voucher">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-1 d-block mb-2 text-muted"></i>
                                No vouchers found matching your filter criteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($vouchers->hasPages())
        <div class="card-footer bg-white py-3 border-top">
            <div class="d-flex justify-content-end">
                {{ $vouchers->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
