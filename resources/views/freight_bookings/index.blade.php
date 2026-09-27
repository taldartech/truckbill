@extends('layouts.app')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="{{ asset('css/fb-show-redesigned.css') }}?v={{ config('app.ASSET_VERSION') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 2.125rem !important;
            padding: 0.2rem 0.55rem !important;
            font-size: 0.8125rem !important;
            border-radius: 0.55rem !important;
            border-color: var(--brand-border, #e5e7eb) !important;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 0 !important;
            line-height: 1.7rem !important;
            color: #334155 !important;
        }
        .select2-container--bootstrap-5 .select2-dropdown {
            font-size: 0.8125rem !important;
            border-radius: 8px !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
        }
        .select2-container--bootstrap-5 .select2-results__option {
            padding: 4px 8px !important;
        }

        :root {
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.3);
            --primary-brand: var(--brand-primary);
            --primary-brand-dark: var(--brand-primary-dark);
            --success-brand: var(--brand-success);
            --warning-brand: var(--brand-warning);
            --danger-brand: var(--brand-danger);
            --slate-600: var(--brand-text-muted);
            --slate-800: var(--brand-text-primary);
        }

        .table td, .table th {
            padding: 8px 10px;
            vertical-align: middle;
            border-color: rgba(0,0,0,0.05) !important;
        }

        @media (max-width: 767.98px) {
            .table td, .table th {
                font-size: 0.8125rem !important;
                padding: 4px 6px !important;
            }
        }

        /* Action Buttons */
        .btn-action {
            width: 28px;
            height: 28px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            background: white;
            color: var(--slate-600);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0.9;
        }

        .btn-action i {
            color: inherit;
            font-size: 14px;
        }

        .btn-action-info    { background: var(--brand-info) !important; color: white !important; }
        .btn-action-primary { background: var(--brand-primary) !important; color: white !important; }
        .btn-action-success { background: var(--brand-success) !important; color: white !important; }
        .btn-action-outline-success {
            background: white !important;
            color: var(--brand-success) !important;
            border: 1px solid var(--brand-success) !important;
        }

        .header-pill-btn {
            border-radius: 50px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            height: 32px;
            padding: 0 16px !important;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .header-pill-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        }

        .table-success { background-color: #d1e7dd !important; border-color: #badbcc !important; }
        .table-danger  { background-color: #f8d7da !important; border-color: #f5c2c7 !important; }

        .freight-bookings-index .table-responsive {
            max-height: none !important;
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        .freight-bookings-index .table-responsive::-webkit-scrollbar {
            height: 6px;
        }

        .freight-bookings-index .table-responsive::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .freight-bookings-index .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .freight-bookings-index .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        #freightBookingsTable {
            width: 100% !important;
            border-collapse: collapse;
            border-spacing: 0;
        }

        #freightBookingsTable th,
        #freightBookingsTable td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 5px !important;
            padding-right: 5px !important;
        }

        #freightBookingsTable thead th {
            position: sticky;
            top: 0;
            background: var(--brand-table-header-bg) !important;
            z-index: 20;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            padding-top: 4px !important;
            padding-bottom: 4px !important;
            padding-left: 3px !important;
            padding-right: 3px !important;
            font-size: 11.5px !important;
            font-weight: 700;
            letter-spacing: 0;
        }

        /* Large desktop view: fit within 100% */
        @media (min-width: 1200px) {
            #freightBookingsTable {
                table-layout: fixed;
                min-width: 0 !important;
            }

            #freightBookingsTable th:nth-child(1), #freightBookingsTable td:nth-child(1) { width: 3.5%; text-align: center; } /* Checkbox */
            #freightBookingsTable th:nth-child(2), #freightBookingsTable td:nth-child(2) { width: 7.5%; } /* GR Date */
            #freightBookingsTable th:nth-child(3), #freightBookingsTable td:nth-child(3) { width: 8.5%; } /* LR No */
            #freightBookingsTable th:nth-child(4), #freightBookingsTable td:nth-child(4) { width: 11%; }  /* Customer */
            #freightBookingsTable th:nth-child(5), #freightBookingsTable td:nth-child(5) { width: 11%; }  /* Transporter */
            #freightBookingsTable th:nth-child(6), #freightBookingsTable td:nth-child(6) { width: 9%; }   /* Truck */
            #freightBookingsTable th:nth-child(7), #freightBookingsTable td:nth-child(7) { width: 10%; }  /* Route */
            #freightBookingsTable th:nth-child(8), #freightBookingsTable td:nth-child(8) { width: 6%; }   /* Weight */
            #freightBookingsTable th:nth-child(9), #freightBookingsTable td:nth-child(9) { width: 6%; }   /* Rate */
            #freightBookingsTable th:nth-child(10), #freightBookingsTable td:nth-child(10) { width: 8%; } /* Total */
            #freightBookingsTable th:nth-child(11), #freightBookingsTable td:nth-child(11) { width: 8%; } /* Balance */
            #freightBookingsTable th:nth-child(12), #freightBookingsTable td:nth-child(12) { width: 11.5%; min-width: 115px; } /* Actions */
        }

        #freightBookingsTable td:nth-child(12),
        #freightBookingsTable th:nth-child(12) {
            overflow: visible;
            text-overflow: clip;
        }

        .freight-bookings-index .app-index-table-actions {
            display: inline-flex !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 4px !important;
            white-space: nowrap !important;
            max-width: none !important;
        }

        /* Mobile & Tablet: smooth internal horizontal scroll without page overflow */
        @media (max-width: 1199.98px) {
            #freightBookingsTable {
                table-layout: auto !important;
                min-width: 1050px !important;
                width: 100% !important;
            }

            #freightBookingsTable th:nth-child(n),
            #freightBookingsTable td:nth-child(n) {
                width: auto !important;
                white-space: nowrap !important;
            }

            #freightBookingsTable th:nth-child(12),
            #freightBookingsTable td:nth-child(12) {
                min-width: 120px !important;
                width: 120px !important;
                white-space: nowrap !important;
                overflow: visible !important;
            }
        }

        @media (max-width: 575.98px) {
            .freight-bookings-index .app-index-list-card > .card-header {
                padding: 0.5rem 0.75rem !important;
            }

            .freight-bookings-index .modal-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 0.4rem;
            }

            .freight-bookings-index .modal-footer .btn {
                width: 100%;
                margin: 0 !important;
                text-align: center;
            }

            /* Pagination size matches Bilty standard: unified block with small font */
            .freight-bookings-index .app-index-partload-pagination {
                padding: 0.4rem 0.5rem !important;
                gap: 0.35rem !important;
            }

            .freight-bookings-index .app-index-partload-pagination .dataTables_info {
                font-size: 0.72rem !important;
                text-align: center !important;
            }

            .freight-bookings-index .app-index-partload-pagination .pagination {
                margin: 0 !important;
                gap: 0 !important;
            }

            .freight-bookings-index .app-index-partload-pagination .pagination .page-item {
                margin: 0 !important;
                padding: 0 !important;
            }

            .freight-bookings-index .app-index-partload-pagination .pagination .page-link {
                font-size: 0.72rem !important;
                padding: 0.22rem 0.5rem !important;
            }

            .freight-bookings-index .app-index-partload-pagination .pagination .page-item:first-child .page-link {
                border-top-left-radius: 0.375rem !important;
                border-bottom-left-radius: 0.375rem !important;
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
            }

            .freight-bookings-index .app-index-partload-pagination .pagination .page-item:last-child .page-link {
                border-top-right-radius: 0.375rem !important;
                border-bottom-right-radius: 0.375rem !important;
                border-top-left-radius: 0 !important;
                border-bottom-left-radius: 0 !important;
            }

            .freight-bookings-index .app-index-partload-pagination .pagination .page-item:not(:first-child):not(:last-child) .page-link {
                border-radius: 0 !important;
            }

            .freight-bookings-index .app-index-partload-pagination .pagination .page-item:only-child .page-link {
                border-radius: 0.375rem !important;
            }
        }

        @media (max-width: 767.98px) {
            .freight-bookings-index .app-index-partload-pagination .dataTables_info {
                font-size: 0.75rem !important;
                text-align: center !important;
            }

            .freight-bookings-index .app-index-partload-pagination .pagination {
                margin: 0 !important;
                gap: 0 !important;
            }

            .freight-bookings-index .app-index-partload-pagination .pagination .page-link {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.55rem !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('warning'))
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: "{{ session('warning') }}",
                confirmButtonColor: '#f39c12'
            });
        </script>
    @endif
@endpush

