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
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-3 align-self-end p-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="balance_as_on_end_date"
                                id="balance_as_on_end_date" value="1"
                                @if ($balanceAsOnEndDate ?? false) checked @endif>
                            <label class="form-check-label" for="balance_as_on_end_date">
                                Balance &amp; payments as on end date
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-end">
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
                                'balance_as_on_end_date' => ($balanceAsOnEndDate ?? false) ? '1' : null,
                            ])) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                    </div>
                </div>
            </div>
            <style>
                .table-sticky {
                    width: 100%;
                    /* Set your desired width */
                    max-height: 400px;
                    /* Adjust as needed */
                    overflow-y: auto;
                    /* Enables vertical scroll */
                    overflow-x: auto;
                    /* Enables horizontal scroll */
                    display: block;
                    padding-top: 0px;
                }

                .table-sticky table {
                    border-collapse: collapse;
                    width: max-content;
                    /* Adjust if you want wider tables */
                    min-width: 100%;
                }

                .select2-dropdown {
                    border: 1px solid var(--brand-border);
                    border-radius: 0.375rem;
                }

                .select2-results__option--highlighted {
                    background-color: var(--brand-primary);
                    color: white;
                }

                .table-sticky thead th {
                    position: sticky;
                    top: 0;
                    z-index: 2;
                    border-bottom: 1px solid var(--brand-border);
                }
            </style>
            <div class="card-body table-sticky">
                @include('reports.exports.dispatch', [
                    'balanceAsOnEndDate' => $balanceAsOnEndDate ?? false,
                    'endDate' => $endDate,
                ])
            </div>
            <div class="card-body pt-0 d-flex justify-content-end">
                {{ $lrs->links() }}
            </div>
        </div>
    @endif
@endsection
