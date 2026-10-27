@extends('layouts.app')

@php
    $paymentBadge = match ($invoice->payment_status) {
        'paid' => 'success',
        'partial' => 'warning',
        default => 'secondary',
    };
    $companyStateName = $invoice->company?->state?->name;
    $customerStateName = $invoice->consignee?->state?->name;
@endphp

@push('styles')
    <style>
        .invoice-edit-page {
            --ie-surface: #f8fafc;
            --ie-border: rgba(15, 23, 42, 0.08);
            --ie-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .invoice-edit-hero {
            background: linear-gradient(135deg, rgba(var(--brand-primary-rgb, 0, 51, 102), 0.06) 0%, #fff 55%);
            border: 1px solid var(--ie-border);
            border-radius: 0.75rem;
            box-shadow: var(--ie-shadow);
        }

        .invoice-edit-sidebar {
            position: sticky;
            top: 1rem;
        }

        .invoice-edit-panel {
            background: #fff;
            border: 1px solid var(--ie-border);
            border-radius: 0.75rem;
            box-shadow: var(--ie-shadow);
        }

        .invoice-edit-panel__header {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid var(--ie-border);
            background: var(--ie-surface);
            border-radius: 0.75rem 0.75rem 0 0;
        }

        .invoice-edit-panel__body {
            padding: 1rem;
        }

        .invoice-edit-stat {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0;
            border-bottom: 1px dashed var(--ie-border);
            font-size: 0.875rem;
        }

        .invoice-edit-stat:last-child {
            border-bottom: 0;
        }

        .invoice-edit-stat__label {
            color: #64748b;
        }

        .invoice-edit-stat__value {
            font-weight: 600;
            text-align: right;
        }

        .invoice-edit-preview-total {
            background: linear-gradient(135deg, rgba(var(--brand-primary-rgb, 0, 51, 102), 0.08), rgba(var(--brand-primary-rgb, 0, 51, 102), 0.02));
            border: 1px solid rgba(var(--brand-primary-rgb, 0, 51, 102), 0.12);
            border-radius: 0.65rem;
            padding: 0.875rem 1rem;
        }

        .invoice-edit-section-title {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 1rem;
        }

        .invoice-edit-section-title__icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(var(--brand-primary-rgb, 0, 51, 102), 0.1);
            color: var(--brand-primary);
            flex-shrink: 0;
        }

        .invoice-edit-memo-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.875rem;
        }

        .invoice-edit-memo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.75rem;
        }

        .invoice-edit-memo-item {
            position: relative;
            border: 1px solid var(--ie-border);
            border-radius: 0.65rem;
            background: #fff;
            padding: 0.875rem;
            cursor: pointer;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }

        .invoice-edit-memo-item:hover {
            border-color: rgba(var(--brand-primary-rgb, 0, 51, 102), 0.35);
            transform: translateY(-1px);
        }

        .invoice-edit-memo-item.is-checked {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 1px rgba(var(--brand-primary-rgb, 0, 51, 102), 0.15);
            background: rgba(var(--brand-primary-rgb, 0, 51, 102), 0.03);
        }

        .invoice-edit-memo-item .form-check {
            margin: 0;
            padding-left: 1.75rem;
        }

        .invoice-edit-memo-item .form-check-input {
            margin-top: 0.2rem;
        }

        .invoice-edit-memo-item .form-check-label {
            font-weight: 600;
            cursor: pointer;
            line-height: 1.3;
        }

        .invoice-edit-memo-item__hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
            padding-left: 1.75rem;
        }

        .invoice-edit-memo-empty,
        .invoice-edit-skeleton {
            border: 1px dashed var(--ie-border);
            border-radius: 0.65rem;
            padding: 1.5rem;
            text-align: center;
            color: #64748b;
            background: #fff;
        }

        .invoice-edit-skeleton-line {
            height: 0.75rem;
            border-radius: 999px;
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: invoice-edit-shimmer 1.2s infinite;
            margin-bottom: 0.5rem;
        }

        @keyframes invoice-edit-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .invoice-edit-lr-block {
            border: 1px solid var(--ie-border);
            border-radius: 0.65rem;
            overflow: hidden;
            background: #fff;
            margin-bottom: 0.875rem;
        }

        .invoice-edit-lr-block:last-child {
            margin-bottom: 0;
        }

        .invoice-edit-lr-block__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--ie-surface);
            border-bottom: 1px solid var(--ie-border);
        }

        .invoice-edit-lr-table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            white-space: nowrap;
        }

        .invoice-edit-lr-table td {
            font-size: 0.875rem;
            vertical-align: middle;
        }

        .invoice-edit-route {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }

        .invoice-edit-route__city {
            background: #f1f5f9;
            border-radius: 999px;
            padding: 0.1rem 0.55rem;
            font-size: 0.8rem;
        }

        .invoice-edit-tax-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            padding: 0.2rem 0;
        }

        .invoice-edit-tax-line.is-muted {
            color: #64748b;
            font-size: 0.8rem;
        }

        @media (max-width: 991.98px) {
            .invoice-edit-sidebar {
                position: static;
            }
        }

        @media (max-width: 767.98px) {
            .invoice-edit-page {
                margin-top: 0.5rem !important;
                margin-bottom: 1.5rem !important;
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
            }

            .invoice-edit-page .row.g-4 {
                --bs-gutter-y: 0.75rem;
                --bs-gutter-x: 0.75rem;
            }

            .invoice-edit-page .row.g-3 {
                --bs-gutter-y: 0.6rem;
                --bs-gutter-x: 0.6rem;
            }

            .invoice-edit-hero {
                padding: 0.65rem 0.85rem !important;
                border-radius: 0.5rem;
                margin-bottom: 0.75rem !important;
            }

            .invoice-edit-hero h4 {
                font-size: 1.05rem;
                margin-bottom: 0.25rem !important;
            }

            .invoice-edit-hero .badge {
                font-size: 0.68rem;
                padding: 0.2rem 0.45rem;
            }

            .invoice-edit-hero .btn {
                padding: 0.22rem 0.5rem;
                font-size: 0.75rem;
            }

            .invoice-edit-panel {
                border-radius: 0.5rem;
                margin-bottom: 0.75rem !important;
            }

            .invoice-edit-panel__header {
                padding: 0.45rem 0.75rem;
            }

            .invoice-edit-panel__body {
                padding: 0.75rem 0.85rem;
            }

            /* Section header (Bill details, Memos) */
            .invoice-edit-section-title {
                gap: 0.5rem;
                margin-bottom: 0.65rem;
            }

            .invoice-edit-section-title__icon {
                width: 1.85rem;
                height: 1.85rem;
                font-size: 0.95rem;
                border-radius: 0.4rem;
            }

            .invoice-edit-section-title h5 {
                font-size: 0.88rem;
            }

            .invoice-edit-section-title .small {
                font-size: 0.7rem;
            }

            .invoice-edit-section-title .badge {
                font-size: 0.68rem;
                padding: 0.18rem 0.4rem;
            }

            /* Form labels & inputs in edit panel (Bill details) */
            .invoice-edit-page .form-label {
                font-size: 0.74rem !important;
                font-weight: 600 !important;
                margin-bottom: 0.15rem !important;
            }

            .invoice-edit-page .form-control {
                font-size: 0.78rem !important;
                padding: 0.28rem 0.5rem !important;
                min-height: 32px !important;
                height: 32px !important;
                border-radius: 0.375rem !important;
                line-height: 1.3 !important;
            }

            .invoice-edit-page input[type="date"].form-control {
                font-size: 0.75rem !important;
                padding: 0.2rem 0.35rem !important;
            }

            .invoice-edit-page .small.text-muted,
            .invoice-edit-page .form-text,
            .invoice-edit-page .invalid-feedback,
            #manual-invoice-number-feedback {
                font-size: 0.67rem !important;
                margin-top: 0.15rem;
                line-height: 1.35;
            }

            /* Buttons inside panel (Save changes & Cancel) */
            .invoice-edit-panel .btn {
                padding: 0.38rem 0.65rem;
                font-size: 0.82rem;
                border-radius: 0.45rem;
            }

            /* Invoice overview stats */
            .invoice-edit-stat {
                font-size: 0.76rem;
                padding: 0.3rem 0;
            }

            .invoice-edit-memo-toolbar {
                font-size: 0.72rem;
                margin-bottom: 0.4rem;
            }

            .invoice-edit-memo-toolbar .btn {
                padding: 0.15rem 0.4rem;
                font-size: 0.68rem;
            }

            .invoice-edit-memo-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                gap: 0.4rem;
            }

            .invoice-edit-memo-item {
                padding: 0.35rem 0.5rem;
                border-radius: 0.45rem;
            }

            .invoice-edit-memo-item .form-check-label {
                font-size: 0.74rem;
            }

            .invoice-edit-memo-item__hint {
                font-size: 0.64rem;
                margin-top: 0.08rem;
                padding-left: 1.45rem;
            }

            #lrDetailsSection .invoice-edit-panel__header {
                padding: 0.35rem 0.55rem;
            }

            #lrDetailsSection .invoice-edit-panel__header h6 {
                font-size: 0.76rem;
            }

            #lrDetailsSection .invoice-edit-panel__header #lrSummaryMeta {
                font-size: 0.64rem !important;
            }

            #lrDetailsSection .invoice-edit-panel__header .badge {
                font-size: 0.64rem;
                padding: 0.15rem 0.35rem;
            }

            .invoice-edit-lr-block {
                border-radius: 0.45rem;
                margin-bottom: 0.45rem;
            }

            .invoice-edit-lr-block__head {
                padding: 0.35rem 0.55rem;
            }

            .invoice-edit-lr-block__head .fw-semibold {
                font-size: 0.74rem;
            }

            .invoice-edit-lr-block__head .small {
                font-size: 0.64rem;
            }

            .invoice-edit-lr-block__head .badge {
                font-size: 0.66rem;
                padding: 0.15rem 0.35rem;
            }

            .invoice-edit-lr-table th {
                font-size: 0.6rem !important;
                padding: 0.2rem 0.3rem !important;
                letter-spacing: 0.02em;
            }

            .invoice-edit-lr-table td {
                font-size: 0.68rem !important;
                padding: 0.2rem 0.3rem !important;
                white-space: nowrap;
            }

            .invoice-edit-route {
                display: inline-flex;
                align-items: center;
                gap: 0.12rem;
                flex-wrap: nowrap;
                white-space: nowrap;
            }

            .invoice-edit-route__city {
                font-size: 0.6rem !important;
                padding: 0.04rem 0.25rem !important;
            }

            .invoice-edit-route i {
                font-size: 0.65rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid invoice-edit-page mt-3 mb-4">
        <div class="invoice-edit-hero p-3 p-md-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="text-muted small mb-1">Invoice management</div>
                    <h4 class="mb-1">
                        <i class="bi bi-pencil-square me-2 text-brand-primary"></i>Edit invoice
                    </h4>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        <span class="badge text-bg-{{ $paymentBadge }}">{{ ucfirst($invoice->payment_status) }}</span>
                        <span class="badge text-bg-light border text-dark">
                            {{ ucfirst((string) $invoice->invoice_type) }}
                        </span>
                        @if ($invoice->invoice_number)
                            <span class="text-muted small">{{ $invoice->invoice_number }}</span>
                        @endif
                        @if ($invoice->manual_invoice_number)
                            <span class="text-muted small">Ref: {{ $invoice->manual_invoice_number }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('invoices.update', $invoice->id) }}" id="invoiceEditForm" class="needs-validation" novalidate>
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-8 order-lg-1 order-2">
                    <div class="invoice-edit-panel mb-4">
                        <div class="invoice-edit-panel__body">
                            <div class="invoice-edit-section-title">
                                <span class="invoice-edit-section-title__icon"><i class="bi bi-receipt"></i></span>
                                <div>
                                    <h5 class="mb-0">Bill details</h5>
                                    <div class="small text-muted">Reference number and bill date</div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="manual_invoice_number" class="form-label fw-semibold">
                                        Reference / manual invoice #
                                    </label>
                                    <input type="text" id="manual_invoice_number" name="manual_invoice_number"
                                        class="form-control @error('manual_invoice_number') is-invalid @enderror"
                                        value="{{ old('manual_invoice_number', $invoice->manual_invoice_number) }}"
                                        placeholder="Optional reference or printed bill no."
                                        autocomplete="off">
                                    <div id="manual-invoice-number-feedback" class="small mt-1 text-muted">Optional field</div>
                                    @error('manual_invoice_number')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="generated_date" class="form-label fw-semibold">
                                        Bill date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" id="generated_date" name="generated_date"
                                        class="form-control @error('generated_date') is-invalid @enderror @if ($invoice->payment_status !== 'pending') bg-light @endif"
                                        value="{{ old('generated_date', $invoice->generated_date ? \Illuminate\Support\Carbon::parse($invoice->generated_date)->format('Y-m-d') : '') }}"
                                        @if ($invoice->payment_status !== 'pending') readonly @endif
                                        required>
                                    @error('generated_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @if ($invoice->payment_status !== 'pending')
                                        <div class="form-text">
                                            <i class="bi bi-lock me-1"></i>Bill date is locked when payment is not pending.
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label for="due_date" class="form-label fw-semibold">Due date</label>
                                    <input type="date" id="due_date" name="due_date"
                                        class="form-control @error('due_date') is-invalid @enderror"
                                        value="{{ old('due_date', $invoice->due_date ? \Illuminate\Support\Carbon::parse($invoice->due_date)->format('Y-m-d') : '') }}">
                                    <div class="small mt-1 text-muted">Optional. Leave blank to use {{ \App\Models\Invoice::DEFAULT_CREDIT_DAYS }} days from the bill date.</div>
                                    @error('due_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (! $invoice->custom_invoice)
                        <div class="invoice-edit-panel mb-4 @error('memo_ids') border-danger @enderror">
                            <div class="invoice-edit-panel__body">
                                <div class="invoice-edit-section-title">
                                    <span class="invoice-edit-section-title__icon"><i class="bi bi-journal-text"></i></span>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0">Memos <span class="text-danger">*</span></h5>
                                        <div class="small text-muted">Select one or more memos to include on this invoice</div>
                                    </div>
                                    <span class="badge text-bg-primary" id="memoSelectedCount">0 selected</span>
                                </div>

                                <div class="invoice-edit-memo-toolbar">
                                    <div class="small text-muted" id="memoAvailableCount">Loading memos…</div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="memoSelectAllBtn">
                                            Select all
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="memoClearAllBtn">
                                            Clear
                                        </button>
                                    </div>
                                </div>

                                <div id="memoCheckboxesLoading" class="invoice-edit-skeleton">
                                    <div class="invoice-edit-skeleton-line" style="width: 70%;"></div>
                                    <div class="invoice-edit-skeleton-line" style="width: 55%;"></div>
                                    <div class="invoice-edit-skeleton-line" style="width: 62%;"></div>
                                </div>
                                <div id="memoCheckboxes" class="invoice-edit-memo-grid d-none"></div>
                                <div id="memoCheckboxesEmpty" class="invoice-edit-memo-empty d-none">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No memos available for this customer.
                                </div>

                                @error('memo_ids')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                                <div id="memoSelectionError" class="alert alert-danger py-2 px-3 small mt-3 mb-0 d-none">
                                    <i class="bi bi-exclamation-circle me-1"></i>Please select at least one memo.
                                </div>
                            </div>
                        </div>

                        <div id="lrDetailsSection" class="invoice-edit-panel d-none">
                            <div class="invoice-edit-panel__header d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <h6 class="mb-0 fw-semibold"><i class="bi bi-truck me-1"></i>LR breakdown</h6>
                                    <div class="small text-muted" id="lrSummaryMeta">—</div>
                                </div>
                                <span class="badge text-bg-secondary" id="lrSelectedMemoCount">0 memos</span>
                            </div>
                            <div id="lrDetailsContent" class="invoice-edit-panel__body pb-2"></div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4 order-lg-2 order-1">
                    <div class="invoice-edit-sidebar d-flex flex-column gap-3">
                        <div class="invoice-edit-panel">
                            <div class="invoice-edit-panel__header">
                                <h6 class="mb-0 fw-semibold">Invoice overview</h6>
                            </div>
                            <div class="invoice-edit-panel__body">
                                <div class="invoice-edit-stat">
                                    <span class="invoice-edit-stat__label">Customer</span>
                                    <span class="invoice-edit-stat__value">{{ $invoice->consignee?->name ?? '—' }}</span>
                                </div>
                                <div class="invoice-edit-stat">
                                    <span class="invoice-edit-stat__label">Company</span>
                                    <span class="invoice-edit-stat__value">{{ $invoice->company?->name ?? '—' }}</span>
                                </div>
                                <div class="invoice-edit-stat">
                                    <span class="invoice-edit-stat__label">GST slab</span>
                                    <span class="invoice-edit-stat__value">{{ (int) ($invoice->company->gst_slab ?? 5) }}%</span>
                                </div>
                                <div class="invoice-edit-stat">
                                    <span class="invoice-edit-stat__label">Tax basis</span>
                                    <span class="invoice-edit-stat__value text-end">
                                        {{ $companyStateName ?: '—' }}
                                        @if ($companyStateName && $customerStateName)
                                            <br><span class="text-muted fw-normal">→ {{ $customerStateName }}</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="invoice-edit-panel">
                            <div class="invoice-edit-panel__body d-grid gap-2">
                                <button type="submit" class="btn btn-brand-primary" id="invoiceEditSubmitBtn">
                                    <i class="bi bi-check2-circle me-1"></i>Save changes
                                </button>
                                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    @include('invoices.partials.confirmation-assets')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const manualInvoiceInput = document.getElementById('manual_invoice_number');
            const manualInvoiceFeedback = document.getElementById('manual-invoice-number-feedback');
            const checkUrl = @json(url('/api/check-manual-invoice-number-uniqueness'));
            const invoiceId = {{ $invoice->id }};
            const customerId = {{ (int) $invoice->customer_id }};
            const memoCheckboxes = document.getElementById('memoCheckboxes');
            const memoCheckboxesLoading = document.getElementById('memoCheckboxesLoading');
            const memoCheckboxesEmpty = document.getElementById('memoCheckboxesEmpty');
            const memoSelectedCount = document.getElementById('memoSelectedCount');
            const memoAvailableCount = document.getElementById('memoAvailableCount');
            const memoSelectionError = document.getElementById('memoSelectionError');
            const memoSelectAllBtn = document.getElementById('memoSelectAllBtn');
            const memoClearAllBtn = document.getElementById('memoClearAllBtn');
            const invoiceEditForm = document.getElementById('invoiceEditForm');
            const lrDetailsSection = document.getElementById('lrDetailsSection');
            const lrDetailsContent = document.getElementById('lrDetailsContent');
            const lrSelectedMemoCount = document.getElementById('lrSelectedMemoCount');
            const lrSummaryMeta = document.getElementById('lrSummaryMeta');
            const gstSlab = @json((int) ($invoice->company->gst_slab ?? 5));
            const companyState = @json(strtolower($invoice->company?->state?->name ?? ''));
            const customerState = @json(strtolower($invoice->consignee?->state?->name ?? ''));
            const invoiceType = @json($invoice->invoice_type);
            let manualInvoiceTimeout;
            let lrDetailsController = null;
            let availableMemoCount = 0;
            let lastEditTaxState = { baseAmount: 0, lrCount: 0, memoCount: 0 };

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatAmount(value) {
                return '₹' + Number(value || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function formatDate(value) {
                if (!value) {
                    return '—';
                }

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return escapeHtml(value);
                }

                return date.toLocaleDateString('en-GB');
            }

            function getSelectedMemoIds() {
                if (!memoCheckboxes) {
                    return [];
                }

                return Array.from(memoCheckboxes.querySelectorAll('.memo-checkbox:checked'))
                    .map(function(checkbox) {
                        return checkbox.value;
                    });
            }

            function calculateTaxPreview(baseAmount) {
                const gstRate = gstSlab / 100;
                const type = String(invoiceType || '').toLowerCase();
                const sameState = companyState && customerState && companyState === customerState;

                let sgst = 0;
                let cgst = 0;
                let igst = 0;
                let netAmount = baseAmount;
                const lines = [];

                if (sameState) {
                    if (type === 'forward') {
                        sgst = baseAmount * (gstRate / 2);
                        cgst = baseAmount * (gstRate / 2);
                        netAmount = baseAmount + sgst + cgst;
                        lines.push({ label: `SGST (${gstSlab / 2}%)`, amount: sgst });
                        lines.push({ label: `CGST (${gstSlab / 2}%)`, amount: cgst });
                    } else {
                        sgst = baseAmount * (gstRate / 2);
                        cgst = baseAmount * (gstRate / 2);
                        netAmount = baseAmount;
                        lines.push({ label: `SGST (${gstSlab / 2}%)`, amount: sgst });
                        lines.push({ label: `CGST (${gstSlab / 2}%)`, amount: cgst });
                        lines.push({ label: 'Tax under RCM', amount: sgst + cgst, muted: true });
                    }
                } else if (type === 'forward') {
                    igst = baseAmount * gstRate;
                    netAmount = baseAmount + igst;
                    lines.push({ label: `IGST (${gstSlab}%)`, amount: igst });
                } else {
                    igst = baseAmount * gstRate;
                    netAmount = baseAmount;
                    lines.push({ label: `IGST (${gstSlab}%)`, amount: igst });
                    lines.push({ label: 'Tax under RCM', amount: igst, muted: true });
                }

                const totalAmount = type === 'forward' ? netAmount : baseAmount + sgst + cgst + igst;

                return { baseAmount, netAmount, totalAmount, lines };
            }

            function renderTaxSummary(baseAmount, lrCount, memoCount) {
                lastEditTaxState = {
                    baseAmount: baseAmount,
                    lrCount: lrCount,
                    memoCount: memoCount,
                };
            }

            function renderLrDetails(data, selectedMemoIds) {
                if (!lrDetailsSection || !lrDetailsContent) {
                    return;
                }

                let html = '';
                let grandTotal = 0;
                let memoCount = 0;
                let lrCount = 0;

                selectedMemoIds.forEach(function(memoId) {
                    const rows = Array.isArray(data[memoId]) ? data[memoId] : [];
                    if (!rows.length) {
                        return;
                    }

                    memoCount += 1;
                    lrCount += rows.length;
                    const memoTotal = rows.reduce(function(sum, lr) {
                        return sum + Number(lr.amount || 0);
                    }, 0);
                    grandTotal += memoTotal;

                    html += `
                        <div class="invoice-edit-lr-block">
                            <div class="invoice-edit-lr-block__head">
                                <div>
                                    <div class="fw-semibold">Memo #${escapeHtml(memoId)}</div>
                                    <div class="small text-muted">${rows.length} lorry receipt(s)</div>
                                </div>
                                <span class="badge text-bg-light border">${formatAmount(memoTotal)}</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0 invoice-edit-lr-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>LR</th>
                                            <th>Route</th>
                                            <th>Date</th>
                                            <th>Truck</th>
                                            <th class="text-end">Wt</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows.map(function(lr) {
                                            return `
                                                <tr>
                                                    <td class="fw-semibold text-brand-primary">${escapeHtml(lr.lr_number)}</td>
                                                    <td>
                                                        <span class="invoice-edit-route">
                                                            <span class="invoice-edit-route__city">${escapeHtml(lr.source_city || '—')}</span>
                                                            <i class="bi bi-arrow-right-short text-muted"></i>
                                                            <span class="invoice-edit-route__city">${escapeHtml(lr.destination_city || '—')}</span>
                                                        </span>
                                                    </td>
                                                    <td>${formatDate(lr.gr_date)}</td>
                                                    <td>${escapeHtml(lr.truck_number || '—')}</td>
                                                    <td class="text-end">${escapeHtml(lr.weight ?? '—')}</td>
                                                    <td class="text-end fw-semibold">${formatAmount(lr.amount)}</td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });

                if (!html) {
                    lrDetailsSection.classList.add('d-none');
                    lrDetailsContent.innerHTML = '';
                    renderTaxSummary(0, 0, 0);
                    return;
                }

                lrDetailsContent.innerHTML = html;
                lrDetailsSection.classList.remove('d-none');

                if (lrSelectedMemoCount) {
                    lrSelectedMemoCount.textContent = memoCount + ' memo' + (memoCount === 1 ? '' : 's');
                }

                if (lrSummaryMeta) {
                    lrSummaryMeta.textContent = `${lrCount} LR(s) · subtotal ${formatAmount(grandTotal)}`;
                }

                renderTaxSummary(grandTotal, lrCount, memoCount);
            }

            function loadSelectedLrDetails() {
                const selectedMemoIds = getSelectedMemoIds();

                if (!lrDetailsSection || !lrDetailsContent) {
                    return;
                }

                if (!selectedMemoIds.length) {
                    lrDetailsSection.classList.add('d-none');
                    lrDetailsContent.innerHTML = '';
                    renderTaxSummary(0, 0, 0);
                    return;
                }

                lrDetailsSection.classList.remove('d-none');
                lrDetailsContent.innerHTML = `
                    <div class="text-muted small py-3 text-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                        Loading LR details…
                    </div>
                `;

                if (lrDetailsController) {
                    lrDetailsController.abort();
                }

                lrDetailsController = new AbortController();

                fetch(`/invoices/lrs?${(function() {
                        const params = new URLSearchParams();
                        selectedMemoIds.forEach(function(id) {
                            params.append('memo_ids[]', id);
                        });
                        params.set('invoice_id', invoiceId);
                        return params.toString();
                    })()}`, {
                        cache: 'no-store',
                        signal: lrDetailsController.signal,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Failed to load LR details');
                        }

                        return response.json();
                    })
                    .then(function(data) {
                        renderLrDetails(data, selectedMemoIds);
                    })
                    .catch(function(error) {
                        if (error && error.name === 'AbortError') {
                            return;
                        }

                        lrDetailsContent.innerHTML = `
                            <div class="alert alert-warning mb-0 mx-3">
                                <i class="bi bi-exclamation-triangle me-1"></i>Unable to load LR details. Please try again.
                            </div>
                        `;
                    });
            }

            function checkManualInvoiceUniqueness(manualNumber, currentInvoiceId) {
                if (!manualInvoiceInput || !manualInvoiceFeedback) {
                    return;
                }

                manualInvoiceFeedback.innerHTML =
                    '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Checking availability…</span>';
                manualInvoiceInput.classList.remove('is-valid', 'is-invalid');

                fetch(checkUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            manual_invoice_number: manualNumber,
                            invoice_id: currentInvoiceId,
                        }),
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.valid) {
                            manualInvoiceFeedback.innerHTML =
                                '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + data.message + '</span>';
                            manualInvoiceInput.classList.remove('is-invalid');
                            manualInvoiceInput.classList.add('is-valid');
                            manualInvoiceInput.setCustomValidity('');
                        } else {
                            manualInvoiceFeedback.innerHTML =
                                '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>' + data.message + '</span>';
                            manualInvoiceInput.classList.remove('is-valid');
                            manualInvoiceInput.classList.add('is-invalid');
                            manualInvoiceInput.setCustomValidity(data.message);
                        }
                    })
                    .catch(function() {
                        manualInvoiceFeedback.innerHTML =
                            '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Unable to check availability.</span>';
                        manualInvoiceInput.classList.remove('is-valid', 'is-invalid');
                        manualInvoiceInput.setCustomValidity('');
                    });
            }

            if (manualInvoiceInput && manualInvoiceFeedback) {
                manualInvoiceInput.addEventListener('input', function() {
                    const manualNumber = this.value.trim();
                    clearTimeout(manualInvoiceTimeout);

                    if (!manualNumber) {
                        manualInvoiceFeedback.innerHTML = '<span class="text-muted">Optional field</span>';
                        manualInvoiceInput.classList.remove('is-valid', 'is-invalid');
                        manualInvoiceInput.setCustomValidity('');
                        return;
                    }

                    manualInvoiceTimeout = setTimeout(function() {
                        checkManualInvoiceUniqueness(manualNumber, invoiceId);
                    }, 500);
                });

                manualInvoiceInput.addEventListener('blur', function() {
                    const manualNumber = this.value.trim();
                    if (manualNumber) {
                        clearTimeout(manualInvoiceTimeout);
                        checkManualInvoiceUniqueness(manualNumber, invoiceId);
                    }
                });
            }

            function bindMemoItem(item) {
                const checkbox = item.querySelector('.memo-checkbox');
                if (!checkbox) {
                    return;
                }

                item.addEventListener('click', function(event) {
                    if (event.target.closest('.form-check')) {
                        return;
                    }

                    checkbox.checked = !checkbox.checked;
                    updateMemoSelectionState();
                });

                checkbox.addEventListener('change', updateMemoSelectionState);
            }

            function updateMemoSelectionState() {
                if (!memoCheckboxes) {
                    return true;
                }

                const checkedBoxes = memoCheckboxes.querySelectorAll('.memo-checkbox:checked');
                const count = checkedBoxes.length;

                if (memoSelectedCount) {
                    memoSelectedCount.textContent = count + ' selected';
                    memoSelectedCount.classList.toggle('text-bg-primary', count > 0);
                    memoSelectedCount.classList.toggle('text-bg-secondary', count === 0);
                }

                memoCheckboxes.querySelectorAll('.invoice-edit-memo-item').forEach(function(item) {
                    const checkbox = item.querySelector('.memo-checkbox');
                    item.classList.toggle('is-checked', Boolean(checkbox && checkbox.checked));
                });

                const isValid = count > 0;
                if (memoSelectionError) {
                    memoSelectionError.classList.toggle('d-none', isValid);
                }

                if (memoSelectAllBtn) {
                    memoSelectAllBtn.disabled = availableMemoCount === 0 || count === availableMemoCount;
                }

                if (memoClearAllBtn) {
                    memoClearAllBtn.disabled = count === 0;
                }

                loadSelectedLrDetails();
                return isValid;
            }

            if (memoSelectAllBtn) {
                memoSelectAllBtn.addEventListener('click', function() {
                    memoCheckboxes?.querySelectorAll('.memo-checkbox').forEach(function(checkbox) {
                        checkbox.checked = true;
                    });
                    updateMemoSelectionState();
                });
            }

            if (memoClearAllBtn) {
                memoClearAllBtn.addEventListener('click', function() {
                    memoCheckboxes?.querySelectorAll('.memo-checkbox').forEach(function(checkbox) {
                        checkbox.checked = false;
                    });
                    updateMemoSelectionState();
                });
            }

            if (memoCheckboxes) {
                const selectedMemoIds = @json(old('memo_ids', $selectedMemoIds ?? []));

                fetch(`/invoices/memos/${customerId}?invoice_id=${invoiceId}`, {
                        cache: 'no-store',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Failed to load memos');
                        }

                        return response.json();
                    })
                    .then(function(data) {
                        if (memoCheckboxesLoading) {
                            memoCheckboxesLoading.classList.add('d-none');
                        }

                        memoCheckboxes.innerHTML = '';
                        memoCheckboxes.classList.remove('d-none');

                        if (!Array.isArray(data) || data.length === 0) {
                            memoCheckboxes.classList.add('d-none');
                            availableMemoCount = 0;
                            if (memoCheckboxesEmpty) {
                                memoCheckboxesEmpty.classList.remove('d-none');
                            }
                            if (memoAvailableCount) {
                                memoAvailableCount.textContent = 'No memos available';
                            }
                            updateMemoSelectionState();
                            return;
                        }

                        availableMemoCount = data.length;
                        if (memoAvailableCount) {
                            memoAvailableCount.textContent = `${availableMemoCount} memo(s) available`;
                        }

                        if (memoCheckboxesEmpty) {
                            memoCheckboxesEmpty.classList.add('d-none');
                        }

                        data.forEach(function(memo) {
                            const isSelected = selectedMemoIds.length > 0
                                ? selectedMemoIds.map(String).includes(String(memo.id))
                                : Boolean(memo.selected);

                            const item = document.createElement('div');
                            item.className = 'invoice-edit-memo-item' + (isSelected ? ' is-checked' : '');
                            item.innerHTML = `
                                <div class="form-check">
                                    <input class="form-check-input memo-checkbox" type="checkbox"
                                        name="memo_ids[]" value="${memo.id}" id="memo_${memo.id}"
                                        ${isSelected ? 'checked' : ''}>
                                    <label class="form-check-label" for="memo_${memo.id}">
                                        ${escapeHtml(memo.memo_number)}
                                    </label>
                                </div>
                                <div class="invoice-edit-memo-item__hint">Tap to toggle</div>
                            `;
                            memoCheckboxes.appendChild(item);
                            bindMemoItem(item);
                        });

                        updateMemoSelectionState();
                    })
                    .catch(function() {
                        if (memoCheckboxesLoading) {
                            memoCheckboxesLoading.classList.add('d-none');
                        }

                        memoCheckboxes.classList.remove('d-none');
                        memoCheckboxes.innerHTML = `
                            <div class="invoice-edit-memo-empty text-danger col-12">
                                <i class="bi bi-exclamation-triangle me-1"></i>Unable to load memos. Please refresh and try again.
                            </div>
                        `;
                    });
            }

            if (invoiceEditForm) {
                invoiceEditForm.addEventListener('submit', function(event) {
                    if (memoCheckboxes && !updateMemoSelectionState()) {
                        event.preventDefault();
                        event.stopPropagation();
                        memoSelectionError?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        return;
                    }

                    if (!invoiceEditForm.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                        invoiceEditForm.classList.add('was-validated');
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    invoiceEditForm.classList.add('was-validated');

                    const selectedMemoLabels = memoCheckboxes
                        ? Array.from(memoCheckboxes.querySelectorAll('.memo-checkbox:checked'))
                            .map(function(checkbox) {
                                const label = memoCheckboxes.querySelector('label[for="' + checkbox.id + '"]');
                                return label ? label.textContent.trim() : checkbox.value;
                            })
                        : [];
                    const isCustomInvoice = @json((bool) $invoice->custom_invoice);
                    let confirmationData;

                    if (isCustomInvoice) {
                        confirmationData = {
                            dialogTitle: 'Confirm invoice update',
                            title: 'Review invoice before saving',
                            confirmText: 'Save changes',
                            customer: @json($invoice->consignee?->name ?? '—'),
                            generatedDate: document.getElementById('generated_date')?.value || '',
                            reference: document.getElementById('manual_invoice_number')?.value || '',
                            invoiceType: invoiceType,
                            totalAmount: @json((float) $invoice->total_amount),
                            netAmount: @json((float) ($invoice->net_amount ?? $invoice->total_amount)),
                            totalLabel: 'Invoice total',
                        };
                    } else {
                        const tax = calculateTaxPreview(lastEditTaxState.baseAmount);
                        confirmationData = {
                            dialogTitle: 'Confirm invoice update',
                            title: 'Review invoice before saving',
                            confirmText: 'Save changes',
                            customer: @json($invoice->consignee?->name ?? '—'),
                            generatedDate: document.getElementById('generated_date')?.value || '',
                            reference: document.getElementById('manual_invoice_number')?.value || '',
                            memos: selectedMemoLabels.join(', ') || '—',
                            lrMeta: lastEditTaxState.lrCount
                                ? `${lastEditTaxState.lrCount} LR(s) across ${lastEditTaxState.memoCount} memo(s)`
                                : (lrSummaryMeta?.textContent || memoSelectedCount?.textContent || '—'),
                            invoiceType: invoiceType,
                            subtotal: tax.baseAmount,
                            taxLines: tax.lines,
                            totalAmount: tax.totalAmount,
                            netAmount: tax.netAmount,
                            totalLabel: String(invoiceType).toLowerCase() === 'forward' ? 'Net total' : 'Invoice total',
                        };
                    }

                    if (window.InvoiceConfirmation) {
                        window.InvoiceConfirmation.confirmInvoiceSummary(confirmationData).then(function(confirmed) {
                            if (confirmed) {
                                invoiceEditForm.submit();
                            }
                        });
                    } else if (window.confirm('Save these invoice changes?')) {
                        invoiceEditForm.submit();
                    }
                });
            }
        });
    </script>
@endsection
