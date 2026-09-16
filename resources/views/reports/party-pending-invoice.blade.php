@extends('layouts.app')

@section('title', 'Party Pending Invoice')

@section('content')
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Party Pending Invoice</h3>
                </div>
                <div class="d-flex">
                    <a class="btn btn-warning btn-sm ms-2" target="_blank"
                        href="{{ route('reports.party-pending-invoice-export') }}">
                        <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline ms-1">Export</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body table-sticky" style="overflow-x: auto;">
            @php
                $hasRows = $partyRows instanceof \Illuminate\Pagination\LengthAwarePaginator
                    ? $partyRows->total() > 0
                    : $partyRows->isNotEmpty();
            @endphp
            @if (!$hasRows)
                <div class="alert alert-info mb-0">No records found.</div>
            @else
                <div class="table-responsive">
                    @include('reports.exports.party-pending-invoice', [
                        'partyRows' => $partyRows,
                        'grandTotalCollect' => $grandTotalCollect,
                        'grandTotalPaid' => $grandTotalPaid,
                        'grandTotalPending' => $grandTotalPending,
                    ])
                </div>
                @if ($partyRows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $partyRows->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
