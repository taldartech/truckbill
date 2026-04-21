@extends('layouts.app')

@push('styles')
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
    </style>
@endpush

@section('content')
    <div class="container mt-4">
        <div class="col-sm-6">
            <h3 class="mb-0">Invoice Management</h3>
        </div>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Always visible container --}}
        <div class="d-flex justify-content-end gap-2 mb-3">
            <button type="button" class="btn btn-brand-primary"
                onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
                <i class="bi bi-funnel"></i> Search filter
            </button>
            <a href="{{ route('custom-invoices.create') }}" class="btn btn-warning">
                <i class="bi bi-file-earmark-plus"></i> Create Custom Invoice
            </a>
            <a href="{{ route('invoices.create') }}" class="btn btn-brand-primary">
                <i class="bi bi-plus"></i> Create
            </a>
        </div>

        {{-- Quick Stats --}}
        @php
            $totalInvoices = $invoices->total();
            $totalAmount = $invoices->sum('total_amount');
            $paidCount = $invoices->where('payment_status', 'paid')->count();
            $unpaidCount = $invoices->where('payment_status', 'pending')->count();
        @endphp
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card text-bg-primary mb-2">
                    <div class="card-body p-2">Total Invoices: <b>{{ $totalInvoices }}</b></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-brand-primary text-white mb-2">
                    <div class="card-body p-2">Paid: <b>{{ $paidCount }}</b></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-warning mb-2">
                    <div class="card-body p-2">Unpaid: <b>{{ $unpaidCount }}</b></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-info mb-2">
                    <div class="card-body p-2">Total Amount: <b>&#8377;{{ number_format($totalAmount, 2) }}</b></div>
                </div>
            </div>
        </div>

        {{-- Hidden filter form --}}
        <form method="GET" action="{{ route('invoices.index') }}" id="advanced-search-box"
            class="card card-body shadow-sm mb-4 d-none">
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
                    <select name="customer" id="filter_customer_select" class="form-select select2">
                        <option value="">All</option>
                        @foreach ($consignees as $consignee)
                            <option value="{{ $consignee->name }}"
                                {{ request('customer') === $consignee->name ? 'selected' : '' }}>
                                {{ $consignee->name }}
                            </option>
                        @endforeach
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

        {{-- Main content card --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Invoices List</h3>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 10px">ID</th>
                            <th>Invoice #</th>
                            <th>Memo No.</th>
                            <th>Type</th>
                            <th>Generated Date</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr class="align-middle">
                                <td>{{ $invoice->id }}</td>
                                <td>
                                    <a href="#" class="text-primary invoice-details-link"
                                        data-id="{{ $invoice->id }}">
                                        {{ $invoice->invoice_number
                                            ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '')
                                            : $invoice->manual_invoice_number }}
                                    </a>
                                </td>
                                <td>{{ is_array($invoice->memo_ids) ? implode(', ', $invoice->memo_ids) : $invoice->memo_ids }}
                                </td>
                                <td>{{ $invoice->invoice_type }}</td>
                                {{-- <td>{{ $invoice->generated_date }}</td> --}}
                                <td>{{ formatDate(optional($invoice)->generated_date) }}</td>
                                <td>{{ $invoice->consignee ? $invoice->consignee->name : '-' }}</td>
                                <td class="text-brand-primary fw-bold">&#8377;{{ number_format($invoice->total_amount, 2) }}
                                </td>
                                <td class="text-brand-accent fw-bold">&#8377;{{ number_format($invoice->paid_amount, 2) }}
                                </td>
                                <td class="fw-bold {{ $invoice->remaining_amount > 0 ? 'text-danger' : 'text-brand-primary' }}">
                                    &#8377;{{ number_format($invoice->remaining_amount, 2) }}
                                </td>
                                <td>{{ ucfirst($invoice->payment_status) }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('reports.party-bill-export', ['invoice_number' => $invoice->id, 'type' => 'pdf']) }}"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" target="_blank"
                                            data-bs-placement="top" title="View PDF">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if (auth()->user()->canActAsAdmin())
                                            @if ($invoice->payment_status !== 'paid')
                                                <a href="{{ route('invoices.edit', $invoice->id) }}"
                                                    class="btn btn-sm btn-edit" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Edit Invoice">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endif
                                            @if ($invoice->payment_status === 'pending' && (int) $invoice->id === (int) $lastInvoiceId)
                                                <form action="{{ route('invoices.destroy', $invoice->id) }}"
                                                    method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="Delete Invoice"
                                                        onclick="return confirm('Delete this invoice?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                        @if (auth()->user()->canActAsAdmin() && $invoice->remaining_amount > 0)
                                            <button type="button" class="btn btn-sm btn-view add-payment-btn"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Add Payment"
                                                data-invoice-id="{{ $invoice->id }}"
                                                data-invoice-number="{{ $invoice->invoice_number
                                                    ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '')
                                                    : $invoice->manual_invoice_number }}"
                                                data-total-amount="{{ $invoice->total_amount }}"
                                                data-paid-amount="{{ $invoice->paid_amount }}"
                                                data-remaining-amount="{{ $invoice->remaining_amount }}">
                                                <i class="bi bi-currency-rupee"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">
                                    <div class="empty-state">
                                        <i class="bi bi-box"></i>
                                        <p class="mt-2">No invoices found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $invoices->links() }}
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
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
                        <div class="mb-3">
                            <label class="form-label fw-bold">Invoice Number</label>
                            <input type="text" class="form-control" id="payment_invoice_number" readonly>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Total Amount</label>
                                <input type="text" class="form-control" id="payment_total_amount" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Paid Amount</label>
                                <input type="text" class="form-control" id="payment_paid_amount" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Remaining Balance</label>
                            <input type="text" class="form-control fw-bold text-danger" id="payment_remaining_amount"
                                readonly>
                        </div>
                        <div class="mb-3">
                            <label for="payment_date" class="form-label fw-bold">Payment Date <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_mode" class="form-label fw-bold">Payment Mode <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="payment_mode" name="mode" required
                                placeholder="e.g., Cash, Bank Transfer, Cheque, UPI">
                        </div>
                        <div class="mb-3">
                            <label for="payment_amount" class="form-label fw-bold">Amount <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="payment_amount" name="amount"
                                step="0.01" min="0.01" required placeholder="0.00">
                            <div class="form-text">Maximum: ₹<span id="max_payment_amount">0.00</span></div>
                        </div>
                        <div class="mb-3">
                            <label for="payment_remark" class="form-label">Remark</label>
                            <textarea class="form-control" id="payment_remark" name="remark" rows="3" placeholder="Optional remarks"></textarea>
                        </div>
                        <div id="payment_error" class="alert alert-danger d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-brand-primary">
                            <i class="bi bi-check-circle"></i> Add Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Invoice Details Modal -->
    <div class="modal fade" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel"
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
                </div>
            </div>
        </div>
    </div>

    <script>
        // Wait for both jQuery and Bootstrap to be loaded
        $(document).ready(function() {
            // Initialize tooltips - ensure Bootstrap is loaded
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }

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
                                        <td class="text-end">₹${data.total_amount ? parseFloat(data.total_amount).toFixed(2) : '0.00'}</td>
                                    </tr>
                                    ${(data.sgst > 0 || data.cgst > 0 || data.igst > 0) ? `
                                    ${data.sgst > 0 ? `
                                    <tr>
                                        <th class="fw-bold">SGST (${data.sgst_rate ? parseFloat(data.sgst_rate).toFixed(2) : '0.00'}%):</th>
                                        <td class="text-end">₹${parseFloat(data.sgst).toFixed(2)}</td>
                                    </tr>
                                    ` : ''}
                                    ${data.cgst > 0 ? `
                                    <tr>
                                        <th class="fw-bold">CGST (${data.cgst_rate ? parseFloat(data.cgst_rate).toFixed(2) : '0.00'}%):</th>
                                        <td class="text-end">₹${parseFloat(data.cgst).toFixed(2)}</td>
                                    </tr>
                                    ` : ''}
                                    ${data.igst > 0 ? `
                                    <tr>
                                        <th class="fw-bold">IGST (${data.igst_rate ? parseFloat(data.igst_rate).toFixed(2) : '0.00'}%):</th>
                                        <td class="text-end">₹${parseFloat(data.igst).toFixed(2)}</td>
                                    </tr>
                                    ` : ''}
                                    ` : ''}
                                    <tr>
                                        <th class="fw-bold">Net Amount (After GST)${(data.invoice_type === 'Reverse') ? ' Under RCM' : ''}:</th>
                                        <td class="text-end"><strong>₹${parseFloat(data.invoice_type === 'Reverse' ? (data.total_amount || 0) : (data.net_amount || 0)).toFixed(2)}</strong></td>
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

                // Set form values
                $('#payment_invoice_id').val(invoiceId);
                $('#payment_invoice_number').val(invoiceNumber);
                $('#payment_total_amount').val('₹' + totalAmount.toFixed(2));
                $('#payment_paid_amount').val('₹' + paidAmount.toFixed(2));
                $('#payment_remaining_amount').val('₹' + remainingAmount.toFixed(2));
                $('#max_payment_amount').text(remainingAmount.toFixed(2));
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
                var remainingAmount = parseFloat($('#max_payment_amount').text());
                if (formData.amount > remainingAmount) {
                    $('#payment_error').removeClass('d-none').text(
                        'Payment amount cannot exceed remaining balance of ₹' + remainingAmount.toFixed(
                            2));
                    return;
                }

                // Disable submit button
                var submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.ajax({
                    url: '/invoices/' + invoiceId + '/payments',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Close modal
                            if (typeof bootstrap !== 'undefined') {
                                var modal = bootstrap.Modal.getInstance(document.getElementById(
                                    'addPaymentModal'));
                                if (modal) {
                                    modal.hide();
                                }
                            }

                            // Show success message
                            alert('Payment added successfully!');

                            // Reload page to show updated amounts
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
                            '<i class="bi bi-check-circle"></i> Add Payment');
                    }
                });
            });

            // Select2 init (same pattern as other pages)
            $('.select2').each(function() {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'All',
                    allowClear: true
                });
            });
        }); // End of document ready
    </script>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush
