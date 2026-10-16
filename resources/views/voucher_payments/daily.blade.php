@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
    <style>
        .daily-payments-index .table-responsive {
            max-height: none !important;
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        #paymentsTable {
            width: 100% !important;
            border-collapse: collapse;
        }

        #paymentsTable th,
        #paymentsTable td {
            vertical-align: middle;
            padding: 8px 10px;
        }

        #paymentsTable thead th {
            position: sticky;
            top: 0;
            background: var(--brand-table-header-bg) !important;
            z-index: 10;
            white-space: nowrap !important;
        }

        @media (max-width: 1199.98px) {
            #paymentsTable {
                table-layout: auto !important;
                min-width: 1150px !important;
            }

            #paymentsTable th,
            #paymentsTable td {
                white-space: nowrap !important;
            }
        }

        @media (max-width: 767.98px) {
            #paymentsTable th,
            #paymentsTable td {
                font-size: 0.8125rem !important;
                padding: 6px 8px !important;
            }

            .daily-payments-index .app-index-dt-toolbar h5 {
                font-size: 0.8125rem !important;
            }

            .daily-payments-index #advancedSearchFields .form-label {
                font-size: 0.75rem !important;
            }

            .daily-payments-index #advancedSearchFields .form-control-sm,
            .daily-payments-index #advancedSearchFields .form-select-sm {
                font-size: 0.8125rem !important;
                padding: 0.25rem 0.5rem !important;
            }

            .daily-payments-index #advancedSearchFields .select2-container {
                width: 100% !important;
            }

            .daily-payments-index #advancedSearchFields .select2-container .select2-selection--single {
                height: 31px !important;
                font-size: 0.8125rem !important;
            }

            .daily-payments-index #advancedSearchFields .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                font-size: 0.78rem !important;
                line-height: 29px !important;
                padding-left: 0.45rem !important;
                padding-right: 1.4rem !important;
            }
        }

        .daily-payments-index .dataTables_wrapper {
            width: 100% !important;
            overflow-x: visible !important;
        }

        .daily-payments-index .dataTables_wrapper .row {
            margin: 0 0 0.5rem 0 !important;
            align-items: center;
        }

        .daily-payments-index .dataTables_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .daily-payments-index .dataTables_filter label {
            margin-bottom: 0;
            font-size: 0.8125rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .daily-payments-index .dataTables_filter input {
            font-size: 0.8125rem;
            padding: 0.25rem 0.5rem;
            border: 1px solid var(--brand-border, #e5e7eb);
            border-radius: 0.375rem;
            min-width: 10rem;
        }

        @media (max-width: 575.98px) {
            .daily-payments-index .app-index-list-card > .card-header {
                padding: 0.5rem 0.75rem !important;
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.5rem;
            }

            .daily-payments-index .app-index-card-toolbar {
                width: 100%;
            }

            .daily-payments-index .app-index-card-toolbar .btn {
                width: 100%;
                text-align: center;
                justify-content: center;
                padding: 0.35rem 0.5rem !important;
                font-size: 0.75rem !important;
            }

            .daily-payments-index .dataTables_filter {
                justify-content: stretch;
                width: 100%;
            }

            .daily-payments-index .dataTables_filter label {
                width: 100%;
            }

            .daily-payments-index .dataTables_filter input {
                flex: 1 1 auto;
                width: 100%;
                min-width: 0;
            }

            .daily-filter-actions {
                flex-direction: row;
                justify-content: flex-end;
                align-items: center;
                gap: 0.35rem;
                width: 100%;
            }

            .daily-filter-actions .btn {
                flex: 0 0 auto !important;
                width: auto !important;
                min-width: 0;
                margin: 0 !important;
                text-align: center;
                white-space: nowrap;
                padding: 0.28rem 0.75rem !important;
                font-size: 0.78rem !important;
            }

            .daily-date-label {
                font-size: 0.72rem !important;
                margin-bottom: 0.2rem !important;
            }

            .daily-date-input {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.4rem !important;
                height: 31px !important;
            }

            .daily-selected-box {
                padding: 0 0.5rem !important;
                font-size: 0.72rem !important;
                height: 31px !important;
                display: flex !important;
                align-items: center !important;
            }

            .daily-selected-box .badge {
                font-size: 0.65rem !important;
                padding: 0.2rem 0.35rem !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid mt-2 pt-1 app-index-page app-index-page--standard daily-payments-index">
        @if (session('success'))
            <div class="mb-3">
                <span class="badge text-bg-dark border app-index-flash">
                    <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
                </span>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-3">
                <span class="badge text-bg-dark border app-index-flash">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i> {{ session('error') }}
                </span>
            </div>
        @endif

        <div class="card app-index-list-card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0 fs-6 fs-md-5">Daily Payments</h3>
            </div>

            <!-- Search and Filter Section -->
            <div class="app-index-dt-toolbar">
                <div class="d-flex justify-content-between align-items-center flex-nowrap gap-2 mb-2">
                    <h5 class="mb-0 fw-bold" style="font-size: 0.85rem;">Search & Filter</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 text-nowrap" style="font-size: 0.75rem;" onclick="toggleAdvancedSearch()">
                        <i class="bi bi-funnel"></i> Toggle Filters
                    </button>
                </div>
                <div>
                    <form method="GET" action="{{ route('voucher_payments.daily') }}" id="searchForm">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-6 col-sm-5 col-md-4 col-lg-3">
                                <label class="form-label small fw-semibold mb-1 text-truncate d-block daily-date-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control form-control-sm daily-date-input" value="{{ $selectedDate }}" required>
                            </div>
                            <div class="col-6 col-sm-7 col-md-8 col-lg-9">
                                <label class="form-label small fw-semibold mb-1 text-muted text-truncate d-block daily-date-label">Summary</label>
                                <div class="rounded bg-light border d-flex align-items-center justify-content-between gap-1 w-100 px-2 daily-selected-box">
                                    <span class="text-nowrap text-truncate fw-semibold" title="{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}">{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</span>
                                    <span class="badge bg-primary text-nowrap">{{ $payments->count() + ($payments1->count() ?? 0) }} payments</span>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Search Fields (initially hidden) -->
                        <div id="advancedSearchFields" class="d-none">
                            <div class="row g-2 g-md-3">
                                <div class="col-6 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-semibold mb-1 text-truncate d-block">Truck No</label>
                                    <input type="text" name="truck_no" class="form-control form-control-sm"
                                        value="{{ request('truck_no') }}" placeholder="Truck number">
                                </div>
                                <div class="col-6 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-semibold mb-1 text-truncate d-block">Transporter</label>
                                    <select name="transporter" id="voucherPaymentsDailyTransporterFilter" class="form-select form-select-sm">
                                        <option value="">All Transporters</option>
                                        @if (! empty($selectedVoucherPaymentTransporter))
                                            <option value="{{ $selectedVoucherPaymentTransporter['id'] }}" selected>{{ $selectedVoucherPaymentTransporter['text'] }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-6 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-semibold mb-1 text-truncate d-block">Source</label>
                                    <select name="source" id="voucherPaymentsDailySourceCityFilter" class="form-select form-select-sm">
                                        <option value="">All Sources</option>
                                        @if (! empty($selectedVoucherPaymentSourceCity))
                                            <option value="{{ $selectedVoucherPaymentSourceCity['id'] }}" selected>{{ $selectedVoucherPaymentSourceCity['text'] }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-6 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-semibold mb-1 text-truncate d-block">Destination</label>
                                    <select name="destination" id="voucherPaymentsDailyDestinationCityFilter" class="form-select form-select-sm">
                                        <option value="">All Destinations</option>
                                        @if (! empty($selectedVoucherPaymentDestinationCity))
                                            <option value="{{ $selectedVoucherPaymentDestinationCity['id'] }}" selected>{{ $selectedVoucherPaymentDestinationCity['text'] }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-6 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-semibold mb-1 text-truncate d-block" title="Good Receipt (Bilty) No">GR (Bilty) No</label>
                                    <input type="text" name="lr_no" class="form-control form-control-sm" value="{{ request('lr_no') }}" placeholder="LR number">
                                </div>
                                <div class="col-6 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-semibold mb-1 text-truncate d-block">Payment Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Statuses</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="in-progress" {{ request('status') == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                    </select>
                                </div>
                                <div class="col-6 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-semibold mb-1 text-truncate d-block">Payment Mode</label>
                                    <select name="payment_mode" class="form-select form-select-sm">
                                        <option value="">All Payment Modes</option>
                                        @foreach ($paymentModes as $paymentMode)
                                            <option value="{{ $paymentMode->name }}"
                                                {{ request('payment_mode') == $paymentMode->name ? 'selected' : '' }}>
                                                {{ $paymentMode->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-semibold mb-1 text-truncate d-block">Branch</label>
                                    <select name="branch" class="form-select form-select-sm">
                                        <option value="">All Branches</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}"
                                                {{ request('branch') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->branch_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center gap-2 mt-3 daily-filter-actions">
                            <button type="submit" class="btn btn-secondary btn-sm text-nowrap">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <a href="{{ route('voucher_payments.daily') }}?date={{ $selectedDate }}"
                                class="btn btn-outline-secondary btn-sm text-nowrap">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payments List -->
        <div class="card app-index-list-card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0 fs-6 fs-md-5">Voucher Payments —
                    {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</h3>
                @if (auth()->user()->canActAsAdmin())
                    <div class="app-index-card-toolbar">
                        <button id="bulkActionBtn" class="btn app-index-header-btn d-none">
                            <i class="bi bi-arrow-up-circle"></i> Update Status
                        </button>
                    </div>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 app-index-table w-100" id="paymentsTable">
                    <thead>
                        <tr>
                            @if (auth()->user()->canActAsAdmin())
                                <th style="width: 30px">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                            @endif

                            <th>LR No</th>
                            <th>Transporter</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Payment Type</th>
                            <th>Payment Mode</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created Time</th>
                            <th>Branch</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr class="align-middle payment-row" data-status="{{ $payment->payment_status }}"
                                data-id="{{ $payment->id }}">
                                @if (auth()->user()->canActAsAdmin())
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input payment-checkbox" type="checkbox"
                                                value="{{ $payment->id }}" data-status="{{ $payment->payment_status }}"
                                                {{ $payment->payment_status === 'paid' ? 'disabled' : '' }}>
                                        </div>
                                    </td>
                                @endif

                                <td>
                                    @if ($payment->freight_booking && $payment->freight_booking->lr)
                                        {{ $payment->freight_booking->lr->lr_number }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                    <br>
                                    @if ($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->truck)
                                        <span
                                            class="badge bg-primary">{{ $payment->freight_booking->lr->truck->truck_number }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($payment->freight_booking && $payment->freight_booking->transporter)
                                        @if ($payment->freight_booking->transporter->is_owner)
                                            <i class="bi bi-truck"></i>
                                        @else
                                            <i class="bi bi-building"></i>
                                        @endif
                                        {{ $payment->freight_booking->transporter->name }} <br>
                                        <small
                                            class="text-muted">{{ $payment->freight_booking->transporter->beneficiary_id ?? '-' }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if (
                                        $payment->freight_booking &&
                                            $payment->freight_booking->lr &&
                                            $payment->freight_booking->lr->consignor &&
                                            $payment->freight_booking->lr->consignor->city)
                                        {{ $payment->freight_booking->lr->consignor->city->name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if (
                                        $payment->freight_booking &&
                                            $payment->freight_booking->lr &&
                                            $payment->freight_booking->lr->consignee &&
                                            $payment->freight_booking->lr->consignee->city)
                                        {{ $payment->freight_booking->lr->consignee->city->name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->payment_type)
                                        {{ ucfirst($payment->payment_type) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->payment_mode)
                                        {{ ucfirst($payment->payment_mode) }}
                                        @if ($payment->pump)
                                            <br><small class="text-muted">{{ $payment->pump->pump_name }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($payment->amount)
                                        <strong>{{ number_format($payment->amount, 2) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->payment_status)
                                        @switch($payment->payment_status)
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @break

                                            @case('in-progress')
                                                <span class="badge bg-info">In Progress</span>
                                            @break

                                            @case('paid')
                                                <span class="badge bg-success">Paid</span>
                                            @break

                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($payment->payment_status) }}</span>
                                        @endswitch
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $payment->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y h:i A') }}</small>
                                </td>
                                <td>
                                    @if ($payment->created_by && $payment->creator->userRole?->name == 'super_admin')
                                        Super Admin
                                    @elseif ($payment->created_by && $payment->creator->branch)
                                        {{ $payment->creator->branch->branch_name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @foreach ($payments1 as $payment)
                            @php
                                $linked_lr_id = $payment->freight_booking->linked_lr_id ?? null;
                                $lrs = $linked_lr_id
                                    ? ($linkedLorryReceiptsByLinkedId[$linked_lr_id] ?? collect())
                                    : collect();
                            @endphp
                            <tr class="align-middle payment-row" data-status="{{ $payment->payment_status }}"
                                data-id="{{ $payment->id }}">
                                @if (auth()->user()->canActAsAdmin())
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input multiple-payment-checkbox" type="checkbox"
                                                value="{{ $payment->id }}" data-status="{{ $payment->payment_status }}"
                                                {{ $payment->payment_status === 'paid' ? 'disabled' : '' }}>
                                        </div>
                                    </td>
                                @endif

                                <td>
                                    @forelse ($lrs as $lr)
                                        <div>{{ $lr->lr_number }}</div>
                                    @empty
                                        <div class="text-muted">-</div>
                                    @endforelse
                                    @if ($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->truck)
                                        <span
                                            class="badge bg-primary">{{ $payment->freight_booking->lr->truck->truck_number }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($payment->freight_booking && $payment->freight_booking->transporter)
                                        @if ($payment->freight_booking->transporter->is_owner)
                                            <i class="bi bi-truck"></i>
                                        @else
                                            <i class="bi bi-building"></i>
                                        @endif
                                        {{ $payment->freight_booking->transporter->name }}
                                        <br>
                                        <small
                                            class="text-muted">{{ $payment->freight_booking->transporter->beneficiary_id ?? '-' }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($lrs as $lr)
                                        @if ($lr->consignor && $lr->consignor->city)
                                            <div>{{ $lr->consignor->city->name }}</div>
                                        @else
                                            <div class="text-muted">-</div>
                                        @endif
                                    @empty
                                        <div class="text-muted">-</div>
                                    @endforelse
                                </td>
                                <td>
                                    @forelse ($lrs as $lr)
                                        @if ($lr->consignee && $lr->consignee->city)
                                            <div>{{ $lr->consignee->city->name }}</div>
                                        @else
                                            <div class="text-muted">-</div>
                                        @endif
                                    @empty
                                        <div class="text-muted">-</div>
                                    @endforelse
                                </td>
                                <td>
                                    @if ($payment->payment_type)
                                        {{ ucfirst($payment->payment_type) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->payment_mode)
                                        {{ ucfirst($payment->payment_mode) }}
                                        @if ($payment->pump)
                                            <br><small class="text-muted">{{ $payment->pump->pump_name }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($payment->amount)
                                        <strong>{{ number_format($payment->amount, 2) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->payment_status)
                                        @switch($payment->payment_status)
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @break

                                            @case('in-progress')
                                                <span class="badge bg-info">In Progress</span>
                                            @break

                                            @case('paid')
                                                <span class="badge bg-success">Paid</span>
                                            @break

                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($payment->payment_status) }}</span>
                                        @endswitch
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $payment->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y h:i A') }}</small>
                                </td>
                                <td>
                                    @if ($payment->created_by && $payment->creator->branch)
                                        {{ $payment->creator->branch->branch_name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>

    </div>
@endsection

<!-- Bulk Action Modal -->
<div class="modal fade fb-pay-modal" id="bulkActionModal" tabindex="-1" aria-labelledby="bulkActionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkActionModalLabel">Update Payment Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkActionForm" method="POST" action="{{ route('voucher_payments.bulkUpdateStatus') }}">
                    @csrf
                    <div id="selectedPaymentsContainer"></div>
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">New Status</label>
                        <select class="form-select" id="newStatus" name="new_status" required>
                            <option value="" selected disabled>Select status...</option>
                            <option value="pending">Pending</option>
                            <option value="in-progress">In Progress</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitBulkAction">Update Status</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/dataTables.searchPanes.min.js"></script>
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/searchPanes.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#paymentsTable').DataTable({
                "paging": false,
                "lengthChange": false,
                "searching": true,
                "ordering": false,
                "info": false,
                "autoWidth": false,
                "language": {
                    "emptyTable": "<div class='empty-state text-center my-4'>" +
                        "<i class='bi bi-calendar-x fa-3x text-muted mb-3'></i>" +
                        "<h5>No Payments Found</h5>" +
                        "<p class='text-muted'>No voucher payments were initiated on {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}.</p>" +
                        "</div>"
                }
            });
        });
    </script>

    <script>
        // Toggle advanced search fields
        function toggleAdvancedSearch() {
            const advancedSearch = document.getElementById('advancedSearchFields');
            advancedSearch.classList.toggle('d-none');
        }

        // Show advanced search if any filter is set
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasFilters = Array.from(urlParams.keys()).some(key =>
                !['date', 'page'].includes(key) && urlParams.get(key) !== ''
            );

            if (hasFilters) {
                document.getElementById('advancedSearchFields').classList.remove('d-none');
            }
        });
        // Single payment status update
        function updateStatus(paymentId, status) {
            if (confirm('Are you sure you want to update the payment status to ' + status + '?')) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('voucher_payments.update_status') }}';

                // CSRF Token
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                // Method field
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'PUT';
                form.appendChild(methodField);

                // Payment ID
                const paymentIdField = document.createElement('input');
                paymentIdField.type = 'hidden';
                paymentIdField.name = 'payment_id';
                paymentIdField.value = paymentId;
                form.appendChild(paymentIdField);

                // Status
                const statusField = document.createElement('input');
                statusField.type = 'hidden';
                statusField.name = 'status';
                statusField.value = status;
                form.appendChild(statusField);

                // Redirect back
                const redirectField = document.createElement('input');
                redirectField.type = 'hidden';
                redirectField.name = 'redirect';
                redirectField.value = window.location.href;
                form.appendChild(redirectField);

                // Append to body and submit
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Multi-select functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const paymentCheckboxes = document.querySelectorAll('.payment-checkbox');
            const multiplePaymentCheckboxes = document.querySelectorAll('.multiple-payment-checkbox');
            const bulkActionBtn = document.getElementById('bulkActionBtn');
            const bulkActionModal = new bootstrap.Modal(document.getElementById('bulkActionModal'));
            const newStatusSelect = document.getElementById('newStatus');
            const submitBulkAction = document.getElementById('submitBulkAction');
            const bulkActionForm = document.getElementById('bulkActionForm');
            const selectedPaymentsContainer = document.getElementById('selectedPaymentsContainer');

            let currentSelectedStatus = null;

            // Handle select all checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    let validCheckboxes = [];

                    // If nothing is selected yet, we can select any non-paid status
                    if (currentSelectedStatus === null) {
                        paymentCheckboxes.forEach(checkbox => {
                            if (!checkbox.disabled) {
                                checkbox.checked = isChecked;
                                if (isChecked && currentSelectedStatus === null) {
                                    currentSelectedStatus = checkbox.dataset.status;
                                }
                                if (isChecked && checkbox.dataset.status ===
                                    currentSelectedStatus) {
                                    validCheckboxes.push(checkbox);
                                }
                            }
                        });
                        multiplePaymentCheckboxes.forEach(checkbox => {
                            if (!checkbox.disabled) {
                                checkbox.checked = isChecked;
                                if (isChecked && currentSelectedStatus === null) {
                                    currentSelectedStatus = checkbox.dataset.status;
                                }
                                if (isChecked && checkbox.dataset.status ===
                                    currentSelectedStatus) {
                                    validCheckboxes.push(checkbox);
                                }
                            }
                        });
                    } else {
                        // Only select checkboxes with the same status
                        paymentCheckboxes.forEach(checkbox => {
                            if (!checkbox.disabled && checkbox.dataset.status ===
                                currentSelectedStatus) {
                                checkbox.checked = isChecked;
                                if (isChecked) {
                                    validCheckboxes.push(checkbox);
                                }
                            }
                        });
                        multiplePaymentCheckboxes.forEach(checkbox => {
                            if (!checkbox.disabled && checkbox.dataset.status ===
                                currentSelectedStatus) {
                                checkbox.checked = isChecked;
                                if (isChecked) {
                                    validCheckboxes.push(checkbox);
                                }
                            }
                        });
                    }

                    // Show/hide bulk action button
                    bulkActionBtn.classList.toggle('d-none', validCheckboxes.filter(cb => cb.checked)
                        .length === 0);
                });
            }

            // Handle individual checkboxes
            paymentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedBoxes = Array.from(paymentCheckboxes).filter(cb => cb.checked);

                    // If this is the first checkbox being checked, set the current status
                    if (checkedBoxes.length === 1 && this.checked) {
                        currentSelectedStatus = this.dataset.status;
                    }

                    // If this checkbox has a different status than the current selection, uncheck it
                    if (this.checked && this.dataset.status !== currentSelectedStatus) {
                        this.checked = false;
                        alert('You can only select voucher payments with the same status.');
                        return;
                    }

                    // If no checkboxes are checked, reset the current status
                    if (checkedBoxes.length === 0) {
                        currentSelectedStatus = null;
                    }

                    // Update select all checkbox
                    updateSelectAllCheckbox();

                    // Show/hide bulk action button
                    updateBulkActionVisibility();
                });
            });

            multiplePaymentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedBoxes = Array.from(multiplePaymentCheckboxes).filter(cb => cb
                        .checked);

                    // If this is the first checkbox being checked, set the current status
                    if (checkedBoxes.length === 1 && this.checked) {
                        currentSelectedStatus = this.dataset.status;
                    }

                    // If this checkbox has a different status than the current selection, uncheck it
                    if (this.checked && this.dataset.status !== currentSelectedStatus) {
                        this.checked = false;
                        alert('You can only select voucher payments with the same status.');
                        return;
                    }

                    // If no checkboxes are checked, reset the current status
                    if (checkedBoxes.length === 0) {
                        currentSelectedStatus = null;
                    }

                    // Update select all checkbox
                    updateSelectAllCheckbox();

                    // Show/hide bulk action button
                    updateBulkActionVisibility();
                });
            });

            function updateBulkActionVisibility() {
                const anyChecked =
                    Array.from(paymentCheckboxes).some(cb => cb.checked) ||
                    Array.from(multiplePaymentCheckboxes).some(cb => cb.checked);

                bulkActionBtn.classList.toggle('d-none', !anyChecked);
            }

            function updateSelectAllCheckbox() {
                if (!selectAllCheckbox) return;

                const allCheckboxes = [...paymentCheckboxes, ...multiplePaymentCheckboxes];

                const eligibleCheckboxes = allCheckboxes.filter(cb =>
                    !cb.disabled &&
                    (currentSelectedStatus === null || cb.dataset.status === currentSelectedStatus)
                );

                const allChecked = eligibleCheckboxes.length > 0 && eligibleCheckboxes.every(cb => cb.checked);

                selectAllCheckbox.checked = allChecked;
            }

            // Handle bulk action button click
            if (bulkActionBtn) {
                bulkActionBtn.addEventListener('click', function() {
                    const planEndDateStr = @json(Auth::user()->company?->subscription?->ends_on?->toDateString() ?? null);
                    const today = new Date();
                    if (planEndDateStr && new Date(planEndDateStr) < today) {
                        alert('Your plan has expired. Please renew your plan to use this feature.');
                        return false;
                    }
                    const checkedBoxes = Array.from(paymentCheckboxes).filter(cb => cb.checked);
                    const checkedBoxesMultiple = Array.from(multiplePaymentCheckboxes).filter(cb => cb
                        .checked);
                    if (checkedBoxes.length === 0 && checkedBoxesMultiple.length === 0) return;

                    // Clear previous selections
                    selectedPaymentsContainer.innerHTML = '';

                    // Add hidden inputs for each selected payment
                    checkedBoxes.forEach(checkbox => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'payment_ids[]';
                        input.value = checkbox.value;
                        selectedPaymentsContainer.appendChild(input);
                    });
                    checkedBoxesMultiple.forEach(checkbox => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'multiple_payment_ids[]';
                        input.value = checkbox.value;
                        selectedPaymentsContainer.appendChild(input);
                    });

                    // Set available status options based on current selection
                    newStatusSelect.innerHTML =
                        '<option value="" selected disabled>Select status...</option>';

                    if (currentSelectedStatus === 'pending') {
                        newStatusSelect.innerHTML += '<option value="in-progress">In Progress</option>';
                    } else if (currentSelectedStatus === 'in-progress') {
                        newStatusSelect.innerHTML += '<option value="paid">Paid</option>';
                    }

                    // Show the modal
                    bulkActionModal.show();
                });
            }

            // Handle form submission
            if (submitBulkAction) {
                submitBulkAction.addEventListener('click', function() {
                    if (newStatusSelect.value) {
                        bulkActionForm.submit();
                    } else {
                        alert('Please select a status.');
                    }
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            const $t = $('#voucherPaymentsDailyTransporterFilter');
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

            function initDailyCitySelect2(selector, placeholder) {
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
            initDailyCitySelect2('#voucherPaymentsDailySourceCityFilter', 'Search source city…');
            initDailyCitySelect2('#voucherPaymentsDailyDestinationCityFilter', 'Search destination city…');
        });
    </script>
@endpush
