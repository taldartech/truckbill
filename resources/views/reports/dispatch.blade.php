@extends('layouts.app')

@section('title', 'Dispatch Report')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-3">
        <div class="card-body p-2 p-md-3">
            <form method="GET" action="{{ route('reports.dispatch') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1 text-nowrap">Balance As On Date</label>
                        <input type="date" name="balance_as_on_date" class="form-control form-control-sm" value="{{ $balanceAsOnDate ?? '' }}">
                    </div>
                    <div class="col-6 col-md-3 d-flex align-items-end justify-content-end gap-1 gap-sm-2">
                        <button type="submit" class="btn btn-brand-primary btn-sm" title="Search">
                            <i class="bi bi-search"></i> <span class="d-none d-sm-inline ms-1">Search</span>
                        </button>
                        <a href="{{ route('reports.dispatch') }}?date={{ date('Y-m-d') }}" class="btn btn-secondary btn-sm" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline ms-1">Reset</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$generated)
        <div class="alert alert-warning py-2">
            Please generate the report by selecting Dates.
        </div>
    @elseif (empty($lrs) || $lrs->isEmpty())
        <div class="alert alert-info py-2">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-3">
            <div class="card-header py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Dispatch Report</h3>
                    <div>
                        <a class="btn btn-warning btn-sm" title="Export"
                            href="{{ route('reports.dispatch-export', array_filter([
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                               'balance_as_on_date' => $balanceAsOnDate ?? null,
                            ])) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline ms-1">Export</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    @include('reports.exports.dispatch', [
                        'balanceAsOnDate' => $balanceAsOnDate ?? null,
                        'endDate' => $endDate,
                    ])
                </div>
            </div>
            <div class="card-body py-2 px-3 d-flex justify-content-end">
                {{ $lrs->links() }}
            </div>
        </div>
    @endif
@endsection
