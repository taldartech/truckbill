@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit Freight Booking</h2>
    <form method="POST" action="{{ route('freight_bookings.update', $freight_booking->id) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="lr_id" class="form-label">LR *</label>
                <select name="lr_id" id="lr_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($lrs as $lr)
                        <option value="{{ $lr->id }}" {{ old('lr_id', $freight_booking->lr_id) == $lr->id ? 'selected' : '' }}>{{ $lr->gr_number }}</option>
                    @endforeach
                </select>
                @error('lr_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
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
                <input type="number" step="0.01" class="form-control" name="shortage" id="shortage" value="{{ old('shortage', $freight_booking->shortage) }}">
                @error('shortage') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="good_type" class="form-label">Goods Type</label>
                <input type="text" class="form-control" name="good_type" id="good_type" value="{{ old('good_type', $freight_booking->good_type) }}">
                @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
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
                <label for="misc_income" class="form-label">Misc. Income</label>
                <input type="number" step="0.01" class="form-control" name="misc_income" id="misc_income" value="{{ old('misc_income', $freight_booking->misc_income) }}">
                @error('misc_income') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
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
                    <option value="0" {{ old('is_delivered', $freight_booking->is_delivered) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_delivered', $freight_booking->is_delivered) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="is_cancelled" class="form-label">Cancelled?</label>
                <select name="is_cancelled" id="is_cancelled" class="form-select">
                    <option value="0" {{ old('is_cancelled', $freight_booking->is_cancelled) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_cancelled', $freight_booking->is_cancelled) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="linked_freight_booking_id" class="form-label">Linked Freight Booking</label>
                <select name="linked_freight_booking_id" id="linked_freight_booking_id" class="form-select">
                    <option value="">None</option>
                    @foreach($linkedBookings as $fb)
                        <option value="{{ $fb->id }}" {{ old('linked_freight_booking_id', $freight_booking->linked_freight_booking_id) == $fb->id ? 'selected' : '' }}>
                            #{{ $fb->id }} (LR: {{ $fb->lr ? $fb->lr->gr_number : '-' }})
                        </option>
                    @endforeach
                </select>
                @error('linked_freight_booking_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('freight_bookings.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
