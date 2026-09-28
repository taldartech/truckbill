@extends('layouts.app')

@section('title', 'TDS Report')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.tds') }}" id="searchForm" onsubmit="validateForm(event)">
                <input type="hidden" name="tab" value="{{ $tab ?? 'payable' }}">
                <div class="row mb-2" style="align-items: end;">
                    <div class="col-md-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>

                    <div class="col-md-3 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.tds') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$generated)
        <div class="alert alert-warning">
            Please generate the report by selecting Dates.
        </div>
    @else
        <!-- Tabs for TDS Payable vs TDS Receivable -->
        <ul class="nav nav-pills mb-3" id="tdsReportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ ($tab ?? 'payable') !== 'receivable' ? 'active' : '' }}"
                   href="{{ route('reports.tds', ['start_date' => $startDate, 'end_date' => $endDate, 'tab' => 'payable']) }}">
                    <i class="bi bi-truck me-1"></i> TDS Payable (Transporters u/s 194C)
                    @if(isset($lrs) && method_exists($lrs, 'total') && $lrs->total() > 0)
                        <span class="badge {{ ($tab ?? 'payable') !== 'receivable' ? 'bg-light text-primary' : 'bg-secondary' }} ms-1">{{ $lrs->total() }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ ($tab ?? 'payable') === 'receivable' ? 'active' : '' }}"
                   href="{{ route('reports.tds', ['start_date' => $startDate, 'end_date' => $endDate, 'tab' => 'receivable']) }}">
                    <i class="bi bi-receipt me-1"></i> TDS Receivable (Customer Invoices u/s 194C)
                    @if(isset($customerPayments) && method_exists($customerPayments, 'total') && $customerPayments->total() > 0)
                        <span class="badge {{ ($tab ?? 'payable') === 'receivable' ? 'bg-light text-primary' : 'bg-secondary' }} ms-1">{{ $customerPayments->total() }}</span>
                    @endif
                </a>
            </li>
        </ul>

        @if (($tab ?? 'payable') === 'receivable')
            {{-- Customer TDS Receivable Tab --}}
            @if (empty($customerPayments) || $customerPayments->isEmpty())
                <div class="alert alert-info">
                    No customer TDS receivable records found for the selected dates.
                </div>
            @else
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title mb-0">TDS Receivable Report (Customer Deductions u/s 194C)</h3>
                                <small class="text-muted">TDS deducted by clients on invoice settlements (Form 26AS / AIS reconciliation)</small>
                            </div>
                            <div class="d-flex">
                                <a class="btn btn-warning ms-2"
                                   href="{{ route('reports.tds-export', ['start_date' => $startDate, 'end_date' => $endDate, 'tab' => 'receivable']) }}">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="overflow-x: auto;">
                        <table class="table table-bordered table-striped align-middle" style="font-size: 12px !important;">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr No</th>
                                    <th>Date</th>
                                    <th>Invoice #</th>
                                    <th>Customer Name</th>
                                    <th>GST / PAN</th>
                                    <th class="text-end">Invoice Amt (₹)</th>
                                    <th class="text-end">Remitted (₹)</th>
                                    <th class="text-end text-primary">TDS 194C (₹)</th>
                                    <th class="text-end text-danger">Deductions (₹)</th>
                                    <th class="text-end text-success">Total Settled (₹)</th>
                                    <th>Mode</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customerPayments as $index => $payment)
                                    @php
                                        $inv = $payment->invoice;
                                        $invTotal = (float) ($inv?->net_amount ?? $inv?->total_amount ?? 0);
                                        $settled = (float) $payment->amount + (float) ($payment->tds_amount ?? 0) + (float) ($payment->deduction_amount ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ $customerPayments->firstItem() ? $customerPayments->firstItem() + $index : $index + 1 }}</td>
                                        <td style="white-space: nowrap;">
                                            {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : '-' }}
                                        </td>
                                        <td class="fw-semibold">
                                            @if($inv)
                                                <a href="{{ route('invoices.show', $inv->id) }}" target="_blank" class="text-decoration-none">
                                                    {{ $inv->invoice_number ?: ($inv->manual_invoice_number ?: 'INV-'.$inv->id) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $inv?->consignee?->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($inv?->consignee?->gst_number)
                                                <span>{{ $inv->consignee->gst_number }}</span>
                                            @elseif($inv?->consignee?->pan_number)
                                                <span>{{ $inv->consignee->pan_number }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">&#8377;{{ number_format($invTotal, 2) }}</td>
                                        <td class="text-end fw-semibold text-dark">&#8377;{{ number_format((float) $payment->amount, 2) }}</td>
                                        <td class="text-end fw-bold text-primary">&#8377;{{ number_format((float) $payment->tds_amount, 2) }}</td>
                                        <td class="text-end text-danger">
                                            @if((float)($payment->deduction_amount ?? 0) > 0)
                                                &#8377;{{ number_format((float) $payment->deduction_amount, 2) }}
                                                @if($payment->deduction_reason)
                                                    <small class="text-muted d-block text-xs">({{ $payment->deduction_reason }})</small>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-success">&#8377;{{ number_format($settled, 2) }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ ucfirst($payment->mode ?? '-') }}</span></td>
                                        <td>{{ $payment->remark ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-end px-3">
                        {{ $customerPayments->links() }}
                    </div>
                </div>
            @endif
        @else
            {{-- Transporter TDS Payable Tab --}}
            @if (empty($lrs) || $lrs->isEmpty())
                <div class="alert alert-info">
                    No transporter TDS records found for the selected criteria.
                </div>
            @else
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title mb-0">TDS Payable Report (Transporters u/s 194C)</h3>
                                <small class="text-muted">TDS deducted by company from transporters / truck owners (Govt statutory liability)</small>
                            </div>
                            <div class="d-flex">
                                <a class="btn btn-warning ms-2"
                                    href="{{ route('reports.tds-export', ['start_date' => $startDate, 'end_date' => $endDate, 'tab' => 'payable']) }}">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="overflow-x: auto;">
                        <table class="table table-bordered table-striped" style="font-size: 12px !important;">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr No</th>
                                    <th>Date</th>
                                    <th>LR No</th>
                                    <th>Truck No</th>
                                    <th>Destination</th>
                                    <th class="text-end">TR Freight (₹)</th>
                                    <th class="text-end text-primary">TDS (₹)</th>
                                    <th>Rate</th>
                                    <th>Transport Name</th>
                                    <th>PAN No</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lrs as $index => $lr)
                                    <tr>
                                        <td>{{ $lrs->firstItem() ? $lrs->firstItem() + $index : $index + 1 }}</td>
                                        <td style="white-space: nowrap;">
                                            {{ \Carbon\Carbon::parse($lr->lr->gr_date)->format('d-m-Y') }}
                                        </td>
                                        <td>{{ $lr->lr->lr_number }}</td>
                                        <td>{{ $lr->lr->truck->truck_number ?? 'N/A' }}</td>
                                        <td>{{ $lr->lr->destination_city . ', ' . $lr->lr->destination_state }}</td>
                                        <td class="text-end">{{ number_format((float) $lr->tr_amount, 2) }}</td>
                                        <td class="text-end fw-bold text-primary">{{ roundUpTdsAmount((float) ($lr->tds_amount ?? 0)) }}</td>
                                        <td>{{ $lr->transporter_tds_percentage }}%</td>
                                        <td>{{ $lr->lr->transporter->name ?? 'N/A' }}</td>
                                        <td>{{ $lr->lr->transporter->pan_number ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-end px-3">
                        {{ $lrs->links() }}
                    </div>
                </div>
            @endif
        @endif
    @endif
@endsection
