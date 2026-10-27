@extends('layouts.app')

@section('title', 'Party Ledger')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    @include('reports.partials.select2-customer-brand')
@endpush

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.party-ledger') }}" id="searchForm"
                class="tb-report-customer-select2-form" onsubmit="validateForm(event)">
                <div class="row mb-2 g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="party_ledger_customer_id" class="form-select form-select-sm js-party-ledger-customer-select2"
                            required>
                            <option value=""></option>
                            @if ($presetCustomer)
                                <option value="{{ $presetCustomer->id }}" selected>{{ $presetCustomer->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-6 col-md-3 text-end d-flex align-items-end justify-content-end gap-1 gap-sm-2 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-brand-primary btn-sm btn-report-search" title="Search">
                            <i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Search</span>
                        </button>
                        <a href="{{ route('reports.party-ledger') }}" class="btn btn-secondary btn-sm btn-report-reset" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i><span class="d-none d-sm-inline ms-1">Reset</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$customerId)
        <div class="alert alert-warning">
            Please select the Customer to generate the report.
        </div>
    @elseif ($ledgerRows->isEmpty())
        <div class="alert alert-info">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Party Ledger</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning btn-sm ms-2"
                            href="{{ route('reports.party-ledger-export', [
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'customer_id' => $customerId,
                            ]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline ms-1">Export</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    @include('reports.exports.party-ledger-table', [
                        'ledgerRows' => $ledgerRows,
                    ])
                </div>
            </div>
            @if ($lrs instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="card-body pt-0 d-flex justify-content-end">
                    {{ $lrs->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const partiesAjax = {
                url: '/api/consignees/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1,
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || [],
                        pagination: {
                            more: Boolean(data.pagination && data.pagination.more),
                        },
                    };
                },
                cache: true,
            };

            $('.js-party-ledger-customer-select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: false,
                placeholder: 'Search customer…',
                minimumInputLength: 0,
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $('#searchForm').closest('.card-body'),
                ajax: partiesAjax,
            });
        });

        function validateForm(event) {
            event.preventDefault();
            const customerId = $('select[name="customer_id"]').val();

            if (!customerId) {
                alert('Please select the Customer.');
                return false;
            }

            event.target.submit();
        }
    </script>
@endpush
