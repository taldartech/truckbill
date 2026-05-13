@extends('layouts.app')

@section('title', 'Party Ledger')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    @include('reports.partials.select2-customer-brand')
@endpush

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.party-ledger') }}" id="searchForm"
                class="tb-report-customer-select2-form" onsubmit="validateForm(event)">
                <div class="row mb-2 g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="party_ledger_customer_id" class="form-select js-party-ledger-customer-select2"
                            required>
                            <option value=""></option>
                            @if ($presetCustomer)
                                <option value="{{ $presetCustomer->id }}" selected>{{ $presetCustomer->name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3 text-end d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-brand-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.party-ledger') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$customerId)
        <div class="alert alert-warning">
            Please select the Customer to generate the report.
        </div>
    @elseif (empty($lrs) || $lrs->isEmpty())
        <div class="alert alert-info">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Party Ledger</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.party-ledger-export', ['start_date' => $startDate, 'end_date' => $endDate, 'customer_id' => $customerId]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <table class="table table-bordered table-striped" style="font-size: 12px !important;">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>Date</th>
                            <th>LR Number</th>
                            <th>Truck No</th>
                            <th>Disptach (Destination)</th>
                            <th>LR Rate</th>
                            <th>LR Amt</th>
                            <th>Bill no</th>
                            <th>Bill Date</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lrs as $index => $lr)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}
                                </td>
                                <td>{{ $lr->lr_number }}</td>
                                <td>{{ $lr->truck ? $lr->truck->truck_number : 'N/A' }}</td>
                                <td>{{ $lr->destination_city . ', ' . $lr->destination_state }}</td>
                                <td style="text-align: right;">{{ number_format($lr->fr_rate, 2) }}</td>
                                <td style="text-align: right;">{{ number_format($lr->amount, 2) }}</td>
                                @php
                                    $invoice = $invoiceByLrId[$lr->id] ?? null;
                                    if (($invoice->invoice_type ?? '') === 'Forward') {
                                        $gst = 5;
                                    } else {
                                        $gst = 0;
                                    }
                                @endphp
                                <td>{{ $invoice ? ($invoice->invoice_number ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '') : ($invoice->manual_invoice_number ?? $invoice->id)) : 'N/A' }}</td>
                                <td style="white-space: nowrap;">
                                    {{ $invoice && $invoice->generated_date ? \Carbon\Carbon::parse($invoice->generated_date)->format('d-m-Y') : 'N/A' }}
                                </td>
                                <td style="text-align: right;">
                                    {{ number_format($lr->amount + ($lr->amount * $gst) / 100, 2) }}
                                </td>
                                <td>{{ $invoice->payment_status ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                {{ $lrs->links() }}
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const partiesAjax = {
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
                        results: data.results || [],
                        pagination: {
                            more: Boolean(data.pagination && data.pagination.more),
                        },
                    };
                },
                cache: true,
            };

            $('.js-party-ledger-customer-select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: false,
                placeholder: 'Search customer…',
                minimumInputLength: 0,
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $('#searchForm').closest('.card-body'),
                ajax: partiesAjax,
            });
        });
    </script>
    <script>
        function validateForm(event) {
            event.preventDefault();
            // Get the values of the date inputs
            const customerId = $('select[name="customer_id"]').val();

            if (!customerId) {
                alert('Please select the Customer.');
                return false;
            }

            // If validation passes, submit the form
            event.target.submit();
        }
    </script>
@endpush
