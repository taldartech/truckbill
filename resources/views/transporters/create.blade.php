@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add New Transporter</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form  action="{{ route('transporters.store') }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        @php
    $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
    $userCompanyId = auth()->user()->company_id ?? null;
@endphp
        @error('registered_truck_id')
            <div class="text-danger">{{ $message }}</div>
        @enderror
      <!--begin::Body-->
      <div class="card-body">
        <!--begin::Row-->
        <div class="row g-3">
          <!--begin::Col-->
          <div class="col-md-6">
            <label for="validationCustom01" class="form-label">Company *</label>
            @if($canEditCompany)
                <select class="form-select" id="company_id" name="company_id" required>
                    <option value="">Select</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            @else
                <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
                <div class="form-control-plaintext">
                    {{ $companies->firstWhere('id', $userCompanyId)->name ?? '' }}
                </div>
                <div class="valid-feedback">Looks good!</div>
            @endif
            @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-6">
            <label for="name" class="form-label">Name *</label>
            <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
            <div class="valid-feedback">Looks good!</div>
          </div>
          <!--end::Col-->
          <!--begin::Col-->
          <div class="col-md-6">
            <label for="gst_number" class="form-label">GST Number</label>
            <input type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
            @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
            <div class="valid-feedback">Looks good!</div>
          </div>
          <!--end::Col-->
          <!--begin::Col-->
          <div class="col-md-6">
            <label for="gst_type" class="form-label">GST Type *</label>
            <select class="form-select" id="gst_type" name="gst_type" required>
                <option value="">Select</option>
                <option value="composition" {{ old('gst_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                <option value="regular" {{ old('gst_type') == 'regular' ? 'selected' : '' }}>Regular</option>
            </select>
            @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
            <div class="invalid-feedback">Please choose a GST Type.</div>
          </div>
          <!--end::Col-->
           <!--begin::Col-->
           <div class="col-md-6">
            <label for="pan_number" class="form-label">PAN Number</label>
            <input type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number') }}">
            @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
            <div class="valid-feedback">Looks good!</div>
          </div>
          <!--end::Col-->
          <!--begin::Col-->
          <div class="col-md-6">
            <label for="type" class="form-label">PAN is Active *</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="pan_active" id="pan_active_yes" value="1" {{ old('pan_active') === '1' ? 'checked' : '' }} required>
                <label class="form-check-label" for="pan_active_yes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="pan_active" id="pan_active_no" value="0" {{ old('pan_active') === '0' ? 'checked' : '' }} required>
                <label class="form-check-label" for="pan_active_no">No</label>
            </div>
            @error('pan_active') <div class="text-danger">{{ $message }}</div> @enderror
            <div class="invalid-feedback">Please choose a type.</div>
        </div>
        <!--end::Col-->
            <!--begin::Col-->
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6">
                        <label for="type" class="form-label">Type *</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_owner" id="owner" value="1" {{ old('is_owner') === '1' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="owner">Owner</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_owner" id="agency" value="0" {{ old('is_owner') === '0' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="agency">Agency</label>
                        </div>
                        @error('is_owner') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please choose a type.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Declaration Given? *</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="declaration_given" id="decl_yes" value="1" {{ old('declaration_given') === '1' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="decl_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="declaration_given" id="decl_no" value="0" {{ old('declaration_given') === '0' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="decl_no">No</label>
                        </div>
                        @error('declaration_given') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
              <!--end::Col-->
          <!--begin::Col-->
          <div class="col-md-6">
            <label for="total_trucks" class="form-label">Total Trucks *</label>
            <input type="number" class="form-control" id="total_trucks" name="total_trucks" min="0" required value="{{ old('total_trucks', 0) }}">
            @error('total_trucks') <div class="text-danger">{{ $message }}</div> @enderror
            <div class="invalid-feedback">Please enter  total trucks.</div>
          </div>
          <!--end::Col-->
          <!--begin::Col-->
          <div class="col-md-6">
            <label for="advance_amount" class="form-label">Advance Amount</label>
            <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" value="{{ old('advance_amount', 0) }}">
            @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
            <div class="invalid-feedback">Please enter advance amount.</div>
          </div>
          <!--end::Col-->
          
          <!--begin::Col-->
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="invalidCheck" required="">
              <label class="form-check-label" for="invalidCheck">
                Agree to terms and conditions
              </label>
              <div class="invalid-feedback">You must agree before submitting.</div>
            </div>
          </div>
          <!--end::Col-->
        </div>
        <!--end::Row-->
      </div>
      <!--end::Body-->
      <!--begin::Footer-->
      <div class="card-footer">
        <button class="btn btn-info" type="submit">Submit form</button>
        <a href="{{ route('transporters.index') }}" class="btn btn-info">Back to List</a>
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
{{-- <div class="container mt-4">
    <h2>Add New Transporter</h2>
    <form action="{{ route('transporters.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @php
    $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
    $userCompanyId = auth()->user()->company_id ?? null;
@endphp
        @error('registered_truck_id')
            <div class="text-danger">{{ $message }}</div>
        @enderror
        <div class="mb-3">
            <label for="company_id" class="form-label">Company *</label>
            @if($canEditCompany)
                <select class="form-select" id="company_id" name="company_id" required>
                    <option value="">Select</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            @else
                <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
                <div class="form-control-plaintext">
                    {{ $companies->firstWhere('id', $userCompanyId)->name ?? '' }}
                </div>
            @endif
            @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        @php
            $companyIdError = session('errors') ? session('errors')->first('company_id') : null;
        @endphp
        @if($companyIdError)
            <div class="alert alert-danger">
                {{ $companyIdError }}
            </div>
        @endif
        <div class="mb-3">
            <label for="name" class="form-label">Name *</label>
            <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="gst_number" class="form-label">GST Number</label>
            <input type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
            @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="gst_type" class="form-label">GST Type *</label>
            <select class="form-select" id="gst_type" name="gst_type" required>
                <option value="">Select</option>
                <option value="composition" {{ old('gst_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                <option value="regular" {{ old('gst_type') == 'regular' ? 'selected' : '' }}>Regular</option>
            </select>
            @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="pan_number" class="form-label">PAN Number</label>
            <input type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number') }}">
            @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Type *</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_owner" id="owner" value="1" {{ old('is_owner') === '1' ? 'checked' : '' }} required>
                <label class="form-check-label" for="owner">Owner</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_owner" id="agency" value="0" {{ old('is_owner') === '0' ? 'checked' : '' }} required>
                <label class="form-check-label" for="agency">Agency</label>
            </div>
            @error('is_owner') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Declaration Given? *</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="declaration_given" id="decl_yes" value="1" {{ old('declaration_given') === '1' ? 'checked' : '' }} required>
                <label class="form-check-label" for="decl_yes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="declaration_given" id="decl_no" value="0" {{ old('declaration_given') === '0' ? 'checked' : '' }} required>
                <label class="form-check-label" for="decl_no">No</label>
            </div>
            @error('declaration_given') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="total_trucks" class="form-label">Total Trucks *</label>
            <input type="number" class="form-control" id="total_trucks" name="total_trucks" min="0" required value="{{ old('total_trucks', 0) }}">
            @error('total_trucks') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        {{-- <div class="mb-3">
            <label for="registered_truck_id" class="form-label">Registered Truck</label>
            <input type="text" class="form-control" id="registered_truck_id" name="registered_truck_id" readonly value="">
            @error('registered_truck_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div> --}}
        {{-- <div class="mb-3">
            <label for="advance_amount" class="form-label">Advance Amount</label>
            <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" value="{{ old('advance_amount', 0) }}">
            @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary">Create Transporter</button>
        <a href="{{ route('transporters.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form> --}}
{{-- </div> --}}
@endsection