@section('content')




    <div class="container-fluid mt-2 pt-1 app-index-page app-index-page--standard freight-bookings-index">
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
                <h3 class="card-title mb-0">Good Hires (Lorry Hire)</h3>
            </div>

            <div class="app-index-dt-toolbar">
                <div class="app-index-filters" data-app-index-filters>
                    <div class="app-index-filters__top">
                        <div class="app-index-filters__intro">
                            <h6 class="app-index-filters__title">Search &amp; Filters</h6>
                            <div class="app-index-filters__legend d-none d-md-inline-flex">
                                <span class="app-index-filters__legend-item">
                                    <span class="app-index-filters__swatch app-index-filters__swatch--delivered"></span>
                                    Delivered
                                </span>
                                <span class="app-index-filters__legend-item">
                                    <span class="app-index-filters__swatch app-index-filters__swatch--cancelled"></span>
                                    Cancelled
                                </span>
                                <span class="app-index-filters__legend-item">
                                    <span class="app-index-filters__swatch app-index-filters__swatch--pending"></span>
                                    Pending
                                </span>
                            </div>
                        </div>
                        <div class="app-index-filters__top-actions">
                            <div class="app-index-filters__bulk">
                                <button id="bulk-memo-id-btn" class="header-pill-btn btn btn-primary border-0" style="display: none;" data-bs-toggle="modal" data-bs-target="#bulkMemoIdModal">
                                    <i class="bi bi-clipboard-check"></i> Bulk Memo
                                </button>
                                <button id="voucher-payment-btn" class="header-pill-btn btn btn-brand-primary border-0" style="display: none;">
                                    <i class="bi bi-receipt"></i> Voucher
                                </button>
                                <button id="combined-payment-btn" class="header-pill-btn btn btn-warning border-0" style="display: none;">
                                    <i class="bi bi-collection"></i> Combined
                                </button>
                            </div>
                            <button type="button"
                                class="app-index-filters__toggle d-md-none"
                                data-app-index-filters-toggle
                                aria-expanded="false"
                                aria-controls="fbIndexFiltersBody">
                                <i class="bi bi-sliders"></i>
                                <span>Filters</span>
                                <i class="bi bi-chevron-down app-index-filters__toggle-chevron"></i>
                            </button>
                        </div>
                    </div>

                    <div class="app-index-filters__body" id="fbIndexFiltersBody">
                        <form method="GET" action="{{ route('freight_bookings.index') }}" id="searchForm" class="app-index-filters__form">
                            <div class="app-index-filters__controls">
                                <div class="app-index-filters__field app-index-filters__field--search">
                                    <label class="app-index-filters__label" for="filterFbSearch">Search</label>
                                    <input type="text" class="form-control form-control-sm" name="search" id="filterFbSearch"
                                        value="{{ \App\Support\ListingQueryString::scalarValue(request(), 'search', \App\Support\ListingQueryString::FREIGHT_BOOKING_KEYS) }}"
                                        placeholder="LR, city, route...">
                                </div>
                                <div class="app-index-filters__field app-index-filters__field--lr">
                                    <label class="app-index-filters__label" for="filterFbLrNumber">Bilty / LR</label>
                                    <input type="text" class="form-control form-control-sm" name="lr_number" id="filterFbLrNumber"
                                        value="{{ \App\Support\ListingQueryString::scalarValue(request(), 'lr_number', \App\Support\ListingQueryString::FREIGHT_BOOKING_KEYS) }}"
                                        placeholder="LR number">
                                </div>
                                <div class="app-index-filters__field app-index-filters__field--party">
                                    <label class="app-index-filters__label" for="filterFbCustomerId">Customer</label>
                                    <select name="customer_id" id="filterFbCustomerId" class="form-select form-select-sm select2-fb-filter">
                                        <option value="">All Customers</option>
                                        @if(isset($selectedCustomer) && $selectedCustomer)
                                            <option value="{{ $selectedCustomer->id }}" selected>{{ $selectedCustomer->name }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="app-index-filters__field app-index-filters__field--party">
                                    <label class="app-index-filters__label" for="filterFbTransporterId">Transporter</label>
                                    <select name="transporter_id" id="filterFbTransporterId" class="form-select form-select-sm select2-fb-filter">
                                        <option value="">All Transporters</option>
                                        @if(isset($selectedTransporter) && $selectedTransporter)
                                            <option value="{{ $selectedTransporter->id }}" selected>{{ $selectedTransporter->displayNameWithShortName() }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="app-index-filters__field app-index-filters__field--truck">
                                    <label class="app-index-filters__label" for="filterFbTruckId">Truck</label>
                                    <select name="truck_id" id="filterFbTruckId" class="form-select form-select-sm select2-fb-filter">
                                        <option value="">All Trucks</option>
                                        @if(isset($selectedTruck) && $selectedTruck)
                                            <option value="{{ $selectedTruck->id }}" selected>{{ $selectedTruck->truck_number }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="app-index-filters__field app-index-filters__field--date">
                                    <label class="app-index-filters__label" for="fbStartDate">From</label>
                                    <input type="date" class="form-control form-control-sm" name="start_date" id="fbStartDate" value="{{ $startDate }}">
                                </div>
                                <div class="app-index-filters__field app-index-filters__field--date">
                                    <label class="app-index-filters__label" for="fbEndDate">To</label>
                                    <input type="date" class="form-control form-control-sm" name="end_date" id="fbEndDate" value="{{ $endDate }}">
                                </div>
                                <label class="app-index-filters__chip{{ request('pending_memo') ? ' is-active' : '' }}" for="pendingMemoFilter">
                                    <input class="form-check-input" type="checkbox" name="pending_memo" value="1"
                                        id="pendingMemoFilter" {{ request('pending_memo') ? 'checked' : '' }}>
                                    <span class="app-index-filters__chip-label">Pending Memo</span>
                                </label>
                                <div class="app-index-filters__actions">
                                    <button type="submit" class="app-index-filters__btn app-index-filters__btn--apply" id="filterFbBtn" data-tooltip="Filter" aria-label="Filter">
                                        <i class="bi bi-funnel-fill"></i>
                                    </button>
                                    <a href="{{ route('freight_bookings.index') }}" class="app-index-filters__btn app-index-filters__btn--reset" id="resetFbBtn" data-tooltip="Reset" aria-label="Reset">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                </div>
                            </div>
                        </form>

                        @if (request('search'))
                            <div class="app-index-filters__result">
                                <i class="bi bi-info-circle me-1"></i>
                                Results for: <strong>"{{ request('search') }}"</strong>
                                <a href="{{ route('freight_bookings.index') }}" class="btn btn-link btn-sm p-0 ms-2" style="font-size: 0.75rem;">Clear Search</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="freightBookingsTable" class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center"><input type="checkbox" id="select-all"></th>
                                <th class="text-center">GR Date</th>
                                <th>LR No</th>
                                <th>Customer</th>
                                <th>Transporter</th>
                                <th>Truck No</th>
                                <th>Route</th>
                                <th>Weight</th>
                                <th>Rate</th>
                                <th>Total</th>
                                <th class="text-end">Balance</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Combined Payment Modal -->
    <div class="modal fade fb-pay-modal" id="combinedPaymentModal" tabindex="-1" aria-labelledby="combinedPaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="combinedPaymentModalLabel">
                        <i class="bi bi-wallet2"></i> Combined Payment - Linked Bookings
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="combinedPaymentBody">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="combinedPaymentSubmitBtn">
                        <i class="fas fa-save"></i> Submit Combined Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- LR Details Modal -->
    <div class="modal fade fb-pay-modal" id="lrDetailsModal" tabindex="-1" aria-labelledby="lrDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lrDetailsModalLabel">LR Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="lrDetailsContent">
                    <!-- Content will be loaded dynamically -->
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Voucher Payment Modal -->
    <div class="modal fade fb-pay-modal" id="voucherPaymentModal" tabindex="-1" aria-labelledby="voucherPaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voucherPaymentModalLabel">
                        <i class="bi bi-wallet2"></i> Voucher Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="voucherPaymentBody">
                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-clock-history"></i> Previous Payments
                        </div>
                        <div class="fb-pay-panel__body--flush" id="previousPaymentsTable">
                            <div class="fb-pay-loading">
                                <div class="spinner-border spinner-border-sm me-1" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading payment history...
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-truck"></i> Transporter Details
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-facts fb-pay-facts--7">
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Transporter</span>
                                    <div class="fb-pay-fact__value" id="lrTransporter">-</div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Mobile</span>
                                    <div class="fb-pay-fact__value" id="transporterMobile">-</div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Short Name</span>
                                    <div class="fb-pay-fact__value" id="transporterShortName">-</div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Beneficiary ID</span>
                                    <div class="fb-pay-fact__value" id="transporterBeneficiaryId">-</div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">TDS Percentage</span>
                                    <div class="fb-pay-fact__value" id="tdsPercentage">-</div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Truck Number</span>
                                    <div class="fb-pay-fact__value fb-pay-fact__value--accent" id="truckNumber">-</div>
                                </div>
                                <div class="fb-pay-fact" id="advanceAmountFact" style="display: none;">
                                    <span class="fb-pay-fact__label">Advance Amount</span>
                                    <div class="fb-pay-fact__value fb-pay-fact__value--warn" id="advanceAmount">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel fb-pay-panel--alloc">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-currency-rupee"></i> Payment Allocation
                        </div>
                        <div class="fb-pay-panel__body--flush">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0 fb-pay-table">
                                    <thead>
                                        <tr>
                                            <th>FR No.</th>
                                            <th>LR Number</th>
                                            <th>Balance Amount</th>
                                            <th>Payment Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="voucherPaymentTableBody">
                                    </tbody>
                                </table>
                            </div>
                            <div class="fb-pay-total">
                                <span>Total Payment Amount</span>
                                <strong>₹<span id="totalPaymentAmount">0.00</span></strong>
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-pencil-square"></i> Payment Details
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-fields fb-pay-fields--4">
                                <div>
                                    <label for="paymentType" class="form-label">Payment Type *</label>
                                    <select class="form-select" id="paymentType" required>
                                        <option value="">Select Payment Type</option>
                                        @foreach ($paymentTypes as $type)
                                            <option value="{{ $type->name }}" data-id="{{ $type->id }}">
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="paymentMode" class="form-label">Payment Mode *</label>
                                    <select class="form-select" id="paymentMode" required>
                                        <option value="">Select Payment Mode</option>
                                        @foreach ($paymentModes as $mode)
                                            <option value="{{ $mode->name }}" data-id="{{ $mode->id }}">
                                                {{ $mode->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="paymentDate" class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" name="date" id="paymentDate"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                                <div>
                                    <label for="paymentRemarks" class="form-label">Remarks</label>
                                    <input type="text" class="form-control" id="paymentRemarks" placeholder="Enter remarks">
                                </div>
                                <div id="pumpNameContainer" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="pumpName" class="form-label mb-0">Pump Name *</label>
                                        <a href="javascript:void(0)"
                                            class="lrf-quick-add js-open-quick-add-pump"
                                            data-pump-select="pumpName">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </a>
                                    </div>
                                    <select class="form-select" id="pumpName">
                                        <option value="">Select Pump</option>
                                        @foreach ($pumps as $pump)
                                            <option value="{{ $pump->id }}">{{ $pump->pump_name }}
                                                {{ $pump->contact_name ? '('.$pump->contact_name.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success btn-sm" onclick="submitVoucherPayment()">
                        <i class="bi bi-check-lg me-1"></i> Submit Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Individual Voucher Payment Modal -->
    <div class="modal fade fb-pay-modal" id="individualPaymentModal" tabindex="-1" aria-labelledby="individualPaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="individualPaymentModalLabel">💳 Individual Voucher Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="individualPaymentBody">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="submitIndividualPayment"><i class="bi bi-currency-rupee"></i> Process
                        Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Memo ID Modal -->
    <div class="modal fade fb-pay-modal" id="bulkMemoIdModal" tabindex="-1" aria-labelledby="bulkMemoIdModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clipboard-check"></i> Bulk Memo ID Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bulkMemoIdBody">
                    <!-- Dynamic content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="bulkMemoIdForm" class="btn btn-primary">Assign Memo ID</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Voucher Print Confirmation -->
    <div class="modal fade fb-pay-modal" id="voucherPrintConfirmModal" tabindex="-1"
        aria-labelledby="voucherPrintConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voucherPrintConfirmModalLabel">
                        <i class="bi bi-printer"></i>
                        Print Confirmation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fb-confirm-note">
                        <i class="bi bi-info-circle"></i>
                        Did you print voucher challan successfully?
                    </div>
                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-check2-circle"></i> Mark as printed
                        </div>
                        <div class="fb-pay-panel__body">
                            <p class="mb-0" style="font-size: 0.84rem; color: #475569;">
                                Choose <strong>Yes, Printed</strong> to record this print on the booking.
                                Choose <strong>No</strong> if the print did not complete.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> No
                    </button>
                    <button type="button" class="btn btn-brand-primary" id="voucherPrintConfirmYesBtn">
                        <i class="bi bi-check-lg me-1"></i> Yes, Printed
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('freight_bookings.partials.quick-add-pump')

@endsection

@push('scripts')
    <script>
        const paymentTypes = @json($paymentTypes);
        const paymentModes = @json($paymentModes);
        let selectedRows = {};
        let selectedBookingIds = [];

        $(document).on('change', '.row-checkbox', function() {
            const id = $(this).val();

            if (this.checked) {
                selectedRows[id] = {
                    id: id,
                    lr: $(this).data('lr'),
                    truck: $(this).data('truck'),
                    balance: parseFloat($(this).data('balance') || 0),
                    transporterId: $(this).data('transporter-id'),
                    customerId: $(this).data('customer-id'),
                    memoId: $(this).data('memo-id'),
                    linkedLrId: $(this).data('linked-lr-id'),
                    voucher_number: $(this).data('voucher_number'),
                    freight_rate: parseFloat($(this).data('freight-rate') || 0),
                    freight_booking_id: $(this).data('freight-booking-id'),
                    lr_id: $(this).data('lr-id'),
                    transporter: $(this).data('transporter'),
                    lr_transporter: $(this).attr('data-lr-transporter') || $(this).data('lr-transporter'),
                    transporter_mobile_number: $(this).attr('data-lr-transporter-mobile-number') || $(this).data('lr-transporter-mobile-number'),
                    transporter_short_name: $(this).attr('data-lr-transporter-short-name') || $(this).data('lr-transporter-short-name'),
                    transporter_beneficiary_id: $(this).attr('data-lr-transporter-beneficiary-id') || $(this).data('lr-transporter-beneficiary-id'),
                    fb_transporter: $(this).attr('data-fb-transporter') || $(this).data('fb-transporter'),
                    tds_percentage: $(this).attr('data-tds-percentage') || $(this).data('tds-percentage'),
                    amount: $(this).data('amount'),
                    shortage_amount: $(this).data('shortage-amount'),
                    damage_amount: $(this).data('damage-amount'),
                    advance_amount: $(this).attr('data-advance-amount') || $(this).data('advance-amount')
                };
                if (!selectedBookingIds.includes(id)) {
                    selectedBookingIds.push(id);
                }
            } else {
                delete selectedRows[id];
                selectedBookingIds = selectedBookingIds.filter(x => x !== id);
            }
        });

        $('#select-all').on('change', function() {
            const checked = this.checked;

            $('.row-checkbox').each(function() {
                const id = $(this).val();
                $(this).prop('checked', checked);

                if (checked) {
                    if (!selectedBookingIds.includes(id)) {
                        selectedBookingIds.push(id);
                        selectedRows[id] = {
                            id: id,
                            lr: $(this).data('lr'),
                            truck: $(this).data('truck'),
                            balance: parseFloat($(this).data('balance') || 0),
                            transporterId: $(this).data('transporter-id'),
                            customerId: $(this).data('customer-id'),
                            memoId: $(this).data('memo-id'),
                            linkedLrId: $(this).data('linked-lr-id'),
                            voucher_number: $(this).data('voucher_number'),
                            freight_rate: parseFloat($(this).data('freight-rate') || 0),
                            freight_booking_id: $(this).data('freight-booking-id'),
                            lr_id: $(this).data('lr-id'),
                            transporter: $(this).data('transporter'),
                            lr_transporter: $(this).attr('data-lr-transporter') || $(this).data('lr-transporter'),
                            transporter_mobile_number: $(this).attr('data-lr-transporter-mobile-number') || $(this).data('lr-transporter-mobile-number'),
                            transporter_short_name: $(this).attr('data-lr-transporter-short-name') || $(this).data('lr-transporter-short-name'),
                            transporter_beneficiary_id: $(this).attr('data-lr-transporter-beneficiary-id') || $(this).data('lr-transporter-beneficiary-id'),
                            fb_transporter: $(this).attr('data-fb-transporter') || $(this).data('fb-transporter'),
                            tds_percentage: $(this).attr('data-tds-percentage') || $(this).data('tds-percentage'),
                            amount: $(this).data('amount'),
                            shortage_amount: $(this).data('shortage-amount'),
                            damage_amount: $(this).data('damage-amount'),
                            advance_amount: $(this).attr('data-advance-amount') || $(this).data('advance-amount')
                        };
                    }
                } else {
                    selectedBookingIds = [];
                    selectedRows = {};
                }
            });

            updatePaymentButtonsVisibility();
        });

        function updateSelectAllState() {
            const total = $('.row-checkbox').length;
            const checked = Object.values(selectedRows).length;

            if (checked === 0) {
                $('#select-all').prop({
                    checked: false,
                    indeterminate: false
                });
            } else if (checked === total) {
                $('#select-all').prop({
                    checked: true,
                    indeterminate: false
                });
            } else {
                $('#select-all').prop({
                    indeterminate: true
                });
            }
        }

        function getSelectedCheckboxes() {
            return selectedBookingIds
                .map(id => $('.row-checkbox[value="' + id + '"]'))
                .filter(cb => cb.length);
        }
    </script>

    <script>
        $(document).ready(function() {
            // Select all checkbox functionality
            $('#select-all').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.row-checkbox').prop('checked', isChecked);
                updatePaymentButtonsVisibility();
            });

            // Individual checkbox change
            $(document).on('change', '.row-checkbox', function() {
                updatePaymentButtonsVisibility();

                // Update select-all checkbox state
                const totalCheckboxes = $('.row-checkbox').length;
                const checkedCheckboxes = Object.values(selectedRows).length;

                if (checkedCheckboxes === 0) {
                    $('#select-all').prop('indeterminate', false).prop('checked', false);
                } else if (checkedCheckboxes === totalCheckboxes) {
                    $('#select-all').prop('indeterminate', false).prop('checked', true);
                } else {
                    $('#select-all').prop('indeterminate', true);
                }
            });


            // Combined Payment Button Click Handler
            const planEndDateStr = @json(Auth::user()->company?->subscription?->ends_on?->toDateString() ?? null);
            const today = new Date();
            $('#combined-payment-btn').on('click', function() {
                const checkedBoxes = Object.values(selectedRows);
                if (checkedBoxes.length === 0) {
                    alert('Please select at least one booking.');
                    return;
                }
                if (planEndDateStr) {
                    const planEndDate = new Date(planEndDateStr);
                    if (today > planEndDate) {
                        alert(
                            'Your subscription plan has expired. Please renew your subscription to continue using the service.'
                        );
                        // Stop further execution so modal doesn't open
                        return false; // or just return
                    }
                }

                // Get first selected checkbox data
                const firstData = checkedBoxes[0];
                const linkedLrId = firstData.linkedLrId;

                // If there's a linked LR ID, fetch all related LRs
                if (linkedLrId) {
                    $.get(`/lorryreceipts/by-linked/${linkedLrId}`, function(response) {
                        const lrsArray = response.lrs || response;

                        if (lrsArray && lrsArray.length > 0) {
                            // ✅ CHECK: Verify all linked LRs have freight_booking_id
                            const allHaveFreightBooking = lrsArray.every(lr =>
                                lr.freight_booking_id !== null &&
                                lr.freight_booking_id !== undefined &&
                                lr.freight_booking_id !== ''
                            );

                            if (!allHaveFreightBooking) {
                                // Find which LRs are missing freight bookings
                                const missingLRs = lrsArray.filter(lr =>
                                    lr.freight_booking_id === null ||
                                    lr.freight_booking_id === undefined ||
                                    lr.freight_booking_id === ''
                                );

                                const lrNumbers = missingLRs.map(lr => lr.lr_number).join(', ');

                                alert(
                                    `⚠️ Error: The following linked LRs do not have Good Hires (Lorry Hire):\n\n${lrNumbers}\n\nPlease create FRs for all linked LRs before proceeding with combined payment.`
                                );
                                return;
                            }

                            // All checks passed, proceed with modal
                            buildCombinedModal(lrsArray);
                        } else {
                            // Fallback to just the selected LRs if no linked ones found
                            buildCombinedModal(checkedBoxes.map(mapSelectedRowToLrData));
                        }
                    }).fail(function(error) {
                        console.error('Error fetching linked LRs:', error);
                        alert('Error loading linked LRs. Please try again.');
                    });
                } else {
                    // No linked LR ID, just use the selected rows
                    buildCombinedModal(checkedBoxes.map(mapSelectedRowToLrData));
                }
            });

            function mapSelectedRowToLrData(data) {
                return {
                    freight_booking_id: data.freight_booking_id,
                    lr_number: data.lr,
                    truck_number: data.truck,
                    balance_amount: data.balance,
                    shortage_amount: data.shortage_amount ?? 0,
                    damage_amount: data.damage_amount ?? 0,
                    transporter_name: data.lr_transporter || data.transporter || '',
                    transporter_mobile: data.transporter_mobile_number || '',
                    transporter_short_name: data.transporter_short_name || '',
                    transporter_beneficiary_id: data.transporter_beneficiary_id || '',
                    transporter_tds_percentage: data.tds_percentage || '',
                    transporter_advance_amount: data.advance_amount ?? 0,
                };
            }

            function formatCombinedAmount(value) {
                return parseFloat(value || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function buildCombinedModal(lrData) {
                let rowsHtml = '';
                const bookingIds = [];
                let totalBalance = 0;
                const firstLr = lrData[0] || {};

                lrData.forEach(lr => {
                    totalBalance += parseFloat(lr.balance_amount) || 0;
                });

                lrData.forEach(lr => {
                    const fbId = lr.freight_booking_id;
                    const lrNo = lr.lr_number;
                    const shortage = parseFloat(lr.shortage_amount) || 0;
                    const damage = parseFloat(lr.damage_amount) || 0;
                    const balance = parseFloat(lr.balance_amount) || 0;

                    bookingIds.push(fbId);

                    rowsHtml += `
                        <tr>
                            <td>${lrNo}</td>
                            <td>${formatCombinedAmount(shortage)}</td>
                            <td>${formatCombinedAmount(damage)}</td>
                            <td>${formatCombinedAmount(balance)}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm allocated-input"
                                    name="payment_amounts[]" data-fb-id="${fbId}"
                                    data-balance="${balance}"
                                    step="0.01" min="0" max="${balance}" readonly>
                            </td>
                        </tr>
                    `;
                });

                const modalContent = `
                    <form id="combinedPaymentForm" method="POST" action="{{ route('freight_bookings.store_combined_payment') }}" data-total-balance="${totalBalance}">
                        @csrf
                        <div class="alert alert-secondary mb-3">
                            <h6><i class="fas fa-truck"></i> Transporter Details</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Transporter:</strong><br>
                                    <span>${firstLr.transporter_name || 'N/A'}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Mobile:</strong><br>
                                    <span>${firstLr.transporter_mobile || 'N/A'}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Short Name:</strong><br>
                                    <span>${firstLr.transporter_short_name || 'N/A'}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Beneficiary ID:</strong><br>
                                    <span>${firstLr.transporter_beneficiary_id || 'N/A'}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>TDS %:</strong><br>
                                    <span>${firstLr.transporter_tds_percentage || 'N/A'}%</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Truck:</strong><br>
                                    <span>${firstLr.truck_number || 'N/A'}</span>
                                </div>
                                <div class="col-md-3 text-danger">
                                    <strong>Advance:</strong><br>
                                    <span>${firstLr.transporter_advance_amount ?? 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="col-md-4">
                                    <strong>Total Balance:</strong>
                                    <span class="fw-bold text-danger">${formatCombinedAmount(totalBalance)}</span>
                                </div>
                                <div class="col-md-4 d-flex justify-content-end align-items-center gap-2">
                                    <label class="form-label mb-0 ms-2">Combined Amount *</label>
                                    <input type="number" class="form-control" id="combinedAmountInput" name="combined_amount"
                                        step="0.01" min="0.01" max="${totalBalance}" required style="max-width: 200px;">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>LR Number</th>
                                        <th>Shortage Amount</th>
                                        <th>Damage Amount</th>
                                        <th>Balance</th>
                                        <th>Allocated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rowsHtml}
                                </tbody>
                            </table>
                        </div>
                        ${bookingIds.map(id => `<input type="hidden" name="booking_ids[]" value="${id}">`).join('')}
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="combinedPaymentType" class="form-label">Payment Type *</label>
                                <select class="form-select" id="combinedPaymentType" name="payment_type" required>
                                    <option value="">Select Payment Type</option>
                                    ${paymentTypes.map(type => `<option value="${type.id}">${type.name}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="combinedPaymentMode" class="form-label">Payment Mode *</label>
                                <select class="form-select" id="combinedPaymentMode" name="payment_mode" required>
                                    <option value="">Select Payment Mode</option>
                                    ${paymentModes.map(mode => `<option value="${mode.id}">${mode.name}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-md-4" id="combinedPumpContainer" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="combinedPumpName" class="form-label mb-0">Pump Name *</label>
                                    <a href="javascript:void(0)"
                                        class="lrf-quick-add js-open-quick-add-pump"
                                        data-pump-select="combinedPumpName">
                                        <i class="bi bi-plus-lg"></i> Add
                                    </a>
                                </div>
                                <select class="form-select" id="combinedPumpName" name="pump_id">
                                    <option value="">Select Pump</option>
                                    @foreach ($pumps as $pump)
                                        <option value="{{ $pump->id }}">{{ $pump->pump_name }} {{ $pump->contact_name ? '('.$pump->contact_name.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="combinedPaymentDate" class="form-label">Payment Date</label>
                                <input type="date" class="form-control" id="combinedPaymentDate" name="payment_date"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="combinedNotes" class="form-label">Notes</label>
                                <input type="text" class="form-control" id="combinedNotes" name="notes" placeholder="Enter notes...">
                            </div>
                        </div>
                    </form>
                `;

                $('#combinedPaymentBody').html(modalContent);

                const modal = new bootstrap.Modal(document.getElementById('combinedPaymentModal'));
                modal.show();

                initializeCombinedPaymentHandlers(totalBalance);
            }

            function initializeCombinedPaymentHandlers(totalBalance) {
                const amountInput = document.getElementById('combinedAmountInput');
                const allocatedInputs = document.querySelectorAll('#combinedPaymentForm .allocated-input');

                function updateCombinedPaymentType() {
                    const paymentTypeSelect = document.getElementById('combinedPaymentType');
                    if (!paymentTypeSelect) {
                        return;
                    }

                    const totalAmount = parseFloat(amountInput?.value) || 0;
                    const isFullAmount = totalAmount > 0 && Math.abs(totalAmount - totalBalance) <= 0.01;
                    const targetType = isFullAmount ? 'final' : 'advance';
                    const match = Array.from(paymentTypeSelect.options).find(
                        (option) => option.textContent.trim().toLowerCase() === targetType
                    );

                    if (match) {
                        paymentTypeSelect.value = match.value;
                    }
                }

                function allocateCombinedAmount() {
                    const totalAmount = parseFloat(amountInput?.value) || 0;
                    if (totalAmount <= 0 || totalBalance <= 0) {
                        return;
                    }

                    let totalAlloc = 0;
                    const allocs = [];
                    allocatedInputs.forEach(input => {
                        const bal = parseFloat(input.dataset.balance) || 0;
                        const pct = totalBalance > 0 ? (bal / totalBalance) : 0;
                        let a = Math.min(totalAmount * pct, bal);
                        allocs.push({ input, a });
                        totalAlloc += a;
                    });

                    const diff = totalAmount - totalAlloc;
                    if (Math.abs(diff) > 0.01 && allocs.length > 0) {
                        for (const item of allocs) {
                            const bal = parseFloat(item.input.dataset.balance) || 0;
                            if (bal - item.a > 0 && diff > 0) {
                                item.a += Math.min(bal - item.a, diff);
                                break;
                            }
                        }
                    }

                    allocs.forEach(({ input, a }) => {
                        input.value = a.toFixed(2);
                    });

                    updateCombinedPaymentType();
                }

                if (amountInput) {
                    $(amountInput).off('input').on('input', allocateCombinedAmount);
                }

                updateCombinedPaymentType();

                function toggleCombinedPump() {
                    const sel = document.getElementById('combinedPaymentMode');
                    const pump = document.getElementById('combinedPumpContainer');
                    if (!sel || !pump) {
                        return;
                    }
                    const txt = sel.options[sel.selectedIndex]?.text?.toLowerCase() || '';
                    pump.style.display = txt.includes('fuel') ? 'block' : 'none';
                    if (!txt.includes('fuel')) {
                        const pumpSelect = document.getElementById('combinedPumpName');
                        if (pumpSelect) {
                            pumpSelect.value = '';
                        }
                    }
                }

                $('#combinedPaymentMode').off('change').on('change', toggleCombinedPump);
                $('#combinedPaymentModal').off('shown.bs.modal.combined').on('shown.bs.modal.combined', toggleCombinedPump);
                toggleCombinedPump();
            }

            $(document).off('click', '#combinedPaymentSubmitBtn').on('click', '#combinedPaymentSubmitBtn', function() {
                const btn = this;
                const form = document.getElementById('combinedPaymentForm');
                if (!form) {
                    return;
                }

                const totalBalance = parseFloat(form.dataset.totalBalance) || 0;
                const combinedAmount = parseFloat(document.getElementById('combinedAmountInput')?.value) || 0;
                if (combinedAmount <= 0 || combinedAmount > totalBalance) {
                    alert('Please enter a valid combined amount between 0.01 and ' + totalBalance);
                    return;
                }

                const paymentType = document.getElementById('combinedPaymentType')?.value;
                if (!paymentType) {
                    alert('Please select a payment type');
                    return;
                }

                const paymentMode = document.getElementById('combinedPaymentMode')?.value;
                if (!paymentMode) {
                    alert('Please select a payment mode');
                    return;
                }

                const paymentDate = document.getElementById('combinedPaymentDate')?.value;
                if (!paymentDate) {
                    alert('Please select a payment date');
                    return;
                }

                if (!confirm('Please review all payment details carefully before proceeding. Once the voucher status is marked as PAID, it cannot be edited or changed.')) {
                    return;
                }

                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';

                const formData = new FormData(form);
                const url = form.action;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: formData
                })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || data.success === false) {
                        if (data.warning && data.duplicate_details) {
                            const d = data.duplicate_details;
                            return (typeof Swal !== 'undefined' ? Swal.fire({
                                icon: 'warning',
                                title: 'Duplicate Payment Detected',
                                html: `<p>A similar payment already exists:</p><ul><li>Date: ${d.date}</li><li>Amount: ${d.amount}</li><li>Payment Type: ${d.payment_type}</li><li>Payment Mode: ${d.payment_mode}</li></ul><p>Proceed anyway?</p>`,
                                showCancelButton: true,
                                confirmButtonText: 'Yes, Proceed',
                                cancelButtonText: 'Cancel'
                            }) : Promise.resolve({ isConfirmed: confirm('Duplicate payment detected. Proceed?') }))
                            .then(r => {
                                if (r?.isConfirmed) {
                                    formData.append('confirm_duplicate', '1');
                                    return fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                        },
                                        body: formData
                                    });
                                }
                                throw new Error('Cancelled');
                            })
                            .then(r => r.json());
                        }
                        throw new Error(data.message || 'Failed to process combined payment');
                    }
                    return data;
                })
                .then((data) => {
                    bootstrap.Modal.getInstance(document.getElementById('combinedPaymentModal'))?.hide();
                    let successText = data.message || 'Combined payment processed successfully';
                    if (data.adjust_in_advance && data.remaining_advance_balance !== undefined) {
                        const name = data.transporter_name || 'Transporter';
                        const bal = parseFloat(data.remaining_advance_balance).toFixed(2);
                        successText += '\n\nRemaining advance balance (' + name + '):  ' + bal;
                    }
                    (typeof Swal !== 'undefined' ? Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: successText
                    }) : Promise.resolve())
                    .then(() => {
                        window.location.reload();
                    });
                })
                .catch((err) => {
                    if (err.message !== 'Cancelled') {
                        (typeof Swal !== 'undefined' ? Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.message
                        }) : alert(err.message));
                    }
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            });

            // Bulk Memo ID Modal
            $('#bulk-memo-id-btn').on('click', function() {
                const checkedBoxes = Object.values(selectedRows);
                const planEndDateStr = @json(Auth::user()->company?->subscription?->ends_on?->toDateString() ?? null);
                const today = new Date();
                if (planEndDateStr && new Date(planEndDateStr) < today) {
                    alert('Your plan has expired. Please renew your plan to use this feature.');
                    return false;
                }

                if (checkedBoxes.length === 0) {
                    alert('Please select at least one good hire (lorry hire).');
                    return;
                }

                let tableBody = '';
                let lrIds = [];
                let freightBookingIds = [];
                let netAmount = 0;

                checkedBoxes.forEach(function(data) {
                    const lrNumber = data.lr;
                    const truckNumber = data.truck;
                    const amount = parseFloat(data.amount) || 0;
                    const freightRate = parseFloat(data.freight_rate || 0);
                    const freightBookingId = data.freight_booking_id;
                    const shortageAmount = parseFloat(data.shortage_amount) || 0;
                    const damageAmount = parseFloat(data.damage_amount) || 0;
                    netAmount += amount;

                    // Get LR ID from the freight booking (we'll need to add this data attribute)
                    const lrId = data.lr_id; // We'll need to add this

                    if (lrId) {
                        lrIds.push(lrId);
                    }
                    freightBookingIds.push(freightBookingId);

                    tableBody += `
                        <tr>
                            <td>${lrNumber}</td>
                            <td>${truckNumber}</td>
                            <td class="text-end">${freightRate.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            <td class="text-end">${amount + shortageAmount + damageAmount}</td>
                            <td class="text-end">${shortageAmount}</td>
                            <td class="text-end">${damageAmount}</td>
                            <input type="hidden" name="lr_ids[]" value="${lrId}">
                            <input type="hidden" name="freight_booking_ids[]" value="${freightBookingId}">
                        </tr>
                    `;
                });

                const modalContent = `
                    <form id="bulkMemoIdForm" method="POST" action="{{ route('freight_bookings.bulk_memo_id') }}">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Memo ID *</label>
                                <input type="text" name="memo_id" class="form-control" required placeholder="Enter Memo ID">
                                <div class="form-text">This memo ID will be assigned to all selected good receipts (bilty)</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>LR Number</th>
                                        <th>Truck Number</th>
                                        <th class="text-end">Freight Rate</th>
                                        <th class="text-end">LR Amount</th>
                                        <th class="text-end">Shortage</th>
                                        <th class="text-end">Damage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tableBody}
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <th colspan="5" class="text-end">Net Amount:</th>
                                        <th class="text-end">${netAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> Assigning memo ID will:
                            <ul class="mb-0 mt-0">
                                <li>Set good receipt (bilty) status to "Delivered"</li>
                                <li>Mark associated good hires (lorry hire) as delivered</li>
                                <li>This action cannot be easily undone</li>
                            </ul>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Loose Weight System:</strong>
                            <ul class="mb-0 mt-0">
                                <li>Voucher payments and invoices are generated against the <strong>lower</strong> of loading weight and unloading weight</li>
                                <li>TDS deductions for transporters are <strong>always</strong> based on the loading weight</li>
                                <li>After delivery, you have to edit the unloading weight from the FR edit page</li>
                            </ul>
                        </div>
                    </form>
                `;

                $('#bulkMemoIdBody').html(modalContent);
            });

            // Payment amount change handler
            $(document).on('input', '.payment-amount', function() {
                let total = 0;
                $('.payment-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#totalPaymentAmount').text('' + total.toLocaleString('en-IN', {
                    minimumFractionDigits: 2
                }));
                updatePaymentTypeFromAmounts({ inputSelector: '.payment-amount' });
            });
        });

        // Function to open voucher payment modal
        function openVoucherPaymentModal() {
            updatePaymentButtonsVisibility();
            $('#voucherPaymentModal').modal('show');
        }

        function updatePaymentButtonsVisibility() {
            const checkedBoxes = Object.values(selectedRows);
            $('#bulk-memo-id-btn, #voucher-payment-btn, #combined-payment-btn').hide();
            if (checkedBoxes.length === 0) {
                return; // nothing selected
            }
            if (checkedBoxes.length === 1) {
                const singleData = checkedBoxes[0];
                const singleLinkedLrId = singleData.linkedLrId;
                if (!singleLinkedLrId) {
                    $('#voucher-payment-btn').show(); // show only if linked_lr_id is null
                }
                if (singleLinkedLrId) {
                    $('#combined-payment-btn').show();
                }

                const singleMemoId = singleData.memoId;
                if (!singleMemoId) {
                    $('#bulk-memo-id-btn').show();
                }
                return;
            }
            let firstCustomerId = null;
            let firstTransporterId = null;
            let firstLinkedLrId = null;
            let allSameCustomer = true;
            let allSameTransporter = true;
            let allSameLinked = true;
            let anyLinkedLrExists = false;
            let allSameMemoId = true;

            checkedBoxes.forEach(function(data) {
                const customerId = data.customerId;
                const transporterId = data.transporterId;
                const linkedLrId = data.linkedLrId || '';
                const memoId = data.memoId;

                if (linkedLrId) {
                    anyLinkedLrExists = true;
                }
                if (memoId) {
                    allSameMemoId = false;
                }

                if (firstCustomerId === null) {
                    firstCustomerId = customerId;
                    firstTransporterId = transporterId;
                    firstLinkedLrId = linkedLrId;
                } else {
                    if (customerId !== firstCustomerId) {
                        allSameCustomer = false;
                    }
                    if (transporterId !== firstTransporterId) {
                        allSameTransporter = false;
                    }
                    if (linkedLrId !== firstLinkedLrId) {
                        allSameLinked = false;
                    }
                }
            });

            // 1. Bulk Memo ID button → if customer same
            if (allSameCustomer) {
                if (allSameMemoId) {
                    $('#bulk-memo-id-btn').show();
                }
            }

            // 2. Voucher Payment button → if transporter same
            if (allSameTransporter) {
                if (!anyLinkedLrExists) {
                    $('#voucher-payment-btn').show();
                }
            }

            // 3. Combined Payment button → if linked_lr_id same and not empty
            if (allSameLinked && firstLinkedLrId) {
                $('#combined-payment-btn').show();
            }
        }
    </script>
