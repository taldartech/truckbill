@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">
                <i class="fas fa-receipt"></i> Lorry Receipts Management
            </h4>
            <div class="d-flex gap-2">
                <a href="{{ route('lorry_receipts.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Lorry Receipt
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Floating Action Buttons for Business Operations -->
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

            <div class="table-responsive">
                <table id="lorryReceiptsTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Sr. No</th>
                            <th>
                                LR Number
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Search LR Number..." id="search-lr-number">
                            </th>
                            <th>
                                Customer
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Search Customer..." id="search-customer">
                            </th>
                            <th>
                                Consignor
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Search Consignor..." id="search-consignor">
                            </th>
                            <th>
                                Consignee
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Search Consignee..." id="search-consignee">
                            </th>
                            <th>
                                Status
                                <select class="form-select form-select-sm mt-1" id="search-status">
                                    <option value="">All Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Delivered">Delivered</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="In-Progress">In-Progress</option>
                                </select>
                            </th>
                            <th>GR Date</th>
                            <th>
                                Truck Number
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Search Truck..." id="search-truck">
                            </th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#lorryReceiptsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("lorry_receipts.data") }}',
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'lr_number', name: 'lr_number' },
            { data: 'customer_name', name: 'customer.name' },
            { data: 'consignor_name', name: 'consignor.name' },
            { data: 'consignee_name', name: 'consignee.name' },
            { data: 'status_badge', name: 'delivery_status' },
            { data: 'gr_date_formatted', name: 'gr_date' },
            { data: 'truck_number', name: 'truck.truck_number' },
            { data: 'weight', name: 'weight' },
            { data: 'amount_formatted', name: 'amount' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm'
            }
        ],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: "No lorry receipts found",
            zeroRecords: "No matching lorry receipts found"
        },
        stateSave: true,
        scrollX: true
    });

    // Individual column search
    $('#search-lr-number').on('keyup', function() {
        table.column(1).search(this.value).draw();
    });

    $('#search-customer').on('keyup', function() {
        table.column(2).search(this.value).draw();
    });

    $('#search-consignor').on('keyup', function() {
        table.column(3).search(this.value).draw();
    });

    $('#search-consignee').on('keyup', function() {
        table.column(4).search(this.value).draw();
    });

    $('#search-status').on('change', function() {
        table.column(5).search(this.value).draw();
    });

    $('#search-truck').on('keyup', function() {
        table.column(7).search(this.value).draw();
    });

    // Checkbox functionality for business operations
    $('#selectAllCheckbox').on('change', function() {
        $('.rowCheckbox').prop('checked', this.checked);
        updateFloatingButtons();
    });

    $(document).on('change', '.rowCheckbox', function() {
        updateFloatingButtons();
    });

    function updateFloatingButtons() {
        const checkedBoxes = $('.rowCheckbox:checked');
        if (checkedBoxes.length > 0) {
            $('#VocherPaymentBtn, #bulkPaymentBtn').removeClass('d-none');
        } else {
            $('#VocherPaymentBtn, #bulkPaymentBtn').addClass('d-none');
        }
    }
});

// Business operation functions (preserve existing functionality)
function handleVoucherPayment() {
    // Existing voucher payment logic
    console.log('Voucher payment functionality');
}

function handleBulkPayment() {
    // Existing bulk payment logic
    console.log('Bulk payment functionality');
}

function showLRDetails(id) {
    // Existing LR details functionality
    console.log('Show LR details for ID:', id);
}
</script>

