@extends('layouts.app')

@section('content')
    <div class="container px-0 mt-4">
       
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Always visible container --}}
     <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-start align-items-sm-center gap-2 mb-3">
    
    <h3 class="mb-2 mb-sm-0">Invoice Management</h3>
    
    <div class="d-flex flex-column flex-sm-row gap-2">
        <button type="button" class="btn btn-info"
            onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('invoices.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>
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
                <div class="card text-bg-success mb-2">
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
                        <input class="form-check-input" type="checkbox" value="company" id="filter_company"
                            onchange="toggleField(this)">
                        <label class="form-check-label" for="filter_company">Company</label>
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
                <div class="col-md-3 d-none" id="field_manual_invoice_number">
                    <label class="form-label">Invoice #</label>
                    <input type="text" name="manual_invoice_number" class="form-control"
                        value="{{ request('manual_invoice_number') }}">
                </div>
                <div class="col-md-3 d-none" id="field_customer">
                    <label class="form-label">Customer</label>
                    <input type="text" name="customer" class="form-control" value="{{ request('customer') }}">
                </div>
                <div class="col-md-3 d-none" id="field_company">
                    <label class="form-label">Company</label>
                    <input type="text" name="company" class="form-control" value="{{ request('company') }}">
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
                    <input type="date" name="generated_date" class="form-control" value="{{ request('generated_date') }}">
                </div>
            </div>

            {{-- Form action buttons --}}
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-secondary">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary ms-2">
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
                            <th>Manual Invoice #</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Generated Date</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            @if (auth()->user()->userRole->name === 'admin')
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr class="align-middle">
                                <td>{{ $invoice->id }}</td>
                                <td>
                                    <a href="#" class="text-primary invoice-details-link"
                                        data-id="{{ $invoice->id }}">
                                        {{ $invoice->manual_invoice_number }}
                                    </a>
                                </td>
                                <td>{{ $invoice->company ? $invoice->company->name : '-' }}</td>
                                <td>{{ $invoice->invoice_type }}</td>
                                <td>{{ $invoice->generated_date }}</td>
                                <td>{{ $invoice->consignee ? $invoice->consignee->name : '-' }}</td>
                                <td class="text-success fw-bold">&#8377;{{ number_format($invoice->total_amount, 2) }}
                                </td>
                                <td>{{ ucfirst($invoice->payment_status) }}</td>
                                @if (auth()->user()->userRole->name === 'admin')
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('invoices.show', $invoice->id) }}"
                                                class="btn btn-sm btn-info" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="View Invoice">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if ($invoice->payment_status !== 'paid')
                                                <a href="{{ route('invoices.edit', $invoice->id) }}"
                                                    class="btn btn-sm btn-info" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Edit Invoice">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endif
                                            @if ($invoice->payment_status === 'pending')
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
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">
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

    <!-- Invoice Details Modal -->
    <div class="modal fade" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Handle invoice details link click
        $(document).on('click', '.invoice-details-link', function(e) {
            e.preventDefault();
            var invoiceId = $(this).data('id');

            // Show modal with loading state
            var modal = new bootstrap.Modal(document.getElementById('invoiceDetailsModal'));
            modal.show();

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
                                        <td>${data.manual_invoice_number || 'N/A'}</td>
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
                                        <td><span class="badge bg-${data.payment_status === 'paid' ? 'success' : data.payment_status === 'partial' ? 'warning' : 'danger'}">${data.payment_status || 'N/A'}</span></td>
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
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">Total Amount:</span>
                                            <span>₹${data.total_amount ? parseFloat(data.total_amount).toFixed(2) : '0.00'}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">Paid Amount:</span>
                                            <span>₹${data.paid_amount ? parseFloat(data.paid_amount).toFixed(2) : '0.00'}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">Balance:</span>
                                            <span class="fw-bold ${(data.total_amount - data.paid_amount) > 0 ? 'text-danger' : 'text-success'}">
                                                ₹${(data.total_amount - data.paid_amount).toFixed(2)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
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

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        function toggleField(checkbox) {
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

        // Initialize filter fields based on URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            // Get all filter checkboxes
            const checkboxes = document.querySelectorAll('.form-check-input');

            // Check each checkbox based on URL parameters
            checkboxes.forEach(checkbox => {
                const value = checkbox.value;
                const fieldId = 'field_' + value;
                const field = document.getElementById(fieldId);
                const urlValue = new URLSearchParams(window.location.search).get(value);

                if (urlValue) {
                    checkbox.checked = true;
                    field.classList.remove('d-none');
                }
            });
        });
    </script>
@endsection