@endpush

<style>
    /* Modal styles */
    .lr-details-container {
        padding: 1.5rem;
    }

    .lr-details-section {
        margin-bottom: 1.5rem;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .lr-details-section h5 {
        background-color: #f1f8ff;
        padding: 0.75rem 1.25rem;
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #0d6efd;
        border-bottom: 1px solid #e9ecef;
    }

    .lr-details-content {
        padding: 1.25rem;
    }

    .lr-details-row {
        display: flex;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed #e9ecef;
    }

    .lr-details-row:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .lr-details-label {
        width: 200px;
        font-weight: 500;
        color: #6c757d;
        flex-shrink: 0;
    }

    .lr-details-value {
        flex: 1;
        color: #212529;
        word-break: break-word;
    }

    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }

    .badge.bg-warning {
        color: #000;
    }

    /* Animation effects */
    .wavy-animation {
        animation: waveMove 2s ease-in-out infinite;
        display: inline-block;
    }

    .wavy-animation:nth-child(1) {
        animation-delay: 0s;
    }

    .wavy-animation:nth-child(2) {
        animation-delay: 0.3s;
    }

    @keyframes waveMove {
        0% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-5px);
        }

        100% {
            transform: translateY(0);
        }
    }

    /* Floating buttons */
    .floating-buttons button {
        display: block;
        width: 100%;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .floating-buttons button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    /* Table links */
    .table-link {
        color: inherit !important;
        text-decoration: none !important;
        cursor: pointer;
    }

    .table-link:hover {
        text-decoration: underline !important;
    }

    /* Improved card styling */
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: linear-gradient(to right, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #e9ecef;
        padding: 15px 20px;
        border-radius: 10px 10px 0 0 !important;
    }

    .card-body {
        padding: 20px;
    }

    /* Button styling */
    .btn-brand-primary,
    .btn-success,
    .btn-primary,
    .btn-warning {
        transition: all 0.2s ease;
    }

    .btn-brand-primary:hover,
    .btn-success:hover,
    .btn-primary:hover,
    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Table improvements */
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }

