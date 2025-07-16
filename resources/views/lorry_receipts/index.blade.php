@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6">
        <h3 class="mb-0">Lorry Receipts</h3>
        
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" id="VocherPaymentBtn" 
            class="btn btn-lg btn-primary d-none"
            onclick="handleVoucherPayment()"
            style="position: fixed; bottom: 100px; right: 20px; z-index: 1050;">
            <i class="bi bi-cash-coin"></i> Payment
        </button>
        <button type="button" id="bulkPaymentBtn" 
            class="btn btn-lg btn-primary d-none"
            onclick="handleBulkPayment()"
            style="position: fixed; bottom: 20px; right: 20px; z-index: 1050;">
            <i class="bi bi-cash-coin"></i> Memo Update
        </button>
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('lorry_receipts.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Add New LR
        </a>
    </div>

    <form method="GET" action="{{ route('lorry_receipts.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        {{-- Filter checkboxes --}}
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="lr_number" id="filter_lr_number" onchange="toggleField(this)" >
                    <label class="form-check-label" for="filter_lr_number">LR Number</label>
                </div>
               
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="delivery_status" id="filter_status" onchange="toggleField(this)" >
                    <label class="form-check-label" for="filter_status">Status</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="gr_date" id="filter_date" onchange="toggleField(this)" >
                    <label class="form-check-label" for="filter_date">GR Date</label>
                </div>
            </div>
        </div>

        {{-- Conditionally visible filter fields --}}
        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-3 d-none" id="field_lr_number">
                <label class="form-label">LR Number</label>
                <input type="text" name="lr_number" class="form-control" value="{{ request('lr_number') }}">
            </div>
           
            <div class="col-md-3 d-none" id="field_delivery_status">
                <label class="form-label">Status</label>
                <select name="delivery_status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('delivery_status') == $status ? 'selected' : '' }}>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_gr_date">
                <label class="form-label">GR Date Range</label>
                <div class="row g-2">
                    <div class="col">
                        <input type="date" name="gr_date_from" class="form-control" value="{{ request('gr_date_from') }}">
                    </div>
                    <div class="col">
                        <input type="date" name="gr_date_to" class="form-control" value="{{ request('gr_date_to') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Form action buttons --}}
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Lorry Receipts List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                       <th><input type="checkbox" id="selectAllCheckbox"></th>
                        <th style="width: 10px">Sr. No</th>
                        <th>LR Number</th>
                        <th>Consignor</th>
                        <th>Consignee</th>
                        <th>Status</th>
                        <th>Action</th>
                        <th style="display: none">Freight Booking ID</th>
                        <th>GR Date</th>
                        <th>Truck Number</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lrs as $lr)
                    <tr class="align-middle">
                        <td><input type="checkbox" class="rowCheckbox" value="{{ $lr->id }}" data-lr="{{ $lr->lr_number }}"
                            data-truck="{{ $lr->truck->truck_number }}"  data-balance="{{ $lr->freightBooking?->balance_amount ?? 0 }}" data-payment-status="{{ strtolower($lr->delivery_status )}}"></td>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <a href="#" class="table-link" onclick="showLRDetails({{ $lr->id }})">
                                {{ $lr->lr_number }}
                            </a>
                        </td>
                        <td>{{ $lr->consignor?->name ?? 'N/A' }}</td>
                        <td>{{ $lr->consignee?->name ?? 'N/A' }}</td>
                        {{-- <td>{{ $lr->delivery_status ?? 'Pending' }}</td> --}}
                        <td>
                            @php
                                $hasFreightBooking = !empty($lr->freight_booking_id);
                                $rawStatus = $hasFreightBooking ? ($lr->delivery_status ?? 'Pending') : 'Pending';
                                $status = strtolower(trim($rawStatus));
                                $badgeClass = match($status) {
                                    'cancelled'    => 'badge text-bg-danger',
                                    'pending'      => 'badge text-bg-primary',
                                    'delivered'    => 'badge text-bg-success',
                                    'in-progress'  => 'badge text-bg-warning',
                                    default        => 'badge text-bg-secondary',
                                };
                            @endphp
                            <span class="{{ $badgeClass }}">{{ ucfirst($rawStatus) }}</span>
                        </td>
                        <td>
                            @php
                                $deliveryStatus = strtolower(trim($lr->delivery_status ?? 'pending'));
                                $hasFreightBooking = !empty($lr->freight_booking_id);
                                
                                $actionText = !$hasFreightBooking ? 'Create Freight Booking' : match($deliveryStatus) {
                                    'delivered' => 'View Freight Booking',
                                    'in-progress' => 'View Freight Booking',
                                    default => 'Create Freight Booking'
                                };
                                
                                $actionRoute = $hasFreightBooking && in_array($deliveryStatus, ['delivered', 'in-progress']) ? 
                                    route('freight_bookings.index', $lr->freight_booking_id) : 
                                    route('freight_bookings.create', ['lorry_receipt_id' => $lr->id]);
                                
                                $buttonClass = $hasFreightBooking ? match($deliveryStatus) {
                                    'delivered' => 'btn-info',
                                    'in-progress' => 'btn-warning',
                                    default => 'btn-success'
                                } : 'btn-success';
                            @endphp
                            <div class="d-flex gap-1">
                                {{-- <a href="{{ $actionRoute }}" class="btn btn-sm @php
                                    $status = strtolower(trim($lr->delivery_status ?? 'pending'));
                                    echo match($status) {
                                        'delivered' => 'btn-info',
                                        'in-progress' => 'btn-warning',
                                        default => 'btn-success'
                                    };
                                @endphp">
                                    <i class="bi bi-plus"></i> {{ $actionText }}
                                </a> --}}
                                <a href="{{ $actionRoute }}" 
       class="btn btn-sm {{ $buttonClass }}"
       title="{{ $actionText }}"
       data-bs-toggle="tooltip" 
       data-bs-placement="top">
        <i class="bi bi-truck"></i> 
    </a>
                                @if($hasFreightBooking && in_array(strtolower(trim($lr->delivery_status ?? 'pending')), ['delivered', 'in-progress']))
                                <a href="{{ route('voucher_payments.create', ['lorry_receipt_id' => $lr->id, 'freight_booking_id' => $lr->freight_booking_id]) }}" 
                                    class="btn btn-sm btn-success " 
                                    title="create Voucher Payments" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top">
                                    <i class="bi bi-receipt"></i> 
                                </a>
                                <a href="{{ route('invoices.index', ['lorry_receipt_id' => $lr->id]) }}" 
                                    class="btn btn-sm btn-secondary" 
                                    title="create Invoices" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top">
                                    <i class="bi bi-file-earmark-text"></i> 
                                </a>
                                @endif
                            </div>
                        </td>
                        
                        <td>{{ $lr->gr_date }}</td>
                        <td>{{ $lr->truck->truck_number }}</td>
                        <td>{{ $lr->weight }}</td>
                        <td>{{ $lr->amount }}</td>
                        
                        <td>
                            <div class="d-flex gap-1">
                                {{-- <a href="{{ route('lorry_receipts.edit', $lr->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-pencil"></i>
                                </a> --}}
                                <a href="{{ route('lorry_receipts.edit', $lr->id) }}" 
                                    class="btn btn-sm btn-info" 
                                    title="Edit Lorry Receipt" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top">
                                    <i class="bi bi-pencil"></i>
                                 </a>
                                <form action="{{ route('lorry_receipts.destroy', $lr->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this lorry receipt?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="empty-state">
                                <i class="bi bi-box"></i>
                                <p class="mt-2">No lorry receipts found.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $lrs->links() }}
        </div>
    </div>
