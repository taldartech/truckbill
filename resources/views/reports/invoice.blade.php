@extends('layouts.app')

@section('title', 'Invoice Report')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-4 ">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.invoice') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row mb-2" style="align-items: end ;">
                    <div class="col-md-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                

                
               <div class="col-md-3  d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.invoice') }}?date={{ date('Y-m-d') }}" class="btn btn-secondary">
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
                            {{-- <th>E-Commerce GSTIN</th> --}}
                            <th>Rate</th>
                            <th>Taxable Value</th>
                            {{-- <th>Cess Amount</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $index => $invoice)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $invoice->consignee->gst_number }}</td>
                                <td>{{ $invoice->consignee->name }}</td>
                                <td>{{ $invoice->invoice_number ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '') : ($invoice->manual_invoice_number ?? $invoice->id) }}</td>
                                <td style="white-space: nowrap;">
                                    {{ \Carbon\Carbon::parse($invoice->generated_date)->format('d-m-Y') }}
                                </td>
                                <td>{{ $invoice->net_amount ?? $invoice->total_amount }}</td>
                                <td>{{ $invoice->consignee->state->name }}</td>
                                <td>{{ $invoice->invoice_type == 'Reverse' ? 'Yes' : 'No' }}</td>
                                <td>{{ $invoice->applicable_tax_percentage ?? '5' }}%</td>
                                <td>{{ 'Regular B2B' }}</td>
                                {{-- <td></td> --}}
                                <td>{{ '5.00' }}</td>
                                <td>{{ $invoice->base_amount ?? ($invoice->net_amount ?? $invoice->total_amount) }}</td>
                                {{-- <td></td> --}}
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                {{ $invoices->links() }}
            </div>
        </div>
    @endif
@endsection