@endsection
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
    window.submitVoucherPayment = function() {
        try {
            const paymentType = document.getElementById('payment_type');
            const paymentMode = document.getElementById('payment_mode');
            const totalAmountInput = document.getElementById('totalAmountInput');
            const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
            const submitButton = document.querySelector('.btn-success[onclick="submitVoucherPayment()"]');

            if (!paymentType || !paymentType.value) {
                alert('Please select payment type');
                return;
            }
            if (!paymentMode || !paymentMode.value) {
                alert('Please select payment mode');
                return;
            }
            if (!totalAmountInput || !totalAmountInput.value) {
                alert('Please enter payment amount');
                return;
            }
            if (checkboxes.length === 0) {
                alert('Please select at least one Lorry Receipt');
                return;
            }

            // Get allocated amounts from the modal
            const allocatedInputs = document.querySelectorAll('[name^="allocated_amounts["]');
            const allocatedAmounts = Array.from(allocatedInputs).map(input => parseFloat(input.value) || 0);

            // Validate that we have the same number of allocated amounts as selected LRs
            if (allocatedAmounts.length !== checkboxes.length) {
                alert('Allocated amounts do not match selected Lorry Receipts');
                return;
            }

            // Validate total amount
            const totalAmount = allocatedAmounts.reduce((sum, amount) => sum + amount, 0);
            if (totalAmount <= 0) {
                alert('Please enter valid amounts for all selected Lorry Receipts');
                return;
            }

            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';

            // Create form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("voucher_payments.bulkPayment") }}';
            form.className = 'd-none';

            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            // Add payment details
            const inputs = [
                { name: 'payment_type', value: paymentType.value },
                { name: 'payment_mode', value: paymentMode.value },
                { name: 'reference_transaction_id', value: document.getElementById('reference_transaction_id')?.value || '' },
                { name: 'remark', value: document.getElementById('remark')?.value || '' },
                { name: 'company_id', value: '{{ auth()->user()->company_id }}' }
            ];

            // Add all form inputs
            inputs.forEach(input => {
                const inputElement = document.createElement('input');
                inputElement.type = 'hidden';
                inputElement.name = input.name;
                inputElement.value = input.value;
                form.appendChild(inputElement);
            });

            // Process each selected Lorry Receipt with its Freight Booking and amount
            Array.from(checkboxes).forEach((checkbox, index) => {
                const lorryReceiptId = checkbox.value;
                const freightBookingId = checkbox.getAttribute('data-freight_booking_id');
                const allocatedAmount = allocatedAmounts[index];

                if (!freightBookingId || allocatedAmount <= 0) {
                    return;
                }

                // Add Lorry Receipt ID
                const lrInput = document.createElement('input');
                lrInput.type = 'hidden';
                lrInput.name = 'lorry_receipts[]';
                lrInput.value = lorryReceiptId;
                form.appendChild(lrInput);

                // Add Freight Booking ID
                const freightInput = document.createElement('input');
                freightInput.type = 'hidden';
                freightInput.name = 'freight_bookings[' + lorryReceiptId + ']';
                freightInput.value = freightBookingId;
                form.appendChild(freightInput);

                // Add amount for this LR
                const amountInput = document.createElement('input');
                amountInput.type = 'hidden';
                amountInput.name = 'amounts[' + lorryReceiptId + ']';
                amountInput.value = allocatedAmount;
                form.appendChild(amountInput);
            });

            // Append form and submit
            document.body.appendChild(form);
            form.submit();

        } catch (error) {
            console.error('Error in submitVoucherPayment:', error);
            alert('An error occurred while processing the payment. Please try again.');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Submit Payment';
            }
        }
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