</div>

<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        p     const field = document.getElementById(fieldId);
        if (checkbox.checked) {
            field.classList.remove('d-none');
        } else {
            field.classList.add('d-none');
            const input = field.querySelector('input, select');
            if (input) input.value = '';
        }
    }

    // Initialize filter fields based on URL parameters
    document.addEventListener('DOMContentLoaded', function() {
        // Get all filter checkboxes
        const checkboxes = document.querySelectorAll('.form-check-input');
        
        // Check each checkbox based on URL parameters
        checkboxes.forEach(checkbox => {
            const value = checkbox.value;
            const fieldId = 'field_' + value;
            const field = document.getElementById(fieldId);
            const urlValue = new URLSearchParams(window.location.search).get(value);
            
            if (urlValue) {
                checkbox.checked = true;
                field.classList.remove('d-none');
            }
        });
    });
</script>
<style>
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
        0%   { transform: translateY(0); }
        50%  { transform: translateY(-5px); }
        100% { transform: translateY(0); }
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
</style>

<!-- Modal for showing LR details -->
<div class="modal fade" id="lrDetailsModal" tabindex="-1" aria-labelledby="lrDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lrDetailsModalLabel">Lorry Receipt Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="lrDetailsContent"></div>
        </div>
    </div>
</div>

<!-- Modal for Bulk Payment -->
<div class="modal fade" id="bulkPaymentModal" tabindex="-1" aria-labelledby="bulkPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkPaymentModalLabel">Memo Update </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="memoId" class="form-label">Memo ID</label>
                    <input type="text" class="form-control" id="memoId" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Selected Lorry Receipts:</label>
                    <ul class="selected-lr-list" id="selectedLrList"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processBulkPayment()">OK</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal for Voucher Payment -->
