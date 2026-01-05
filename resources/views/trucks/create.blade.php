@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
<div class="card card-info card-outline mb-4">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add New Truck</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form id="truckForm" action="{{ route('trucks.store') }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row mb-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="transporter_id" class="form-label">Truck Owner *</label>
                    <input type="text" class="form-control" value="{{ $transporter->name }}" disabled>
                    <input type="hidden" id="transporter_id" name="transporter_id" value="{{ $transporter->id }}">
                    @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a transporter.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="truck_number" class="form-label">Truck Number *</label>
                    <input type="text" class="form-control" placeholder="Enter Truck Number" id="truck_number" name="truck_number" required value="{{ old('truck_number') }}">
                    @error('truck_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a truck number.</div>
                </div>
                <!--end::Col-->
            </div>
            <div class="row mb-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="truck_type" class="form-label">Truck Type *</label>
                    <select class="form-select" id="truck_type" name="truck_type" required>
                        <option value="">Select</option>
                        <option value="Open" {{ old('truck_type') == 'Open' ? 'selected' : '' }}>Open</option>
                        <option value="Closed" {{ old('truck_type') == 'Closed' ? 'selected' : '' }}>Closed</option>
                        <option value="Container" {{ old('truck_type') == 'Container' ? 'selected' : '' }}>Container</option>
                        <option value="Trailer" {{ old('truck_type') == 'Trailer' ? 'selected' : '' }}>Trailer</option>
                        <option value="Tanker" {{ old('truck_type') == 'Tanker' ? 'selected' : '' }}>Tanker</option>
                        <option value="Refrigerated" {{ old('truck_type') == 'Refrigerated' ? 'selected' : '' }}>Refrigerated</option>
                        <option value="Flatbed" {{ old('truck_type') == 'Flatbed' ? 'selected' : '' }}>Flatbed</option>
                        <option value="Tipper" {{ old('truck_type') == 'Tipper' ? 'selected' : '' }}>Tipper</option>
                        <option value="Other" {{ old('truck_type') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('truck_type') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a truck type.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="driver_id" class="form-label">Driver</label>
                    <div class="input-group">
                        <select class="form-select" id="driver_id" name="driver_id">
                            <option value="">Select</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->mobile }})</option>
                            @endforeach
                        </select>
                        <a href="{{ route('drivers.create') }}" class="btn btn-outline-secondary" target="_blank">Add New Driver</a>
                    </div>
                    @error('driver_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!--end::Col-->
            </div>
            <div class="row mb-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="registration_date" class="form-label">Registration Date *</label>
                    <input type="date" class="form-control" placeholder="Enter Registration Date" id="registration_date" name="registration_date"  value="{{ old('registration_date') }}">
                    @error('registration_date') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a registration date.</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <div class="card p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Quick Actions</h5></div></div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <button class="btn btn-brand-primary w-100 mb-2" type="submit">💾 Create Truck</button>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('transporters.index') }}" class="btn btn-back w-100 mb-2">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Body-->
        {{-- <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-brand-primary" type="submit">Submit form</button>
            <a href="{{ route('trucks.index') }}" class="btn btn-back">Back to List</a>
        </div>
        <!--end::Footer--> --}}
        <!-- Quick Actions Section -->
       
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('truckForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Stop Laravel submit
        
                let truckNumber = document.getElementById('truck_number').value.trim();
                if (truckNumber === '') {
                    alert('Please enter a Truck Number');
                    return;
                }
        
                fetch(`/check-truck-number?truck_number=${encodeURIComponent(truckNumber)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            alert('🚫 This Truck Number already exists.');
                            document.getElementById('truck_number').focus();
                        } else {
                            e.target.submit(); // No duplicate → Submit form
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        e.target.submit(); // fallback submit
                    });
            });
        });
        </script>
        
        
    
        </div>
    </div>
</div>
@endsection
