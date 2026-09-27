@extends('layouts.app')

@section('title', 'Consignor & Consignee wise Report')

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
            <form method="GET" action="{{ route('reports.consignor-consignee') }}" id="searchForm"
                class="cc-report-search-form tb-report-customer-select2-form" onsubmit="validateForm(event)">
                <div class="row mb-2 g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Consignor</label>
                        <select name="consignor_id" id="report_consignor_id" class="form-select form-select-sm js-report-consignor-select2">
                            <option value=""></option>
                            @if ($presetConsignor)
                                <option value="{{ $presetConsignor->id }}" selected>{{ $presetConsignor->name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Consignee</label>
                        <select name="consignee_id" id="report_consignee_id" class="form-select form-select-sm js-report-consignee-select2">
                            <option value=""></option>
                            @if ($presetConsignee)
                                <option value="{{ $presetConsignee->id }}" selected>{{ $presetConsignee->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-12 col-md-4 d-flex align-items-end justify-content-end gap-1 gap-sm-2 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-brand-primary btn-sm" title="Search">
                            <i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Search</span>
                        </button>
                        <a href="{{ route('reports.consignor-consignee') }}" class="btn btn-secondary btn-sm" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i><span class="d-none d-sm-inline ms-1">Reset</span>
                        </a>
                    </div>
                 </div>
            </form>
        </div>
    </div>

    @if (!$consignorId && !$consigneeId)
        <div class="alert alert-warning">
            Please select at least one of Consignor or Consignee to generate the report.
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
                        <h3 class="card-title mb-0">Consigner & Consignee wise Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning btn-sm ms-2"
                            href="{{ route('reports.consignor-consignee-export', ['start_date' => $startDate, 'end_date' => $endDate, 'consignor_id' => $consignorId, 'consignee_id' => $consigneeId]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline ms-1">Export</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap small">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>Date</th>
                            <th>LR No</th>
                            <th>Inv No of Factory</th>
                            <th>Truck No</th>
                            <th>Weight</th>
                            <th>LR Rate</th>
                            <th>LR Amt</th>
                            <th>Consignor Name</th>
                            <th>Consignee Name</th>
                            <th>Destination</th>
                            <th>Transport/ Truck Owner Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lrs as $index => $lr)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}
                                </td>
                                <td>{{ $lr->lr_number }}</td>
                                <td>{{ $lr->invoice_number }}</td>
                                <td>{{ $lr->truck_id ? $lr->truck->truck_number : 'N/A' }}</td>
                                <td>{{ $lr->weight }}</td>
                                <td>{{ number_format($lr->fr_rate, 2) }}</td>
                                <td>{{ number_format($lr->amount, 2) }}</td>
                                <td>{{ $lr->consignor_id ? $lr->consignor->name : 'N/A' }}</td>
                                <td>{{ $lr->consignee_id ? $lr->consignee->name : 'N/A' }}</td>
                                <td>{{ $lr->destination_city . ', ' . $lr->destination_state }}</td>
                                <td>{{ $lr->transporter_id ? $lr->transporter->name : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                {{ $lrs->links() }}
            </div>
        </div>
    @endif
@endsection

@push('scripts')
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

            $('.js-report-consignor-select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: 'Search consignor…',
                minimumInputLength: 0,
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $('#searchForm').closest('.card-body'),
                ajax: partiesAjax,
            });

            $('.js-report-consignee-select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: 'Search consignee…',
                minimumInputLength: 0,
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $('#searchForm').closest('.card-body'),
                ajax: partiesAjax,
            });
        });
    </script>
    <script>
        function validateForm(event) {
            event.preventDefault();
            // Get the values of the date inputs
            const consignorId = $('select[name="consignor_id"]').val();
            const consigneeId = $('select[name="consignee_id"]').val();

            if (!consignorId && !consigneeId) {
                alert('Please select at least one of Consignor or Consignee.');
                return false;
            }

            if (consignorId && consigneeId && consignorId === consigneeId) {
                alert('Consignor and Consignee cannot be the same.');
                return false;
            }

            // If validation passes, submit the form
            event.target.submit();
        }
    </script>
@endpush
