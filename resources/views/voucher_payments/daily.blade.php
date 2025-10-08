@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
    <div class="container mt-4">
        <div class="row">
            <div class="col-sm-8">
                <h3 class="mb-0">Daily Payments</h3>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Search and Filter Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Search & Filter</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAdvancedSearch()">
                    <i class="bi bi-funnel"></i> Toggle Filters
                </button>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('voucher_payments.daily') }}" id="searchForm">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" required>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="ms-3">
                                <strong>Selected Date:</strong> {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
                                <span class="badge bg-primary ms-2">{{ $payments->count() }} payments</span>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Search Fields (initially hidden) -->
                    <div id="advancedSearchFields" class="d-none">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Truck No</label>
                                <input type="text" name="truck_no" class="form-control"
                                    value="{{ request('truck_no') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Transporter</label>
                                <select name="transporter" class="form-select">
                                    <option value="">All Transporters</option>
                                    @foreach (\App\Models\Transporter::all() as $transporter)
                                        <option value="{{ $transporter->id }}"
                                            {{ request('transporter') == $transporter->id ? 'selected' : '' }}>
                                            {{ $transporter->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Source</label>
                                <select name="source" class="form-select">
                                    <option value="">All Sources</option>
                                    @foreach (\App\Models\City::orderBy('name')->get() as $city)
                                        <option value="{{ $city->id }}"
                                            {{ request('source') == $city->id ? 'selected' : '' }}>
                                            {{ $city->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Destination</label>
                                <select name="destination" class="form-select">
                                    <option value="">All Destinations</option>
                                    @foreach (\App\Models\City::orderBy('name')->get() as $city)
                                        <option value="{{ $city->id }}"
                                            {{ request('destination') == $city->id ? 'selected' : '' }}>
                                            {{ $city->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Lorry Receipt No</label>
                                <input type="text" name="lr_no" class="form-control" value="{{ request('lr_no') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Freight Booking No</label>
                                <input type="text" name="freight_booking_no" class="form-control"
                                    value="{{ request('freight_booking_no') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="in-progress" {{ request('status') == 'in-progress' ? 'selected' : '' }}>
                                        In Progress</option>
                                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <a href="{{ route('voucher_payments.daily') }}?date={{ $selectedDate }}"
                                class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments List -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Voucher Payments -
                            {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</h3>
                    </div>
                    @if (auth()->user()->userRole->name === 'admin' || auth()->user()->userRole->name === 'super_admin')
                        <div class="d-flex">
                            <button id="bulkActionBtn" class="btn btn-primary d-none">
                                <i class="bi bi-arrow-up-circle"></i> Update Status
                            </button>
                            {{-- @if (
                                $payments->where('payment_status', 'pending')->where('payment_mode', 'bank transfer')->count() > 0 ||
                                    $payments1->where('payment_status', 'pending')->where('payment_mode', 'bank transfer')->count() > 0)
                                <a class="btn btn-warning ms-2" href="{{ route('voucher_payments.export_pending', ['date' => $selectedDate]) }}">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Pending Bank Payments
                                </a>
                            @endif --}}
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <table class="table table-bordered" id="paymentsTable">
                    <thead>
                        <tr>
                            @if (auth()->user()->userRole->name === 'admin' || auth()->user()->userRole->name === 'super_admin')
                                <th style="width: 30px">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                            @endif


                            <th>LR No</th>
                            <th>Transporter</th>
                            <th>Truck No</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Payment Type</th>
                            <th>Payment Mode</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created Time</th>
                            {{-- @if (auth()->user()->userRole->name === 'admin' || auth()->user()->userRole->name === 'super_admin')
                        <th>Action</th>
                        @endif --}}
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr class="align-middle payment-row" data-status="{{ $payment->payment_status }}"
                                data-id="{{ $payment->id }}">
                                @if (auth()->user()->userRole->name === 'admin' || auth()->user()->userRole->name === 'super_admin')
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
                                </td>

                                <td>
                                    @if ($payment->freight_booking && $payment->freight_booking->transporter)
                                        @if ($payment->freight_booking->transporter->is_owner)
                                            <i class="bi bi-truck"></i> {{ $payment->freight_booking->transporter->name }}
                                        @else
                                            <i class="bi bi-building"></i>
                                            {{ $payment->freight_booking->transporter->name }}
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->truck)
                                        <span
                                            class="badge bg-primary">{{ $payment->freight_booking->lr->truck->truck_number }}</span>
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
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($payment->amount)
                                        <strong>₹{{ number_format($payment->amount, 2) }}</strong>
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
                                    <small>{{ $payment->created_at->setTimezone('Asia/Kolkata')->format('h:i A') }}</small>
                                </td>
                                {{-- @if (auth()->user()->userRole->name === 'admin' || auth()->user()->userRole->name === 'super_admin')
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionDropdown{{ $payment->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-gear"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="actionDropdown{{ $payment->id }}">
                                            @if ($payment->payment_status !== 'pending')
                                            <li><a class="dropdown-item {{ $payment->payment_status === 'paid' ? 'disabled' : '' }}" href="#" onclick="{{ $payment->payment_status === 'paid' ? 'return false' : "updateStatus($payment->id, 'pending')" }}"><i class="bi bi-hourglass text-warning"></i> Mark as Pending</a></li>
                                            @endif
                                            @if ($payment->payment_status === 'pending')
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus({{ $payment->id }}, 'in-progress')"><i class="bi bi-arrow-repeat text-info"></i> Mark as In Progress</a></li>
                                            @endif
                                            @if ($payment->payment_status === 'in-progress')
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus({{ $payment->id }}, 'paid')"><i class="bi bi-check-circle text-success"></i> Mark as Paid</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                @endif --}}
                            </tr>
                        @endforeach
                        @foreach ($payments1 as $payment)
                            @php
                                $linked_lr_id = $payment->freight_booking->linked_lr_id ?? null;
                                $lrs = \App\Models\LorryReceipt::where('linked_lr_id', $linked_lr_id)
                                    ->where('linked_lr_id', '!=', null)
                                    ->get();
                            @endphp
                            <tr class="align-middle payment-row" data-status="{{ $payment->payment_status }}"
                                data-id="{{ $payment->id }}">
                                @if (auth()->user()->userRole->name === 'admin' || auth()->user()->userRole->name === 'super_admin')
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
                                </td>

                                <td>
                                    @if ($payment->freight_booking && $payment->freight_booking->transporter)
                                        @if ($payment->freight_booking->transporter->is_owner)
                                            <i class="bi bi-truck"></i> {{ $payment->freight_booking->transporter->name }}
                                        @else
                                            <i class="bi bi-building"></i>
                                            {{ $payment->freight_booking->transporter->name }}
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->truck)
                                        <span
                                            class="badge bg-primary">{{ $payment->freight_booking->lr->truck->truck_number }}</span>
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
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($payment->amount)
                                        <strong>₹{{ number_format($payment->amount, 2) }}</strong>
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
                                    <small>{{ $payment->created_at->setTimezone('Asia/Kolkata')->format('h:i A') }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection

<!-- Bulk Action Modal -->
<div class="modal fade" id="bulkActionModal" tabindex="-1" aria-labelledby="bulkActionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
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
@endpush
