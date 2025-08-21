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
<div class="card card-info card-outline mb-4" style="max-width: 1200px; margin: 0 auto;">
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

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">LR & Transport Information</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="lr_id" class="form-label">LR Number *</label>
                        <select name="lr_id" id="lr_id" class="form-select @error('lr_id') is-invalid @enderror" required>
                            <option value="">Select LR Number</option>
                            @foreach($lorry_receipt as $lr)
                                <option value="{{ $lr->id }}" {{ old('lr_id', $freight_booking->lr_id) == $lr->id ? 'selected' : '' }}>
                                    {{ $lr->lr_number }}
                                </option>
                            @endforeach
                        </select>
                        @error('lr_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="transporter_name" class="form-label">Transporter *</label>
                        <input type="text" name="transporter_name" id="transporter_name" class="form-control" readonly value="{{ $preFilled['transporter_name'] ?? '' }}">
                        @error('transporter_name') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="truck_number" class="form-label">Truck Number *</label>
                        <input type="text" name="truck_number" id="truck_number" class="form-control" readonly value="{{ $preFilled['truck_number'] ?? '' }}">
                        @error('truck_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Freight & Financial Information</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tr_fr_rate" class="form-label">TR Freight Rate *</label>
                        <input type="number" step="0.01" class="form-control @error('tr_fr_rate') is-invalid @enderror" name="tr_fr_rate" id="tr_fr_rate" value="{{ old('tr_fr_rate', $freight_booking->tr_fr_rate) }}" required>
                        @error('tr_fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="tr_amount" class="form-label">TR Amount *</label>
                        <input type="number" step="0.01" class="form-control @error('tr_amount') is-invalid @enderror" name="tr_amount" id="tr_amount" value="{{ old('tr_amount', $freight_booking->tr_amount) }}" required>
                        @error('tr_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="advance_amount" class="form-label">Advance Amount *</label>
                        <input type="number" step="0.01" class="form-control @error('advance_amount') is-invalid @enderror" name="advance_amount" id="advance_amount" value="{{ old('advance_amount', $freight_booking->advance_amount) }}" required>
                        @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="balance_amount" class="form-label">Balance Amount *</label>
                        <input type="number" step="0.01" class="form-control @error('balance_amount') is-invalid @enderror" name="balance_amount" id="balance_amount" value="{{ old('balance_amount', $freight_booking->balance_amount) }}" required>
                        @error('balance_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="tds_amount" class="form-label">TDS Amount</label>
                        <input type="number" step="0.01" class="form-control @error('tds_amount') is-invalid @enderror" name="tds_amount" id="tds_amount" value="{{ old('tds_amount', $freight_booking->tds_amount) }}">
                        @error('tds_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
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
                </div>
            </div>

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Deduction & Expense Information</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="shortage" class="form-label">Shortage</label>
                        <input type="number" step="0.01" class="form-control @error('shortage') is-invalid @enderror" name="shortage" id="shortage" value="{{ old('shortage', $freight_booking->shortage) }}">
                        @error('shortage') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="detention" class="form-label">Detention</label>
                        <input type="number" step="0.01" class="form-control @error('detention') is-invalid @enderror" name="detention" id="detention" value="{{ old('detention', $freight_booking->detention) }}">
                        @error('detention') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="deviation" class="form-label">Deviation</label>
                        <input type="number" step="0.01" class="form-control @error('deviation') is-invalid @enderror" name="deviation" id="deviation" value="{{ old('deviation', $freight_booking->deviation) }}">
                        @error('deviation') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="misc_expense" class="form-label">Misc. Expense</label>
                        <input type="number" step="0.01" class="form-control @error('misc_expense') is-invalid @enderror" name="misc_expense" id="misc_expense" value="{{ old('misc_expense', $freight_booking->misc_expense) }}">
                        @error('misc_expense') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="pan_verified" class="form-label">PAN Verified *</label>
                        <input type="hidden" name="pan_verified" id="pan_verified" value="{{ old('pan_verified', $freight_booking->pan_verified) }}" required>
                        <div class="form-control">
                            {{ old('pan_verified', $freight_booking->pan_verified) ? 'Yes' : 'No' }}
                        </div>
                        <small class="text-muted d-block">Read-only field</small>
                        @error('pan_verified') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Damage & Shortage Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="damage_qty" class="form-label">Damage Quantity</label>
                        <input type="number" step="0.01" class="form-control @error('damage_qty') is-invalid @enderror" name="damage_qty" id="damage_qty" value="{{ old('damage_qty', $freight_booking->damage_qty) }}">
                        @error('damage_qty') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="damage_rate" class="form-label">Damage Rate</label>
                        <input type="number" step="0.01" class="form-control @error('damage_rate') is-invalid @enderror" name="damage_rate" id="damage_rate" value="{{ old('damage_rate', $freight_booking->damage_rate) }}">
                        @error('damage_rate') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="damage_amount" class="form-label">Damage Amount</label>
                        <input type="number" step="0.01" class="form-control @error('damage_amount') is-invalid @enderror" name="damage_amount" id="damage_amount" value="{{ old('damage_amount', $freight_booking->damage_amount) }}">
                        @error('damage_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="shortage_qty" class="form-label">Shortage Quantity</label>
                        <input type="number" step="0.01" class="form-control @error('shortage_qty') is-invalid @enderror" name="shortage_qty" id="shortage_qty" value="{{ old('shortage_qty', $freight_booking->shortage_qty) }}">
                        @error('shortage_qty') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="shortage_rate" class="form-label">Shortage Rate</label>
                        <input type="number" step="0.01" class="form-control @error('shortage_rate') is-invalid @enderror" name="shortage_rate" id="shortage_rate" value="{{ old('shortage_rate', $freight_booking->shortage_rate) }}">
                        @error('shortage_rate') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="shortage_amount" class="form-label">Shortage Amount</label>
                        <input type="number" step="0.01" class="form-control @error('shortage_amount') is-invalid @enderror" name="shortage_amount" id="shortage_amount" value="{{ old('shortage_amount', $freight_booking->shortage_amount) }}">
                        @error('shortage_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Status & Additional Information</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="is_delivered" class="form-label">Delivered?</label>
                        @if(Auth::user()->role !== 'super_admin' && old('is_delivered', $freight_booking->is_delivered) == '1')
                            <input type="text" class="form-control" value="Yes" readonly>
                            <input type="hidden" name="is_delivered" value="1">
                            <small class="text-muted d-block">Read-only field</small>
                        @else
                            <select name="is_delivered" id="is_delivered" class="form-select @error('is_delivered') is-invalid @enderror">
                                <option value="0" {{ old('is_delivered', $freight_booking->is_delivered) == '0' ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_delivered', $freight_booking->is_delivered) == '1' ? 'selected' : '' }}>Yes</option>
                            </select>
                        @endif
                        @error('is_delivered') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="is_cancelled" class="form-label">Cancelled?</label>
                        @if(Auth::user()->role !== 'super_admin' && old('is_delivered', $freight_booking->is_delivered) == '1')
                            <input type="text" class="form-control" value="No" readonly>
                            <input type="hidden" name="is_cancelled" value="0">
                            <small class="text-muted d-block">Read-only field</small>
                        @else
                            <select name="is_cancelled" id="is_cancelled" class="form-select @error('is_cancelled') is-invalid @enderror">
                                <option value="0" {{ old('is_cancelled', $freight_booking->is_cancelled) == '0' ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_cancelled', $freight_booking->is_cancelled) == '1' ? 'selected' : '' }}>Yes</option>
                            </select>
                        @endif
                        @error('is_cancelled') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="linked_freight_booking_id" class="form-label">Linked Freight Booking</label>
                        <select name="linked_freight_booking_id" id="linked_freight_booking_id" class="form-select @error('linked_freight_booking_id') is-invalid @enderror" value="{{ old('linked_freight_booking_id', $freight_booking->linked_freight_booking_id) }}">
                            <option value="">None</option>
                            @foreach($linkedBookings as $fb)
                                <option value="{{ $fb->id }}" {{ old('linked_freight_booking_id') == $fb->id ? 'selected' : '' }}>
                                    #{{ $fb->id }} (LR: {{ $fb->lr ? $fb->lr->gr_number : '-' }})
                                </option>
                            @endforeach
                        </select>
                        @error('linked_freight_booking_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="remark" class="form-label">Remark</label>
                        <textarea name="remark" id="remark" class="form-control @error('remark') is-invalid @enderror" rows="3">{{ old('remark', $freight_booking->remark) }}</textarea>
                        @error('remark') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Section -->
        <div class="card p-3 bg-light">
            <div class="row"><div class="col"><h5 class="mb-3">Quick Actions</h5></div></div>
            <div class="row g-2">
                <div class="col-md-4">
                    <button class="btn btn-success w-100 mb-2" type="submit">💾 Update Freight Booking</button>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('freight_bookings.index') }}" class="btn btn-outline-dark w-100 mb-2">📄 Back to List</a>
                </div>
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

    // Add event listener to is_delivered select
    document.getElementById('is_delivered').addEventListener('change', function() {
        if (this.value === '1') {
            handleMemoUpdate();
        }
    });
</script>
@endsection
