@extends('layouts.app')
@section('content')
<div class="card card-info card-outline mb-4">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add New Voucher</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form action="{{ route('vouchers.store') }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="freight_booking_id" class="form-label">Freight Booking *</label>
                    <select name="freight_booking_id" id="freight_booking_id" class="form-select" required>
                        <option value="">Select Freight Booking</option>
                        @foreach($freightBookings as $booking)
                            <option value="{{ $booking->id }}" {{ old('freight_booking_id') == $booking->id ? 'selected' : '' }}>
                                LR #{{ $booking->lr_id }} - Rate: {{ number_format($booking->tr_fr_rate, 2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('freight_booking_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a freight booking.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="truck_id" class="form-label">Truck *</label>
                    <select name="truck_id" id="truck_id" class="form-select" required>
                        <option value="">Select Truck</option>
                        @foreach($trucks as $truck)
                            <option value="{{ $truck->id }}" {{ old('truck_id') == $truck->id ? 'selected' : '' }}>
                                {{ $truck->registration_number }} - {{ $truck->truck_type }}
                            </option>
                        @endforeach
                    </select>
                    @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a truck.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="transporter_id" class="form-label">Transporter *</label>
                    <select name="transporter_id" id="transporter_id" class="form-select" required>
                        <option value="">Select Transporter</option>
                        @foreach($transporters as $transporter)
                            <option value="{{ $transporter->id }}" {{ old('transporter_id') == $transporter->id ? 'selected' : '' }}>
                                {{ $transporter->name }} - GST: {{ $transporter->gst_number }}
                            </option>
                        @endforeach
                    </select>
                    @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a transporter.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="pan_verified" class="form-label">PAN Verified *</label>
                    <select name="pan_verified" id="pan_verified" class="form-select" required>
                        <option value="0" {{ old('pan_verified') == 0 ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('pan_verified') == 1 ? 'selected' : '' }}>Yes</option>
                    </select>
                    @error('pan_verified') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select PAN verification status.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="status" class="form-label">Status *</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in-progress" {{ old('status') == 'in-progress' ? 'selected' : '' }}>In-Progress</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    @error('status') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a status.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="voucher_number" class="form-label">Voucher Number *</label>
                    <input type="text" name="voucher_number" id="voucher_number" class="form-control" value="{{ old('voucher_number') }}" required>
                    @error('voucher_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a voucher number.</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
        </div>
        <!--end::Body-->
        <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-info" type="submit">Submit form</button>
            <a href="{{ route('vouchers.index') }}" class="btn btn-info">Back to List</a>
        </div>
        <!--end::Footer-->
    </form>
    <!--end::Form-->
    <!--begin::JavaScript-->
    <script>
        // Example starter JavaScript for disabling form submissions if there are invalid fields
        (() => {
            'use strict';

            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            const forms = document.querySelectorAll('.needs-validation');

            // Loop over them and prevent submission
            Array.from(forms).forEach((form) => {
                form.addEventListener(
                    'submit',
                    (event) => {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }

                        form.classList.add('was-validated');
                    },
                    false,
                );
            });
        })();
    </script>
    <!--end::JavaScript-->
</div>
@endsection
