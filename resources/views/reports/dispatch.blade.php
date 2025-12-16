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
                </div>

                <div class="row">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.dispatch') }}?date={{ date('Y-m-d') }}"
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

                .table-sticky thead th {
                    position: sticky;
                    top: 0;
                    background: #fff;
                    /* Ensures visibility */
                    z-index: 2;
                    /* Keeps header above table rows */
                    border-bottom: 1px solid #ddd;
                }
            </style>
            <div class="card-body table-sticky">
                @include('reports.exports.dispatch')
            </div>
        </div>
    @endif
@endsection
