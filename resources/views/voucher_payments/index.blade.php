@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .voucher-payments-index .table-responsive {
            max-height: none !important;
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        #voucherPaymentsTable {
            width: 100% !important;
            border-collapse: collapse;
        }

        #voucherPaymentsTable th,
        #voucherPaymentsTable td {
            vertical-align: middle;
            padding: 8px 10px;
        }

        #voucherPaymentsTable thead th {
            position: sticky;
            top: 0;
            background: var(--brand-table-header-bg) !important;
            z-index: 10;
            white-space: nowrap !important;
        }

        @media (max-width: 1199.98px) {
            #voucherPaymentsTable {
                table-layout: auto !important;
                min-width: 960px !important;
            }

            #voucherPaymentsTable th,
            #voucherPaymentsTable td {
                white-space: nowrap !important;
            }
        }

        @media (max-width: 767.98px) {
            #voucherPaymentsTable th {
                font-size: 0.76rem !important;
                padding: 5px 6px !important;
            }

            #voucherPaymentsTable td {
                font-size: 0.73rem !important;
                padding: 5px 6px !important;
            }

            #voucherPaymentsTable td .badge {
                font-size: 0.68rem !important;
            }

            #voucherPaymentsTable td small {
                font-size: 0.68rem !important;
            }
        }

        .voucher-payments-index .app-index-table-actions {
            display: inline-flex !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 4px !important;
            white-space: nowrap !important;
        }

        .vp-filter-title {
            font-size: 0.875rem;
            font-weight: 700;
        }

        .vp-filter-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            align-items: center;
        }

        .vp-filter-checkboxes .form-check {
            margin-bottom: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .vp-filter-checkboxes .form-check-input {
            margin-top: 0;
            cursor: pointer;
        }

        .vp-filter-checkboxes .form-check-label {
            font-size: 0.875rem;
            cursor: pointer;
            user-select: none;
        }

        @media (max-width: 767.98px) {
            #voucherPaymentsTable th,
            #voucherPaymentsTable td {
                font-size: 0.8125rem !important;
                padding: 6px 8px !important;
            }

            .vp-filter-title {
                font-size: 0.8125rem !important;
                margin-bottom: 0.35rem !important;
            }

            .vp-filter-checkboxes {
                gap: 0.35rem 0.65rem !important;
            }

            .vp-filter-checkboxes .form-check {
                margin-right: 0 !important;
            }

            .vp-filter-checkboxes .form-check-input {
                width: 0.9rem !important;
                height: 0.9rem !important;
                min-width: 0.9rem !important;
            }

            .vp-filter-checkboxes .form-check-label {
                font-size: 0.78rem !important;
                line-height: 1.25;
            }

            #filter-fields .form-label {
                font-size: 0.75rem !important;
            }

            #filter-fields .form-control-sm,
            #filter-fields .form-select-sm {
                font-size: 0.8125rem !important;
                padding: 0.25rem 0.5rem !important;
            }

            .voucher-payments-index .app-index-list-card > .card-header {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                flex-wrap: nowrap !important;
            }

            .voucher-payments-index .app-index-card-toolbar {
                width: auto !important;
                margin-left: auto !important;
            }
        }

        @media (max-width: 575.98px) {
            .voucher-payments-index .app-index-list-card > .card-header {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                flex-wrap: nowrap !important;
                gap: 0.4rem !important;
                padding: 0.45rem 0.65rem !important;
            }

            .voucher-payments-index .app-index-list-card > .card-header .card-title {
                font-size: 0.82rem !important;
                margin: 0 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                flex-shrink: 1 !important;
            }

            #voucherPaymentsTable th {
                font-size: 0.72rem !important;
                padding: 4px 6px !important;
            }

            #voucherPaymentsTable td {
                font-size: 0.70rem !important;
                padding: 4px 6px !important;
            }

            #voucherPaymentsTable td .badge {
                font-size: 0.64rem !important;
                padding: 0.15rem 0.35rem !important;
            }

            #voucherPaymentsTable td small {
                font-size: 0.64rem !important;
            }

            .voucher-payments-index .dataTables_filter {
                margin-top: 0.65rem !important;
                margin-bottom: 0.6rem !important;
                padding: 0 0.65rem !important;
            }

            .voucher-payments-index .dataTables_filter input {
                height: 32px !important;
                font-size: 0.78rem !important;
                border-radius: 6px !important;
            }

            .voucher-payments-index .app-index-card-toolbar {
                width: auto !important;
                margin-left: auto !important;
                display: inline-flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
                gap: 0.35rem !important;
                flex-shrink: 0 !important;
            }

            .voucher-payments-index .app-index-card-toolbar .app-index-header-btn {
                width: 32px !important;
                height: 32px !important;
                min-width: 32px !important;
                min-height: 32px !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 8px !important;
                font-size: 1rem !important;
                flex: 0 0 auto !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12) !important;
            }

            .vp-filter-actions {
                flex-direction: row !important;
                align-items: center !important;
                gap: 0.5rem !important;
            }

            .vp-filter-actions .btn {
                flex: 1 1 50% !important;
                width: auto !important;
                margin: 0 !important;
                text-align: center;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
        }
    </style>
@endpush

@section('content')

