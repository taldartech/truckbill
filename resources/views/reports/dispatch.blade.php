@extends('layouts.app')

@section('title', 'Dispatch Report')

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.dispatch') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-end">
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

    @if (!$generated)
        <div class="alert alert-warning">
            Please generate the report by selecting at Dates.
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
                        <h3 class="card-title mb-0">Dispatch Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.dispatch-export', ['start_date' => $startDate, 'end_date' => $endDate]) }}">
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
                          <th>GR Date</th>
                          <th>LR No</th>
                          <th>Truck No</th>
                          <th>Destination</th>
                          <th>Invoice No.</th>
                          <th>Invoice Date</th>
                          <th>Weight</th>
                          <th>Bags</th>
                          <th>Fr rate</th>
                          <th> Amount</th>
                          <th>Unloading st.</th>
                          <th>Distt. </th>
                          <th>State</th>
                          <th>Consignor</th>
                          <th>Consignee</th>
                          <th>GST Number</th>
                          <th>Tr Rate</th>
                          <th>Tr Freight</th>
                          
                          <th>TDS</th>
                          <th>Cash Advance</th>
                          <th>Bank</th>
                          <th>Adjust In Advance</th>
                          <th>Diesel</th>
                          <th>Total</th>
                          
                          <th>Damage Amount</th>
                          <th>Shortage Amount</th>
                          <th>Detention</th>
                          <th>Deviation</th>
                          <th>Misc.</th>
                          <th>Balance</th>
                          <th>Memo No.</th>
                          <th>Bill No.</th>
                          <th>Driver No.</th>
                          <th>Transport Name</th>
                          <th>MAde to Payment</th>
                          <th>Payment Made to</th>
                          <th>TDS Deducted</th>
                          <th>E-way Bill No.</th>
                          <th>E-way Bill Expiry Date</th>
                          <th>Remarks</th>
                          <th>PAN No</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lrs as $index => $lr)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($lr->lr->gr_date)->format('d-m-Y') }}
                                </td>
                                <td>{{ $lr->lr->lr_number }}</td>
                                <td>{{ $lr->lr->truck->truck_number ?? 'N/A' }}</td>
                                <td>{{ $lr->lr->destination_city . ', ' . $lr->lr->destination_state }}</td>
                                <td>{{ number_format($lr->tr_fr_rate, 2) }}</td>
                                <td>{{ number_format($lr->tds_amount, 2) }}</td>
                                <td>{{ $lr->transporter_tds_percentage }}%</td>
                                <td>{{ $lr->lr->transporter->name ?? 'N/A' }}</td>
                                <td>{{ $lr->lr->transporter->pan_number ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
