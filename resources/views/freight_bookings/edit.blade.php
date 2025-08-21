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
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="good_type" class="form-label">Goods Type</label>
                    <select class="form-select @error('good_type') is-invalid @enderror" name="good_type" id="good_type">
                        <option value="">Select Goods Type</option>
                        @foreach($goodsTypes as $goodsType)
                            <option value="{{ $goodsType->name }}" {{ old('good_type', $freight_booking->good_type) == $goodsType->name ? 'selected' : '' }}>
                                {{ $goodsType->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
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
                    <label for="balance_amount" class="form-label">Balance Amount *</label>
                    <input type="number" step="0.01" class="form-control @error('balance_amount') is-invalid @enderror" name="balance_amount" id="balance_amount" value="{{ old('balance_amount', $freight_booking->balance_amount) }}" required readonly style="background-color: #f8f9fa;">
                    <small class="form-text text-muted">
                        <i class="fas fa-calculator"></i> Auto-calculated: TR Amount - TDS Amount
                    </small>
                    @error('balance_amount') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!-- Hidden delivered field with default value 0 -->
                <input type="hidden" name="is_delivered" id="is_delivered" value="{{ old('is_delivered', $freight_booking->is_delivered) }}">
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

<!-- Modal for Memo Update -->
<div class="modal fade" id="memoUpdateModal" tabindex="-1" aria-labelledby="memoUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="memoUpdateModalLabel">Memo Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="memoUpdateForm">
                    <div class="mb-3">
                        <label for="memoId" class="form-label">Memo ID *</label>
                        <input type="text" class="form-control" id="memoId" name="memo_id" required>
                    </div>
                    <div class="mb-3">
                        <label for="lr_id" class="form-label">LR Number *</label>
                        <select name="lr_id" id="lr_id" class="form-select" required>
                            <option value="">Select LR Number</option>
                            @foreach($lrs as $lr)
                                <option value="{{ $lr->id }}" {{ $freight_booking->lr_id == $lr->id ? 'selected' : '' }}>
                                    {{ $lr->lr_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processMemoUpdate()">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to handle memo update
    window.handleMemoUpdate = function() {
        const modal = new bootstrap.Modal(document.getElementById('memoUpdateModal'));
        // Set the selected LR
        document.getElementById('lr_id').value = {{ $freight_booking->lr_id }};
        modal.show();
    };

    // Function to process memo update
    window.processMemoUpdate = function() {
        const memoId = document.getElementById('memoId').value;
        const lrId = document.getElementById('lr_id').value;
        
        if (!memoId) {
            alert('Please enter a Memo ID');
            return;
        }

        if (!lrId) {
            alert('Please select an LR Number');
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('memo_id', memoId);
        formData.append('lr_ids[]', lrId);

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
                alert('Memo ID updated successfully');
                location.reload();
            } else {
                alert(data.message || 'Failed to update memo');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating memo');
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
    });
</script>
@endsection
