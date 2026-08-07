@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')

<div class="container mt-4 app-index-page app-index-page--standard">
    @if(session('success'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
            </span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-exclamation-triangle-fill text-danger"></i> {{ session('error') }}
            </span>
        </div>
    @endif

    <div class="card app-index-list-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Voucher Payments</h3>
            <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn app-index-header-btn" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
                    <i class="bi bi-funnel"></i> Search filter
                </button>
                <a href="{{ route('voucher_payments.daily') }}" class="btn app-index-header-btn">
                    <i class="bi bi-calendar-day"></i> Daily Report
                </a>
            </div>
        </div>

        {{-- Collapsible advanced filters --}}
        <form method="GET" action="{{ route('voucher_payments.index') }}" id="advanced-search-box" class="app-index-dt-toolbar d-none">
            {{-- Filter checkboxes --}}
            <div class="row g-2">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Choose Filter Fields:</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="truck_no" id="filter_truck_no" onchange="toggleField(this)">
                        <label class="form-check-label" for="filter_truck_no">Truck No</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="transporter" id="filter_transporter" onchange="toggleField(this)">
                        <label class="form-check-label" for="filter_transporter">Transporter</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="source" id="filter_source" onchange="toggleField(this)">
                        <label class="form-check-label" for="filter_source">Source</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="destination" id="filter_destination" onchange="toggleField(this)">
                        <label class="form-check-label" for="filter_destination">Destination</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="lr_no" id="filter_lr_no" onchange="toggleField(this)">
                        <label class="form-check-label" for="filter_lr_no">Good Receipt (Bilty) No</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="freight_booking_no" id="filter_freight_booking_no" onchange="toggleField(this)">
                        <label class="form-check-label" for="filter_freight_booking_no">Good Hire (Lorry Hire) No</label>
                    </div>
                </div>
            </div>

            {{-- Conditionally visible filter fields --}}
            <div class="row g-3 mt-2" id="filter-fields">
                <div class="col-md-3 d-none" id="field_truck_no">
                    <label class="form-label">Truck No</label>
                    <input type="text" name="truck_no" class="form-control" value="{{ request('truck_no') }}">
                </div>
                <div class="col-md-3 d-none" id="field_transporter">
                    <label class="form-label">Transporter</label>
                    <select name="transporter" id="voucherPaymentsTransporterFilter" class="form-select">
                        <option value="">All Transporters</option>
                        @if (! empty($selectedVoucherPaymentTransporter))
                            <option value="{{ $selectedVoucherPaymentTransporter['id'] }}" selected>{{ $selectedVoucherPaymentTransporter['text'] }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3 d-none" id="field_source">
                    <label class="form-label">Source</label>
                    <select name="source" id="voucherPaymentsSourceCityFilter" class="form-select">
                        <option value="">All Sources</option>
                        @if (! empty($selectedVoucherPaymentSourceCity))
                            <option value="{{ $selectedVoucherPaymentSourceCity['id'] }}" selected>{{ $selectedVoucherPaymentSourceCity['text'] }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3 d-none" id="field_destination">
                    <label class="form-label">Destination</label>
                    <select name="destination" id="voucherPaymentsDestinationCityFilter" class="form-select">
                        <option value="">All Destinations</option>
                        @if (! empty($selectedVoucherPaymentDestinationCity))
                            <option value="{{ $selectedVoucherPaymentDestinationCity['id'] }}" selected>{{ $selectedVoucherPaymentDestinationCity['text'] }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3 d-none" id="field_lr_no">
                    <label class="form-label">Good Receipt (Bilty) No</label>
                    <input type="text" name="lr_no" class="form-control" value="{{ request('lr_no') }}">
                </div>
                <div class="col-md-3 d-none" id="field_freight_booking_no">
                    <label class="form-label">Good Hire (Lorry Hire) No</label>
                    <input type="text" name="freight_booking_no" class="form-control" value="{{ request('freight_booking_no') }}">
                </div>
            </div>

            {{-- Form action buttons --}}
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-secondary">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="{{ route('voucher_payments.index') }}" class="btn btn-secondary ms-2">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Voucher #</th>
                            <th>Good Receipt (Bilty) No</th>
                            <th>Customer</th>
                            <th>Truck No</th>
                            <th>Transporter</th>
                            <th>Source</th>
                            <th>Destination</th>

                            @if(auth()->check() && auth()->user()->canActAsAdmin())
                            <th class="app-index-actions-cell">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($voucherGroups as $group)
                            @php
                                $payment = $payments[$group->voucher_number] ?? null;
                                if (!$payment) continue;
                            @endphp
                            <tr class="align-middle">

                                <td>
                                    <a href="{{ route('voucher_payments.show', $payment->voucher_number) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="fw-bold text-decoration-none">
                                        {{ $payment->voucher_number }}
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                </td>
                                <td>
                                    @if($payment->freight_booking && $payment->freight_booking->lr)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->lr_number }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->customer)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->customer->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->truck)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->truck->truck_number }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->freight_booking && $payment->freight_booking->transporter)
                                        @if($payment->freight_booking->transporter->is_owner)
                                            <span class="badge bg-light text-dark"><i class="bi bi-truck"></i> {{ $payment->freight_booking->transporter->name }}</span>
                                        @else
                                            <span class="badge bg-primary"><i class="bi bi-building"></i> {{ $payment->freight_booking->transporter->name }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->consignor && $payment->freight_booking->lr->consignor->city)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->consignor->city->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->consignee && $payment->freight_booking->lr->consignee->city)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->consignee->city->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                @if(auth()->check() && auth()->user()->canActAsAdmin())
                                <td class="app-index-actions-cell">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('voucher_payments.show', $payment->voucher_number) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr class="app-index-empty-row">
                                <td colspan="10" class="text-center">
                                    <div class="empty-state">
                                        <i class="bi bi-box"></i>
                                        <p class="mt-2">No payments found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-index-partload-pagination d-flex flex-wrap align-items-center justify-content-between gap-2">
                {{ $voucherGroups->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        const field = document.getElementById(fieldId);
        const input = field.querySelector('input, select');

        if (checkbox.checked) {
            field.classList.remove('d-none');
            if (input) input.disabled = false;
        } else {
            field.classList.add('d-none');
            if (input) input.disabled = true;
        }
    }

    // Initialize filter fields based on URL parameters
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);

        // Check each filter checkbox if its parameter exists in URL
        document.querySelectorAll('#advanced-search-box input[type="checkbox"]').forEach(checkbox => {
            const paramName = checkbox.value;
            if (urlParams.has(paramName) && urlParams.get(paramName)) {
                checkbox.checked = true;
                toggleField(checkbox);
            }
        });

        // Show the filter box if any filter is applied
        if (Array.from(urlParams.keys()).some(key => [
            'voucher_number', 'payment_type', 'payment_mode', 'transaction_id', 'status',
            'transporter', 'truck_no', 'source', 'destination', 'lr_no', 'freight_booking_no', 'payment_status',
        ].includes(key))) {
            document.getElementById('advanced-search-box').classList.remove('d-none');
        }
    });
</script>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            const $t = $('#voucherPaymentsTransporterFilter');
            if (!$t.length || typeof $.fn.select2 !== 'function') {
                return;
            }
            $t.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search transporters…',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '/api/transporters/search',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1,
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: data.pagination,
                        };
                    },
                },
            });

            function initVoucherCitySelect2(selector, placeholder) {
                const $c = $(selector);
                if (!$c.length || typeof $.fn.select2 !== 'function') {
                    return;
                }
                $c.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: placeholder,
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: '/api/cities/search',
                        dataType: 'json',
                        delay: 200,
                        data: function (params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1,
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results,
                                pagination: data.pagination,
                            };
                        },
                    },
                });
            }
            initVoucherCitySelect2('#voucherPaymentsSourceCityFilter', 'Search source city…');
            initVoucherCitySelect2('#voucherPaymentsDestinationCityFilter', 'Search destination city…');
        });
    </script>
@endpush