function handleVoucherPayment() {
    const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
    const modalBody = document.getElementById('voucherPaymentBody');
    
    modalBody.innerHTML = '';

    if (checkboxes.length === 0) {
        alert("Please select at least one Lorry Receipt.");
        return;
    }

    // Check if any LR has no voucher number
    const hasNoVoucher = Array.from(checkboxes).some(cb => cb.getAttribute('data-voucher_number') === '0');
    if (hasNoVoucher) {
        alert("Please create Freight Booking first for selected Lorry Receipts.");
        return;
    }

    // Check for LRs with zero balance and collect their numbers
    const zeroBalanceLRs = Array.from(checkboxes).filter(cb => {
        const balance = parseFloat(cb.getAttribute('data-balance') || 0);
        return balance <= 0;
    }).map(cb => cb.getAttribute('data-lr'));

    if (zeroBalanceLRs.length > 0) {
        const message = `The following Lorry Receipts have zero balance. No payment is required:
        ${zeroBalanceLRs.join(', ')}`;
        alert(message);
        return;
    }

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

    // Collect LR data for allocation
    const lrData = Array.from(checkboxes).map(checkbox => ({
        lrNumber: checkbox.getAttribute('data-lr'),
        balance: parseFloat(checkbox.getAttribute('data-balance') || 0)
    }));

    // Show LR Number + balance amount and voucher number
    lrData.forEach((item, index) => {
        const voucherNumber = checkboxes[index].getAttribute('data-voucher_number');
        modalBody.innerHTML += `
            <div class="mb-3 border-bottom pb-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div><strong>${index + 1}. LR Number:</strong> ${item.lrNumber}</div>
                    <div><strong>Voucher Number:</strong> ${voucherNumber}</div>
                </div>
                <div class="mb-2">
                    <strong>Balance Amount:</strong> ₹${item.balance.toFixed(2)}
                </div>
                <div>
                    <strong>Allocated Amount:</strong> ₹<span class="allocated-amount" data-lr="${item.lrNumber}">0.00</span>
                </div>
                <input type="hidden" class="allocated-input" data-lr="${item.lrNumber}" name="allocated_amounts[${index}]" value="0">
            </div>
        `;
    });

    // Add payment type, payment mode, and optional fields
    modalBody.innerHTML += `
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="payment_type" class="form-label">Payment Type *</label>
                <select name="payment_type" id="payment_type" class="form-select" required>
                    <option value="">Select Payment Type</option>
                    @foreach($paymentTypes as $paymentType)
                        <option value="{{ $paymentType->name }}">
                            {{ $paymentType->name }}
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback">Please select a payment type.</div>
            </div>
            <div class="col-md-6">
                <label for="payment_mode" class="form-label">Payment Mode *</label>
                <select name="payment_mode" id="payment_mode" class="form-select" required>
                    <option value="">Select Payment Mode</option>
                    @foreach($paymentModes as $paymentMode)
                        <option value="{{ $paymentMode->name }}">
                            {{ $paymentMode->name }}
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback">Please select a payment mode.</div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="reference_transaction_id" class="form-label">Reference Transaction ID</label>
                <input type="text" class="form-control" name="reference_transaction_id" id="reference_transaction_id">
            </div>
            <div class="col-md-6">
                <label for="remark" class="form-label">Remark</label>
                <input type="text" class="form-control" name="remark" id="remark">
            </div>
        </div>

        <div class="mt-4">
            <label><strong>Enter Payment Amount:</strong></label>
            <input type="number" class="form-control" id="totalAmountInput" name="total_amount" placeholder="Enter Total Amount" required>
            <input type="hidden" id="totalAllocatedAmount" name="amount_given" value="0">
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        </div>
    `;

    // Calculate the max total balance amount
const maxTotalBalance = lrData.reduce((sum, item) => sum + item.balance, 0);

    // Add allocation logic
    const totalAmountInput = document.getElementById('totalAmountInput');
    totalAmountInput.addEventListener('input', () => {

        let enteredAmount = parseFloat(totalAmountInput.value || 0);

// Validation: prevent amount greater than total balance
if (enteredAmount > maxTotalBalance) {
    alert(`Payment amount cannot be greater than total balance ₹${maxTotalBalance.toFixed(2)}`);
    enteredAmount = maxTotalBalance;
    totalAmountInput.value = enteredAmount.toFixed(2);
}

        let remaining = parseFloat(totalAmountInput.value || 0);
        let totalAllocated = 0;

        lrData.forEach((item) => {
            let allocate = 0;
            if (remaining > 0) {
                if (remaining >= item.balance) {
                    allocate = item.balance;
                    remaining -= item.balance;
                } else {
                    allocate = remaining;
                    remaining = 0;
                }
            }

            totalAllocated += allocate;

            const span = document.querySelector(`.allocated-amount[data-lr="${item.lrNumber}"]`);
            const hiddenInput = document.querySelector(`.allocated-input[data-lr="${item.lrNumber}"]`);
            span.textContent = allocate.toFixed(2);
            hiddenInput.value = allocate.toFixed(2);
        });

        // Update hidden total allocated input
        document.getElementById('totalAllocatedAmount').value = totalAllocated.toFixed(2);
    });

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