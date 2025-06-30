@extends('layouts.app')
@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Add Voucher</h1>
      </div>
      <div class="col-sm-6 text-end">
        <a href="{{ route('vouchers.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </div>
  </div>
</div>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-body">
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <form action="{{ route('vouchers.store') }}" method="POST">
          @csrf
          <div class="row">
            <div class="col-md-4 mb-3">
              <div class="form-group">
                <label for="freight_booking_id">Freight Booking *</label>
                <select name="freight_booking_id" id="freight_booking_id" class="form-select" required>
                  <option value="">Select Freight Booking</option>
                  @foreach($freightBookings as $booking)
                    <option value="{{ $booking->id }}" {{ old('freight_booking_id') == $booking->id ? 'selected' : '' }}>
                      LR #{{ $booking->lr_id }} - Rate: {{ number_format($booking->tr_fr_rate, 2) }}
                    </option>
                  @endforeach
                </select>
                @error('freight_booking_id') <div class="text-danger">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="form-group">
                <label for="truck_id">Truck *</label>
                <select name="truck_id" id="truck_id" class="form-select" required>
                  <option value="">Select Truck</option>
                  @foreach($trucks as $truck)
                    <option value="{{ $truck->id }}" {{ old('truck_id') == $truck->id ? 'selected' : '' }}>
                      {{ $truck->registration_number }} - {{ $truck->truck_type }}
                    </option>
                  @endforeach
                </select>
                @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="form-group">
                <label for="transporter_id">Transporter *</label>
                <select name="transporter_id" id="transporter_id" class="form-select" required>
                  <option value="">Select Transporter</option>
                  @foreach($transporters as $transporter)
                    <option value="{{ $transporter->id }}" {{ old('transporter_id') == $transporter->id ? 'selected' : '' }}>
                      {{ $transporter->name }} - GST: {{ $transporter->gst_number }}
                    </option>
                  @endforeach
                </select>
                @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <div class="form-group">
                <label for="pan_verified">PAN Verified</label>
                <select name="pan_verified" id="pan_verified" class="form-control">
                  <option value="0" {{ old('pan_verified') == 0 ? 'selected' : '' }}>No</option>
                  <option value="1" {{ old('pan_verified') == 1 ? 'selected' : '' }}>Yes</option>
                </select>
                @error('pan_verified') <div class="text-danger">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="form-group">
                <label for="status">Status *</label>
                <select name="status" id="status" class="form-control" required>
                  <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                  <option value="in-progress" {{ old('status') == 'in-progress' ? 'selected' : '' }}>In-Progress</option>
                  <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
                @error('status') <div class="text-danger">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="form-group">
                <label for="voucher_number">Voucher Number *</label>
                <input type="text" name="voucher_number" id="voucher_number" class="form-control" value="{{ old('voucher_number') }}" required>
                @error('voucher_number') <div class="text-danger">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Voucher</button>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection


@section('content')
<div class="container mt-4">
    <h2>Add Voucher</h2>
    <form method="POST" action="{{ route('vouchers.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="freight_booking_id" class="form-label">Freight Booking *</label>
                <select name="freight_booking_id" id="freight_booking_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($freightBookings as $fb)
                        <option value="{{ $fb->id }}" {{ old('freight_booking_id') == $fb->id ? 'selected' : '' }}>{{ $fb->id }}</option>
                    @endforeach
                </select>
                @error('freight_booking_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="truck_id" class="form-label">Truck *</label>
                <select name="truck_id" id="truck_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($trucks as $truck)
                        <option value="{{ $truck->id }}" {{ old('truck_id') == $truck->id ? 'selected' : '' }}>{{ $truck->truck_number }}</option>
                    @endforeach
                </select>
                @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="transporter_id" class="form-label">Transporter *</label>
                <select name="transporter_id" id="transporter_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($transporters as $transporter)
                        <option value="{{ $transporter->id }}" {{ old('transporter_id') == $transporter->id ? 'selected' : '' }}>{{ $transporter->name }}</option>
                    @endforeach
                </select>
                @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="pan_verified" class="form-label">PAN Verified?</label>
                <select name="pan_verified" id="pan_verified" class="form-select">
                    <option value="0" {{ old('pan_verified') == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('pan_verified') == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="status" class="form-label">Status *</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
                @error('status') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('vouchers.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
