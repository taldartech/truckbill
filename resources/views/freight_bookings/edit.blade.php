{{-- 

@extends('layouts.app')

@section('content')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const lrSelect = document.getElementById('lr_id');
        const transporterSelect = document.getElementById('transporter_id');
        const truckSelect = document.getElementById('truck_id');

        // Function to update trucks based on selected transporter
        function updateTrucks() {
            const selectedTransporter = transporterSelect.value;
            if (!selectedTransporter) {
                truckSelect.innerHTML = '<option value="">Select Truck</option>';
                return;
            }

            // Make AJAX request to get trucks for selected transporter
            fetch(`/api/transporters/${selectedTransporter}/trucks`)
                .then(response => response.json())
                .then(trucks => {
                    let options = '<option value="">Select Truck</option>';
                    trucks.forEach(truck => {
                        options += `<option value="${truck.id}">${truck.truck_number}</option>`;
                    });
                    truckSelect.innerHTML = options;
                })
                .catch(error => console.error('Error:', error));
        }

        // Function to load LR details
        function loadLRDetails() {
            const selectedLR = lrSelect.value;
            if (!selectedLR) {
                document.getElementById('transporter_id').value = '';
                document.getElementById('truck_id').value = '';
                return;
            }

            // Make AJAX request to get LR details
            fetch(`/api/lorry_receipts/${selectedLR}`)
                .then(response => response.json())
                .then(lr => {
                    // Set transporter
                    const transporterSelect = document.getElementById('transporter_id');
                    const truckSelect = document.getElementById('truck_id');
                    
                    // Get transporter name
                    fetch(`/api/transporters/${lr.transporter_id}`)
                        .then(response => response.json())
                        .then(transporter => {
                            transporterSelect.value = `${transporter.name}`;
                        });
                    
                    // Get truck number
                    fetch(`/api/trucks/${lr.truck_id}`)
                        .then(response => response.json())
                        .then(truck => {
                            truckSelect.value = `${truck.truck_number}`;
                        });
                })
                .catch(error => console.error('Error:', error));
        }

        // Update trucks when transporter changes
        transporterSelect.addEventListener('change', updateTrucks);
        
        // Load LR details when LR changes
        lrSelect.addEventListener('change', loadLRDetails);
    });
</script>
@php
    $lrs = \App\Models\LorryReceipt::all();
    $transporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)->get();
@endphp

