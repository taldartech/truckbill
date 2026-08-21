@extends('layouts.app')

@section('title', 'Pending Memo')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    @include('reports.partials.select2-customer-brand')
@endpush

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.pending-memo') }}" id="searchForm" class="tb-report-customer-select2-form">
                <div class="row mb-2" style="align-items: end;">
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="pending_memo_customer_id" class="form-select js-report-customer-ajax-select2">
                            <option value=""></option>
                            @if ($presetCustomer)
                                <option value="{{ $presetCustomer->id }}" selected>{{ $presetCustomer->name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="delivered" {{ ($status ?? 'delivered') === 'delivered' ? 'selected' : '' }}>
                                Delivered
                            </option>
                            <option value="in-progress" {{ ($status ?? '') === 'in-progress' ? 'selected' : '' }}>
                                In-Progress
                            </option>
                            <option value="both" {{ ($status ?? '') === 'both' ? 'selected' : '' }}>
                                Both
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3  d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-brand-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.pending-memo') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Pending Memo / POD</h3>
                </div>
                <div class="d-flex">
                    <a class="btn btn-warning ms-2" target="_blank"
                        href="{{ route('reports.pending-memo-export', [
                            'customer_id' => $customerId,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'status' => $status ?? 'delivered',
                        ]) }}">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export
                    </a>

                </div>
            </div>
        </div>
        <div class="card-body" style="overflow-x: auto;">
            @if ($lrs->count() > 0)
                @php
                    $totalAmount = 0;
                    $damageAmount = 0;
                    $shortageAmount = 0;
                @endphp
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Consignment No</th>
                                <th>Truck No</th>
                                <th>Customer</th>
                                <th>From Place</th>
                                <th>To Place</th>
                                <th>Lane</th>
                                <th>QTY(MT)</th>
                                <th>Rate</th>
                                <th>Damage</th>
                                <th>Shortage</th>
                                <th>Amt.</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lrs as $lr)
                                @php
                                    $damageAmount += $lr->damage_amount;
                                    $shortageAmount += $lr->shortage_amount;
                                    $amount = $lr->actual_amount ?? 0;
                                    $totalAmount += $amount;
                                @endphp
                                <tr>
                                    <td>{{ date('d-m-Y', strtotime($lr->gr_date)) }}</td>
                                    <td>{{ $lr->lr_number }}</td>
                                    <td>{{ $lr->truck->truck_number ?? 'N/A' }}</td>
                                    <td>{{ $lr->customer->name ?? 'N/A' }}</td>
                                    <td>{{ $lr->source_city }}</td>
                                    <td>{{ $lr->destination_city }}</td>
                                    <td>{{ $lr->lane }}</td>
                                    <td style="text-align: right">{{ number_format($lr->weight, 3) }}</td>
                                    <td style="text-align: right">{{ number_format($lr->fr_rate, 2) }}</td>
                                    <td style="text-align: right">{{ number_format($lr->damage_amount, 2) }}</td>
                                    <td style="text-align: right">{{ number_format($lr->shortage_amount, 2) }}</td>
                                    <td style="text-align: right">{{ number_format($amount, 2) }}</td>

                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    {{ $lrs->links() }}
                </div>
            @else
                <div class="alert alert-info">No records found for the selected criteria.</div>
            @endif
        </div>
    </div>

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

            $('#pending_memo_customer_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: 'Search customer…',
                minimumInputLength: 0,
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $('#searchForm').closest('.card-body'),
                ajax: partiesAjax,
            });
        });
    </script>
@endpush
