@extends('layouts.app')

@section('title', 'Invoice Report')

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.invoice') }}" id="searchForm" onsubmit="validateForm(event)">
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
                        <a href="{{ route('reports.invoice') }}?date={{ date('Y-m-d') }}"
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
    @elseif (empty($invoices) || $invoices->isEmpty())
        <div class="alert alert-info">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Invoice Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.invoice-export', ['start_date' => $startDate, 'end_date' => $endDate]) }}">
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
                          <th>GSTIN/UIN of Recipient</th>
                          <th>Receiver</th>
                          <th>Invoice Number</th>
                          <th>Invoice Date</th>
                          <th>Invoice Value</th>
                          <th>Place of Supply</th>
                          <th>Reverse Charge</th>
                          <th>Applicable % of Tax</th>
                          <th>Invoice Type</th>
                          <th>E-Commerce GSTIN</th>
                          <th>Rate</th>
                          <th>Taxable Value</th>
                          <th>Cess Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $index => $invoice)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $invoice->consignee->gst_number }}</td>
                                <td>{{ $invoice->consignee->name }}</td>
                                <td>{{ $invoice->manual_invoice_number }}</td>
                                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($invoice->generated_date)->format('d-m-Y') }}
                                </td>
                                <td>{{ $invoice->net_amount }}</td>
                                <td>{{ $invoice->consignee->state->name }}</td>
                                <td>{{ $invoice->company->gst_scheme == 'reverse charge' ? 'Yes' : 'No' }}</td>
                                <td>{{ $invoice->applicable_tax_percentage }}%</td>
                                <td>{{ "Regular B2B" }}</td>
                                <td></td>
                                <td>{{ "5.00" }}</td>
                                <td>{{ $invoice->net_amount }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
