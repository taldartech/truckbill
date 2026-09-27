@extends('layouts.app')

@section('title', 'TDS Report')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-3">
        <div class="card-body p-2 p-md-3">
            <form method="GET" action="{{ route('reports.tds') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-12 col-md-6 d-flex justify-content-end gap-1 gap-sm-2 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-brand-primary btn-sm" title="Search">
                            <i class="bi bi-search"></i> <span class="d-none d-sm-inline ms-1">Search</span>
                        </button>
                        <a href="{{ route('reports.tds') }}" class="btn btn-secondary btn-sm" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline ms-1">Reset</span>
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
            <div class="card-header py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">TDS Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning btn-sm" title="Export"
                            href="{{ route('reports.tds-export', ['start_date' => $startDate, 'end_date' => $endDate]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline ms-1">Export</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap small">
                        <thead>
                            <tr>
                                <th>Sr No</th>
                                <th>Date</th>
                                <th>LR No</th>
                                <th>Truck No</th>
                                <th>Destination</th>
                                <th>TR Freight</th>
                                <th>TDS</th>
                                <th>Rate</th>
                                <th>Transport Name</th>
                                <th>PAN No</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lrs as $index => $lr)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="text-nowrap">
                                        {{ \Carbon\Carbon::parse($lr->lr->gr_date)->format('d-m-Y') }}
                                    </td>
                                    <td>{{ $lr->lr->lr_number }}</td>
                                    <td>{{ $lr->lr->truck->truck_number ?? 'N/A' }}</td>
                                    <td>{{ $lr->lr->destination_city . ', ' . $lr->lr->destination_state }}</td>
                                    <td>{{ number_format($lr->tr_amount, 2) }}</td>
                                    <td>{{ roundUpTdsAmount((float) ($lr->tds_amount ?? 0)) }}</td>
                                    <td>{{ $lr->transporter_tds_percentage }}%</td>
                                    <td>{{ $lr->lr->transporter->name ?? 'N/A' }}</td>
                                    <td>{{ $lr->lr->transporter->pan_number ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-2 p-md-3 d-flex justify-content-end">
                {{ $lrs->links() }}
            </div>
        </div>
    @endif
@endsection
