@extends('layouts.app')

@section('content')
<style>
/* Prevent form expansion on validation errors */
.form-control, .form-select {
    min-height: 38px;
    box-sizing: border-box;
}

.text-danger {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    word-wrap: break-word;
}

/* Ensure consistent column widths */
.row .col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

/* Prevent card from expanding */
.card {
    overflow: hidden;
}

/* Consistent spacing for form groups */
.mb-3 {
    margin-bottom: 1rem !important;
}
</style>

<!-- LR Information Card -->
<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title h5">Lorry Receipt Information</div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>LR Number:</strong><br>
                    <span class="text-primary">{{ $freight_booking->lr ? $freight_booking->lr->lr_number : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>Transporter:</strong><br>
                    <span>{{ $freight_booking->transporter_name ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>Transporter Type:</strong><br>
                    @if($freight_booking->lr && $freight_booking->lr->transporter)
                        @if($freight_booking->lr->transporter->is_owner)
                            <span class="badge bg-primary text-white fs-6">Truck Owner</span>
                        @else
                            <span class="badge bg-info text-white fs-6">Agency</span>
                        @endif
                    @else
                        <span>N/A</span>
                    @endif
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>TDS Percentage:</strong><br>
                    <span class="badge bg-warning text-dark fs-6">{{ number_format($freight_booking->transporter_tds_percentage ?? 0, 2) }}%</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>Truck:</strong><br>
                    <span>{{ $freight_booking->lr && $freight_booking->lr->truck ? $freight_booking->lr->truck->truck_number : 'N/A' }}</span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-2">
                    <strong>Customer:</strong><br>
                    <span>{{ $freight_booking->lr && $freight_booking->lr->customer ? $freight_booking->lr->customer->name : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-2">
                    <strong>Consignor:</strong><br>
                    <span>{{ $freight_booking->lr && $freight_booking->lr->consignor ? $freight_booking->lr->consignor->name : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-2">
                    <strong>Consignee:</strong><br>
                    <span>{{ $freight_booking->lr && $freight_booking->lr->consignee ? $freight_booking->lr->consignee->name : 'N/A' }}</span>
                </div>
            </div>
        </div>
        
        <!-- Goods Type Details Section -->
        <div class="row">
            <div class="col-md-3">
                <div class="mb-2">
                    <strong>Goods Type:</strong><br>
                    <span class="badge bg-secondary text-white fs-6">{{ $freight_booking->lr ? $freight_booking->lr->good_type : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-2">
                    <strong>Weight:</strong><br>
                    <span class="badge bg-info text-white fs-6">{{ $freight_booking->lr ? number_format($freight_booking->lr->weight ?? 0, 2) : '0.00' }} kg</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-2">
                    <strong>LR Amount:</strong><br>
                    <span class="badge bg-success text-white fs-6">₹{{ $freight_booking->lr ? number_format($freight_booking->lr->amount ?? 0, 2) : '0.00' }}</span>
                </div>
            </div>
        </div>
        
        <!-- Route Details Section -->
        <div class="row">
            <div class="col-md-6">
                <div class="mb-2">
                    <strong>Route:</strong><br>
                    <span class="text-muted">
                        {{ $freight_booking->lr ? $freight_booking->lr->source_city : 'N/A' }}, {{ $freight_booking->lr ? $freight_booking->lr->source_state : 'N/A' }}
                        <i class="fas fa-arrow-right mx-2"></i>
                        {{ $freight_booking->lr ? $freight_booking->lr->destination_city : 'N/A' }}, {{ $freight_booking->lr ? $freight_booking->lr->destination_state : 'N/A' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <!--begin::Header-->
    <div class="card-header">
        <div class="card-title h4">Edit Freight Booking</div>
    </div>
    <!--end::Header-->
    <!--begin::Form-->
    <form action="{{ route('freight_bookings.update', $freight_booking->id) }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        @method('PUT')
        @php
            $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
            $userCompanyId = auth()->user()->company_id ?? null;
        @endphp
        
        <div class="card-body">
            {{-- Company field hidden as requested --}}
            @if($canEditCompany)
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="company_id" class="form-label">Company *</label>
                        <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id', $freight_booking->company_id) == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
            @endif

            <!-- Financial Information Section -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="tr_fr_rate" class="form-label">TR Freight Rate *</label>
                    <input type="number" step="0.01" min="1" max="20000" class="form-control @error('tr_fr_rate') is-invalid @enderror" name="tr_fr_rate" id="tr_fr_rate" value="{{ old('tr_fr_rate', $freight_booking->tr_fr_rate) }}" placeholder="Enter freight rate (₹1 - ₹20,000 per ton)" required>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Rate must be between ₹1 and ₹20,000 per ton
                    </small>
                    @error('tr_fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="tr_amount" class="form-label">TR Amount *</label>
                    <input type="number" step="0.01" min="0.01" class="form-control @error('tr_amount') is-invalid @enderror" name="tr_amount" id="tr_amount" value="{{ old('tr_amount', $freight_booking->tr_amount) }}" placeholder="Auto-calculated (editable)" required>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Auto-calculated: TR Freight Rate × Weight. Must be greater than ₹0.
                    </small>
                    @error('tr_amount') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="tds_amount" class="form-label">TDS Amount *</label>
                    <input type="number" step="0.01" class="form-control @error('tds_amount') is-invalid @enderror" name="tds_amount" id="tds_amount" value="{{ old('tds_amount', $freight_booking->tds_amount) }}" readonly style="background-color: #f8f9fa;">
                    <small class="form-text text-muted">
                        <i class="fas fa-percentage"></i> Auto-calculated based on transporter TDS percentage
                    </small>
                    @error('tds_amount') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!-- Hidden advance amount field with default value 0 -->
                <input type="hidden" name="advance_amount" id="advance_amount" value="0">
                <!-- Hidden memo_id field -->
                <input type="hidden" name="memo_id" id="memo_id" value="{{ $freight_booking->lr && $freight_booking->lr->memo_id ? $freight_booking->lr->memo_id : '' }}">
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="eway_bill_number" class="form-label">E-Way Bill Number</label>
                    <div class="input-group">
                        <input type="text" class="form-control @error('eway_bill_number') is-invalid @enderror" name="eway_bill_number" id="eway_bill_number" value="{{ old('eway_bill_number', $freight_booking->eway_bill_number) }}" placeholder="Enter E-way bill number">
                        <a href="https://ewaybillgst.gov.in/Login.aspx" target="_blank" class="btn btn-outline-info" title="Check E-way Bill Status">
                            <i class="fas fa-external-link-alt"></i> Check Status
                        </a>
                    </div>
                    @error('eway_bill_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="pan_verified" class="form-label">PAN Verified *</label>
                    <input type="hidden" name="pan_verified" id="pan_verified" value="{{ old('pan_verified', $freight_booking->pan_verified) }}" required>
                    <input type="text" class="form-control" readonly value="{{ old('pan_verified', $freight_booking->pan_verified) ? 'Yes' : 'No' }}" style="background-color: #f8f9fa;">
                    <small class="form-text text-muted">
                        <i class="fas fa-lock"></i> Read-only field from transporter data
                    </small>
                    @error('pan_verified') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="advance_amount_main" class="form-label">Advance Amount</label>
                    <input type="number" step="0.01" class="form-control" id="advance_amount_main" value="{{ old('advance_amount', $freight_booking->advance_amount) }}" readonly style="background-color: #f8f9fa;">
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Read-only field
                    </small>
                </div>
                <div class="col-md-4">
                    <label for="balance_amount" class="form-label">Balance Amount *</label>
                    <input type="number" step="0.01" class="form-control @error('balance_amount') is-invalid @enderror" name="balance_amount" id="balance_amount" value="{{ old('balance_amount', $freight_booking->balance_amount) }}" required readonly style="background-color: #f8f9fa;">
                    <small class="form-text text-muted">
                        <i class="fas fa-calculator"></i> Auto-calculated: TR Amount - TDS Amount
                    </small>
                    @error('balance_amount') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>

            <!-- Additional Charges & Status Section -->
            <div class="row">
                <div class="col-12">
                    <h5 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-plus-circle"></i> Additional Charges & Status
                    </h5>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Delivery Status</label>
                    <div class="d-flex gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_delivered" name="is_delivered" value="1" {{ old('is_delivered', $freight_booking->is_delivered) ? 'checked' : '' }} {{ ($freight_booking->is_delivered && $freight_booking->lr && $freight_booking->lr->memo_id) ? 'disabled' : '' }}>
                            <label class="form-check-label" for="is_delivered">
                                <i class="fas fa-truck"></i> Delivered
                                @if($freight_booking->is_delivered && $freight_booking->lr && $freight_booking->lr->memo_id)
                                    <small class="text-muted ms-2">
                                        <i class="fas fa-lock"></i> Locked (Memo ID: {{ $freight_booking->lr->memo_id }})
                                    </small>
                                @endif
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_cancelled" name="is_cancelled" value="1" {{ old('is_cancelled', $freight_booking->is_cancelled) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_cancelled">
                                <i class="fas fa-times-circle"></i> Cancelled
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div id="additional-charges-section" style="display: {{ $freight_booking->is_delivered ? 'block' : 'none' }};">
                <div class="row mb-2">
                    <div class="col-md-2">
                        <label for="detention" class="form-label">Detention</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('detention') is-invalid @enderror" name="detention" id="detention" value="{{ old('detention', $freight_booking->detention) }}" placeholder="Enter detention amount">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> ≥ 0 and < TR Amount
                        </small>
                        @error('detention') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label for="deviation" class="form-label">Deviation</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('deviation') is-invalid @enderror" name="deviation" id="deviation" value="{{ old('deviation', $freight_booking->deviation) }}" placeholder="Enter deviation amount">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> ≥ 0 and < TR Amount
                        </small>
                        @error('deviation') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label for="misc_expense" class="form-label">Misc. Expense</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('misc_expense') is-invalid @enderror" name="misc_expense" id="misc_expense" value="{{ old('misc_expense', $freight_booking->misc_expense) }}" placeholder="Enter misc expense">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> ≥ 0 and < TR Amount
                        </small>
                        @error('misc_expense') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="damage_amount" class="form-label">Damage Amount</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('damage_amount') is-invalid @enderror" name="damage_amount" id="damage_amount" value="{{ old('damage_amount', $freight_booking->damage_amount) }}" placeholder="Enter damage amount">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Must be ≥ 0 and < TR Amount
                        </small>
                        @error('damage_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="shortage_amount" class="form-label">Shortage Amount</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('shortage_amount') is-invalid @enderror" name="shortage_amount" id="shortage_amount" value="{{ old('shortage_amount', $freight_booking->shortage_amount) }}" placeholder="Enter shortage amount">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Must be ≥ 0 and < TR Amount
                        </small>
                        @error('shortage_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <!-- Hidden weight input and transporter TDS percentage -->
            <input type="hidden" id="weight" value="{{ $freight_booking->lr ? $freight_booking->lr->weight : 0 }}">
            <input type="hidden" id="transporter_tds_percentage" name="transporter_tds_percentage" value="{{ old('transporter_tds_percentage', $freight_booking->transporter_tds_percentage) }}">

         
            

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="remark" class="form-label">Remark</label>
                    <textarea name="remark" id="remark" class="form-control @error('remark') is-invalid @enderror" rows="3" placeholder="Enter any additional remarks or notes">{{ old('remark', $freight_booking->remark) }}</textarea>
                    @error('remark') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>

        </div>
        
        <!--end::Footer-->
        <div class="card-footer">
            <div>
                <button class="btn btn-success me-2" type="submit">
                    <i class="bi bi-check-circle"></i> Update Freight Booking
                </button>
                <a href="{{ route('freight_bookings.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Modal for Memo ID Entry -->
<div class="modal fade" id="memoIdModal" tabindex="-1" aria-labelledby="memoIdModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="memoIdModalLabel">Enter Memo ID for Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> A Memo ID is required to mark this freight booking as delivered.
                </div>
                <form id="memoIdForm">
                    <div class="mb-3">
                        <label for="deliveryMemoId" class="form-label">Memo ID *</label>
                        <input type="text" class="form-control" id="deliveryMemoId" name="memo_id" required placeholder="Enter memo ID">
                        <small class="form-text text-muted">This will be saved to the lorry receipt record.</small>
                    </div>
                </form>
            </div>
            <div class="modal-body" id="memoModalError" style="display: none;">
                <div class="alert alert-danger" id="memoErrorMessage"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveMemoId()" id="saveMemoBtn">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    Save Memo ID
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to save memo ID for delivery
    window.saveMemoId = function() {
        const memoId = document.getElementById('deliveryMemoId').value.trim();
        const saveBtn = document.getElementById('saveMemoBtn');
        const spinner = saveBtn.querySelector('.spinner-border');
        const errorDiv = document.getElementById('memoModalError');
        const errorMessage = document.getElementById('memoErrorMessage');
        
        if (!memoId) {
            errorMessage.textContent = 'Please enter a Memo ID';
            errorDiv.style.display = 'block';
            return;
        }

        // Show loading state
        saveBtn.disabled = true;
        spinner.style.display = 'inline-block';
        errorDiv.style.display = 'none';

        // Create form data
        const formData = new FormData();
        formData.append('memo_id', memoId);
        formData.append('lr_ids[]', {{ $freight_booking->lr_id }});

        // Send AJAX request to update memo ID
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
                // Close modal and enable delivered toggle
                const modal = bootstrap.Modal.getInstance(document.getElementById('memoIdModal'));
                modal.hide();
                
                // Show additional charges sections
                document.getElementById('additional-charges-section').style.display = 'block';
                
                // Show success message
                alert('Memo ID saved successfully. Freight booking marked as delivered.');
                
                // Optionally reload to reflect changes
                location.reload();
            } else {
                errorMessage.textContent = data.message || 'Failed to save memo ID';
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.textContent = 'An error occurred while saving memo ID';
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            // Reset loading state
            saveBtn.disabled = false;
            spinner.style.display = 'none';
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Get all required elements with null checks
        const weightElement = document.getElementById('weight');
        const weight = weightElement ? parseFloat(weightElement.value || 0) : 0;
        const rateInput = document.getElementById('tr_fr_rate');
        const amountInput = document.getElementById('tr_amount');
        const trAmountInput = document.getElementById('tr_amount');
        const balanceAmountInput = document.getElementById('balance_amount');
        const tdsAmountInput = document.getElementById('tds_amount');

        // Get TDS percentage from freight booking's transporter_tds_percentage field
        const tdsPercentageElement = document.getElementById('transporter_tds_percentage');
        const tdsPercentage = tdsPercentageElement ? parseFloat(tdsPercentageElement.value || 0) : 0;

        // Function to calculate TDS and Balance amounts
        function calculateTdsAndBalance() {
            const trAmount = parseFloat(trAmountInput ? trAmountInput.value : 0) || 0;
            
            // Calculate TDS Amount based on TR Amount
            const tdsAmount = (trAmount * (tdsPercentage / 100)).toFixed(2);
            if (tdsAmountInput) {
                tdsAmountInput.value = tdsAmount;
            }
            
            // Calculate Balance Amount (TR Amount - TDS Amount)
            const balance = trAmount - parseFloat(tdsAmount);
            if (balanceAmountInput) {
                balanceAmountInput.value = balance.toFixed(2);
            }
        }

        // Add null checks for event listeners
        if (rateInput) {
            rateInput.addEventListener('input', function () {
                const rate = parseFloat(rateInput.value || 0);
                const amount = (weight * rate).toFixed(2);
                if (amountInput) {
                    amountInput.value = amount;
                }
                
                // Recalculate TDS and Balance when TR Amount changes
                calculateTdsAndBalance();
            });
        }

        // Update TDS and balance when TR Amount changes (manual input)
        if (trAmountInput) {
            trAmountInput.addEventListener('input', calculateTdsAndBalance);
        }

        // Calculate initial TDS and balance if values exist
        calculateTdsAndBalance();

        // Validation for additional charges fields
        function validateAdditionalCharges() {
            const trAmount = parseFloat(trAmountInput ? trAmountInput.value : 0) || 0;
            const fieldsToValidate = ['detention', 'deviation', 'misc_expense', 'damage_amount', 'shortage_amount'];
            
            fieldsToValidate.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field) {
                    field.addEventListener('input', function() {
                        const value = parseFloat(field.value || 0);
                        const isValid = value >= 0 && value < trAmount;
                        
                        if (!isValid && value > 0) {
                            field.classList.add('is-invalid');
                            field.classList.remove('is-valid');
                            
                            // Show custom validation message
                            let errorDiv = field.parentNode.querySelector('.validation-error');
                            if (!errorDiv) {
                                errorDiv = document.createElement('div');
                                errorDiv.className = 'validation-error text-danger small mt-1';
                                field.parentNode.appendChild(errorDiv);
                            }
                            errorDiv.textContent = `Value must be ≥ 0 and < TR Amount (₹${trAmount.toFixed(2)})`;
                        } else {
                            field.classList.remove('is-invalid');
                            field.classList.add('is-valid');
                            
                            // Remove error message
                            const errorDiv = field.parentNode.querySelector('.validation-error');
                            if (errorDiv) {
                                errorDiv.remove();
                            }
                        }
                    });
                }
            });
        }

        // Initialize validation
        validateAdditionalCharges();

        // Re-validate when TR Amount changes
        if (trAmountInput) {
            trAmountInput.addEventListener('input', function() {
                validateAdditionalCharges();
            });
        }

        // Handle is_delivered toggle
        const isDeliveredToggle = document.getElementById('is_delivered');
        if (isDeliveredToggle) {
            // Check if toggle should be frozen (memo_id exists)
            const currentMemoId = '{{ $freight_booking->lr && $freight_booking->lr->memo_id ? $freight_booking->lr->memo_id : "" }}';
            const isCurrentlyDelivered = {{ $freight_booking->is_delivered ? 'true' : 'false' }};
            
            // If delivered and memo_id exists, freeze the toggle
            if (isCurrentlyDelivered && currentMemoId) {
                isDeliveredToggle.disabled = true;
            }
            
            // Show additional charges if currently delivered
            if (isCurrentlyDelivered) {
                document.getElementById('additional-charges-section').style.display = 'block';
            }
            
            isDeliveredToggle.addEventListener('change', function() {
                // Prevent unchecking if memo_id exists
                if (!this.checked && currentMemoId) {
                    this.checked = true;
                    alert('Cannot unmark as delivered - Memo ID has been set. Contact administrator if changes are needed.');
                    return;
                }
                
                if (this.checked) {
                    // Show additional charges immediately when delivered is checked
                    document.getElementById('additional-charges-section').style.display = 'block';
                    
                    // Check if memo_id already exists
                    if (!currentMemoId) {
                        // Show memo ID modal
                        const modal = new bootstrap.Modal(document.getElementById('memoIdModal'));
                        modal.show();
                        
                        // Reset toggle if user cancels
                        document.getElementById('memoIdModal').addEventListener('hidden.bs.modal', function() {
                            if (!document.getElementById('deliveryMemoId').value) {
                                isDeliveredToggle.checked = false;
                                document.getElementById('additional-charges-section').style.display = 'none';
                            }
                        });
                    }
                } else {
                    // Hide additional charges when not delivered
                    document.getElementById('additional-charges-section').style.display = 'none';
                }
            });
        }
    });
</script>
@endsection
