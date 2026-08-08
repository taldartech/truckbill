@extends('layouts.app')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="{{ asset('css/fb-show-redesigned.css') }}">
    <style>
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
            padding: 3px 3px !important;
            font-size: 12.5px !important;
            line-height: 1.25 !important;
            vertical-align: middle;
            border-color: rgba(0,0,0,0.05) !important;
        }

        .app-index-table > :not(caption) > * > * {
            padding: 3px 3px !important;
        }

        .lorry-receipts-index .table-responsive {
            max-height: none !important;
            overflow-x: visible !important;
            overflow-y: visible !important;
        }

        #lorryReceiptsTable {
            width: 100% !important;
            min-width: 0 !important;
            table-layout: fixed;
            border-collapse: collapse;
            border-spacing: 0;
        }

        #lorryReceiptsTable th,
        #lorryReceiptsTable td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 3px !important;
            padding-right: 3px !important;
        }

        #lorryReceiptsTable thead th {
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

        /* Compact column widths so the row fits without horizontal scroll */
        #lorryReceiptsTable th:nth-child(1),
        #lorryReceiptsTable td:nth-child(1) { width: 6.5%; }  /* GR Date */
        #lorryReceiptsTable th:nth-child(2),
        #lorryReceiptsTable td:nth-child(2) { width: 9.5%; }  /* LR Details */
        #lorryReceiptsTable th:nth-child(3),
        #lorryReceiptsTable td:nth-child(3) { width: 12%; }   /* Customer */
        #lorryReceiptsTable th:nth-child(4),
        #lorryReceiptsTable td:nth-child(4) { width: 11%; }   /* Consignor */
        #lorryReceiptsTable th:nth-child(5),
        #lorryReceiptsTable td:nth-child(5) { width: 11%; }   /* Consignee */
        #lorryReceiptsTable th:nth-child(6),
        #lorryReceiptsTable td:nth-child(6) { width: 7.5%; }  /* Status */
        #lorryReceiptsTable th:nth-child(7),
        #lorryReceiptsTable td:nth-child(7) { width: 9%; }    /* Truck */
        #lorryReceiptsTable th:nth-child(8),
        #lorryReceiptsTable td:nth-child(8) { width: 6%; }    /* Weight */
        #lorryReceiptsTable th:nth-child(9),
        #lorryReceiptsTable td:nth-child(9) { width: 6%; }    /* Rate */
        #lorryReceiptsTable th:nth-child(10),
        #lorryReceiptsTable td:nth-child(10) { width: 8%; }   /* Amount */
        #lorryReceiptsTable th:nth-child(11),
        #lorryReceiptsTable td:nth-child(11) { width: 13.5%; } /* Action */

        #lorryReceiptsTable td:nth-child(11),
        #lorryReceiptsTable th:nth-child(11) {
            overflow: visible;
            text-overflow: clip;
            padding-left: 2px !important;
            padding-right: 2px !important;
        }

        @media (max-width: 991.98px) {
            .lorry-receipts-index .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            .lorry-receipts-index .dataTables_wrapper,
            .lorry-receipts-index .dataTables_scroll,
            .lorry-receipts-index .dataTables_scrollHead,
            .lorry-receipts-index .dataTables_scrollBody {
                overflow-x: auto !important;
            }

            #lorryReceiptsTable {
                table-layout: auto !important;
                min-width: 920px !important;
            }

            #lorryReceiptsTable th:nth-child(n),
            #lorryReceiptsTable td:nth-child(n) {
                width: auto !important;
            }

            #lorryReceiptsTable th:nth-child(11),
            #lorryReceiptsTable td:nth-child(11) {
                min-width: 118px;
            }
        }

        @media (max-width: 575.98px) {
            .lorry-receipts-index .status-pill {
                font-size: 9px !important;
            }

            .app-index-list-card > .card-header .d-flex.gap-2 {
                width: 100%;
                flex-wrap: wrap;
            }

            .app-index-list-card > .card-header .app-index-header-btn {
                flex: 1 1 auto;
            }
        }

        .lorry-receipts-index .city-label {
            font-size: 11px;
        }

        .lorry-receipts-index .status-pill {
            font-size: 9.5px !important;
            padding: 1px 5px !important;
        }

        .lorry-receipts-index .lr-number-link {
            font-size: 12.5px;
        }

        .lorry-receipts-index .dataTables_wrapper,
        .lorry-receipts-index .dataTables_scroll,
        .lorry-receipts-index .dataTables_scrollHead,
        .lorry-receipts-index .dataTables_scrollBody {
            overflow-x: hidden !important;
        }

        .lorry-receipts-index .dataTables_scrollBody {
            overflow-y: visible !important;
        }

        /* Status Dots */
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 1px 6px !important;
            border-radius: 50px !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            font-size: 8.5px !important;
            letter-spacing: 0.04em;
        }

        .status-delivered { background: var(--brand-active-bg); color: var(--brand-primary-dark); }
        .status-delivered .status-dot { background: var(--brand-success); }
        .status-pending { background: #FFF7ED; color: #9A3412; } /* Derived from Amber */
        .status-pending .status-dot { background: var(--brand-warning); }

        .lr-number-link {
            color: var(--brand-primary) !important;
            font-weight: 700;
            text-decoration: none !important;
        }

        .lr-number-link:hover {
            color: var(--brand-primary-dark) !important;
            text-decoration: underline !important;
        }

        .route-visual {
            color: #94a3b8;
            font-size: 9px;
            margin: 0 3px;
        }

        .city-label {
            font-size: 10px;
            color: var(--slate-600);
            font-weight: 500;
        }

        .filter-action-btn {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .input-group-custom {
            background: white;
            border-radius: 8px;
            padding: 1px 6px;
            border: 1px solid rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        .input-group-custom span {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--slate-600);
            margin-right: 6px;
        }

        .input-group-custom input {
            border: none !important;
            font-size: 11px !important;
            padding: 1px 0 !important;
            background: transparent !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .lorry-receipts-index .app-index-list-card {
            margin-bottom: 0.75rem !important;
        }

        .lorry-receipts-index .app-index-list-card > .card-header {
            padding: 0.4rem 0.75rem !important;
            min-height: 0;
        }

        .lorry-receipts-index .app-index-dt-toolbar {
            padding: 0.4rem 0.75rem !important;
        }

        .lorry-receipts-index .app-index-partload-pagination {
            padding: 0.4rem 0.75rem !important;
        }

    </style>
@endpush

@section('content')
    <div class="container-fluid mt-2 pt-1 app-index-page app-index-page--standard lorry-receipts-index">
        @if (session('success'))
            <div class="mb-2">
                <span class="badge text-bg-dark border app-index-flash">
                    <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
                </span>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-2">
                <span class="badge text-bg-dark border app-index-flash">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i> {{ session('error') }}
                </span>
            </div>
        @endif

        <div class="card app-index-list-card mb-2">
            <div class="card-header">
                <h3 class="card-title mb-0">Good Receipts (Bilty)</h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('lorry_receipts.create') }}" class="btn app-index-header-btn" aria-label="Create good receipt">
                        <i class="bi bi-plus-circle"></i> Create
                    </a>
                    <a href="{{ route('lorry_receipts.createLinked') }}" class="btn app-index-header-btn" aria-label="Create linked good receipt">
                        <i class="bi bi-link-45deg"></i> Linked
                    </a>
                </div>
            </div>

            <div class="app-index-dt-toolbar d-flex flex-wrap justify-content-end align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="input-group-custom">
                        <span>From</span>
                        <input type="date" id="fromDate">
                    </div>
                    <div class="input-group-custom">
                        <span>To</span>
                        <input type="date" id="toDate">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="filter-action-btn" id="filterBtn" style="background: var(--brand-primary); color: white;" title="Filter" data-bs-toggle="tooltip">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <button type="button" class="filter-action-btn" id="resetDateFilter" style="background: var(--brand-text-primary); color: white;" title="Reset" data-bs-toggle="tooltip">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="lorryReceiptsTable" class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                        <thead class="table-light">
                            <tr>
                                <th>GR Date</th>
                                <th>LR Details</th>
                                <th>Customer</th>
                                <th>Consignor</th>
                                <th>Consignee</th>
                                <th>Status</th>
                                <th>Truck</th>
                                <th>Weight</th>
                                <th>Rate</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- LR Copy Modal -->
    <div class="modal fade fb-pay-modal" id="lrCopyModal" tabindex="-1" aria-labelledby="lrCopyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lrCopyModalLabel">
                        <i class="bi bi-file-earmark-pdf"></i>
                        LR Copy
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fb-confirm-note">
                        <i class="bi bi-info-circle"></i>
                        PDF layout (A / B / C) uses the Good Receipt LR copy template saved in Company settings.
                    </div>

                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-printer"></i> Choose copy type
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="lr-copy-choices">
                                <button type="button" class="lr-copy-choice" data-with-freight="1">
                                    <span class="lr-copy-choice__icon">
                                        <i class="bi bi-currency-rupee"></i>
                                    </span>
                                    <span class="lr-copy-choice__text">
                                        <span class="lr-copy-choice__title">With Freight</span>
                                        <span class="lr-copy-choice__desc">Include freight charges on the PDF</span>
                                    </span>
                                    <i class="bi bi-chevron-right lr-copy-choice__arrow"></i>
                                </button>
                                <button type="button" class="lr-copy-choice" data-with-freight="0">
                                    <span class="lr-copy-choice__icon lr-copy-choice__icon--muted">
                                        <i class="bi bi-file-text"></i>
                                    </span>
                                    <span class="lr-copy-choice__text">
                                        <span class="lr-copy-choice__title">Without Freight</span>
                                        <span class="lr-copy-choice__desc">Hide freight charges on the PDF</span>
                                    </span>
                                    <i class="bi bi-chevron-right lr-copy-choice__arrow"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Good Receipt (Bilty) Details Modal -->
    <div class="modal fade fb-pay-modal" id="lrDetailsModal" tabindex="-1" aria-labelledby="lrDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lrDetailsModalLabel">
                        <i class="bi bi-file-text"></i> Good Receipt (Bilty) Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="lrDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading good receipt (bilty) details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Close
                    </button>
                    <a href="#" class="btn btn-primary" id="printLrBtn" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> Print LR with Freight
                    </a>
                    <a href="#" class="btn btn-primary" id="printLrWithoutFreightBtn" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> Print LR without Freight
                    </a>
                    @if (Auth::user()->canActAsAdmin())
                        <a href="#" class="btn btn-primary" id="editLorryReceiptBtn">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modal styles */
        .lr-details-container {
            padding: 1.5rem;
        }

        .lr-details-section {
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            background-color: var(--brand-bg);
            border: 1px solid var(--brand-border);
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .lr-details-section h5 {
            background-color: #f1f8ff;
            padding: 0.75rem 1.25rem;
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--brand-primary);
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

        .selected-lr-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1rem;
        }

        .selected-lr-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .selected-lr-list li:last-child {
            border-bottom: none;
        }

        #voucherPaymentBody .mb-3 {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 6px;
        }

        .lr-details-container {
            padding: 20px;
        }

        .lr-details-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid var(--brand-border);
            border-radius: 5px;
        }

        .lr-details-section h5 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #495057;
        }

        .lr-details-row {
            display: flex;
            margin-bottom: 10px;
        }

        .lr-details-label {
            width: 150px;
            font-weight: 600;
            color: #6c757d;
        }

        .lr-details-value {
            flex: 1;
            color: #495057;
        }

        .table-link {
            color: inherit !important;
            text-decoration: none !important;
            cursor: pointer;
        }

        .table-link:hover {
            text-decoration: underline !important;
        }

        /* Highlight rows with memo_id */
        tr.memo-assigned {
            background-color: #e6f7ff !important;
            position: relative;
        }

        tr.memo-assigned:hover {
            background-color: #cceeff !important;
        }

        tr.memo-assigned td:first-child::before {
            content: "📝";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            margin-left: 3px;
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
            background: var(--brand-table-header-bg);
            border-bottom: 1px solid var(--brand-border);
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-body {
            padding: 20px;
        }

        /* Button styling */
        .btn-brand-primary,
        .btn-success {
            transition: all 0.2s ease;
        }

        .btn-brand-primary:hover,
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Table improvements */
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: var(--brand-table-header-bg);
            border-bottom: 2px solid var(--brand-border);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        /* Tooltip enhancement */
        [data-bs-toggle="tooltip"] {
            position: relative;
        }

        /* Memo badge */
        .memo-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #0dcaf0;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

    </style>

    @push('scripts')
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

        <script>
            $(document).ready(function() {
                const formatDate = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');

                    return `${year}-${month}-${day}`;
                };

                function loadListingQueryFromUrl() {
                    const params = new URLSearchParams(window.location.search);

                    if (params.has('gr_date_from')) {
                        $('#fromDate').val(params.get('gr_date_from'));
                    }

                    if (params.has('gr_date_to')) {
                        $('#toDate').val(params.get('gr_date_to'));
                    }

                    return params.has('gr_date_from') || params.has('gr_date_to');
                }

                function syncListingQueryToUrl() {
                    const params = new URLSearchParams();
                    const fromDate = $('#fromDate').val();
                    const toDate = $('#toDate').val();

                    if (fromDate) {
                        params.set('gr_date_from', fromDate);
                    }

                    if (toDate) {
                        params.set('gr_date_to', toDate);
                    }

                    const query = params.toString();
                    const nextUrl = query
                        ? `${window.location.pathname}?${query}`
                        : window.location.pathname;

                    window.history.replaceState({}, '', nextUrl);
                }

                function setDefaultDates() {
                    const today = new Date();
                    const fromDate = new Date();
                    fromDate.setDate(today.getDate() - 15);
                    if (!$('#fromDate').val()) {
                        $('#fromDate').val(formatDate(fromDate));
                    }

                    if (!$('#toDate').val()) {
                        $('#toDate').val(formatDate(today));
                    }
                }

                if (!loadListingQueryFromUrl()) {
                    setDefaultDates();
                }

                syncListingQueryToUrl();

                // Initialize DataTable
                var table = $('#lorryReceiptsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    createdRow: function(row, data, dataIndex) {
                        // Make LR number cell clickable
                        var lrNumberCell = $(row).find(
                            'td:eq(1)'); // LR Number is in the 2nd column (index 1)
                        lrNumberCell.css('cursor', 'pointer');
                        lrNumberCell.on('click', function() {
                            showLRDetails(data.id);
                        });
                    },
                    ajax: {
                        url: '{{ route('lorry_receipts.index') }}',
                        type: 'GET',
                        data: function(d) {
                            d.gr_date_from = $('#fromDate').val();
                            d.gr_date_to = $('#toDate').val();
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    },
                    columns: [

                        {
                            data: 'gr_date_formatted',
                            name: 'gr_date'
                        },
                        {
                            data: 'lr_number',
                            name: 'lr_number',
                            render: function(data, type, row) {
                                return '<a href="#" class="lr-number-link" data-id="' + row.id + '">' +
                                    data + (row.linked_lr_id ?
                                        ' <span class="badge bg-info ms-1">[' + row.linked_lr_id +
                                        ']</span>' : '') + '</a>';
                            }
                        },
                        {
                            data: 'customer_name',
                            name: 'customer_name'
                        },
                        {
                            data: 'consignor_name',
                            name: 'consignor_name',
                            render: function(data, type, row) {
                                if (row.source_city) {
                                    return data + '<br><span class="city-label">' + row.source_city + '</span>';
                                }
                                return data;
                            }
                        },
                        {
                            data: 'consignee_name',
                            name: 'consignee_name',
                            render: function(data, type, row) {
                                if (row.destination_city) {
                                    return data + '<br><span class="city-label">' + row.destination_city + '</span>';
                                }
                                return data;
                            }
                        },
                        {
                            data: 'status_badge',
                            name: 'delivery_status'
                        },

                        {
                            data: 'truck_number',
                            name: 'truck_number'
                        },
                        {
                            data: 'weight',
                            name: 'weight',
                            className: 'text-end fw-medium'
                        },
                        {
                            data: 'fr_rate',
                            name: 'fr_rate',
                            className: 'text-end text-muted'
                        },
                        {
                            data: 'amount_formatted',
                            name: 'amount',
                            className: 'text-end fw-bold text-brand-primary'
                        },
                        {
                            data: 'actions',
                            name: 'actions',
                            orderable: false,
                            searchable: false,
                            className: 'text-center'
                        }
                    ],
                    order: [[0, 'desc']],
                    pageLength: 25,
                    responsive: false,
                    scrollX: false,
                    autoWidth: false,
                    dom: '<"app-index-dt-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2"<"d-flex flex-wrap align-items-center gap-2"l><"d-flex flex-wrap align-items-center gap-2"f>>' +
                        't' +
                        '<"app-index-partload-pagination d-flex flex-wrap align-items-center justify-content-between gap-2"<"app-index-partload-pagination__summary"i><"app-index-partload-pagination__nav"p>>',
                    drawCallback: function(settings) {
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    }
                });

                $('#filterBtn').on('click', function() {
                    syncListingQueryToUrl();
                    $('#lorryReceiptsTable').DataTable().ajax.reload();
                });

                $('#resetDateFilter').on('click', function() {
                    $('#fromDate').val('');
                    $('#toDate').val('');
                    syncListingQueryToUrl();
                    table.ajax.reload();
                });
            });

            // Helper function to format GR Date to DD-MM-YYYY
            function formatGRDate(dateString) {
                if (!dateString) return 'N/A';
                const parts = dateString.split('-')
                if (parts.length === 3 && parts[0].length === 4) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
                return dateString;
            }

            // Show LR Details function - Optimized with merged API call
            async function showLRDetails(id) {
                const modalElement = document.getElementById('lrDetailsModal');
                const contentDiv = document.getElementById('lrDetailsContent');
                const modal = new bootstrap.Modal(modalElement);

                // Show loading state
                contentDiv.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading good receipt (bilty) details...</p>
                    </div>
                `;
                modal.show();

                try {
                    // Single API call that includes linked LRs if available
                    const response = await fetch(`/api/lorry-receipts/${id}/details`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const lr = await response.json();

                    // Build HTML content
                    let detailsHtml = '';

                    // Basic info and parties sections (only if not linked)
                    if (!lr.linked_lr_id) {
                        detailsHtml = `
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Basic Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless table-sm">
                                                <tr><td class="fw-bold">LR Number:</td><td>${lr.lr_number}</td></tr>
                                                <tr><td class="fw-bold">GR Date:</td><td>${formatGRDate(lr.gr_date)}</td></tr>
                                                <tr><td class="fw-bold">Status:</td><td><span class="badge ${lr.delivery_status === 'Delivered' ? 'bg-success' : 'bg-warning'}">${lr.delivery_status}</span></td></tr>
                                                <tr><td class="fw-bold">Invoice Number:</td><td>${lr.invoice_number || 'N/A'}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="bi bi-people"></i> Parties</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless table-sm">
                                                <tr><td class="fw-bold">Customer:</td><td>${lr.customer?.name || 'N/A'}</td></tr>
                                                <tr><td class="fw-bold">Consignor:</td><td>${lr.consignor?.name || 'N/A'}</td></tr>
                                                <tr><td class="fw-bold">Consignee:</td><td>${lr.consignee?.name || 'N/A'}</td></tr>
                                                <tr><td class="fw-bold">Transporter:</td><td>${lr.transporter?.name || 'N/A'}</td></tr>
                                                <tr><td class="fw-bold">Reference By:</td><td>${lr.reference?.name || 'N/A'}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="bi bi-truck"></i> Transport Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless table-sm">
                                                <tr><td class="fw-bold">Truck Number:</td><td>${lr.truck?.truck_number || 'N/A'}</td></tr>
                                                <tr><td class="fw-bold">Goods:</td><td>${lr.good_type || 'N/A'}</td></tr>
                                                <tr><td class="fw-bold">Weight:</td><td>${lr.weight || 'N/A'}</td></tr>
                                                <tr><td class="fw-bold">Amount:</td><td class="fw-bold text-success">${lr.amount || '0'}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    // Linked LRs section (if data available from merged API response)
                    let linkedLRSectionHtml = '';
                    if (lr.linked_lrs_data) {
                        const { common, lrs } = lr.linked_lrs_data;
                        const lrRows = lrs.map(linkedLR => `
                            <tr>
                                <td>${linkedLR.lr_number}</td>
                                <td>${linkedLR.customer || 'N/A'}</td>
                                <td>${linkedLR.consignor || 'N/A'}</td>
                                <td>${linkedLR.consignee || 'N/A'}</td>
                                <td>${linkedLR.good_type || 'N/A'}</td>
                                <td>${linkedLR.weight || 'N/A'}</td>
                                <td class="fw-bold text-success">${linkedLR.amount || '0'}</td>
                                <td><span class="badge bg-${linkedLR.status === 'delivered' ? 'success' : 'warning'}">${linkedLR.status || 'N/A'}</span></td>
                            </tr>
                        `).join('');

                        linkedLRSectionHtml = `
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-truck"></i> Common Details</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr><td class="fw-bold">Transporter:</td><td>${common.transporter || 'Varies'}</td></tr>
                                        <tr><td class="fw-bold">Truck Number:</td><td>${common.truck_number || 'Varies'}</td></tr>
                                        <tr><td class="fw-bold">GR Date:</td><td>${common.gr_date ? formatGRDate(common.gr_date) : 'Varies'}</td></tr>
                                        <tr><td class="fw-bold">Reference By:</td><td>${common.reference_by || 'Varies'}</td></tr>
                                    </table>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-list"></i> Linked Good Receipts (Bilty)</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>LR Number</th>
                                                <th>Customer</th>
                                                <th>Consignor</th>
                                                <th>Consignee</th>
                                                <th>Goods</th>
                                                <th>Weight</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>${lrRows}</tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                    }

                    // User information section
                    const creator = lr.created_by && lr.creator ? `${lr.creator.name} (${lr.creator.email})` : 'N/A';
                    const updater = lr.updated_by && lr.updater ? `${lr.updater.name} (${lr.updater.email})` : 'N/A';

                    detailsHtml += `
                        <div class="row mt-3">
                            <div class="col-md-12">${linkedLRSectionHtml}</div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-person-circle"></i> User Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless table-sm">
                                            <tr><td class="fw-bold">Created By:</td><td>${creator}</td></tr>
                                            <tr><td class="fw-bold">Last Updated By:</td><td>${updater}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    contentDiv.innerHTML = detailsHtml;

                    // Update edit button
                    const editBtn = document.getElementById('editLorryReceiptBtn');
                    if (editBtn) {
                        editBtn.href = `/lorry_receipts/${id}/edit`;
                    }

                    // Update print buttons
                    const printLrBtn = document.getElementById('printLrBtn');
                    if (printLrBtn) {
                        printLrBtn.href = `/reports/lr-copy-export?lr_id=${id}&show_freight=1`;
                    }
                    const printLrWithoutFreightBtn = document.getElementById('printLrWithoutFreightBtn');
                    if (printLrWithoutFreightBtn) {
                        printLrWithoutFreightBtn.href = `/reports/lr-copy-export?lr_id=${id}&show_freight=0`;
                    }
                } catch (error) {
                    console.error('Error fetching LR details:', error);
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger m-3">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Error:</strong> Unable to load LR details. Please try again.
                        </div>
                    `;
                }
            }

            // LR Copy functionality
            let currentLrId = null;

            $(document).on('click', '.lr-copy-btn', function() {
                currentLrId = $(this).data('lr-id');
            });

            $(document).on('click', '.lr-copy-choice', function() {
                const withFreight = String($(this).data('with-freight')) === '1';
                generateLrCopy(withFreight);
            });

            function generateLrCopy(withFreight) {
                if (!currentLrId) {
                    alert('Error: LR ID not found. Please try again.');
                    return;
                }

                const url = '{{ route("reports.lr-copy-export") }}?lr_id=' + currentLrId +
                    '&show_freight=' + (withFreight ? '1' : '0');
                window.open(url, '_blank');

                const modalElement = document.getElementById('lrCopyModal');
                const modal = bootstrap.Modal.getInstance(modalElement);

                if (modal) {
                    modal.hide();

                    setTimeout(function() {
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 100);
                } else {
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            }

            window.generateLrCopy = generateLrCopy;
        </script>
    @endpush
@endsection