</style>

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();


            // Handle bulk memo ID button
            $('#bulk-memo-id-btn').on('click', function() {
                const checkedBoxes = Object.values(selectedRows);

                if (checkedBoxes.length === 0) {
                    alert('Please select good hires (lorry hire) first.');
                    return;
                }

                // Clear previous data
                $('#bulkMemoTableBody').empty();

                checkedBoxes.forEach(function(data) {
                    const lrNumber = data.lr;
                    const lrId = data.lr_id;
                    const freightBookingId = data.freight_booking_id;

                    const row = `
                <tr>
                    <td>
                        <input type="hidden" name="lr_ids[]" value="${lrId}">
                        <input type="hidden" name="freight_booking_ids[]" value="${freightBookingId}">
                        ${lrNumber}
                    </td>
                </tr>
            `;
                    $('#bulkMemoTableBody').append(row);
                });
            });
        });
    </script>

    <style>
        .badge {
            font-size: 0.75em;
        }

        .clickable {
            cursor: pointer;
            color: #0d6efd;
            text-decoration: underline;
        }

        .clickable:hover {
            text-decoration: none;
        }

        .memo-assigned {
            background-color: #fff3cd !important;
        }

        .memo-tooltip {
            position: relative;
            display: inline-block;
            margin-left: 5px;
        }

        .memo-tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .memo-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 filter dropdowns
            $('#filterFbCustomerId').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'All Customers',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '/api/consignees/search',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: data.pagination
                        };
                    }
                }
            });

            $('#filterFbTransporterId').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'All Transporters',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '/api/transporters/search',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: data.pagination
                        };
                    }
                }
            });

            $('#filterFbTruckId').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'All Trucks',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '/api/trucks/search',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: data.pagination
                        };
                    }
                }
            });

            function syncFbListingQueryToUrl() {
                const params = new URLSearchParams();
                const lrNumber = $('#filterFbLrNumber').val();
                const customerId = $('#filterFbCustomerId').val();
                const transporterId = $('#filterFbTransporterId').val();
                const truckId = $('#filterFbTruckId').val();
                const startDate = $('#fbStartDate').val();
                const endDate = $('#fbEndDate').val();
                const search = $('#filterFbSearch').val();
                const pendingMemo = $('#pendingMemoFilter').is(':checked');

                if (lrNumber) params.set('lr_number', lrNumber);
                if (customerId) params.set('customer_id', customerId);
                if (transporterId) params.set('transporter_id', transporterId);
                if (truckId) params.set('truck_id', truckId);
                if (startDate) params.set('start_date', startDate);
                if (endDate) params.set('end_date', endDate);
                if (search) params.set('search', search);
                if (pendingMemo) params.set('pending_memo', '1');

                const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.pushState({}, '', newUrl);
            }

            // DataTable initialization (server-side; rows load via AJAX)
            var table = $('#freightBookingsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('freight_bookings.datatable') }}",
                    data: function(d) {
                        $('#searchForm').serializeArray().forEach(function(field) {
                            if (field.name === 'search') {
                                d.filter_search = field.value;
                            } else {
                                d[field.name] = field.value;
                            }
                        });
                    }
                },
                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'gr_date',
                        name: 'gr_date',
                        className: 'text-center'
                    },
                    {
                        data: 'lr_number',
                        name: 'lr_number'
                    },
                    {
                        data: 'customer',
                        name: 'customer',
                        orderable: false
                    },
                    {
                        data: 'transporter',
                        name: 'transporter',
                        orderable: false
                    },


