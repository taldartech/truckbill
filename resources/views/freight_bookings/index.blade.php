@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Freight Bookings Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Always visible container --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('freight_bookings.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Add New
        </a>
    </div>

    {{-- Hidden filter form --}}
    <form method="GET" action="{{ route('freight_bookings.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        {{-- Filter checkboxes --}}
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="lr_id" id="filter_lr" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_lr">LR</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="is_delivered" id="filter_delivered" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_delivered">Delivered</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="is_cancelled" id="filter_cancelled" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_cancelled">Cancelled</label>
                </div>
            </div>
        </div>

        {{-- Conditionally visible filter fields --}}
        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-2 d-none" id="field_lr_id">
                <label for="lr_id" class="form-label">LR</label>
                <select name="lr_id" class="form-select">
                    <option value="">All</option>
                    @foreach($lrs as $lr)
                        <option value="{{ $lr->id }}" {{ request('lr_id') == $lr->id ? 'selected' : '' }}>{{ $lr->lr_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-none" id="field_is_delivered">
                <label for="is_delivered" class="form-label">Delivered</label>
                <select name="is_delivered" class="form-select">
                    <option value="">All</option>
                    <option value="1" {{ request('is_delivered') === '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ request('is_delivered') === '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="col-md-2 d-none" id="field_is_cancelled">
                <label for="is_cancelled" class="form-label">Cancelled</label>
                <select name="is_cancelled" class="form-select">
                    <option value="">All</option>
                    <option value="1" {{ request('is_cancelled') === '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ request('is_cancelled') === '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>
        </div>

        {{-- Form action buttons --}}
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('freight_bookings.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    <!-- LR Details Modal -->
    <div class="modal fade" id="lrDetailsModal" tabindex="-1" aria-labelledby="lrDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lrDetailsModalLabel">LR Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="lrDetailsContent">
                    <!-- LR details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <style>
        .table-link {
            color: #000 !important;
            text-decoration: none !important;
            cursor: pointer;
        }
        .table-link:hover {
            color: #000 !important;
            text-decoration: none !important;
        }
    </style>
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Freight Bookings List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>LR No</th>
                        <th>Voucher No</th>
                        <th>TR Rate</th>
                        <th>TR Amount</th>
                        <th>Advance</th>
                        <th>Balance</th>
                        <th>GR Date</th>
                        <th>Delivered?</th>
                        <th>Cancelled?</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr class="align-middle">
                            <td>{{ $booking->id }}</td>
                            <td>
                                @if($booking->lr)
                                    <a href="#" class="table-link" onclick="showLRDetails({{ $booking->lr->id }})">
                                        {{ $booking->lr->lr_number }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $booking->voucher_number }}</td>
                            <td>{{ $booking->tr_fr_rate }}</td>
                            <td>{{ $booking->tr_amount }}</td>
                            <td>{{ $booking->advance_amount }}</td>
                            <td>{{ $booking->balance_amount }}</td>
                            <td>{{ $booking->lr ? $booking->lr->gr_date : '-' }}</td>
                            <td>{{ $booking->is_delivered ? 'Yes' : 'No' }}</td>
                            <td>{{ $booking->is_cancelled ? 'Yes' : 'No' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if(!$booking->is_cancelled)
                                        <a href="{{ route('freight_bookings.edit', $booking->id) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                    <form action="{{ route('freight_bookings.destroy', $booking->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this booking?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-box-seam text-muted" style="font-size: 3rem"></i>
                                    <p class="text-muted mt-2">No bookings found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $bookings->links() }}
</div>
<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        const fieldDiv = document.getElementById(fieldId);
        if (checkbox.checked) {
            fieldDiv.classList.remove('d-none');
            // Initialize the field value if it exists in URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const value = urlParams.get(checkbox.value);
            if (value) {
                const input = fieldDiv.querySelector('input, select');
                if (input) {
                    if (input.type === 'select-one') {
                        const option = input.querySelector(`option[value="${value}"]`);
                        if (option) option.selected = true;
                    } else {
                        input.value = value;
                    }
                }
            }
        } else {
            fieldDiv.classList.add('d-none');
            const input = fieldDiv.querySelector('input, select');
            if (input) {
                if (input.type === 'select-one') {
                    input.value = '';
                } else {
                    input.value = '';
                }
            }
        }
    }

    // Initialize checkboxes and fields based on URL parameters
    document.addEventListener('DOMContentLoaded', function() {
        const filterFields = document.querySelectorAll('.form-check-input');
        const urlParams = new URLSearchParams(window.location.search);
        
        filterFields.forEach(checkbox => {
            const value = urlParams.get(checkbox.value);
            if (value) {
                checkbox.checked = true;
                toggleField(checkbox);
            }
        });
    });
    function showLRDetails(lrId) {
        const modal = new bootstrap.Modal(document.getElementById('lrDetailsModal'));
        const contentDiv = document.getElementById('lrDetailsContent');
        
        // Show loading state
        contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        // Fetch LR details
        fetch(`/api/lorry-receipts/${lrId}/details`)
            .then(response => response.json())
            .then(data => {
                // Format and display the details
                contentDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h5>LR Information</h5>
                            <p><strong>LR Number:</strong> ${data.lr_number}</p>
                            <p><strong>GR Date:</strong> ${data.gr_date}</p>
                            <p><strong>Amount:</strong> ${data.amount}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Transport Details</h5>
                            <p><strong>Transporter:</strong> ${data.transporter ? data.transporter.name : 'N/A'}</p>
                            <p><strong>Truck:</strong> ${data.truck ? data.truck.registration_number : 'N/A'}</p>
                            <p><strong>Status:</strong> ${data.delivery_status}</p>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>Consignor Information</h5>
                            <p><strong>Name:</strong> ${data.consignor ? data.consignor.name : 'N/A'}</p>
                            <p><strong>Contact:</strong> ${data.consignor ? data.consignor.contact_number : 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Consignee Information</h5>
                            <p><strong>Name:</strong> ${data.consignee ? data.consignee.name : 'N/A'}</p>
                            <p><strong>Contact:</strong> ${data.consignee ? data.consignee.contact_number : 'N/A'}</p>
                        </div>
                    </div>
                `;
                
                // Show the modal
                modal.show();
            })
            .catch(error => {
                contentDiv.innerHTML = `<div class="alert alert-danger">Error loading LR details: ${error.message}</div>`;
            });
    }

</script>
@endsection