<div class="modal fade" id="voucherPaymentModal" tabindex="-1" aria-labelledby="voucherPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Voucher Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="voucherPaymentBody">
                <!-- Dynamic content will be inserted here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" onclick="submitVoucherPayment()">Submit Payment</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Bulk Payment Button styles */
    #bulkPaymentBtn {
        padding: 12px 24px;
        font-size: 16px;
        transition: all 0.3s ease;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    #bulkPaymentBtn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }

    /* Bulk Payment Modal Styles */
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
</style>

<script>
// Select all checkbox functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const rowCheckboxes = document.querySelectorAll('.rowCheckbox');

    // Handle select all checkbox
    selectAllCheckbox.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkPaymentBtn(); // Update bulk payment button visibility
    });

    // Handle individual row checkboxes
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // If any checkbox is unchecked, uncheck the select all checkbox
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                // If all checkboxes are checked, check the select all checkbox
                const allChecked = Array.from(rowCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }

            // Update both buttons' visibility
            updateBulkPaymentButtonVisibility();
            updateVoucherPaymentButtonVisibility();
        });
    });

    // Function to get selected row IDs
    window.getSelectedLorryReceipts = function() {
        return Array.from(rowCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
    };

    // Function to handle bulk payment
    window.handleBulkPayment = function() {
        const selectedIds = getSelectedLorryReceipts();
        if (selectedIds.length > 0) {
            // Show the bulk payment modal
            const modal = new bootstrap.Modal(document.getElementById('bulkPaymentModal'));
            const lrList = document.getElementById('selectedLrList');
            
            // Clear existing list
            lrList.innerHTML = '';
            
            // Create a map of LR IDs to their numbers
            const lrNumbers = new Map();
            @foreach($lrs as $lr)
                lrNumbers.set({{ $lr->id }}, '{{ $lr->lr_number }}');
            @endforeach

            // Add selected LR numbers to the list
            selectedIds.forEach(id => {
                const li = document.createElement('li');
                const lrNumber = lrNumbers.get(Number(id)) || 'Unknown LR';
                li.textContent = 'LR Number: ' + lrNumber;
                lrList.appendChild(li);
            });
            
            modal.show();
        }
    };

    // Function to process bulk payment
    window.processBulkPayment = function() {
        const selectedIds = getSelectedLorryReceipts();
        const memoId = document.getElementById('memoId').value;
        
        if (!memoId) {
            alert('Please enter a Memo ID');
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('memo_id', memoId);
        
        // Add each selected ID as a separate array element
        selectedIds.forEach(id => {
            formData.append('lr_ids[]', id);
        });

        // Send AJAX request
        fetch('/lorry_receipts/bulk-payment', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Memo ID updated successfully for selected lorry receipts');
                // Refresh the page to show updated LR numbers
                location.reload();
            } else {
                alert(data.message || 'Failed to update LR numbers');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating LR numbers');
        });
    };

    // Function to process voucher payment
    window.processVoucherPayment = function() {
        const selectedIds = getSelectedLorryReceipts();
        const voucherId = document.getElementById('voucherId').value;
        
        if (!voucherId) {
            alert('Please enter a Voucher ID');
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('voucher_id', voucherId);
        
        // Add each selected ID as a separate array element
        selectedIds.forEach(id => {
            formData.append('lr_ids[]', id);
        });

        // Send AJAX request
        fetch('/lorry_receipts/voucher-payment', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Voucher ID updated successfully for selected lorry receipts');
                // Refresh the page to show updated LR numbers
                location.reload();
            } else {
                alert(data.message || 'Failed to update LR numbers');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating LR numbers');
        });
    };

    // Function to update bulk payment button visibility
    const updateBulkPaymentButtonVisibility = function() {
        const selectedCheckboxes = document.querySelectorAll('.rowCheckbox:checked');
        const bulkPaymentBtn = document.getElementById('bulkPaymentBtn');
        bulkPaymentBtn.classList.toggle('d-none', selectedCheckboxes.length === 0);
    };

    // Function to update voucher payment button visibility
    const updateVoucherPaymentButtonVisibility = function() {
        const selectedCheckboxes = document.querySelectorAll('.rowCheckbox:checked');
        const voucherPaymentBtn = document.getElementById('VocherPaymentBtn');
        voucherPaymentBtn.classList.toggle('d-none', selectedCheckboxes.length === 0);
    };

    // Add event listeners to all row checkboxes
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkPaymentButtonVisibility();
            updateVoucherPaymentButtonVisibility();
        });
    });

    // Initial check
    updateBulkPaymentButtonVisibility();
    updateVoucherPaymentButtonVisibility();
});

