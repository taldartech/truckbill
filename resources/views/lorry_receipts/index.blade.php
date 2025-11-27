@extends('layouts.app')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
@endpush

@section('content')
    <div class="container mt-4">
        <div class="col-sm-6">
            <h3 class="mb-0">Lorry Receipts Management</h3>
        </div>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="d-flex justify-content-end gap-2 mb-3">
            <div class="d-flex justify-content-end gap-2 mb-3">
                <a href="{{ route('lorry_receipts.create') }}" class="btn btn-info">
                    <i class="bi bi-plus"></i> Create
                </a>
                <a href="{{ route('lorry_receipts.createLinked') }}" class="btn btn-info">
                    <i class="bi bi-plus"></i> Create Linked
                </a>
            </div>
        </div>

        {{-- <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Lorry Receipts List</h3>
            <!-- Add this right after the create buttons div, before the card -->
<div class="row mb-3">
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-3">
                <label for="fromDate" class="form-label">From Date</label>
                <input type="date" class="form-control" id="fromDate">
            </div>
            <div class="col-md-3">
                <label for="toDate" class="form-label">To Date</label>
                <input type="date" class="form-control" id="toDate">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-primary me-2" id="filterBtn">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <button type="button" class="btn btn-secondary" id="resetDateFilter">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div> --}}
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Left: Title -->
                    <h3 class="card-title mb-0">Lorry Receipts List</h3>
                    <!-- Right: Date Filter -->
                    <div>
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <label for="fromDate" class="form-label mb-0">From Date</label>
                                <input type="date" class="form-control" id="fromDate">
                            </div>
                            <div class="col-auto">
                                <label for="toDate" class="form-label mb-0">To Date</label>
                                <input type="date" class="form-control" id="toDate">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary me-2" id="filterBtn">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <button type="button" class="btn btn-secondary" id="resetDateFilter">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <!-- Add this CSS in your style section -->
        <style>
            .date-filter-container {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
                border: 1px solid #dee2e6;
            }
        </style>
    </div>
    <div class="card-body">
        <table id="lorryReceiptsTable" class="table table-bordered table-striped">
            <thead>
                <tr>

                    <th>GR Date</th>
                    <th>LR Number</th>
                    <th>Customer</th>
                    <th>Consignor</th>
                    <th>Consignee</th>
                    <th>Status</th>

                    <th>Truck Number</th>
                    <th>Weight</th>
                    <th>LR Rate</th>
                    <th>Amount</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
    </div>
    </div>




    <!-- Lorry Receipt Details Modal -->
    <div class="modal fade" id="lrDetailsModal" tabindex="-1" aria-labelledby="lrDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="lrDetailsModalLabel">
                        <i class="bi bi-file-text me-2"></i>Lorry Receipt Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="lrDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading lorry receipt details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Close
                    </button>
                    @if (Auth::user()->userRole->name === 'admin')
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
            border: 1px solid #dee2e6;
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
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-body {
            padding: 20px;
        }

        /* Button styling */
        .btn-info,
        .btn-success {
            transition: all 0.2s ease;
        }

        .btn-info:hover,
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
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
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

        .lr-number-link {
            color: #0d6efd;
            text-decoration: none;
        }
    </style>

    @push('scripts')
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

        <script>
            $(document).ready(function() {
                // Set default date values
                function setDefaultDates() {
                    const today = new Date();
                    const oneWeekAgo = new Date();
                    oneWeekAgo.setDate(today.getDate() - 7);

                    // Format dates as YYYY-MM-DD
                    const formatDate = (date) => {
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };

                    // Set default values if not already set
                    if (!$('#fromDate').val()) {
                        $('#fromDate').val(formatDate(oneWeekAgo));
                    }
                    if (!$('#toDate').val()) {
                        $('#toDate').val(formatDate(today));
                    }
                }

                // Call the function when the page loads
                setDefaultDates();

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

                        // Apply memo-assigned class to rows with memo_id
                        if (data.row_class && data.row_class.includes('memo-assigned')) {
                            $(row).addClass('memo-assigned');

                            // Add tooltip to show memo ID
                            var memoId = $(row).find('input.rowCheckbox').data('memo-id');
                            if (memoId) {
                                $(row).attr('title', 'Memo ID: ' + memoId);
                                $(row).attr('data-bs-toggle', 'tooltip');
                                $(row).attr('data-bs-placement', 'top');
                            }
                        }
                    },
                    ajax: {
                        url: '{{ route('lorry_receipts.data') }}',
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
                                        ' <span class="p-1 badge text-bg-info">[' + row.linked_lr_id +
                                        ']</span>' : '') + '</a>';
                            }
                        },
                        {
                            data: 'customer_name',
                            name: 'customer.name'
                        },
                        {
                            data: 'consignor_name',
                            name: 'consignor.name',
                            render: function(data, type, row) {
                                if (row.source_city) {
                                    return data + '<br><span class="p-1 badge text-bg-secondary">' + row
                                        .source_city + '</span>';
                                }
                                return data;
                            }
                        },
                        {
                            data: 'consignee_name',
                            name: 'consignee.name',
                            render: function(data, type, row) {
                                if (row.destination_city) {
                                    return data + '<br><span class="p-1 badge text-bg-secondary">' + row
                                        .destination_city + '</span>';
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
                            name: 'truck.truck_number'
                        },
                        {
                            data: 'weight',
                            name: 'weight',
                            className: 'text-end'
                        },
                        {
                            data: 'fr_rate',
                            name: 'fr_rate',
                            className: 'text-end'
                        },
                        {
                            data: 'amount_formatted',
                            name: 'amount',
                            className: 'text-end'
                        },
                        {
                            data: 'actions',
                            name: 'actions',
                            orderable: false,
                            searchable: false,
                            width: '120px',
                            className: 'text-center'
                        }
                    ],
                    // order: [[1, 'desc']],
                    // pageLength: 25,
                    // responsive: true,
                    // scrollX: true,
                    // dom: 'Bfrtip',
                    // buttons: [
                    //     {
                    //         extend: 'excel',
                    //         text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                    //         className: 'btn btn-success btn-sm'
                    //     }
                    // ],
                    // language: {
                    //     processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    //     emptyTable: "No lorry receipts found",
                    //     zeroRecords: "No matching lorry receipts found"
                    // },
                    drawCallback: function(settings) {
                        // Reinitialize tooltips after each draw
                        $('[data-bs-toggle="tooltip"]').tooltip();


                    }
                });



                $('#filterBtn').on('click', function() {
                    $('#lorryReceiptsTable').DataTable().ajax.reload();


                });

                $('#resetDateFilter').on('click', function() {
                    $('#fromDate').val('');
                    $('#toDate').val('');
                    dataTable.ajax.reload();
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

            // Show LR Details function
            function showLRDetails(id) {
                const modal = new bootstrap.Modal(document.getElementById('lrDetailsModal'));
                const contentDiv = document.getElementById('lrDetailsContent');

                // Show loading state
                contentDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading LR details...</p>
        </div>
    `;

                // Fetch LR details
                fetch(`/api/lorry-receipts/${id}/details`)
                    .then(response => response.json())
                    .then(lr => {
                        let detailsHtml = `


            ${lr.linked_lr_id === null ? `
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-info-circle"></i> Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td class="fw-bold">LR Number:</td>
                                                <td>${lr.lr_number}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">GR Date:</td>
                                                <td>${formatGRDate(lr.gr_date)}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Status:</td>
                                                <td><span class="badge ${lr.delivery_status === 'Delivered' ? 'bg-success' : 'bg-warning'}">${lr.delivery_status}</span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Invoice Number:</td>
                                                <td>${lr.invoice_number || 'N/A'}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Parties -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-people"></i> Parties</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td class="fw-bold">Customer:</td>
                                                <td>${lr.customer ? lr.customer.name : 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Consignor:</td>
                                                <td>${lr.consignor ? lr.consignor.name : 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Consignee:</td>
                                                <td>${lr.consignee ? lr.consignee.name : 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Transporter:</td>
                                                <td>${lr.transporter ? lr.transporter.name : 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Reference By:</td>
                                                <td>${lr.reference_by ? lr.reference.name : 'N/A'}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <!-- Transport Details -->
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-truck"></i> Transport Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td class="fw-bold">Truck Number:</td>
                                                <td>${lr.truck ? lr.truck.truck_number : 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Goods:</td>
                                                <td>${lr.good_type}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Weight:</td>
                                                <td>${lr.weight}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Amount:</td>
                                                <td class="fw-bold text-success">${lr.amount}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
             ` : ''}
     <div class="row mt-3">
        <div class="col-md-12">
            <div id="linkedLRSection"></div>
        </div>
    </div>
    <div class="row mt-3">
        <!-- Created By & Updated By Users -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-circle"></i> User Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="fw-bold">Created By:</td>
                            <td>${lr.created_by ? lr.creator.name + ' (' + lr.creator.email + ')' : 'N/A'}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Last Updated By:</td>
                            <td>${lr.updated_by ? lr.updater.name + ' (' + lr.updater.email + ')' : 'N/A'}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    `;


                        // After setting contentDiv.innerHTML, load the linked LRs if needed
                        function formatGRDate(dateString) {
                            if (!dateString) return 'Varies';
                            const date = new Date(dateString);
                            const day = String(date.getDate()).padStart(2, '0');
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const year = date.getFullYear();
                            return `${day}-${month}-${year}`;
                        }

                        if (lr.linked_lr_id) {
                            fetch(`/api/lorry-receipts/linked/${lr.linked_lr_id}`)
                                .then(response => response.json())
                                .then(data => {
                                    const linkedSection = document.getElementById('linkedLRSection');
                                    const {
                                        common,
                                        lrs
                                    } = data;

                                    let commonHtml = `
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-truck"></i> Common Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless table-sm">
                            <tr><td class="fw-bold">Transporter:</td><td>${common.transporter || 'Varies'}</td></tr>
                            <tr><td class="fw-bold">Truck Number:</td><td>${common.truck_number || 'Varies'}</td></tr>
                           <tr>
  <td class="fw-bold">GR Date:</td>
  <td>${common.gr_date ? formatGRDate(common.gr_date) : 'Varies'}</td>
</tr>

                            <tr><td class="fw-bold">Reference By:</td><td>${common.reference_by || 'Varies'}</td></tr>
                        </table>
                    </div>
                </div>
            `;

                                    let lrRows = lrs.map(linkedLR => `
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

                                    linkedSection.innerHTML = `
                ${commonHtml}
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-list"></i> Linked Lorry Receipts</h6>
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
                                })
                                .catch(error => {
                                    console.error('Error fetching linked LRs:', error);
                                    document.getElementById('linkedLRSection').innerHTML =
                                        '<div class="alert alert-danger">Error loading linked LR details</div>';
                                });
                        }


                        contentDiv.innerHTML = detailsHtml;

                        // Update the edit button's href with the correct URL
                        const editBtn = document.getElementById('editLorryReceiptBtn');
                        if (editBtn) {
                            editBtn.href = `/lorry_receipts/${id}/edit`;
                        }

                        modal.show();
                    })
                    .catch(error => {
                        console.error('Error fetching LR details:', error);
                        contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Error:</strong> Unable to load LR details. Please try again.
                </div>
            `;
                        modal.show();
                    });
            }
        </script>
    @endpush
@endsection