//                     {
//     data: 'transporter',
//     name: 'transporter',
//     orderable: false,
//     render: function(data, type, row) {
//         if (type !== 'display') {
//             return data;
//         }

//         const checkboxHtml = row.checkbox || '';
//         const temp = $('<div>').html(checkboxHtml);
//         const checkbox = temp.find('.row-checkbox');

//         const shortName =
//             checkbox.attr('data-lr-transporter-short-name') || '';

//         const beneficiaryId =
//             checkbox.attr('data-lr-transporter-beneficiary-id') || '';

//         return `
//             <div>
//                 <div>${data || '-'}</div>

//                 <small class="text-muted">
//                     ${shortName || '-'}
//                 </small>

//                 <br>

//                 <span class="badge bg-success">
//                     ${beneficiaryId || 'Not Set'}
//                 </span>
//             </div>
//         `;
//     }
// },
                    {
                        data: 'truck_number',
                        name: 'truck_number',
                        orderable: false
                    },
                    {
                        data: 'route',
                        name: 'route',
                        orderable: false
                    },
                    {
                        data: 'weight',
                        name: 'weight'
                    },
                    {
                        data: 'rate',
                        name: 'rate'
                    },
                    {
                        data: 'total',
                        name: 'total'
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                        className: 'text-end'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                pageLength: 25,
                paging: true,
                info: true,
                searching: false,
                ordering: true,
                theme: 'bootstrap-5',
                responsive: false,
                scrollX: false,
                autoWidth: false,
                lengthChange: false,
                order: [
                    [1, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: [0, 3, 4, 5, 6, -1]
                }],
                dom: 't' +
                    '<"app-index-partload-pagination d-flex flex-wrap align-items-center justify-content-between gap-2"<"app-index-partload-pagination__summary"i><"app-index-partload-pagination__nav"p>>',
                language: {
                    emptyTable: `<div class="text-muted">
                                        <i class="bi bi-inbox display-4"></i>
                                        <p class="mt-2">No good hires (lorry hire) found</p>
                                        @if (request('search'))
                                            <p>Try adjusting your search criteria</p>
                                        @endif
                                    </div>`
                }
            });

            // Restore checkbox states after pagination
            table.on('draw', function() {
                $('.row-checkbox').each(function() {
                    const id = $(this).val();
                    if (selectedBookingIds.includes(id)) {
                        $(this).prop('checked', true);
                        // Update selectedRows if not already stored
                        if (!selectedRows[id]) {
                            selectedRows[id] = {
                                id: id,
                                lr: $(this).data('lr'),
                                truck: $(this).data('truck'),
                                balance: parseFloat($(this).data('balance') || 0),
                                transporterId: $(this).data('transporter-id'),
                                customerId: $(this).data('customer-id'),
                                memoId: $(this).data('memo-id'),
                                linkedLrId: $(this).data('linked-lr-id'),
                                voucher_number: $(this).data('voucher_number'),
                                freight_rate: parseFloat($(this).data('freight-rate') || 0),
                                freight_booking_id: $(this).data('freight-booking-id'),
                                lr_id: $(this).data('lr-id'),
                                transporter: $(this).data('transporter'),
                                lr_transporter: $(this).attr('data-lr-transporter') || $(this).data('lr-transporter'),
                                transporter_mobile_number: $(this).attr('data-lr-transporter-mobile-number') || $(this).data('lr-transporter-mobile-number'),
                                transporter_short_name: $(this).attr('data-lr-transporter-short-name') || $(this).data('lr-transporter-short-name'),
                                transporter_beneficiary_id: $(this).attr('data-lr-transporter-beneficiary-id') || $(this).data('lr-transporter-beneficiary-id'),
                                fb_transporter: $(this).attr('data-fb-transporter') || $(this).data('fb-transporter'),
                                tds_percentage: $(this).attr('data-tds-percentage') || $(this).data('tds-percentage'),
                                amount: $(this).data('amount'),
                                shortage_amount: $(this).data('shortage-amount'),
                                damage_amount: $(this).data('damage-amount'),
                                advance_amount: $(this).attr('data-advance-amount') || $(this).data('advance-amount')
                            };
                        }
                    } else {
                        $(this).prop('checked', false);
                    }
                });

                updateSelectAllState();
                updatePaymentButtonsVisibility();
            });

            // Apply column search
            $('#filterrow th input.column-search').on('keyup change', function() {
                const index = $(this).closest('th').index();
                table.column(index).search(this.value).draw();
            });

            // Select all checkbox handler
            $('#select-all').on('change', function() {
                const checked = this.checked;
                $('.row-checkbox').prop('checked', checked);
                updatePaymentButtonsVisibility();
            });

            // Individual checkbox handler
            $(document).on('change', '.row-checkbox', function() {
                const allChecked = Object.values(selectedRows).length === $('.row-checkbox').length;
                $('#select-all').prop('checked', allChecked);
                updatePaymentButtonsVisibility();
            });

            // Delete confirmation
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this good hire (lorry hire)?')) {
                    $(this).closest('form').submit();
                }
            });

            // Voucher payment button click handler
            $('#voucher-payment-btn').on('click', function(e) {
                e.preventDefault();
                handleVoucherPayment();
            });

            $(document).on('change', '#paymentMode', function() {
                togglePumpName();
            });

            // Bulk payment button click handler
            $('#bulk-payment-btn').on('click', function(e) {
                e.preventDefault();
                handleBulkPayment();
            });

            // Filter form submission and changes
            $('#searchForm').on('submit', function(e) {
                e.preventDefault();
                syncFbListingQueryToUrl();
                table.ajax.reload();
            });

            $('#pendingMemoFilter').on('change', function() {
                $(this).closest('.app-index-filters__chip').toggleClass('is-active', this.checked);
                syncFbListingQueryToUrl();
                table.ajax.reload();
            });

            $('#filterFbCustomerId, #filterFbTransporterId, #filterFbTruckId').on('change', function() {
                syncFbListingQueryToUrl();
                table.ajax.reload();
            });
        });
    </script>
    <script>
        // Global variables for plan expiry check
        const planEndDateStr = @json(Auth::user()->company?->subscription?->ends_on?->toDateString() ?? null);
        const today = new Date();

        function validatePaymentAmount(input) {
            const value = parseFloat(input.value) || 0;
            const min = parseFloat(input.min) || 0.01;
            const max = parseFloat(input.max) || 0;

            const existingFeedback = input.nextElementSibling;
            if (existingFeedback && existingFeedback.classList.contains('invalid-feedback')) {
                existingFeedback.remove();
            }

            input.classList.remove('is-invalid', 'is-valid');

            if (value <= 0) {
                input.classList.add('is-invalid');
                const errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.textContent = 'Amount must be greater than zero';
                input.parentNode.insertBefore(errorMsg, input.nextSibling);
                return false;
            } else if (value > max) {
                input.classList.add('is-invalid');
                const errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.textContent = `Amount cannot exceed balance (${max.toFixed(2)})`;
                input.parentNode.insertBefore(errorMsg, input.nextSibling);
                return false;
            } else {
                input.classList.add('is-valid');
                return true;
            }
        }

        function togglePumpName() {
            const paymentMode = document.getElementById('paymentMode');
            const pumpNameContainer = document.getElementById('pumpNameContainer');
            const pumpName = document.getElementById('pumpName');

            if (paymentMode && pumpNameContainer) {
                const modeText = (paymentMode.options[paymentMode.selectedIndex]?.text || paymentMode.value || '').toLowerCase();
                const isFuel = modeText.includes('fuel');
                pumpNameContainer.style.display = isFuel ? 'block' : 'none';
                if (pumpName) {
                    pumpName.required = isFuel;
                    if (!isFuel) pumpName.value = '';
                }
            }
        }

        function loadPaymentHistory(freightBookingIds) {
            const tableContainer = document.getElementById('previousPaymentsTable');
            if (!tableContainer) return;

            tableContainer.innerHTML = `
                <div class="fb-pay-loading">
                    <div class="spinner-border spinner-border-sm me-1" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Loading payment history...
                </div>
            `;

            fetch('{{ route('freight_bookings.payment_history') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ freight_booking_ids: freightBookingIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.payments && data.payments.length > 0) {
                    let tableHtml = `
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 fb-pay-table">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>FR No.</th>
                                        <th>Voucher Number</th>
                                        <th>Amount</th>
                                        <th>Payment Type</th>
                                        <th>Payment Mode</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    data.payments.forEach(payment => {
                        const statusLower = (payment.payment_status || '').toLowerCase();
                        const statusBadge = statusLower === 'paid' ? 'bg-success' :
                            statusLower === 'pending' ? 'bg-warning text-dark' :
                            statusLower === 'in progress' ? 'bg-info' : 'bg-secondary';
                        const pumpName = payment.pump ? (payment.pump.pump_name || '') : '';
                        const paymentModeDisplay = pumpName ?
                            `${payment.payment_mode || 'N/A'}<div class="fb-info-grid__sub">${pumpName}</div>` :
                            (payment.payment_mode || 'N/A');

                        tableHtml += `
                            <tr>
                                <td>#${payment.id}</td>
                                <td>#${payment.freight_booking_id}</td>
                                <td>${payment.voucher_number || 'N/A'}</td>
                                <td>₹${parseFloat(payment.amount || 0).toFixed(2)}</td>
                                <td>${payment.payment_type || 'N/A'}</td>
                                <td>${paymentModeDisplay}</td>
                                <td>${new Date(payment.date).toLocaleDateString('en-IN')}</td>
                                <td><span class="badge ${statusBadge}">${payment.payment_status || 'N/A'}</span></td>
                            </tr>
                        `;
                    });

                    tableHtml += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    tableContainer.innerHTML = tableHtml;
                } else {
                    tableContainer.innerHTML = '<div class="fb-pay-empty">No previous payments found.</div>';
                }
            })
            .catch(error => {
                console.error('Error loading payment history:', error);
                tableContainer.innerHTML = '<div class="fb-pay-empty text-danger">Error loading payment history.</div>';
            });
        }

        function handleVoucherPayment() {
            const checkedBoxes = Object.values(selectedRows);

            if (checkedBoxes.length === 0) {
                alert("Please select at least one Good Hire (Lorry Hire).");
                return;
            }
            if (planEndDateStr) {
                const planEndDate = new Date(planEndDateStr);
                if (today > planEndDate) {
                    alert(
                        'Your subscription plan has expired. Please renew your subscription to continue using the service.'
                    );
                    return false;
                }
            }

            // Check if any LR has no voucher number
            const hasNoVoucher = checkedBoxes.some(data => data.voucher_number === '0' || !data.voucher_number);
            if (hasNoVoucher) {
                alert("Please create Good Hire (Lorry Hire) first for selected Good Receipts (Bilty).");
                return;
            }

            // Check for LRs with zero balance and collect their numbers
            const zeroBalanceLRs = checkedBoxes.filter(data => {
                return data.balance <= 0;
            }).map(data => data.lr);

            if (zeroBalanceLRs.length > 0) {
                const message =
                    `The following Good Receipts (Bilty) have zero balance. No payment is required:\n${zeroBalanceLRs.join(', ')}`;
                alert(message);
                return;
            }

            // Check if all selected bookings have zero balance
            const allZeroBalance = checkedBoxes.every(data => {
                return data.balance <= 0;
            });

            if (allZeroBalance) {
                alert("All selected good hires (lorry hire) have zero balance. No payment is required.");
                return;
            }

            // Collect truck numbers
            const truckNumbers = checkedBoxes.map(data => data.truck);
            const firstTruck = truckNumbers[0];
            const allSameTruck = truckNumbers.every(truck => truck === firstTruck);

            if (!allSameTruck) {
                alert(
                    "Selected Good Hires (Lorry Hire) belong to different Truck Numbers. Please select bookings with the same truck."
                );
                return;
            }

            // Verify same transporter
            const transporterNames = checkedBoxes.map(data => data.lr_transporter || data.fb_transporter || data.transporter);
            const firstTransporter = transporterNames[0];
            const allSameTransporter = transporterNames.every(t => t === firstTransporter);
            if (!allSameTransporter) {
                alert("You can only create a voucher for bookings from the same transporter.");
                return;
            }

            // Set modal title
            const modalTitle = document.getElementById('voucherPaymentModalLabel');
            if (modalTitle) {
                const firstFbId = checkedBoxes[0].freight_booking_id || checkedBoxes[0].id;
                if (checkedBoxes.length === 1) {
                    modalTitle.innerHTML = `<i class="bi bi-wallet2"></i> Voucher Payment — FR #${firstFbId}`;
                } else {
                    modalTitle.innerHTML = `<i class="bi bi-wallet2"></i> Voucher Payment — ${checkedBoxes.length} Bookings`;
                }
            }

            // Populate Transporter Details facts
            const firstData = checkedBoxes[0];
            const firstTransporterName = firstData.lr_transporter || firstData.fb_transporter || firstData.transporter;
            const firstTransporterMobileNumber = firstData.transporter_mobile_number;
            const firstTransporterShortName = firstData.transporter_short_name;
            const firstTransporterBeneficiaryId = firstData.transporter_beneficiary_id;
            const firstTdsPercentage = firstData.tds_percentage;
            const firstAdvanceAmount = parseFloat(firstData.advance_amount) || 0;

            const elTransporter = document.getElementById('lrTransporter');
            if (elTransporter) elTransporter.textContent = firstTransporterName || 'N/A';

            const elMobile = document.getElementById('transporterMobile');
            if (elMobile) elMobile.textContent = firstTransporterMobileNumber || 'N/A';

            const elShortName = document.getElementById('transporterShortName');
            if (elShortName) elShortName.textContent = firstTransporterShortName || 'N/A';

            const elBeneficiary = document.getElementById('transporterBeneficiaryId');
            if (elBeneficiary) elBeneficiary.textContent = firstTransporterBeneficiaryId || 'N/A';

            const elTds = document.getElementById('tdsPercentage');
            if (elTds) {
                if (firstTdsPercentage !== null && firstTdsPercentage !== '' && !isNaN(parseFloat(firstTdsPercentage))) {
                    elTds.textContent = parseFloat(firstTdsPercentage).toFixed(2) + '%';
                } else {
                    elTds.textContent = 'N/A';
                }
            }

            const elTruck = document.getElementById('truckNumber');
            if (elTruck) elTruck.textContent = firstTruck || 'N/A';

            const elAdvanceFact = document.getElementById('advanceAmountFact');
            const elAdvance = document.getElementById('advanceAmount');
            if (elAdvanceFact && elAdvance) {
                if (firstAdvanceAmount > 0) {
                    elAdvance.textContent = firstAdvanceAmount.toFixed(2);
                    elAdvanceFact.style.display = '';
                } else {
                    elAdvanceFact.style.display = 'none';
                }
            }

            // Populate Payment Allocation Table
            const tbody = document.getElementById('voucherPaymentTableBody');
            if (tbody) {
                tbody.innerHTML = '';
                checkedBoxes.forEach(data => {
                    const fbId = data.freight_booking_id || data.id;
                    const balance = parseFloat(data.balance) || 0;
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>#${fbId}</td>
                        <td>${data.lr || 'N/A'}</td>
                        <td>₹${balance.toFixed(2)}</td>
                        <td style="max-width: 10rem;">
                            <input type="number" class="form-control allocated-input"
                                data-fb-id="${fbId}"
                                data-lr="${data.lr || ''}"
                                step="0.01" min="0.01"
                                max="${balance.toFixed(2)}"
                                value="${balance.toFixed(2)}"
                                oninput="validatePaymentAmount(this); updateTotalPayment();"
                                onchange="updateTotalPayment()">
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }

            // Set today date if empty
            const paymentDate = document.getElementById('paymentDate');
            if (paymentDate && !paymentDate.value) {
                paymentDate.value = new Date().toISOString().slice(0, 10);
            }

            // Clear remarks
            const remarks = document.getElementById('paymentRemarks');
            if (remarks) remarks.value = '';

            // Handle pump toggle
            togglePumpName();

            // Load payment history
            const bookingIds = checkedBoxes.map(data => parseInt(data.freight_booking_id || data.id)).filter(id => !isNaN(id));
            loadPaymentHistory(bookingIds);

            // Show modal
            updatePaymentButtonsVisibility();
            const modalEl = document.getElementById('voucherPaymentModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            updatePaymentTypeFromAmounts();
            updateTotalPayment();
        }

        // Individual voucher payment handler
        function handleIndividualVoucherPayment(button) {
            const freightBookingId = button.data('id');
            const lrNumber = button.data('lr');
            const voucherNumber = button.data('voucher_number');
            const balance = parseFloat(button.data('balance'));
            const truckNumber = button.data('truck');

            const modalBody = document.getElementById('individualPaymentBody');
            modalBody.innerHTML = '';

            // Show freight booking details
            modalBody.innerHTML = `
                <div class="lr-details-section">
                    <h5>Good Hire (Lorry Hire) Details</h5>
                    <div class="lr-details-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span><strong>LR Number:</strong></span>
                                    <span>${lrNumber}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><strong>Voucher Number:</strong></span>
                                    <span>${voucherNumber}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span><strong>Truck Number:</strong></span>
                                    <span>${truckNumber}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><strong>Balance Amount:</strong></span>
                                    <span class="text-success"><strong>${balance.toFixed(2)}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lr-details-section mt-3">
                    <h5>Payment Details</h5>
                    <div class="lr-details-content">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="individual_payment_type" class="form-label">Payment Type *</label>
                                <select name="payment_type" id="individual_payment_type" class="form-select" required>
                                    <option value="">Select Payment Type</option>
                                    @foreach ($paymentTypes as $paymentType)
                                        <option value="{{ $paymentType->name }}">
                                            {{ $paymentType->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select a payment type.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="individual_payment_mode" class="form-label">Payment Mode *</label>
                                <select name="payment_mode" id="individual_payment_mode" class="form-select" required>
                                    <option value="">Select Payment Mode</option>
                                    @foreach ($paymentModes as $paymentMode)
                                        <option value="{{ $paymentMode->name }}">
                                            {{ $paymentMode->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select a payment mode.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="individual_reference_transaction_id" class="form-label">Reference Transaction ID</label>
                                <input type="text" class="form-control" name="reference_transaction_id" id="individual_reference_transaction_id">
                            </div>
                            <div class="col-md-6">
                                <label for="individual_remark" class="form-label">Remark</label>
                                <input type="text" class="form-control" name="remark" id="individual_remark">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="individualTotalAmountInput" class="form-label">Payment Amount *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" class="form-control" name="total_amount" id="individualTotalAmountInput" min="0.01" max="${balance}" required
                                        oninput="updatePaymentTypeFromAmounts({ selectId: 'individual_payment_type', amountInputId: 'individualTotalAmountInput' })"
                                        onchange="updatePaymentTypeFromAmounts({ selectId: 'individual_payment_type', amountInputId: 'individualTotalAmountInput' })">
                                </div>
                                <div class="invalid-feedback">Please enter a valid payment amount.</div>
                                <small class="text-muted">Amount must be greater than zero and not exceed ${balance.toFixed(2)}</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Store freight booking ID for submission
            $('#individualPaymentModal').data('freight-booking-id', freightBookingId);

            // Show the modal
            $('#individualPaymentModal').modal('show');
        }

        // Handle individual payment submission
        $('#submitIndividualPayment').on('click', function() {
            const freightBookingId = $('#individualPaymentModal').data('freight-booking-id');
            const submitBtn = this;
            const originalBtnText = submitBtn.innerHTML;

            // Validate form
            const paymentType = document.getElementById('individual_payment_type').value;
            const paymentMode = document.getElementById('individual_payment_mode').value;
            const totalAmountInput = document.getElementById('individualTotalAmountInput');
            const totalAmount = totalAmountInput.value;
            const maxAmount = parseFloat(totalAmountInput.getAttribute('max')) || 0;
            const amountValue = parseFloat(totalAmount) || 0;

            if (!paymentType || !paymentMode) {
                alert('Please fill in all required fields.');
                return;
            }

            // Validate amount is greater than zero
            if (amountValue <= 0) {
                alert('Payment amount must be greater than zero.');
                totalAmountInput.focus();
                return;
            }

            // Validate amount doesn't exceed balance
            if (amountValue > maxAmount) {
                alert(
                    `Payment amount (${amountValue.toFixed(2)}) cannot exceed the balance amount (${maxAmount.toFixed(2)}).`
                );
                totalAmountInput.focus();
                return;
            }

            // Show loading state
            submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Processing...';
            submitBtn.disabled = true;

            // Prepare payment data
            const paymentData = {
                freight_booking_ids: [freightBookingId],
                payment_type: paymentType,
                payment_mode: paymentMode,
                total_amount: parseFloat(totalAmount),
                reference_transaction_id: document.getElementById('individual_reference_transaction_id').value,
                remark: document.getElementById('individual_remark').value,
                allocated_amounts: {
                    [freightBookingId]: parseFloat(totalAmount)
                },
                _token: '{{ csrf_token() }}'
            };

            // Submit payment
            fetch('{{ route('voucher_payments.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(paymentData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        $('#individualPaymentModal').modal('hide');
                        let successText = data.message || 'Payment processed successfully!';
                        if (data.adjust_in_advance && data.remaining_advance_balance !== undefined) {
                            const name = data.transporter_name || 'Transporter';
                            const bal = parseFloat(data.remaining_advance_balance).toFixed(2);
                            successText += '\n\nRemaining advance balance (' + name + '):  ' + bal;
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: successText,
                            confirmButtonColor: '#28a745'
                        }).then(() => {
                            $('#freightBookingsTable').DataTable().ajax.reload();
                        });
                    } else {
                        // Show error message
                        alert(data.message || 'Error processing payment. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing the payment. Please try again.');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                });
        });

        // Function to fetch payment history for selected freight bookings
        function fetchPaymentHistory(freightBookingIds) {
            return fetch('{{ route('freight_bookings.payment_history') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        freight_booking_ids: freightBookingIds
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        return data.payments;
                    } else {
                        throw new Error(data.message || 'Failed to fetch payment history');
                    }
                });
        }

        // Auto-select payment type: final when full balance is paid, otherwise advance.
        function updatePaymentTypeFromAmounts(options = {}) {
            const selectId = options.selectId || 'paymentType';
            const paymentTypeSelect = document.getElementById(selectId);

            if (!paymentTypeSelect) {
                return;
            }

            let inputs = [];
            if (options.amountInputId) {
                const amountInput = document.getElementById(options.amountInputId);
                if (amountInput) {
                    inputs = [amountInput];
                }
            } else {
                const inputSelector = options.inputSelector || '.allocated-input';
                inputs = Array.from(document.querySelectorAll(inputSelector));
            }

            if (inputs.length === 0) {
                return;
            }

            let allFullAmount = true;
            inputs.forEach((input) => {
                const value = parseFloat(input.value) || 0;
                const maxValue = parseFloat(input.getAttribute('max')) || 0;

                if (value <= 0 || Math.abs(value - maxValue) > 0.01) {
                    allFullAmount = false;
                }
            });

            const targetType = allFullAmount ? 'final' : 'advance';
            const match = Array.from(paymentTypeSelect.options).find(
                (option) => option.textContent.trim().toLowerCase() === targetType
            );

            if (match) {
                paymentTypeSelect.value = match.value;
            }
        }

        // Function to update total payment amount
        function updateTotalPayment() {
            const allocatedInputs = document.querySelectorAll('#voucherPaymentModal .allocated-input');
            let total = 0;
            let hasError = false;

            allocatedInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                const maxValue = parseFloat(input.getAttribute('max')) || 0;

                if (value <= 0 || value > maxValue) {
                    hasError = true;
                }
                total += value;
            });

            const totalElement = document.getElementById('totalPaymentAmount');
            if (totalElement) {
                totalElement.textContent = total.toFixed(2);
            }

            // Disable/enable submit button based on validation
            const submitBtn = document.querySelector('#voucherPaymentModal .btn-success');
            if (submitBtn) {
                submitBtn.disabled = hasError || total <= 0;
            }

            updatePaymentTypeFromAmounts();

            return !hasError;
        }

        // Function to submit voucher payment
        function submitVoucherPayment() {
            // Get form values
            const paymentTypeSelect = document.getElementById('paymentType');
            const paymentModeSelect = document.getElementById('paymentMode');
            const pumpId = document.getElementById('pumpName')?.value || null;
            const referenceId = document.getElementById('referenceId')?.value;
            const paymentDate = document.getElementById('paymentDate')?.value;
            const remarks = document.getElementById('paymentRemarks')?.value;

            const selectedTypeOption = paymentTypeSelect?.options[paymentTypeSelect.selectedIndex];
            const selectedModeOption = paymentModeSelect?.options[paymentModeSelect.selectedIndex];

            const paymentTypeId = selectedTypeOption?.getAttribute('data-id') || selectedTypeOption?.value || '';
            const paymentModeId = selectedModeOption?.getAttribute('data-id') || selectedModeOption?.value || '';
            const paymentType = selectedTypeOption?.text?.trim() || '';
            const paymentMode = selectedModeOption?.text?.trim() || '';

            // Validate required fields
            if (!paymentTypeId || !paymentType) {
                alert('Please select a payment type');
                paymentTypeSelect?.focus();
                return;
            }

            if (!paymentModeId || !paymentMode) {
                alert('Please select a payment mode');
                paymentModeSelect?.focus();
                return;
            }

            // Get allocated amounts from modal inputs
            const allocatedInputs = document.querySelectorAll('#voucherPaymentModal .allocated-input');
            let hasValidationError = false;
            const selectedIds = [];
            const paymentAmounts = [];

            allocatedInputs.forEach(input => {
                const amount = parseFloat(input.value) || 0;
                const maxAmount = parseFloat(input.getAttribute('max')) || 0;
                const minAmount = parseFloat(input.getAttribute('min')) || 0;
                const lrNumber = input.getAttribute('data-lr') || 'Unknown';
                const fbId = input.getAttribute('data-fb-id');

                if (amount < minAmount || amount > maxAmount) {
                    alert(
                        `Error: Payment amount for LR ${lrNumber} (${amount.toFixed(2)}) must be between ${minAmount.toFixed(2)} and ${maxAmount.toFixed(2)}.`
                    );
                    input.focus();
                    hasValidationError = true;
                    return;
                }

                selectedIds.push(fbId);
                paymentAmounts.push(amount);
            });

            if (hasValidationError) {
                return;
            }

            // Calculate total amount
            const totalAmount = paymentAmounts.reduce((sum, amount) => sum + amount, 0);

            if (totalAmount <= 0) {
                alert('Please enter valid payment amounts');
                return;
            }

            if (!confirm(
                    "Please review all payment details carefully before proceeding.\nOnce the voucher status is marked as PAID, it cannot be edited or changed.\nEnsure the information is correct before saving."
                )) {
                return;
            }

            // Create form data
            const formData = new FormData();
            formData.append('pump_id', pumpId || '');
            // Append payment type and mode IDs for backend processing
            formData.append('payment_type_id', paymentTypeId);
            formData.append('payment_mode_id', paymentModeId);
            // Also append text values for display if needed
            formData.append('payment_type', paymentType);
            formData.append('payment_mode', paymentMode);

            formData.append('total_amount', totalAmount.toFixed(2));
            formData.append('date', paymentDate);

            if (referenceId) formData.append('reference_number', referenceId);
            if (remarks) formData.append('notes', remarks);

            // Add booking IDs and payment amounts
            selectedIds.forEach((id, index) => {
                formData.append('booking_ids[]', id);
                formData.append('payment_amounts[]', paymentAmounts[index] || 0);
            });

            // Add CSRF token
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            // Show loading state
            const submitBtn = document.querySelector('#voucherPaymentModal .btn-success');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Processing...';
                submitBtn.disabled = true;
            }

            // Submit the form via AJAX
            fetch('{{ route('freight_bookings.voucher_payment') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Hide modal
                    if (data.success) {
                        let successText = data.message || 'Payment processed successfully!';
                        if (data.adjust_in_advance && data.remaining_advance_balance !== undefined) {
                            const name = data.transporter_name || 'Transporter';
                            const bal = parseFloat(data.remaining_advance_balance).toFixed(2);
                            successText += '\n\nRemaining advance balance (' + name + '):  ' + bal;
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: successText,
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                const modalEl = document.getElementById('voucherPaymentModal');
                                if (modalEl) {
                                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                                }
                                location.reload();
                            });
                        } else {
                            alert(successText);
                            const modalEl = document.getElementById('voucherPaymentModal');
                            if (modalEl) {
                                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                            }
                            location.reload();
                        }
                    } else {
                        // Check if this is a duplicate warning
                        if (data.warning && data.duplicate_details) {
                            const duplicate = data.duplicate_details;
                            const confirmMessage = `⚠️ DUPLICATE PAYMENT WARNING ⚠️\n\n` +
                                `A similar payment already exists for this booking:\n\n` +
                                `Date: ${duplicate.date}\n` +
                                `Amount: ${duplicate.amount}\n` +
                                `Payment Type: ${duplicate.payment_type}\n` +
                                `Payment Mode: ${duplicate.payment_mode}\n` +
                                `Created: ${duplicate.created_at}\n\n` +
                                `Do you want to proceed with creating this duplicate payment?`;

                            if (confirm(confirmMessage)) {
                                // Add confirm_duplicate flag and resubmit
                                formData.append('confirm_duplicate', '1');

                                // Resubmit the form
                                fetch('{{ route('freight_bookings.voucher_payment') }}', {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        let successText = data.message || 'Payment processed successfully!';
                                        if (data.adjust_in_advance && data.remaining_advance_balance !== undefined) {
                                            const name = data.transporter_name || 'Transporter';
                                            const bal = parseFloat(data.remaining_advance_balance).toFixed(2);
                                            successText += '\n\nRemaining advance balance (' + name + '):  ' + bal;
                                        }
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Success!',
                                                text: successText,
                                                confirmButtonColor: '#28a745'
                                            }).then(() => {
                                                const modalEl = document.getElementById('voucherPaymentModal');
                                                if (modalEl) {
                                                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                                                }
                                                location.reload();
                                            });
                                        } else {
                                            alert(successText);
                                            const modalEl = document.getElementById('voucherPaymentModal');
                                            if (modalEl) {
                                                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                                            }
                                            location.reload();
                                        }
                                    } else {
                                        alert(data.message || 'Error processing payment. Please try again.');
                                    }
                                })
                                .catch(error => {
                                    console.error('Payment error:', error);
                                    alert('An error occurred while processing the payment. Please try again.');
                                })
                                .finally(() => {
                                    if (submitBtn) {
                                        submitBtn.innerHTML = originalBtnText;
                                        submitBtn.disabled = false;
                                    }
                                });
                                return; // Exit early
                            } else {
                                // User cancelled, restore button
                                if (submitBtn) {
                                    submitBtn.innerHTML = originalBtnText;
                                    submitBtn.disabled = false;
                                }
                                return;
                            }
                        }
                        // Regular error
                        alert(data.message || 'Error processing payment. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Payment error:', error);
                    alert('An error occurred while processing the payment. Please try again.');
                })
                .finally(() => {
                    // Restore button state
                    if (submitBtn) {
                        submitBtn.innerHTML = originalBtnText;
                        submitBtn.disabled = false;
                    }
                });
        }

        $('#voucherPaymentModal').on('shown.bs.modal', function() {
            togglePumpName();
            updatePaymentTypeFromAmounts();
            updateTotalPayment();
        });
    </script>
    <script>
        document.addEventListener("change", function(e) {
            if (e.target && e.target.id === "paymentMode") {
                const paymentModeDropdown = e.target;
                const selectedText = paymentModeDropdown.options[paymentModeDropdown.selectedIndex].text
                    .toLowerCase();
                const pumpNameContainer = document.getElementById("pumpNameContainer");

                if (selectedText.includes("fuel")) {
                    pumpNameContainer.style.display = "block";
                } else {
                    pumpNameContainer.style.display = "none";
                    document.getElementById("pumpName").value = ""; // reset
                }
            }
        });
    </script>
    <script>
        (function() {
            let pendingPrintButton = null;
            const confirmModalElement = document.getElementById('voucherPrintConfirmModal');
            const confirmYesBtn = document.getElementById('voucherPrintConfirmYesBtn');

            document.addEventListener('click', function(event) {
                const printButton = event.target.closest('.voucher-print-btn');
                if (!printButton) {
                    return;
                }

                event.preventDefault();
                const printUrl = printButton.getAttribute('href');
                window.open(printUrl, '_blank');

                pendingPrintButton = printButton;
                if (confirmModalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(confirmModalElement).show();
                }
            });

            if (confirmModalElement) {
                confirmModalElement.addEventListener('shown.bs.modal', function() {
                    if (confirmYesBtn) {
                        confirmYesBtn.focus();
                    }
                });

                confirmModalElement.addEventListener('hidden.bs.modal', function() {
                    pendingPrintButton = null;
                    if (confirmYesBtn) {
                        confirmYesBtn.disabled = false;
                    }
                });
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' && confirmModalElement && confirmModalElement.classList.contains('show')) {
                    event.preventDefault();
                    if (confirmYesBtn && !confirmYesBtn.disabled) {
                        confirmYesBtn.click();
                    }
                }
            });

            if (confirmYesBtn) {
                confirmYesBtn.addEventListener('click', function() {
                    if (confirmYesBtn.disabled) {
                        return;
                    }
                    confirmYesBtn.disabled = true;

                    const printButton = pendingPrintButton;
                    pendingPrintButton = null;

                    if (confirmModalElement && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getOrCreateInstance(confirmModalElement).hide();
                    }

                    if (!printButton) {
                        confirmYesBtn.disabled = false;
                        return;
                    }

                    fetch(printButton.dataset.markUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                freight_booking_id: printButton.dataset.freightBookingId
                            }),
                        })
                        .then(async (response) => {
                            const data = await response.json();
                            if (!response.ok) {
                                throw new Error(data.message || 'Failed to update print status.');
                            }
                            return data;
                        })
                        .then((data) => {
                            printButton.classList.add('is-printed');
                            printButton.setAttribute('title',
                                `Last printed: ${data.voucher_last_printed_at}`);
                        })
                        .catch((error) => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Update Failed',
                                text: error.message || 'Could not update print status.',
                            });
                        })
                        .finally(() => {
                            confirmYesBtn.disabled = false;
                        });
                });
            }
        })();
    </script>
@endpush