</script>

<script>
//     function handleVoucherPayment() {
//     const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
//     const modalBody = document.getElementById('voucherPaymentBody');
//     modalBody.innerHTML = '';

//     if (checkboxes.length === 0) {
//         alert("Please select at least one Lorry Receipt.");
//         return;
//     }

//     checkboxes.forEach((checkbox, index) => {
//         const lrNumber = checkbox.getAttribute('data-lr');
//         const truckNumber = checkbox.getAttribute('data-truck');

//         modalBody.innerHTML += `
//             <div class="mb-3 border-bottom pb-2">
//                 <strong>${index + 1}. LR Number:</strong> ${lrNumber}<br>
//                 <strong>Truck Number:</strong> ${truckNumber}
//                 <input type="number" class="form-control mt-2" name="amounts[]" placeholder="Enter Amount" required>
//                 <input type="hidden" name="lr_numbers[]" value="${lrNumber}">
//             </div>
//         `;
//     });

//     const modal = new bootstrap.Modal(document.getElementById('voucherPaymentModal'));
//     modal.show();
// }
// function handleVoucherPayment() {
//     const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
//     const modalBody = document.getElementById('voucherPaymentBody');
//     modalBody.innerHTML = '';

//     if (checkboxes.length === 0) {
//         alert("Please select at least one Lorry Receipt.");
//         return;
//     }

//     // Collect all truck numbers
//     const truckNumbers = Array.from(checkboxes).map(cb => cb.getAttribute('data-truck'));

//     // Check if all truck numbers are the same
//     const firstTruck = truckNumbers[0];
//     const allSameTruck = truckNumbers.every(truck => truck === firstTruck);

//     if (!allSameTruck) {
//         alert("Selected Lorry Receipts belong to different Truck Numbers. Please select LRs with the same truck.");
//         return;
//     }

//     // Build modal content
//     checkboxes.forEach((checkbox, index) => {
//         const lrNumber = checkbox.getAttribute('data-lr');
//         const truckNumber = checkbox.getAttribute('data-truck');

//         modalBody.innerHTML += `
//             <div class="mb-3 border-bottom pb-2">
//                 <strong>${index + 1}. LR Number:</strong> ${lrNumber}<br>
//                 <strong>Truck Number:</strong> ${truckNumber}
//                 <input type="number" class="form-control mt-2" name="amounts[]" placeholder="Enter Amount" required>
//                 <input type="hidden" name="lr_numbers[]" value="${lrNumber}">
//             </div>
//         `;
//     });

//     // Show modal if validation passed
//     const modal = new bootstrap.Modal(document.getElementById('voucherPaymentModal'));
//     modal.show();
// }
// function handleVoucherPayment() {
//     const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
//     const modalBody = document.getElementById('voucherPaymentBody');
//     modalBody.innerHTML = '';

//     if (checkboxes.length === 0) {
//         alert("Please select at least one Lorry Receipt.");
//         return;
//     }

//     // Collect truck numbers
//     const truckNumbers = Array.from(checkboxes).map(cb => cb.getAttribute('data-truck'));
//     const firstTruck = truckNumbers[0];
//     const allSameTruck = truckNumbers.every(truck => truck === firstTruck);

//     if (!allSameTruck) {
//         alert("Selected Lorry Receipts belong to different Truck Numbers. Please select LRs with the same truck.");
//         return;
//     }

//     // Header: show truck number once at top-right
//     modalBody.innerHTML += `
//         <div class="d-flex justify-content-between align-items-center mb-3">
//             <h5>Selected Lorry Receipts</h5>
//             <div><strong>Truck Number:</strong> ${firstTruck}</div>
//         </div>
//     `;

//     // Build LR list with amount input
//     checkboxes.forEach((checkbox, index) => {
//         const lrNumber = checkbox.getAttribute('data-lr');

//         modalBody.innerHTML += `
//             <div class="mb-3 border-bottom pb-2">
//                 <strong>${index + 1}. LR Number:</strong> ${lrNumber}
//                 <input type="number" class="form-control mt-2" name="amounts[]" placeholder="Enter Amount" required>
//                 <input type="hidden" name="lr_numbers[]" value="${lrNumber}">
//             </div>
//         `;
//     });

