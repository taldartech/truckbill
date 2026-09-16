@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Select2 custom styling for truck dropdown */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .select2-result-truck {
            padding: 4px 0;
        }

        .truck-info {
            font-size: 14px;
            line-height: 1.4;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 12px;
            padding-right: 20px;
            color: var(--brand-text-primary);
            line-height: 36px;
            background: var(--brand-card-bg);
            border: 1px solid var(--brand-border);
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single:focus-visible {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.25rem var(--brand-primary-light);
            outline: 0;
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 12px;
        }

        .select2-dropdown {
            border: 1px solid var(--brand-border);
            border-radius: 0.375rem;
        }

        .select2-results__option--highlighted {
            background-color: var(--brand-primary);
            color: white;
        }

        .invoice-stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .invoice-stat {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            min-width: 0;
            padding: 0.55rem 0.65rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: linear-gradient(185deg, #fff 0%, #f8fafc 100%);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
            border-top: 3px solid #94a3b8;
        }

        .invoice-stat--total { border-top-color: #2d8cff; }
        .invoice-stat--paid { border-top-color: #2fb36f; }
        .invoice-stat--partial { border-top-color: #0ea5e9; }
        .invoice-stat--unpaid { border-top-color: #d97706; }
        .invoice-stat--amount { border-top-color: #6366f1; }

        .invoice-stat__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.85rem;
            height: 1.85rem;
            flex-shrink: 0;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            background: #f1f5f9;
            color: #64748b;
        }

        .invoice-stat--total .invoice-stat__icon { background: rgba(45, 140, 255, 0.12); color: #2d8cff; }
        .invoice-stat--paid .invoice-stat__icon { background: rgba(47, 179, 111, 0.12); color: #2fb36f; }
        .invoice-stat--partial .invoice-stat__icon { background: rgba(14, 165, 233, 0.12); color: #0284c7; }
        .invoice-stat--unpaid .invoice-stat__icon { background: rgba(217, 119, 6, 0.12); color: #d97706; }
        .invoice-stat--amount .invoice-stat__icon { background: rgba(99, 102, 241, 0.12); color: #6366f1; }

        .invoice-stat__meta {
            min-width: 0;
            line-height: 1.15;
        }

        .invoice-stat__label {
            display: block;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .invoice-stat__value {
            display: block;
            margin-top: 0.1rem;
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 1199.98px) {
            .invoice-stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .invoice-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .invoices-index .app-index-list-card > .card-header {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                flex-wrap: nowrap !important;
            }

            .invoices-index .app-index-card-toolbar {
                width: auto !important;
                margin-left: auto !important;
            }
        }

        @media (max-width: 575.98px) {
            .invoices-index .app-index-list-card > .card-header {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                flex-wrap: nowrap !important;
                gap: 0.4rem !important;
                padding: 0.45rem 0.65rem !important;
            }

            .invoices-index .app-index-list-card > .card-header .card-title {
                font-size: 0.95rem !important;
                margin: 0 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                flex-shrink: 1 !important;
            }

            .invoices-index .app-index-card-toolbar {
                width: auto !important;
                margin-left: auto !important;
                display: inline-flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
                gap: 0.35rem !important;
                flex-shrink: 0 !important;
            }

            .invoices-index .app-index-card-toolbar .app-index-header-btn {
                width: 32px !important;
                height: 32px !important;
                min-width: 32px !important;
                min-height: 32px !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 8px !important;
                font-size: 1rem !important;
                flex: 0 0 auto !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12) !important;
            }
        }

        @media (max-width: 420px) {
            .invoice-stats {
                grid-template-columns: 1fr;
            }
        }

        #invoicesTable td,
        #invoicesTable th {
            font-size: 12px !important;
        }
    </style>
@endpush

@section('content')
    <div class="container mt-4 app-index-page app-index-page--standard invoices-index">
        @if (session('success'))
            <div class="mb-3">
                <span class="badge text-bg-dark border app-index-flash">
                    <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
                </span>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-3">
                <span class="badge text-bg-dark border app-index-flash">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i> {{ session('error') }}
                </span>
            </div>
        @endif

        <div class="card app-index-list-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">Invoice Management</h3>
                <div class="app-index-card-toolbar d-flex align-items-center gap-2">
                    <button type="button" class="btn app-index-header-btn" title="Search filter" aria-label="Search filter"
                        onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
                        <i class="bi bi-funnel"></i><span class="d-none d-sm-inline ms-1">Search filter</span>
                    </button>
                    <a href="{{ route('custom-invoices.create') }}" class="btn app-index-header-btn" title="Create Custom Invoice" aria-label="Create custom invoice">
                        <i class="bi bi-file-earmark-plus"></i><span class="d-none d-sm-inline ms-1">Create Custom Invoice</span>
                    </a>
                    <a href="{{ route('invoices.create') }}" class="btn app-index-header-btn" title="Create Invoice" aria-label="Create invoice">
                        <i class="bi bi-plus"></i><span class="d-none d-sm-inline ms-1">Create</span>
                    </a>
                </div>
            </div>

            <div class="p-2 p-md-3 border-bottom bg-white">
                {{-- Quick Stats --}}
                <div class="invoice-stats mb-0">
                    <div class="invoice-stat invoice-stat--total">
                        <span class="invoice-stat__icon"><i class="bi bi-receipt"></i></span>
                        <span class="invoice-stat__meta">
                            <span class="invoice-stat__label">Total Invoices</span>
                            <span class="invoice-stat__value">{{ number_format($totalInvoices) }}</span>
                        </span>
                    </div>
                    <div class="invoice-stat invoice-stat--paid">
                        <span class="invoice-stat__icon"><i class="bi bi-check2-circle"></i></span>
                        <span class="invoice-stat__meta">
                            <span class="invoice-stat__label">Paid</span>
                            <span class="invoice-stat__value">{{ number_format($paidCount) }}</span>
                        </span>
                    </div>
                    <div class="invoice-stat invoice-stat--partial">
                        <span class="invoice-stat__icon"><i class="bi bi-pie-chart"></i></span>
                        <span class="invoice-stat__meta">
                            <span class="invoice-stat__label">Partial Paid</span>
                            <span class="invoice-stat__value">{{ number_format($partialCount ?? 0) }}</span>
                        </span>
                    </div>
                    <div class="invoice-stat invoice-stat--unpaid">
                        <span class="invoice-stat__icon"><i class="bi bi-hourglass-split"></i></span>
                        <span class="invoice-stat__meta">
                            <span class="invoice-stat__label">Unpaid</span>
                            <span class="invoice-stat__value">{{ number_format($unpaidCount) }}</span>
                        </span>
                    </div>
                    <div class="invoice-stat invoice-stat--amount">
                        <span class="invoice-stat__icon"><i class="bi bi-currency-rupee"></i></span>
                        <span class="invoice-stat__meta">
                            <span class="invoice-stat__label">Total Amount</span>
                            <span class="invoice-stat__value">₹{{ number_format($totalAmount, 2) }}</span>
                        </span>
                    </div>
                </div>

                {{-- Hidden filter form --}}
                <form method="GET" action="{{ route('invoices.index') }}" id="advanced-search-box"
                    class="card card-body shadow-sm mb-0 d-none">
                    {{-- Filter checkboxes --}}
                    <div class="row g-2">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Choose Filter Fields:</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" value="manual_invoice_number" id="filter_invoice"
                                    onchange="toggleField(this)">
                                <label class="form-check-label" for="filter_invoice">Invoice #</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" value="customer" id="filter_customer"
                                    onchange="toggleField(this)">
                                <label class="form-check-label" for="filter_customer">Customer</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" value="payment_status" id="filter_status"
                                    onchange="toggleField(this)">
                                <label class="form-check-label" for="filter_status">Status</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" value="generated_date" id="filter_date"
                                    onchange="toggleField(this)">
                                <label class="form-check-label" for="filter_date">Date</label>
                            </div>
                        </div>
                    </div>

                    {{-- Conditionally visible filter fields --}}
                    <div class="row g-3 mt-2" id="filter-fields">
                        <div class="col-md-3 d-none" id="field_generated_date_from">
                            <label class="form-label">From Date</label>
                            <input type="date" name="generated_date_from" class="form-control"
                                value="{{ request('generated_date_from') }}">
                        </div>
                        <div class="col-md-3 d-none" id="field_generated_date_to">
                            <label class="form-label">To Date</label>
                            <input type="date" name="generated_date_to" class="form-control"
                                value="{{ request('generated_date_to') }}">
                        </div>
                        <div class="col-md-3 d-none" id="field_manual_invoice_number">
                            <label class="form-label">Invoice #</label>
                            <input type="text" name="manual_invoice_number" class="form-control"
                                value="{{ request('manual_invoice_number') }}">
                        </div>
                        <div class="col-md-3 d-none" id="field_customer">
                            <label class="form-label">Customer</label>
                            <select name="customer" id="filter_customer_select" class="form-select">
                                <option value="">All</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-none" id="field_payment_status">
                            <label class="form-label">Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="">All</option>
                                <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial
                                </option>
                                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-none" id="field_generated_date">
                            <label class="form-label">Date</label>
                            <input type="date" name="generated_date" class="form-control"
                                value="{{ request('generated_date') }}">
                        </div>
                    </div>

                    {{-- Form action buttons --}}
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-brand-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('invoices.index') }}" class="btn btn-back ms-2">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="invoicesTable" class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 10px">ID</th>
                                <th>Invoice #</th>
                                <th>Memo No.</th>
                                <th>Generated Date</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Remaining</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade fb-pay-modal" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-brand-primary text-white">
                    <h5 class="modal-title" id="addPaymentModalLabel">
                        <i class="bi bi-currency-rupee"></i> Add Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="addPaymentForm">
                    <div class="modal-body">
                        <input type="hidden" id="payment_invoice_id" name="invoice_id">
                        <input type="hidden" id="payment_invoice_number">
                        <input type="hidden" id="payment_total_amount">
                        <input type="hidden" id="payment_paid_amount">
                        <input type="hidden" id="payment_remaining_amount">

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label for="payment_date" class="form-label fw-semibold mb-1">Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date"
                                    name="payment_date" required>
                            </div>
                            <div class="col-6">
                                <label for="payment_mode" class="form-label fw-semibold mb-1">Mode <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="payment_mode" name="mode" required>
                                    <option value="">Select&hellip;</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="NEFT">NEFT</option>
                                    <option value="RTGS">RTGS</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="payment_amount" class="form-label fw-semibold mb-1">Amount <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">&#8377;</span>
                                <input type="number" class="form-control" id="payment_amount" name="amount"
                                    step="0.01" min="0.01" required placeholder="0.00">
                                <button type="button" class="btn btn-outline-secondary" id="fill_full_amount">Full</button>
                            </div>
                            <div class="form-text mt-1">Max: &#8377;<span id="max_payment_amount">0.00</span></div>
                        </div>

                        <div class="mb-1">
                            <label for="payment_remark" class="form-label fw-semibold mb-1">Remark</label>
                            <textarea class="form-control" id="payment_remark" name="remark" rows="2"
                                placeholder="Optional"></textarea>
                        </div>
                        <div id="payment_error" class="alert alert-danger d-none py-2 mb-0 mt-2"></div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn btn-brand-primary">
                            <i class="bi bi-check-circle"></i> Add payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Invoice Details Modal -->
    <div class="modal fade fb-pay-modal" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="invoiceDetailsModalLabel">
                        <i class="bi bi-receipt"></i> Invoice Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" id="invoiceDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading invoice details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" class="btn btn-primary" id="viewFullInvoiceBtn" style="display: none;">
                        <i class="bi bi-eye"></i> View Full Invoice
                    </a>
                    <a href="#" class="btn btn-primary" id="printInvoiceBtn" style="display: none;" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> Print Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('invoices.partials.confirmation-assets')

    <script>
        // Wait for both jQuery and Bootstrap to be loaded
        $(document).ready(function() {
            const initTooltips = function() {
                if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
                    return;
                }
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                    bootstrap.Tooltip.getOrCreateInstance(el);
                });
            };
            initTooltips();

            const invoiceTable = $('#invoicesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route('invoices.index') }}',
                    data: function(d) {
                        d.manual_invoice_number = $('input[name="manual_invoice_number"]').val();
                        d.customer = $('select[name="customer"]').val();
                        d.payment_status = $('select[name="payment_status"]').val();
                        d.generated_date_from = $('input[name="generated_date_from"]').val();
                        d.generated_date_to = $('input[name="generated_date_to"]').val();
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'invoices.id'
                    },
                    {
                        data: 'invoice_link',
                        name: 'invoice_link',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'memo_numbers',
                        name: 'invoices.memo_ids',
                        orderable: false
                    },
                    {
                        data: 'generated_date_formatted',
                        name: 'invoices.generated_date'
                    },
                    {
                        data: 'consignee_name',
                        name: 'consignees.name'
                    },
                    {
                        data: 'total_amount_formatted',
                        name: 'invoices.total_amount',
                        searchable: false
                    },
                    {
                        data: 'paid_amount_formatted',
                        name: 'paid_amount_formatted',
                        searchable: true,
                        orderable: true
                    },
                    {
                        data: 'remaining_amount_formatted',
                        name: 'remaining_amount_formatted',
                        searchable: true,
                        orderable: true
                    },
                    {
                        data: 'payment_status_badge',
                        name: 'invoices.payment_status'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                dom: '<"d-flex flex-wrap justify-content-between align-items-center gap-1 gap-sm-2 p-2 p-sm-3 border-bottom"<"d-flex align-items-center gap-1"l><"d-flex align-items-center gap-1"f>>' +
                    't' +
                    '<"app-index-partload-pagination d-flex flex-wrap align-items-center justify-content-between gap-2 p-2 p-sm-3"<"app-index-partload-pagination__summary"i><"app-index-partload-pagination__nav"p>>',
                drawCallback: function() {
                    initTooltips();
                }
            });

            function showInvoiceModal(modalElement) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } else {
                    setTimeout(function() {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            var modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        } else {
                            modalElement.classList.add('show');
                            modalElement.style.display = 'block';
                            document.body.classList.add('modal-open');
                        }
                    }, 100);
                }
            }

            // Handle invoice details link click
            $(document).on('click', '.invoice-details-link', function(e) {
                e.preventDefault();
                var invoiceId = $(this).data('id');

                var modalElement = document.getElementById('invoiceDetailsModal');
                showInvoiceModal(modalElement);

                $('#invoiceDetailsContent').html(`
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading invoice details...</p>
        </div>
    `);

                // Show view full invoice button
                $('#viewFullInvoiceBtn').attr('href', '/invoices/' + invoiceId).show();
                $('#printInvoiceBtn').attr('href', '/reports/party-bill-export?invoice_number=' + invoiceId + '&type=pdf').show();
                // Fetch invoice details
                $.ajax({
                    url: '/invoices/' + invoiceId,
                    type: 'GET',
                    success: function(data) {

                        var detailsHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-receipt"></i> Invoice Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="fw-bold">Invoice #:</td>
                                        <td>${data.invoice_number ? data.invoice_number + (data.manual_invoice_number ? ' (' + data.manual_invoice_number + ')' : '') : (data.manual_invoice_number || 'N/A')}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Type:</td>
                                        <td>${data.invoice_type || 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Date:</td>
                                        <td>${data.generated_date || 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Status:</td>
                                        <td><span class="badge bg-${data.payment_status === 'paid' ? 'brand-primary' : data.payment_status === 'partial' ? 'warning' : 'danger'}">${data.payment_status || 'N/A'}</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-person"></i> Customer Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="fw-bold">Name:</td>
                                        <td>${data.consignee ? data.consignee.name : 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Company:</td>
                                        <td>${data.company ? data.company.name : 'N/A'}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-currency-rupee"></i> Financial Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th class="fw-bold" style="width: 50%;">Base Amount:</th>
                                        <td class="text-end">${data.total_amount ? parseFloat(data.total_amount).toFixed(2) : '0.00'}</td>
                                    </tr>
                                    ${(data.sgst > 0 || data.cgst > 0 || data.igst > 0) ? `
                                    ${data.sgst > 0 ? `
                                    <tr>
                                        <th class="fw-bold">SGST (${data.sgst_rate ? parseFloat(data.sgst_rate).toFixed(2) : '0.00'}%):</th>
                                        <td class="text-end">${parseFloat(data.sgst).toFixed(2)}</td>
                                    </tr>
                                    ` : ''}
                                    ${data.cgst > 0 ? `
                                    <tr>
                                        <th class="fw-bold">CGST (${data.cgst_rate ? parseFloat(data.cgst_rate).toFixed(2) : '0.00'}%):</th>
                                        <td class="text-end">${parseFloat(data.cgst).toFixed(2)}</td>
                                    </tr>
                                    ` : ''}
                                    ${data.igst > 0 ? `
                                    <tr>
                                        <th class="fw-bold">IGST (${data.igst_rate ? parseFloat(data.igst_rate).toFixed(2) : '0.00'}%):</th>
                                        <td class="text-end">${parseFloat(data.igst).toFixed(2)}</td>
                                    </tr>
                                    ` : ''}
                                    ` : ''}
                                    <tr>
                                        <th class="fw-bold">Net Amount (After GST)${(data.invoice_type === 'Reverse') ? ' Under RCM' : ''}:</th>
                                        <td class="text-end"><strong>${parseFloat(data.invoice_type === 'Reverse' ? (data.total_amount || 0) : (data.net_amount || 0)).toFixed(2)}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <!-- Created By & Updated By Users -->
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-people"></i> User Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="fw-bold">Created By:</td>
                                        <td>${data.created_by ? data.creator.name + ' (' + data.creator.email + ')' : 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Updated By:</td>
                                        <td>${data.updated_by ? data.updater.name + ' (' + data.updater.email + ')' : 'N/A'}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;

                        $('#invoiceDetailsContent').html(detailsHtml);
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = 'Error loading invoice details. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        $('#invoiceDetailsContent').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> ${errorMessage}
                </div>
            `);
                    }
                });
            });

            // Initialize tooltips - ensure Bootstrap is loaded
            $(document).ready(function() {
                if (typeof bootstrap !== 'undefined') {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll(
                        '[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                }
            });

            // Toggle filter inputs when corresponding checkbox is enabled.
            window.toggleField = function(checkbox) {
                if (checkbox.value === 'generated_date') {
                    // Toggle both from and to date fields
                    ['field_generated_date_from', 'field_generated_date_to'].forEach(id => {
                        const field = document.getElementById(id);
                        if (checkbox.checked) {
                            field.classList.remove('d-none');
                        } else {
                            field.classList.add('d-none');
                            const input = field.querySelector('input');
                            if (input) input.value = '';
                        }
                    });
                } else {
                    const fieldId = 'field_' + checkbox.value;
                    const field = document.getElementById(fieldId);
                    if (checkbox.checked) {
                        field.classList.remove('d-none');
                    } else {
                        field.classList.add('d-none');
                        const input = field.querySelector('input, select');
                        if (input) input.value = '';
                    }
                }
            };

            // Restore selected filter fields from current query parameters.
            document.addEventListener('DOMContentLoaded', function() {
                const params = new URLSearchParams(window.location.search);
                const checkboxes = document.querySelectorAll('.form-check-input');
                let hasActiveFilter = false;

                checkboxes.forEach(checkbox => {
                    const value = checkbox.value;
                    let hasValue = false;

                    if (value === 'generated_date') {
                        hasValue = Boolean(params.get('generated_date_from') || params.get('generated_date_to'));
                    } else {
                        hasValue = Boolean(params.get(value));
                    }

                    if (hasValue) {
                        checkbox.checked = true;
                        hasActiveFilter = true;
                        if (value === 'generated_date') {
                            ['field_generated_date_from', 'field_generated_date_to'].forEach(id => {
                                document.getElementById(id).classList.remove('d-none');
                            });
                        } else {
                            document.getElementById('field_' + value).classList.remove('d-none');
                        }
                    }
                });

                // Auto-open filter panel when filters are active
                const advancedSearchBox = document.getElementById('advanced-search-box');
                if (advancedSearchBox && hasActiveFilter) {
                    advancedSearchBox.classList.remove('d-none');
                }
            });

            $('#advanced-search-box').on('submit', function(e) {
                e.preventDefault();
                invoiceTable.ajax.reload();
            });

            $('#advanced-search-box').on('click', 'a[href="{{ route('invoices.index') }}"]', function(e) {
                e.preventDefault();
                const form = document.getElementById('advanced-search-box');
                if (form) {
                    form.reset();
                }
                $('.form-check-input').prop('checked', false);
                ['field_generated_date_from', 'field_generated_date_to', 'field_manual_invoice_number', 'field_customer', 'field_payment_status', 'field_generated_date']
                    .forEach(function(id) {
                        const el = document.getElementById(id);
                        if (el) {
                            el.classList.add('d-none');
                        }
                    });
                $('#filter_customer_select').val(null).trigger('change');
                invoiceTable.ajax.reload();
            });

            function formatPaymentAmount(value) {
                return (parseFloat(value) || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            // Quick-fill the full remaining balance
            $('#fill_full_amount').on('click', function() {
                var max = parseFloat($('#payment_amount').attr('max')) || 0;
                $('#payment_amount').val(max.toFixed(2)).focus();
            });

            // Add Payment Modal Handler
            $(document).on('click', '.add-payment-btn', function(e) {
                e.preventDefault();

                var invoiceId = $(this).data('invoice-id');
                var invoiceNumber = $(this).data('invoice-number');
                var totalAmount = parseFloat($(this).data('total-amount')) || 0;
                var paidAmount = parseFloat($(this).data('paid-amount')) || 0;
                var remainingAmount = parseFloat($(this).data('remaining-amount')) || 0;

                // Validate data
                if (!invoiceId) {
                    alert('Error: Invoice ID is missing');
                    return;
                }

                // Set values for confirmation
                $('#payment_invoice_id').val(invoiceId);
                $('#payment_invoice_number').val(invoiceNumber || '—');
                $('#payment_total_amount').val(totalAmount);
                $('#payment_paid_amount').val(paidAmount);
                $('#payment_remaining_amount').val(remainingAmount);
                $('#max_payment_amount').text(formatPaymentAmount(remainingAmount));
                $('#payment_amount').attr('max', remainingAmount);
            $('#payment_amount').val('');
            $('#payment_mode').val('');
            $('#payment_remark').val('');
            $('#payment_date').val(new Date().toISOString().split('T')[0]); // Set today's date
            $('#payment_error').addClass('d-none').text('');

                // Show modal - ensure Bootstrap is loaded
                if (typeof bootstrap === 'undefined') {
                    alert('Error: Bootstrap is not loaded. Please refresh the page.');
                    return;
                }
                var modalElement = document.getElementById('addPaymentModal');
                if (!modalElement) {
                    alert('Error: Payment modal not found');
                    return;
                }
                var modal = new bootstrap.Modal(modalElement);
                modal.show();
            });

            // Handle payment form submission
            $('#addPaymentForm').on('submit', function(e) {
                e.preventDefault();
                var invoiceId = $('#payment_invoice_id').val();
                var formData = {
                    mode: $('#payment_mode').val(),
                    amount: parseFloat($('#payment_amount').val()),
                    remark: $('#payment_remark').val(),
                    payment_date: $('#payment_date').val(),
                    _token: '{{ csrf_token() }}'
                };

                // Validate amount
                var remainingAmount = parseFloat($('#payment_amount').attr('max')) || 0;
                if (!formData.mode) {
                    $('#payment_error').removeClass('d-none').text('Please select a payment mode.');
                    return;
                }
                if (!formData.amount || formData.amount <= 0) {
                    $('#payment_error').removeClass('d-none').text('Please enter a valid payment amount.');
                    return;
                }
                if (formData.amount > remainingAmount) {
                    $('#payment_error').removeClass('d-none').text(
                        'Payment amount cannot exceed remaining balance of ' + remainingAmount.toFixed(2));
                    return;
                }

                $('#payment_error').addClass('d-none').text('');

                var submitBtn = $(this).find('button[type="submit"]');
                var invoiceTotal = parseFloat($('#payment_total_amount').val()) || 0;
                var paidAmount = parseFloat($('#payment_paid_amount').val()) || 0;
                var confirmationData = {
                    invoiceNumber: $('#payment_invoice_number').val(),
                    paymentDate: formData.payment_date,
                    mode: formData.mode,
                    amount: formData.amount,
                    remark: formData.remark,
                    invoiceTotal: invoiceTotal,
                    paidAmount: paidAmount,
                    balanceBefore: remainingAmount,
                    balanceAfter: Math.max(0, remainingAmount - formData.amount),
                };

                function submitPayment() {
                    submitBtn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm"></span> Processing...');

                    $.ajax({
                        url: '/invoices/' + invoiceId + '/payments',
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            if (response.success) {
                                if (typeof bootstrap !== 'undefined') {
                                    var modal = bootstrap.Modal.getInstance(document.getElementById(
                                        'addPaymentModal'));
                                    if (modal) {
                                        modal.hide();
                                    }
                                }

                                alert('Payment added successfully!');
                                location.reload();
                            }
                        },
                        error: function(xhr) {
                            var errorMessage = 'Error adding payment. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            $('#payment_error').removeClass('d-none').text(errorMessage);
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-check-circle"></i> Add payment');
                        }
                    });
                }

                if (window.InvoiceConfirmation) {
                    window.InvoiceConfirmation.confirmPaymentSummary(confirmationData).then(function(confirmed) {
                        if (confirmed) {
                            submitPayment();
                        }
                    });
                } else if (window.confirm('Record this payment?')) {
                    submitPayment();
                }
            });

            const customerFilterFromRequest = @json(request('customer', ''));

            $('#filter_customer_select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search customer…',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '/api/consignees/search',
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
                            results: (data.results || []).map(function(item) {
                                return {
                                    id: item.text,
                                    text: item.text,
                                };
                            }),
                            pagination: {
                                more: Boolean(data.pagination && data.pagination.more),
                            },
                        };
                    },
                    cache: true,
                },
            });

            if (customerFilterFromRequest) {
                const opt = new Option(customerFilterFromRequest, customerFilterFromRequest, true, true);
                $('#filter_customer_select').append(opt).trigger('change');
            }
        }); // End of document ready
    </script>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush
