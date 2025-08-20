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
    <div class="col-sm-6"><h3 class="mb-0">Lorry Receipts Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('lorry_receipts.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Lorry Receipts List</h3>
        </div>
        <div class="card-body">
            <table id="lorryReceiptsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="selectAllCheckbox"></th>
                        <th style="width: 80px;">Sr. No</th>
                        <th>LR Number</th>
                        <th>Customer</th>
                        <th>Consignor</th>
                        <th>Consignee</th>
                        <th>Status</th>
                        <th>GR Date</th>
                        <th>Truck Number</th>
                        <th>Weight</th>
                        <th>Amount</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Floating Action Buttons for Bulk Operations -->
<div class="floating-buttons" style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
    <button id="bulkPaymentBtn" class="btn btn-warning mb-2" style="display: none;" onclick="showBulkPaymentModal()">
        <i class="bi bi-file-earmark-text"></i> Memo Update
    </button>
    <button id="voucherPaymentBtn" class="btn btn-success" style="display: none;" onclick="showVoucherPaymentModal()">
        <i class="bi bi-receipt"></i> Voucher Payment
    </button>
</div>

<!-- Modal for Bulk Payment -->
<div class="modal fade" id="bulkPaymentModal" tabindex="-1" aria-labelledby="bulkPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkPaymentModalLabel">Memo Update</h5>
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

<style>
    .floating-buttons button {
        display: block;
        width: 100%;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    .floating-buttons button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
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
    // Initialize DataTable
    var table = $('#lorryReceiptsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("lorry_receipts.index") }}',
            type: 'GET'
        },
        columns: [
            {
                data: 'checkbox',
                name: 'checkbox',
                orderable: false,
                searchable: false,
                width: '50px',
                className: 'text-center'
            },
            {
                data: 'sr_no',
                name: 'sr_no',
                width: '80px',
                className: 'text-center',
                orderable: false,
                searchable: false
            },
            {
                data: 'lr_number',
                name: 'lr_number'
            },
            {
                data: 'customer_name',
                name: 'customer.name'
            },
            {
                data: 'consignor_name',
                name: 'consignor.name'
            },
            {
                data: 'consignee_name',
                name: 'consignee.name'
            },
            {
                data: 'status_badge',
                name: 'delivery_status'
            },
            {
                data: 'gr_date_formatted',
                name: 'gr_date'
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
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        scrollX: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm'
            }
        ],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: "No lorry receipts found",
            zeroRecords: "No matching lorry receipts found"
        },
        drawCallback: function(settings) {
            // Reinitialize tooltips after each draw
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Update bulk operation buttons
            updateBulkOperationButtons();
        }
    });

    // Select all checkbox functionality
    $('#selectAllCheckbox').on('change', function() {
        $('.rowCheckbox').prop('checked', this.checked);
        updateBulkOperationButtons();
    });

    // Individual checkbox functionality
    $(document).on('change', '.rowCheckbox', function() {
        var totalCheckboxes = $('.rowCheckbox').length;
        var checkedCheckboxes = $('.rowCheckbox:checked').length;
        
        $('#selectAllCheckbox').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#selectAllCheckbox').prop('checked', checkedCheckboxes === totalCheckboxes);
        
        updateBulkOperationButtons();
    });

    function updateBulkOperationButtons() {
        var checkedBoxes = $('.rowCheckbox:checked');
        var bulkPaymentBtn = $('#bulkPaymentBtn');
        var voucherPaymentBtn = $('#voucherPaymentBtn');
        
        if (checkedBoxes.length > 0) {
            bulkPaymentBtn.show();
            voucherPaymentBtn.show();
        } else {
            bulkPaymentBtn.hide();
            voucherPaymentBtn.hide();
        }
    }
});

// Bulk Payment Modal functionality
function showBulkPaymentModal() {
    const checkedBoxes = document.querySelectorAll('.rowCheckbox:checked');
    const selectedLrList = document.getElementById('selectedLrList');
    
    if (checkedBoxes.length === 0) {
        alert('Please select at least one lorry receipt');
        return;
    }
    
    // Clear previous list
    selectedLrList.innerHTML = '';
    
    // Add selected LRs to the list
    checkedBoxes.forEach(checkbox => {
        const lrNumber = checkbox.getAttribute('data-lr');
        const truckNumber = checkbox.getAttribute('data-truck');
        
        const listItem = document.createElement('li');
        listItem.innerHTML = `<strong>LR:</strong> ${lrNumber} | <strong>Truck:</strong> ${truckNumber}`;
        selectedLrList.appendChild(listItem);
    });
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('bulkPaymentModal'));
    modal.show();
}

// Process bulk payment
function processBulkPayment() {
    const memoId = document.getElementById('memoId').value.trim();
    
    if (!memoId) {
        alert('Please enter a Memo ID');
        return;
    }
    
    const checkedBoxes = document.querySelectorAll('.rowCheckbox:checked');
    const selectedIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('No lorry receipts selected');
        return;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('#bulkPaymentModal .btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;
    
    // Make AJAX request
    fetch('{{ route("lorry_receipts.bulkPayment") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            memo_id: memoId,
            lorry_receipt_ids: selectedIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Bulk payment processed successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the bulk payment');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        bootstrap.Modal.getInstance(document.getElementById('bulkPaymentModal')).hide();
    });
}

// Voucher Payment Modal functionality
function showVoucherPaymentModal() {
    const checkedBoxes = document.querySelectorAll('.rowCheckbox:checked');
    const voucherPaymentBody = document.getElementById('voucherPaymentBody');
    
    if (checkedBoxes.length === 0) {
        alert('Please select at least one lorry receipt');
        return;
    }
    
    // Clear previous content
    voucherPaymentBody.innerHTML = '';
    
    // Create form for each selected LR
    checkedBoxes.forEach((checkbox, index) => {
        const lrNumber = checkbox.getAttribute('data-lr');
        const truckNumber = checkbox.getAttribute('data-truck');
        const freightBookingId = checkbox.getAttribute('data-freight_booking_id');
        const balance = parseFloat(checkbox.getAttribute('data-balance')) || 0;
        const voucherNumber = checkbox.getAttribute('data-voucher_number') || '';
        const paymentStatus = checkbox.getAttribute('data-payment-status') || 'pending';
        
        const formSection = document.createElement('div');
        formSection.className = 'mb-3';
        formSection.innerHTML = `
            <h6>LR: ${lrNumber} | Truck: ${truckNumber}</h6>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Balance Amount</label>
                    <input type="number" class="form-control" value="${balance}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Payment Amount</label>
                    <input type="number" class="form-control payment-amount" data-lr-id="${checkbox.value}" 
                           data-freight-booking-id="${freightBookingId}" max="${balance}" step="0.01" required>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <label class="form-label">Voucher Number</label>
                    <input type="text" class="form-control voucher-number" value="${voucherNumber}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Payment Status</label>
                    <span class="badge bg-${paymentStatus === 'delivered' ? 'success' : 'warning'}">${paymentStatus}</span>
                </div>
            </div>
        `;
        voucherPaymentBody.appendChild(formSection);
    });
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('voucherPaymentModal'));
    modal.show();
}

// Submit voucher payment
function submitVoucherPayment() {
    const paymentInputs = document.querySelectorAll('.payment-amount');
    const voucherInputs = document.querySelectorAll('.voucher-number');
    const payments = [];
    
    let isValid = true;
    
    paymentInputs.forEach((input, index) => {
        const amount = parseFloat(input.value);
        const lrId = input.getAttribute('data-lr-id');
        const freightBookingId = input.getAttribute('data-freight-booking-id');
        const voucherNumber = voucherInputs[index].value.trim();
        
        if (!amount || amount <= 0) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
            payments.push({
                lorry_receipt_id: lrId,
                freight_booking_id: freightBookingId,
                amount: amount,
                voucher_number: voucherNumber
            });
        }
    });
    
    if (!isValid) {
        alert('Please enter valid payment amounts for all selected lorry receipts');
        return;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('#voucherPaymentModal .btn-success');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;
    
    // Make AJAX request
    fetch('{{ route("lorry_receipts.bulkVoucherPayment") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            payments: payments
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Voucher payments processed successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the voucher payments');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        bootstrap.Modal.getInstance(document.getElementById('voucherPaymentModal')).hide();
    });
}

// Show LR Details function
function showLRDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('lrDetailsModal'));
    const contentDiv = document.getElementById('lrDetailsContent');
    
    // Show loading state
    contentDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading details...</p>
        </div>
    `;
    
    // Fetch LR details
    fetch(`/api/lorry-receipts/${id}/details`)
        .then(response => response.json())
        .then(lr => {
            const html = `
                <div class="lr-details-container">
                    <div class="lr-details-section">
                        <h5>Basic Information</h5>
                        <div class="lr-details-row">
                            <div class="lr-details-label">LR Number</div>
                            <div class="lr-details-value">${lr.lr_number}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">GR Date</div>
                            <div class="lr-details-value">${lr.gr_date}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Status</div>
                            <div class="lr-details-value">${lr.delivery_status}</div>
                        </div>
                    </div>
                    
                    <div class="lr-details-section">
                        <h5>Parties</h5>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Customer</div>
                            <div class="lr-details-value">${lr.customer ? lr.customer.name : 'N/A'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Consignor</div>
                            <div class="lr-details-value">${lr.consignor ? lr.consignor.name : 'N/A'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Consignee</div>
                            <div class="lr-details-value">${lr.consignee ? lr.consignee.name : 'N/A'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Transporter</div>
                            <div class="lr-details-value">${lr.transporter ? lr.transporter.name : 'N/A'}</div>
                        </div>
                    </div>
                    
                    <div class="lr-details-section">
                        <h5>Transport Details</h5>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Truck Number</div>
                            <div class="lr-details-value">${lr.truck ? lr.truck.truck_number : 'N/A'}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Goods</div>
                            <div class="lr-details-value">${lr.goods}</div>
                        </div>
                        <div class="lr-details-row">
                            <div class="lr-details-label">Weight</div>
                            <div class="lr-details-value">${lr.weight}</div>
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
@endpush

@endsection
