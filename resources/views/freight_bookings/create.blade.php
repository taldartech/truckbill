@extends('layouts.app')

@section('content')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Update trucks when transporter changes
        transporterSelect.addEventListener('change', updateTrucks);
    });
</script>
<div class="container mt-4">
    <h2>Add Freight Booking</h2>
    <form method="POST" action="{{ route('freight_bookings.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">@php
    $lrs = \App\Models\LorryReceipt::all();
    $transporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)->get();
@endphp

<div class="form-group">
    <label for="lr_id">LR Number</label>
    <select name="lr_id" id="lr_id" class="form-control" required>
        <option value="">Select LR Number</option>
        @foreach($lrs as $lr)
            <option value="{{ $lr->id }}" {{ old('lr_id') == $lr->id ? 'selected' : '' }}>
                {{ $lr->lr_number }} 
            </option>
        @endforeach
    </select>
</div>

<div class="col-md-4 mb-3">
    <label for="transporter_id">Transporter *</label>
    <select name="transporter_id" id="transporter_id" class="form-control" required>
        <option value="">Select Transporter</option>
        @foreach($transporters as $transporter)
            <option value="{{ $transporter->id }}" {{ old('transporter_id') == $transporter->id ? 'selected' : '' }}>
                {{ $transporter->name }}
            </option>
        @endforeach
    </select>
    @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="col-md-4 mb-3">
    <label for="truck_id">Truck *</label>
    <select name="truck_id" id="truck_id" class="form-control" required>
        <option value="">Select Truck</option>
    </select>
    @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>
                @error('lr_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="tr_fr_rate" class="form-label">TR Freight Rate *</label>
                <input type="number" step="0.01" class="form-control" name="tr_fr_rate" id="tr_fr_rate" value="{{ old('tr_fr_rate') }}" required>
                @error('tr_fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="tr_amount" class="form-label">TR Amount *</label>
                <input type="number" step="0.01" class="form-control" name="tr_amount" id="tr_amount" value="{{ old('tr_amount') }}" required>
                @error('tr_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="advance_amount" class="form-label">Advance Amount *</label>
                <input type="number" step="0.01" class="form-control" name="advance_amount" id="advance_amount" value="{{ old('advance_amount') }}" required>
                @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="balance_amount" class="form-label">Balance Amount *</label>
                <input type="number" step="0.01" class="form-control" name="balance_amount" id="balance_amount" value="{{ old('balance_amount') }}" required>
                @error('balance_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="tds_amount" class="form-label">TDS Amount</label>
                <input type="number" step="0.01" class="form-control" name="tds_amount" id="tds_amount" value="{{ old('tds_amount') }}">
                @error('tds_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="shortage" class="form-label">Shortage</label>
                <input type="number" step="0.01" class="form-control" name="shortage" id="shortage" value="{{ old('shortage') }}">
                @error('shortage') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="good_type" class="form-label">Goods Type</label>
                <select class="form-select" name="good_type" id="good_type">
                    <option value="">Select Goods Type</option>
                    @foreach($goodsTypes as $goodsType)
                        <option value="{{ $goodsType->name }}" {{ old('good_type') == $goodsType->name ? 'selected' : '' }}>
                            {{ $goodsType->name }}
                        </option>
                    @endforeach
                </select>
                @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="detention" class="form-label">Detention</label>
                <input type="number" step="0.01" class="form-control" name="detention" id="detention" value="{{ old('detention') }}">
                @error('detention') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="deviation" class="form-label">Deviation</label>
                <input type="number" step="0.01" class="form-control" name="deviation" id="deviation" value="{{ old('deviation') }}">
                @error('deviation') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="misc_expense" class="form-label">Misc. Expense</label>
                <input type="number" step="0.01" class="form-control" name="misc_expense" id="misc_expense" value="{{ old('misc_expense') }}">
                @error('misc_expense') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="misc_income" class="form-label">Misc. Income</label>
                <input type="number" step="0.01" class="form-control" name="misc_income" id="misc_income" value="{{ old('misc_income') }}">
                @error('misc_income') <div class="text-danger">{{ $message }}</div> @enderror
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
                <select name="is_delivered" id="is_delivered" class="form-select">
                    <option value="0" {{ old('is_delivered') == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_delivered') == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="is_cancelled" class="form-label">Cancelled?</label>
                <select name="is_cancelled" id="is_cancelled" class="form-select">
                    <option value="0" {{ old('is_cancelled') == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_cancelled') == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
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
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('freight_bookings.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
