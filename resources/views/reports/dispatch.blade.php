@extends('layouts.app')

@section('title', 'Dispatch Report')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.dispatch') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row mb-2" style="align-items: end;">
                    <div class="col-md-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    



                <div class="col-md-3">
    <label class="form-label">Balance As On Date</label>
    <input type="date"
        name="balance_as_on_date"
        class="form-control"
        value="{{ $balanceAsOnDate ?? '' }}">
    </div>

                <div class="col-md-3 d-flex align-items-end justify-content-end gap-2">
              <button type="submit" class="btn btn-primary">
                 <i class="bi bi-search"></i> Search
              </button>

             <a href="{{ route('reports.dispatch') }}?date={{ date('Y-m-d') }}"
               class="btn btn-secondary">
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
                            href="{{ route('reports.dispatch-export', array_filter([
                                'start_date' => $startDate,
                                'end_date' => $endDate,

                               'balance_as_on_date' => $balanceAsOnDate ?? null,
                                
                            ])) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                    </div>
                </div>
            </div>
            <style>
                .select2-dropdown {
                    border: 1px solid var(--brand-border);
                    border-radius: 0.375rem;
                }

                .select2-results__option--highlighted {
                    background-color: var(--brand-primary);
                    color: white;
                }
            </style>
            <div class="card-body table-sticky">
                

                @include('reports.exports.dispatch', [
              'balanceAsOnDate' => $balanceAsOnDate ?? null,
               'endDate' => $endDate,
                 ])

            </div>
            <div class="card-body pt-0 d-flex justify-content-end">
                {{ $lrs->links() }}
            </div>
        </div>
    @endif
@endsection