//     const modal = new bootstrap.Modal(document.getElementById('voucherPaymentModal'));
//     modal.show();
// }
function handleVoucherPayment() {
    const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
    const modalBody = document.getElementById('voucherPaymentBody');
    

    modalBody.innerHTML = '';

   


    if (checkboxes.length === 0) {
        alert("Please select at least one Lorry Receipt.");
        return;
    }

//     const paymentStatus = checkboxes.getAttribute('data-payment-status') || 'pending';
// if (paymentStatus.toLowerCase() === 'pending') {
//     alert("Please create Freight Booking first.");
//     return;
// }

    // Collect truck numbers
    const truckNumbers = Array.from(checkboxes).map(cb => cb.getAttribute('data-truck'));
    const firstTruck = truckNumbers[0];
    const allSameTruck = truckNumbers.every(truck => truck === firstTruck);

    if (!allSameTruck) {
        alert("Selected Lorry Receipts belong to different Truck Numbers. Please select LRs with the same truck.");
        return;
    }

    // Header: show truck number once at top-right
    modalBody.innerHTML += `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Selected Lorry Receipts</h5>
            <div><strong>Truck Number:</strong> ${firstTruck}</div>
        </div>
    `;

    // Show LR Number + balance amount (no input here)
    checkboxes.forEach((checkbox, index) => {
        const lrNumber = checkbox.getAttribute('data-lr');
        const balance = checkbox.getAttribute('data-balance') || 0;

        modalBody.innerHTML += `
            <div class="mb-3 border-bottom pb-2">
                <strong>${index + 1}. LR Number:</strong> ${lrNumber}<br>
                <strong>Balance Amount:</strong> ₹${parseFloat(balance).toFixed(2)}
                <input type="hidden" name="lr_numbers[]" value="${lrNumber}">
            </div>
        `;
    });

    // Add one final input for "Enter Amount"
    modalBody.innerHTML += `
        <div class="mt-4">
            <label><strong>Enter Payment Amount:</strong></label>
            <input type="number" class="form-control" name="total_amount" placeholder="Enter Total Amount" required>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('voucherPaymentModal'));
    modal.show();
}



    function showLRDetails(lrId) {
        const modal = new bootstrap.Modal(document.getElementById('lrDetailsModal'));
        const contentDiv = document.getElementById('lrDetailsContent');

        // Fetch LR details from API
        fetch(`/api/lorry-receipts/${lrId}/details`)
            .then(response => response.json())
            .then(lr => {
                // Format the details content
                const html = `
                    <div class="lr-details-container">
                        <div class="lr-details-section">
                            <h5>Trip & Parties</h5>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Transporter</div>
                                <div class="lr-details-value">${lr.transporter?.name || 'N/A'}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Truck</div>
                                <div class="lr-details-value">${lr.truck?.truck_number || 'N/A'}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Customer</div>
                                <div class="lr-details-value">${lr.consignee?.name || 'N/A'}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">LR Number</div>
                                <div class="lr-details-value">${lr.lr_number}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Delivery Status</div>
                                <div class="lr-details-value">${lr.delivery_status}</div>
                            </div>
                        </div>

                        <div class="lr-details-section">
                            <h5>Route & Dates</h5>
                            <div class="lr-details-row">
                                <div class="lr-details-label">GR Date</div>
                                <div class="lr-details-value">${lr.gr_date}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Source City</div>
                                <div class="lr-details-value">${lr.source_city}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Source State</div>
                                <div class="lr-details-value">${lr.source_state}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Destination City</div>
                                <div class="lr-details-value">${lr.destination_city}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Destination State</div>
                                <div class="lr-details-value">${lr.destination_state}</div>
                            </div>
                        </div>

                        <div class="lr-details-section">
                            <h5>Goods & Charges</h5>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Lane</div>
                                <div class="lr-details-value">${lr.lane}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">No. of Units</div>
                                <div class="lr-details-value">${lr.no_of_units}</div>
                            </div>
                            <div class="lr-details-row">
                                <div class="lr-details-label">Amount</div>
                                <div class="lr-details-value">${lr.amount}</div>
                            </div>
                        </div>
                    </div>
                `;

                contentDiv.innerHTML = html;
                modal.show();
            })
            .catch(error => {
                console.error('Error fetching LR details:', error);
                contentDiv.innerHTML = '<div class="alert alert-danger">Error loading details. Please try again.</div>';
                modal.show();
            });
    }
</script>

@endsection
