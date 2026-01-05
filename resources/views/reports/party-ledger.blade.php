@extends('layouts.app')

@section('title', 'Customer & Consignee wise Report')

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
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.party-ledger') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row mb-2">
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
                        <select name="customer_id" class="form-select select2" required>
                            <option value="">Select Customer</option>
                            @foreach ($consignees as $customer)
                                <option @if ($customerId == $customer->id) selected @endif value="{{ $customer->id }}">
                                    {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 text-end d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('voucher_payments.daily') }}?date={{ date('Y-m-d') }}"
                            class="btn btn-outline-secondary">
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
                                    $invoice = \App\Models\Invoice::whereJsonContains('lr_ids', $lr->id)->first();
                                    // Fetch amount and payment status from the bill if exists
                                    if ($invoice->total_amount == $invoice->net_amount) {
                                        $gst = 5;
                                    } else {
                                        $gst = 0;
                                    }
                                @endphp
                                <td>{{ $invoice->manual_invoice_number }}</td>
                                <td style="white-space: nowrap;">
                                    {{ $invoice->generated_date ? \Carbon\Carbon::parse($invoice->generated_date)->format('d-m-Y') : 'N/A' }}
                                </td>
                                <td style="text-align: right;">
                                    {{ number_format($lr->amount + ($lr->amount * $gst) / 100, 2) }}
                                </td>
                                <td>{{ $invoice->payment_status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').each(function() {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
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
