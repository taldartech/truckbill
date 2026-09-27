@extends('layouts.app')

@section('title', 'Party Bill')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    @include('reports.partials.select2-customer-brand')
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-body p-2 p-md-3">
            <form method="GET" action="{{ route('reports.party-bill') }}" id="searchForm" onsubmit="return validatePartyBillForm(event)"
                class="tb-report-customer-select2-form">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1">Customer <span class="text-muted">(optional)</span></label>
                        <select name="customer_id" id="party_bill_customer_id" class="form-select form-select-sm">
                            <option value=""></option>
                            @if ($presetCustomer)
                                <option value="{{ $presetCustomer->id }}" selected>{{ $presetCustomer->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-6 col-md-5">
                        <label class="form-label small mb-1">Invoice Number <span class="text-danger">*</span></label>
                        <select name="invoice_number" id="party_bill_invoice_number" class="form-select form-select-sm" required>
                            <option value="">Select Invoice Number</option>
                            @if (! empty($invoices))
                                @foreach ($invoices as $inv)
                                    <option @if ($id == $inv->id) selected @endif value="{{ $inv->id }}">
                                        {{ $inv->invoice_number ? $inv->invoice_number . ($inv->manual_invoice_number ? ' (' . $inv->manual_invoice_number . ')' : '') : ($inv->manual_invoice_number ?? $inv->id) }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex justify-content-end gap-1 gap-sm-2">
                        <button type="submit" class="btn btn-brand-primary btn-sm" title="Search">
                            <i class="bi bi-search"></i> <span class="d-none d-sm-inline ms-1">Search</span>
                        </button>
                        <a href="{{ route('reports.party-bill') }}" class="btn btn-secondary btn-sm" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline ms-1">Reset</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$invoice && $id)
        <div class="alert alert-info">
            No records found for the selected invoice.
        </div>
    @elseif (!$invoice && !$id)
        <div class="alert alert-info">
            Please select an Invoice Number to generate the report.
        </div>
    @elseif (!$invoice)
        <div class="alert alert-info">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Party Bill</h3>
                    </div>
                    <div class="d-flex gap-1 gap-sm-2">
                        <a class="btn btn-warning btn-sm" title="Export"
                            href="{{ route('reports.party-bill-export', ['invoice_number' => $id]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline ms-1">Export</span>
                        </a>
                        <a href="{{ route('reports.party-bill-export', ['invoice_number' => $invoice->id, 'type' => 'pdf']) }}"
                            class="btn btn-danger btn-sm" title="PDF" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i> <span class="d-none d-sm-inline ms-1">PDF</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    @include('reports.exports.party-bill')
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const partyBillInvoiceNumbersUrl = @json(route('api.invoice-numbers'));

        function validatePartyBillForm(event) {
            const invoiceId = $('#party_bill_invoice_number').val();

            if (!invoiceId) {
                event.preventDefault();
                alert('Please select an invoice number.');

                return false;
            }

            return true;
        }

        let partyBillInvoiceListXhr = null;
        let partyBillCustomerInvoiceReloadTimer = null;

        function filterInvoiceNumbers(customerId, options) {
            options = options || {};
            const clearInvoiceSelection = options.clearInvoiceSelection === true;

            if (partyBillInvoiceListXhr && partyBillInvoiceListXhr.readyState !== 4) {
                partyBillInvoiceListXhr.abort();
            }

            const url = customerId
                ? partyBillInvoiceNumbersUrl + '/' + encodeURIComponent(customerId)
                : partyBillInvoiceNumbersUrl;

            partyBillInvoiceListXhr = $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                success: function(data) {
                    updateInvoiceDropdown(data, {
                        clearInvoiceSelection: clearInvoiceSelection,
                    });
                },
                error: function(jqXHR, textStatus) {
                    if (textStatus === 'abort') {
                        return;
                    }
                    alert('Failed to fetch invoice numbers. Please try again.');
                },
            });
        }

        function updateInvoiceDropdown(invoices, options) {
            options = options || {};
            const clearInvoiceSelection = options.clearInvoiceSelection === true;

            const list = Array.isArray(invoices) ? invoices : [];

            const $invoice = $('#party_bill_invoice_number');
            if ($invoice.hasClass('select2-hidden-accessible')) {
                $invoice.select2('destroy');
            }

            let selectedInvoiceId = '';
            if (!clearInvoiceSelection) {
                selectedInvoiceId = $invoice.val() || @json($id ?? '');
            }
            let optionsHtml = '<option value="">Select Invoice Number</option>';

            function getInvoiceLabel(invoice) {
                const invoiceNumber = invoice.invoice_number;
                const manualInvoiceNumber = invoice.manual_invoice_number;

                if (invoiceNumber) {
                    return manualInvoiceNumber
                        ? `${invoiceNumber} (${manualInvoiceNumber})`
                        : invoiceNumber;
                }

                return manualInvoiceNumber ?? invoice.id;
            }

            list.forEach(function(invoiceNumber) {
                let selected = selectedInvoiceId == invoiceNumber.id ? 'selected' : '';
                optionsHtml +=
                    `<option value="${invoiceNumber.id}" ${selected}>${getInvoiceLabel(invoiceNumber)}</option>`;
            });
            $invoice.html(optionsHtml);
            $invoice.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search invoice number…',
                allowClear: false,
                minimumInputLength: 0,
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $('#searchForm').closest('.card-body'),
                matcher: function(params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }
                    if (data.id === '') {
                        return null;
                    }
                    const term = params.term.toLowerCase();
                    const text = (data.text || '').toLowerCase();
                    const idStr = String(data.id ?? '');
                    if (text.indexOf(term) > -1 || idStr.indexOf(term) > -1) {
                        return data;
                    }

                    return null;
                },
            });
        }

        $(document).ready(function() {
            const partiesAjax = {
                url: @json(route('api.consignees.search')),
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
                        results: data.results || [],
                        pagination: {
                            more: Boolean(data.pagination && data.pagination.more),
                        },
                    };
                },
                cache: true,
            };

            const $customer = $('#party_bill_customer_id');

            $customer.select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: 'Filter by customer (optional)…',
                minimumInputLength: 0,
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $('#searchForm').closest('.card-body'),
                ajax: partiesAjax,
            });

            function schedulePartyBillInvoiceReloadForCustomer(clearInvoiceSelection) {
                clearTimeout(partyBillCustomerInvoiceReloadTimer);
                partyBillCustomerInvoiceReloadTimer = setTimeout(function() {
                    const raw = $customer.val();
                    const id = raw === null || raw === undefined || raw === '' ? '' : String(raw);
                    filterInvoiceNumbers(id, { clearInvoiceSelection: clearInvoiceSelection });
                }, 30);
            }

            $customer.on('change select2:clear', function() {
                schedulePartyBillInvoiceReloadForCustomer(true);
            });

            const serverCustomerId = @json($customerId ?? null);
            filterInvoiceNumbers(serverCustomerId ? String(serverCustomerId) : '', {
                clearInvoiceSelection: false,
            });
        });
    </script>
@endpush