<div class="container-fluid mt-2 pt-1 app-index-page app-index-page--standard voucher-payments-index">
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
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">Voucher Payments</h3>
            <div class="app-index-card-toolbar d-flex align-items-center gap-2">
                <button type="button" class="btn app-index-header-btn" title="Search filter" aria-label="Search filter" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
                    <i class="bi bi-funnel"></i><span class="d-none d-sm-inline ms-1">Search filter</span>
                </button>
                <a href="{{ route('voucher_payments.daily') }}" class="btn app-index-header-btn" title="Daily Report" aria-label="Daily report">
                    <i class="bi bi-calendar-day"></i><span class="d-none d-sm-inline ms-1">Daily Report</span>
                </a>
                <a href="{{ route('voucher_payments.export_payout', request()->query()) }}" class="btn app-index-header-btn" title="Export Bank Payout File (NEFT/RTGS)" aria-label="Export bank payout file">
                    <i class="bi bi-download"></i><span class="d-none d-sm-inline ms-1">Bank Payout</span>
                </a>
            </div>
        </div>

        {{-- Collapsible advanced filters --}}
        <form method="GET" action="{{ route('voucher_payments.index') }}" id="advanced-search-box" class="app-index-dt-toolbar d-none">
            {{-- Filter checkboxes --}}
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label fw-bold d-block mb-2 vp-filter-title">Choose Filter Fields:</label>
                    <div class="vp-filter-checkboxes">
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
            </div>

            {{-- Conditionally visible filter fields --}}
            <div class="row g-2 g-md-3 mt-2" id="filter-fields">
                <div class="col-12 col-sm-6 col-lg-3 d-none" id="field_truck_no">
                    <label class="form-label mb-1" style="font-size: 0.8125rem; font-weight: 600;">Truck No</label>
                    <input type="text" name="truck_no" class="form-control form-control-sm" value="{{ request('truck_no') }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-3 d-none" id="field_transporter">
                    <label class="form-label mb-1" style="font-size: 0.8125rem; font-weight: 600;">Transporter</label>
                    <select name="transporter" id="voucherPaymentsTransporterFilter" class="form-select form-select-sm">
                        <option value="">All Transporters</option>
                        @if (! empty($selectedVoucherPaymentTransporter))
                            <option value="{{ $selectedVoucherPaymentTransporter['id'] }}" selected>{{ $selectedVoucherPaymentTransporter['text'] }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 d-none" id="field_source">
                    <label class="form-label mb-1" style="font-size: 0.8125rem; font-weight: 600;">Source</label>
                    <select name="source" id="voucherPaymentsSourceCityFilter" class="form-select form-select-sm">
                        <option value="">All Sources</option>
                        @if (! empty($selectedVoucherPaymentSourceCity))
                            <option value="{{ $selectedVoucherPaymentSourceCity['id'] }}" selected>{{ $selectedVoucherPaymentSourceCity['text'] }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 d-none" id="field_destination">
                    <label class="form-label mb-1" style="font-size: 0.8125rem; font-weight: 600;">Destination</label>
                    <select name="destination" id="voucherPaymentsDestinationCityFilter" class="form-select form-select-sm">
                        <option value="">All Destinations</option>
                        @if (! empty($selectedVoucherPaymentDestinationCity))
                            <option value="{{ $selectedVoucherPaymentDestinationCity['id'] }}" selected>{{ $selectedVoucherPaymentDestinationCity['text'] }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 d-none" id="field_lr_no">
                    <label class="form-label mb-1" style="font-size: 0.8125rem; font-weight: 600;">Good Receipt (Bilty) No</label>
                    <input type="text" name="lr_no" class="form-control form-control-sm" value="{{ request('lr_no') }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-3 d-none" id="field_freight_booking_no">
                    <label class="form-label mb-1" style="font-size: 0.8125rem; font-weight: 600;">Good Hire (Lorry Hire) No</label>
                    <input type="text" name="freight_booking_no" class="form-control form-control-sm" value="{{ request('freight_booking_no') }}">
                </div>
            </div>

            {{-- Form action buttons --}}
            <div class="d-flex justify-content-end gap-2 mt-3 vp-filter-actions">
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="{{ route('voucher_payments.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover align-middle mb-0 app-index-table text-nowrap w-100" id="voucherPaymentsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="px-2 py-2 text-nowrap">Voucher #</th>
                            <th class="px-2 py-2 text-nowrap">Good Receipt (Bilty) No</th>
                            <th class="px-2 py-2 text-nowrap">Customer</th>
                            <th class="px-2 py-2 text-nowrap">Truck No</th>
                            <th class="px-2 py-2 text-nowrap">Transporter</th>
                            <th class="px-2 py-2 text-nowrap">Source</th>
                            <th class="px-2 py-2 text-nowrap">Destination</th>

                            @if(auth()->check() && auth()->user()->canActAsAdmin())
                            <th class="app-index-actions-cell px-2 py-2 text-nowrap text-end">Actions</th>
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

                                <td class="px-2 py-1 text-nowrap">
                                    <a href="{{ route('voucher_payments.show', $payment->voucher_number) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="fw-bold text-decoration-none">
                                        {{ $payment->voucher_number }}
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                </td>
                                <td class="px-2 py-1 text-nowrap">
                                    @if($payment->freight_booking && $payment->freight_booking->lr)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->lr_number }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-2 py-1 text-nowrap">
                                    @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->customer)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->customer->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-2 py-1 text-nowrap">
                                    @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->truck)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->truck->truck_number }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-2 py-1 text-nowrap">
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
                                <td class="px-2 py-1 text-nowrap">
                                    @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->consignor && $payment->freight_booking->lr->consignor->city)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->consignor->city->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-2 py-1 text-nowrap">
                                    @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->consignee && $payment->freight_booking->lr->consignee->city)
                                        <span class="badge text-dark">{{ $payment->freight_booking->lr->consignee->city->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                @if(auth()->check() && auth()->user()->canActAsAdmin())
                                <td class="app-index-actions-cell px-2 py-1 text-nowrap text-end">
                                    <div class="app-index-table-actions justify-content-end">
                                        <a href="{{ route('voucher_payments.show', $payment->voucher_number) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-sm btn-icon btn-action-view" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="View Details">
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