<div class="container mt-4">
    <h2>Edit Freight Booking</h2>
    <form method="POST" action="{{ route('freight_bookings.update', $freight_booking->id) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="lr_id" class="form-label">LR Number *</label>
                <select name="lr_id" id="lr_id" class="form-control" required>
                    <option value="">Select LR Number</option>
                    @foreach($lrs as $lr)
                        <option value="{{ $lr->id }}" {{ $freight_booking->lr_id == $lr->id ? 'selected' : '' }}>
                            {{ $lr->lr_number }} 
                        </option>
                    @endforeach
                </select>
                @error('lr_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="transporter_id" class="form-label">Transporter *</label>
                <input type="text" name="transporter_id" id="transporter_id" class="form-control" readonly value="{{ $transporter ? $transporter->name : '' }}">
                @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="truck_id" class="form-label">Truck *</label>
                <input type="text" name="truck_id" id="truck_id" class="form-control" readonly value="{{ $truck ? $truck->truck_number : '' }}">
                @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="tr_fr_rate" class="form-label">TR Freight Rate *</label>
                <input type="number" step="0.01" class="form-control" name="tr_fr_rate" id="tr_fr_rate" value="{{ old('tr_fr_rate', $freight_booking->tr_fr_rate) }}" required>
                @error('tr_fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="tr_amount" class="form-label">TR Amount *</label>
                <input type="number" step="0.01" class="form-control" name="tr_amount" id="tr_amount" value="{{ old('tr_amount', $freight_booking->tr_amount) }}" required>
                @error('tr_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="advance_amount" class="form-label">Advance Amount *</label>
                <input type="number" step="0.01" class="form-control" name="advance_amount" id="advance_amount" value="{{ old('advance_amount', $freight_booking->advance_amount) }}" required>
                @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="balance_amount" class="form-label">Balance Amount *</label>
                <input type="number" step="0.01" class="form-control" name="balance_amount" id="balance_amount" value="{{ old('balance_amount', $freight_booking->balance_amount) }}" required>
                @error('balance_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="tds_amount" class="form-label">TDS Amount</label>
                <input type="number" step="0.01" class="form-control" name="tds_amount" id="tds_amount" value="{{ old('tds_amount', $freight_booking->tds_amount) }}">
                @error('tds_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="shortage" class="form-label">Shortage</label>
                <input type="number" step="0.01" class="form-control" name="shortage" id="shortage" value="{{ old('shortage', $freight_booking->shortage        ) }}">
                @error('shortage') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="good_type" class="form-label">Goods Type</label>
                <select class="form-select" name="good_type" id="good_type">
                    <option value="">Select Goods Type</option>
                    @foreach($goodsTypes as $goodsType)
                        <option value="{{ $goodsType->name }}" {{ old('good_type', $freight_booking->good_type) == $goodsType->name ? 'selected' : '' }}>
                            {{ $goodsType->name }}
                        </option>
                    @endforeach
                </select>
                @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="pan_verified" class="form-label">PAN Verified *</label>
                <input type="hidden" name="pan_verified" id="pan_verified" value="{{ old('pan_verified', $freight_booking->pan_verified) }}" required>
                <div class="form-control">
                    {{ old('pan_verified', $freight_booking->pan_verified) ? 'Yes' : 'No' }}
                </div>
                @error('pan_verified') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="detention" class="form-label">Detention</label>
                <input type="number" step="0.01" class="form-control" name="detention" id="detention" value="{{ old('detention', $freight_booking->detention) }}">
                @error('detention') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="deviation" class="form-label">Deviation</label>
                <input type="number" step="0.01" class="form-control" name="deviation" id="deviation" value="{{ old('deviation', $freight_booking->deviation) }}">
                @error('deviation') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="misc_expense" class="form-label">Misc. Expense</label>
                <input type="number" step="0.01" class="form-control" name="misc_expense" id="misc_expense" value="{{ old('misc_expense', $freight_booking->misc_expense) }}">
                @error('misc_expense') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="damage_qty" class="form-label">Damage Quantity</label>
                <input type="number" step="0.01" class="form-control" name="damage_qty" id="damage_qty" value="{{ old('damage_qty', $freight_booking->damage_qty) }}">
                @error('damage_qty') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="damage_rate" class="form-label">Damage Rate</label>
                <input type="number" step="0.01" class="form-control" name="damage_rate" id="damage_rate" value="{{ old('damage_rate', $freight_booking->damage_rate) }}">
                @error('damage_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="damage_amount" class="form-label">Damage Amount</label>
                <input type="number" step="0.01" class="form-control" name="damage_amount" id="damage_amount" value="{{ old('damage_amount', $freight_booking->damage_amount) }}">
                @error('damage_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="shortage_qty" class="form-label">Shortage Quantity</label>
                <input type="number" step="0.01" class="form-control" name="shortage_qty" id="shortage_qty" value="{{ old('shortage_qty', $freight_booking->shortage_qty) }}">
                @error('shortage_qty') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="shortage_rate" class="form-label">Shortage Rate</label>
                <input type="number" step="0.01" class="form-control" name="shortage_rate" id="shortage_rate" value="{{ old('shortage_rate', $freight_booking->shortage_rate) }}">
                @error('shortage_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="shortage_amount" class="form-label">Shortage Amount</label>
                <input type="number" step="0.01" class="form-control" name="shortage_amount" id="shortage_amount" value="{{ old('shortage_amount', $freight_booking->shortage_amount) }}">
                @error('shortage_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="remark" class="form-label">Remark</label>
                <textarea name="remark" id="remark" class="form-control">{{ old('remark') }}</textarea>
                @error('remark') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="is_delivered" class="form-label">Delivered?</label>
                @if(Auth::user()->role !== 'super_admin' && old('is_delivered', $freight_booking->is_delivered) == '1')
                    <div class="input-group">
                        <input type="text" class="form-control" value="Yes" readonly>
                        <input type="hidden" name="is_delivered" value="1">
                    </div>
                @else
                    <select name="is_delivered" id="is_delivered" class="form-select">
                        <option value="0" {{ old('is_delivered', $freight_booking->is_delivered) == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('is_delivered', $freight_booking->is_delivered) == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                @endif
            </div>
            <div class="col-md-3 mb-3">
                <label for="is_cancelled" class="form-label">Cancelled?</label>
                @if(Auth::user()->role !== 'super_admin' && old('is_delivered', $freight_booking->is_delivered) == '1')
                    <div class="input-group">
                        <input type="text" class="form-control" value="No" readonly>
                        <input type="hidden" name="is_cancelled" value="0">
                    </div>
                @else
                    <select name="is_cancelled" id="is_cancelled" class="form-select">
                        <option value="0" {{ old('is_cancelled', $freight_booking->is_cancelled) == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('is_cancelled', $freight_booking->is_cancelled) == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="linked_freight_booking_id" class="form-label">Linked Freight Booking</label>
                <select name="linked_freight_booking_id" id="linked_freight_booking_id" class="form-select" value="{{ old('linked_freight_booking_id', $freight_booking->linked_freight_booking_id) }}" >
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
        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('freight_bookings.index') }}" class="btn btn-secondary ms-2">Back to List</a>
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
@endsection --}}
@extends('layouts.app')

@section('content')



<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    <div class="row g-3">
         <div class="col-md-3 ps-4">
            <p><strong>LR Number:</strong> {{ $lorry_receipt->first()->lr_number }}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Transporter:</strong> {{ $lorry_receipt->first()->transporter->name }}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Truck:</strong> {{ $lorry_receipt->first()->truck->truck_number }}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Customer:</strong> {{ $lorry_receipt->first()->customer->name }}</p>
        </div>
        <div class="col-md-3 ps-4">
            <p><strong>Consignor:</strong> {{ $lorry_receipt->first()->consignor->name }}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Consignee:</strong> {{ $lorry_receipt->first()->consignee->name }}</p>
        </div>
        @if($lorry_receipt->first()->freight_booking_id)
        <div class="col-md-3">
            <div class="bg-danger text-white p-2 rounded">
                <p class="mb-0"><strong>Old Freight Number:</strong> {{ $lorry_receipt->first()->freight_booking_id }}</p>
            </div>
        </div>
        @endif
       
    </div>
</div>

@php
    $lrs = \App\Models\LorryReceipt::all();
    $transporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)->get();
@endphp


<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add New Freight Booking</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    @php
    $weight = $weight ?? 0; // default fallback
@endphp
    <form method="POST" action="{{ route('freight_bookings.update', $freight_booking->id) }}" class="needs-validation" novalidate="">
        @csrf
        @method('PUT')
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row mb-3">
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="lr_id" class="form-label">LR Number *</label>
                    <div class="input-group">
                        <input type="hidden" name="lr_id" id="lr_id" value="{{ isset($preFilled) ? $preFilled['lr_id'] : old('lr_id') }}" required>
                        <span class="input-group-text">{{ isset($preFilled) ? $preFilled['lr_number'] : '' }}</span>
                    </div>
                    @error('lr_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="transporter_id" class="form-label">Transporter *</label>
                    <div class="input-group">
                        <input type="hidden" name="transporter_id" value="{{ isset($preFilled) ? $preFilled['transporter_id'] : '' }}">
                        <input type="text" class="form-control" readonly value="{{ isset($preFilled) ? $preFilled['transporter_name'] : '' }}">
                    </div>
                    @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="truck_id" class="form-label">Truck *</label>
                    <div class="input-group">
                        <input type="hidden" name="truck_id" value="{{ isset($preFilled) ? $preFilled['truck_id'] : '' }}">
                        <input type="text" class="form-control" readonly value="{{ isset($preFilled) ? $preFilled['truck_number'] : '' }}">
                    </div>
                    @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
        </div>
        <div class="card mb-4 p-3 bg-light">
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="tr_fr_rate" class="form-label">TR Freight Rate *</label>
                <input type="number" step="0.01" class="form-control" name="tr_fr_rate" id="tr_fr_rate" value="{{ old('tr_fr_rate', $freight_booking->tr_fr_rate) }}" required>
                @error('tr_fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="tr_amount" class="form-label">TR Amount *</label>
                <input type="number" step="0.01" class="form-control" name="tr_amount" id="tr_amount" value="{{ old('tr_amount', $freight_booking->tr_amount) }}" required>
                @error('tr_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="advance_amount" class="form-label">Advance Amount *</label>
                <input type="number" step="0.01" class="form-control" name="advance_amount" id="advance_amount" value="{{ old('advance_amount', $freight_booking->advance_amount) }}">
                @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>

<!-- Hidden weight input (if needed for JS) -->
<input type="hidden" id="weight" value="{{ $weight }}">
            <div class="col-md-4 mb-3">
                <label for="balance_amount" class="form-label">Balance Amount *</label>
                <input type="number" step="0.01" class="form-control" name="balance_amount" id="balance_amount" value="{{ old('balance_amount', $freight_booking->balance_amount) }}" required readonly>
                @error('balance_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="tds_amount" class="form-label">TDS Amount *</label>
                <input type="number" step="0.01" class="form-control" name="tds_amount" id="tds_amount" value="{{ old('tds_amount', $freight_booking->tds_amount) }}" readonly>
                @error('tds_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
    <div class="card mb-4 p-3 bg-light">
        <div class="row">
            {{-- <div class="col-md-4 mb-3">
                <label for="shortage" class="form-label">Shortage</label>
                <input type="number" step="0.01" class="form-control" name="shortage" id="shortage" value="{{ old('shortage') }}">
                @error('shortage') <div class="text-danger">{{ $message }}</div> @enderror
            </div> --}}
            <div class="col-md-4 mb-3">
                <label for="good_type" class="form-label">Goods Type</label>
                <div class="input-group">
                    <input type="hidden" name="good_type" value="{{ isset($preFilled) ? $preFilled['goods_type'] : '' }}">
                    <input type="text" class="form-control" readonly value="{{ isset($preFilled) ? $preFilled['goods_type'] : '' }}">
                </div>
                @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="eway_bill_number" class="form-label">E-Way Bill Number</label>
                <input type="text" class="form-control" name="eway_bill_number" id="eway_bill_number" value="{{ old('eway_bill_number', $freight_booking->eway_bill_number) }}">
                @error('eway_bill_number') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="pan_verified" class="form-label">PAN Verified *</label>
                <input type="hidden" name="pan_verified" id="pan_verified" value="{{ isset($preFilled['pan_verified']) ? (int)$preFilled['pan_verified'] : (old('pan_verified') ? 1 : 0) }}" required>
                
                <div class="form-control">
                    {{ isset($preFilled['pan_verified']) && $preFilled['pan_verified'] ? 'Yes' : 'No' }}
                </div>
                @error('pan_verified') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            {{-- <div class="col-md-4 mb-3">
                <label for="detention" class="form-label">Detention</label>
                <input type="number" step="0.01" class="form-control" name="detention" id="detention" value="{{ old('detention') }}">
                @error('detention') <div class="text-danger">{{ $message }}</div> @enderror
            </div> --}}
        </div>
    
    
      
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="remark" class="form-label">Remark</label>
                <textarea name="remark" id="remark" class="form-control">{{ old('remark', $freight_booking->remark) }}</textarea>
                @error('remark') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="is_delivered" class="form-label">Delivered?</label>
                <select name="is_delivered" id="is_delivered" class="form-select">
                    <option value="0" {{ old('is_delivered', $freight_booking->is_delivered) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_delivered', $freight_booking->is_delivered) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label for="is_cancelled" class="form-label">Cancelled?</label>
                <select name="is_cancelled" id="is_cancelled" class="form-select">
                    <option value="0" {{ old('is_cancelled', $freight_booking->is_cancelled) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_cancelled', $freight_booking->is_cancelled) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>

<!-- Show Memo Number if exists -->
{{-- @if(!empty($preFilled['memo_id']))
<div class="alert alert-info mb-3">
    <strong>Memo ID:</strong> {{ $preFilled['memo_id'] }}
</div> --}}
{{-- @endif --}}
    </div>
    <!-- Extra Fields Card -->
<div id="deliveredDetails" class="card p-3 bg-light mb-3" style="display: none;">
    {{-- <p><strong>LR Number:</strong> {{ $preFilled['lr_number'] }}</p> --}}
    <p><strong>Memo Number:</strong> {{ $preFilled['memo_id'] }}</p>
    <div class="row mb-3">
        <div class="col-md-4 mb-3">
            <label for="detention" class="form-label">Detention</label>
            <input type="number" step="0.01" class="form-control" name="detention" id="detention" value="{{ old('detention', $freight_booking->detention) }}">
            @error('detention') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="deviation" class="form-label">Deviation</label>
            <input type="number" step="0.01" class="form-control" name="deviation" id="deviation" value="{{ old('deviation', $freight_booking->deviation) }}">
            @error('deviation') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="misc_expense" class="form-label">Misc. Expense</label>
            <input type="number" step="0.01" class="form-control" name="misc_expense" id="misc_expense" value="{{ old('misc_expense', $freight_booking->misc_expense) }}">
            @error('misc_expense') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="row mb-3">
        
        <div class="col-md-4 mb-3">
            <label for="damage_amount" class="form-label">Damage Amount</label>
            <input type="number" step="0.01" class="form-control" name="damage_amount" id="damage_amount" value="{{ old('damage_amount', $freight_booking->damage_amount) }}">
            @error('damage_amount') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="shortage_amount" class="form-label">Shortage Amount</label>
            <input type="number" step="0.01" class="form-control" name="shortage_amount" id="shortage_amount" value="{{ old('shortage_amount', $freight_booking->shortage_amount) }}">
            @error('shortage_amount') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

    </div>
  
</div>
        {{-- <div class="row"> --}}
            {{-- <div class="col-md-6 mb-3">
                <label for="linked_freight_booking_id" class="form-label">Linked Freight Booking</label>
                <select name="linked_freight_booking_id" id="linked_freight_booking_id" class="form-select">
                    <option value="">None</option>
                    @foreach($linkedBookings as $fb)
                        <option value="{{ $fb->id }}" {{ old('linked_freight_booking_id') == $fb->id ? 'selected' : '' }}>
                            #{{ $fb->id }} (LR: {{ $fb->lr ? $fb->lr->gr_number : '-' }})
                        </option>
                    @endforeach
                </select>
                @error('linked_freight_booking_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div> --}}
        {{-- </div> --}}
        <!--end::Row-->
        <div class="card p-3 bg-light">
            <div class="row"><div class="col"><h5 class="mb-3">Quick Actions</h5></div></div>
            <div class="row g-2">
                <div class="col-md-4">
                    <button class="btn btn-success w-100 mb-2" type="submit" name="action" value="save">💾 Update Form</button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-secondary w-100 mb-2" type="submit" name="action" value="save_and_pay" title="save the form and make voucher payment"> Save & Make Payment <span class="ms-1" data-bs-toggle="tooltip" title="save the form and make voucher payment">ℹ️</span></button>
                </div>
                
                <div class="col-md-4">
                    <a href="{{ route('freight_bookings.index') }}" class="btn btn-outline-dark w-100 mb-2">📄 Back to List</a>
                </div>
            </div>
        </div>
      </div>
      <!--end::Body-->
      <!--begin::Footer-->
      {{-- <div class="card-footer">
            <button class="btn btn-info" type="submit" name="action" value="save">Submit form</button>
            <button class="btn btn-primary" type="submit" name="action" value="save_and_pay">Save & Make Payment</button>
            <a href="{{ route('freight_bookings.index') }}" class="btn btn-info">Back to List</a>
      </div> --}}
      <input type="hidden" name="redirect_to_payment" value="0">
      <!--end::Footer-->
    </form>
</div>
<!-- Modal for Memo ID Entry -->
<div class="modal fade" id="bulkPaymentModal" tabindex="-1" aria-labelledby="bulkPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkPaymentModalLabel">Enter Memo ID</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="memoId" class="form-label">Memo ID</label>
                    <input type="text" class="form-control" id="memoId" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lorry Receipt:</label>
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
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const isDeliveredSelect = document.getElementById("is_delivered");
        const deliveredDetails = document.getElementById("deliveredDetails");
        const memoId = "{{ $preFilled['memo_id'] ?? '' }}";
        const lrId = "{{ $preFilled['lr_id'] ?? '' }}";
        const lrNumber = "{{ $preFilled['lr_number'] ?? '' }}";
    
        function toggleDeliveredDetails() {
            if (isDeliveredSelect.value === "1") {
                if (memoId) {
                    // Memo exists → show block
                    deliveredDetails.style.display = "block";
                } else {
                    // Memo missing → open modal
                    document.getElementById("selectedLrList").innerHTML =
                        `<li>${lrNumber}</li>`;
                    document.getElementById("memoId").value = "";
                    new bootstrap.Modal(document.getElementById("bulkPaymentModal")).show();
                }
            } else {
                deliveredDetails.style.display = "none";
            }
        }
    
        // Initial check
        toggleDeliveredDetails();
    
        // On change
        isDeliveredSelect.addEventListener("change", toggleDeliveredDetails);
    
        // Process Memo Entry
        window.processBulkPayment = function () {
            const memoValue = document.getElementById("memoId").value;
            if (!memoValue) {
                alert("Please enter Memo ID");
                return;
            }
    
            fetch("{{ route('lorry_receipts.bulkPayment') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    memo_id: memoValue,
                    lr_ids: [lrId]
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(err => console.error(err));
        };
    });
    </script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const weight = parseFloat(document.getElementById('weight').value || 0);
        const rateInput = document.getElementById('tr_fr_rate');
        const amountInput = document.getElementById('tr_amount');
        const trAmountInput = document.getElementById('tr_amount');
        const advanceAmountInput = document.getElementById('advance_amount');
        const balanceAmountInput = document.getElementById('balance_amount');
        const tdsAmountInput = document.getElementById('tds_amount');
        const tdsPercentage = parseFloat("{{ $tdsPercentage ?? 0 }}"); // already in %
        

        const panVerified = parseInt(document.getElementById('pan_verified').value) || 0;

        rateInput.addEventListener('input', function () {
            const rate = parseFloat(rateInput.value || 0);
            const amount = (weight * rate).toFixed(2);
            amountInput.value = amount;

            function calculateBalance() {
            const trAmount = parseFloat(trAmountInput.value) || 0;
            const advanceAmount = parseFloat(advanceAmountInput.value) || 0;
             // Prevent advance > TR
        if (advanceAmount > trAmount) {
            alert("Advance amount cannot be greater than TR amount.");
            advanceAmountInput.value = '';
            balanceAmountInput.value = trAmount.toFixed(2);
            tdsAmountInput.value = (trAmount * (tdsPercentage / 100)).toFixed(2);
          
            return;
        }

            // TDS
            const tdsAmount = (trAmount * (tdsPercentage / 100)).toFixed(2);
        tdsAmountInput.value = tdsAmount;


            const balance = trAmount - advanceAmount - tdsAmount;
            balanceAmountInput.value = balance.toFixed(2);
            
            // Get TDS percentages from PHP variables
            // const defaultPercentage = {{ $tdsPercentages['default'] / 100 }};
            // const agencyPercentage = {{ $tdsPercentages['agency'] / 100 }};
            // const ownerPercentage = {{ $tdsPercentages['owner'] / 100 }};
            
            // // Calculate TDS %
            // let tdsPercentage = defaultPercentage;
            // if (panVerified === 1) {
            //     tdsPercentage = IS_OWNER === 0 ? agencyPercentage : ownerPercentage;
            // }

            // // Calculate TDS Amount
            // const tdsAmountInput = document.getElementById('tds_amount');
            // const tdsAmount = (trAmount * tdsPercentage).toFixed(2);
            // tdsAmountInput.value = tdsAmount;
        }

        // Update balance when TR Amount changes
        trAmountInput.addEventListener('input', calculateBalance);
        
        // Update balance when Advance Amount changes
        advanceAmountInput.addEventListener('input', calculateBalance);

        // Calculate initial balance if values exist
        calculateBalance();

        // Existing code for LR selection
        const lrSelect = document.getElementById('lr_id');
        const transporterSelect = document.getElementById('transporter_id');
        const truckSelect = document.getElementById('truck_id');

        // Function to update trucks based on selected transporter
        function updateTrucks() {
            const selectedTransporter = transporterSelect.value;
            if (!selectedTransporter) {
                truckSelect.innerHTML = '<option value="">Select Truck</option>';
                return;
            }

            // Make AJAX request to get trucks for selected transporter
            fetch(`/api/transporters/${selectedTransporter}/trucks`)
                .then(response => response.json())
                .then(trucks => {
                    let options = '<option value="">Select Truck</option>';
                    trucks.forEach(truck => {
                        options += `<option value="${truck.id}">${truck.truck_number}</option>`;
                    });
                    truckSelect.innerHTML = options;
                })
                .catch(error => console.error('Error:', error));
        }

        // Function to load LR details
        function loadLRDetails() {
            const selectedLR = lrSelect.value;
            if (!selectedLR) {
                document.getElementById('transporter_id').value = '';
                document.getElementById('truck_id').value = '';
                return;
            }

            // Make AJAX request to get LR details
            fetch(`/api/lorry_receipts/${selectedLR}`)
                .then(response => response.json())
                .then(lr => {
                    // Set transporter
                    const transporterSelect = document.getElementById('transporter_id');
                    const truckSelect = document.getElementById('truck_id');
                    
                    // Get transporter name
                    fetch(`/api/transporters/${lr.transporter_id}`)
                        .then(response => response.json())
                        .then(transporter => {
                            transporterSelect.value = `${transporter.name}`;
                        });
                    
                    // Get truck number
                    fetch(`/api/trucks/${lr.truck_id}`)
                        .then(response => response.json())
                        .then(truck => {
                            truckSelect.value = `${truck.truck_number}`;
                        });
                })
                .catch(error => console.error('Error:', error));
        }
        });
    });
</script>

<script>
    const IS_OWNER = {{ $preFilled['is_owner'] ?? 0 }};
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Calculate balance amount when TR Amount or Advance Amount changes
        const trAmountInput = document.getElementById('tr_amount');
        const advanceAmountInput = document.getElementById('advance_amount');
        const balanceAmountInput = document.getElementById('balance_amount');
        const panVerified = parseInt(document.getElementById('pan_verified').value) || 0;
        const detentionInput = document.getElementById('detention');
        const deviationInput = document.getElementById('deviation');
        const miscExpenseInput = document.getElementById('misc_expense');
        const damageAmountInput = document.getElementById('damage_amount');
        const shortageAmountInput = document.getElementById('shortage_amount');
        const tdsAmountInput = document.getElementById('tds_amount');

        const tdsPercentage = parseFloat("{{ $tdsPercentage ?? 0 }}"); // already in %

        function calculateBalance() {
            const trAmount = parseFloat(trAmountInput.value) || 0;
            const advanceAmount = parseFloat(advanceAmountInput.value) || 0;
             // Prevent advance > TR
        if (advanceAmount > trAmount) {
            alert("Advance amount cannot be greater than TR amount.");
            advanceAmountInput.value = '';
            balanceAmountInput.value = trAmount.toFixed(2);
            tdsAmountInput.value = (trAmount * (tdsPercentage / 100)).toFixed(2);
          
            return;
        }

         // Calculate TDS Amount
            // TDS
            const tdsAmount = (trAmount * (tdsPercentage / 100)).toFixed(2);
        tdsAmountInput.value = tdsAmount;
          
          const detention = parseFloat(detentionInput.value) || 0;
          const deviation = parseFloat(deviationInput.value) || 0;
            const balance = trAmount  - advanceAmount + detention + deviation - parseFloat(tdsAmount);
            const miscExpense = parseFloat(miscExpenseInput.value) || 0;
            const damageAmount = parseFloat(damageAmountInput.value) || 0;
            const shortageAmount = parseFloat(shortageAmountInput.value) || 0;
            
        
            balanceAmountInput.value = (balance.toFixed(2) - miscExpense - damageAmount - shortageAmount).toFixed(2);
            
            // // Get TDS percentages from PHP variables
            // const defaultPercentage = {{ $tdsPercentages['default'] / 100 }};
            // const agencyPercentage = {{ $tdsPercentages['agency'] / 100 }};
            // const ownerPercentage = {{ $tdsPercentages['owner'] / 100 }};
            
            // // Calculate TDS %
            // let tdsPercentage = defaultPercentage;
            // if (panVerified === 1) {
            //     tdsPercentage = IS_OWNER === 0 ? agencyPercentage : ownerPercentage;
            // }

            // Calculate TDS Amount
            // const tdsAmountInput = document.getElementById('tds_amount');
            // const tdsAmount = (trAmount * tdsPercentage).toFixed(2);
            // tdsAmountInput.value = tdsAmount;
        }

        // Update balance when TR Amount changes
        trAmountInput.addEventListener('input', calculateBalance);
        
        // Update balance when Advance Amount changes
        advanceAmountInput.addEventListener('input', calculateBalance);
        detentionInput.addEventListener('input', calculateBalance);
        deviationInput.addEventListener('input', calculateBalance);
        miscExpenseInput.addEventListener('input', calculateBalance);
        damageAmountInput.addEventListener('input', calculateBalance);
        shortageAmountInput.addEventListener('input', calculateBalance);
        // Calculate initial balance if values exist
        calculateBalance();

        // Existing code for LR selection
        const lrSelect = document.getElementById('lr_id');
        const transporterSelect = document.getElementById('transporter_id');
        const truckSelect = document.getElementById('truck_id');

        // Function to update trucks based on selected transporter
        function updateTrucks() {
            const selectedTransporter = transporterSelect.value;
            if (!selectedTransporter) {
                truckSelect.innerHTML = '<option value="">Select Truck</option>';
                return;
            }

            // Make AJAX request to get trucks for selected transporter
            fetch(`/api/transporters/${selectedTransporter}/trucks`)
                .then(response => response.json())
                .then(trucks => {
                    let options = '<option value="">Select Truck</option>';
                    trucks.forEach(truck => {
                        options += `<option value="${truck.id}">${truck.truck_number}</option>`;
                    });
                    truckSelect.innerHTML = options;
                })
                .catch(error => console.error('Error:', error));
        }

        // Function to load LR details
        function loadLRDetails() {
            const selectedLR = lrSelect.value;
            if (!selectedLR) {
                document.getElementById('transporter_id').value = '';
                document.getElementById('truck_id').value = '';
                return;
            }

            // Make AJAX request to get LR details
            fetch(`/api/lorry_receipts/${selectedLR}`)
                .then(response => response.json())
                .then(lr => {
                    // Set transporter
                    const transporterSelect = document.getElementById('transporter_id');
                    const truckSelect = document.getElementById('truck_id');
                    
                    // Get transporter name
                    fetch(`/api/transporters/${lr.transporter_id}`)
                        .then(response => response.json())
                        .then(transporter => {
                            transporterSelect.value = `${transporter.name}`;
                        });
                    
                    // Get truck number
                    fetch(`/api/trucks/${lr.truck_id}`)
                        .then(response => response.json())
                        .then(truck => {
                            truckSelect.value = `${truck.truck_number}`;
                        });
                })
                .catch(error => console.error('Error:', error));
        }

        // Update trucks when transporter changes
        transporterSelect.addEventListener('change', updateTrucks);
        
        // Load LR details when LR changes
        lrSelect.addEventListener('change', loadLRDetails);
    });
</script>
@endsection

