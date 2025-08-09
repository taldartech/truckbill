@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4" style="max-width: 950px;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header">
        <div class="d-flex w-100">
            <div class="d-flex align-items-center">
                <h4 class="mb-0 me-3">Add New Lorry Receipt</h4>
            </div>
            <div class="ms-auto">
                <a href="{{ route('consignees.create') }}" class="btn btn-info">
                    <i class="bi bi-plus"></i> Create Consignee
                </a>
            </div>
        </div>
    </div>
   
    <hr class="mb-3 mt-0">
    <!--end::Header-->
    <form method="POST" action="{{ route('lorry_receipts.store') }}" id="lorryReceiptForm" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        <div class="card-body">
            <!-- General Details -->
            <div class="row mb-3">
              
                 <div class="col-md-4">
                    <label for="lr_number" class="form-label">LR Number *</label>
                    <input type="text" class="form-control" id="lr_number" name="lr_number" value="{{ old('lr_number') }}" required placeholder="Enter LR Number">
                    @error('lr_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an LR number.</div>
                </div>
               
                  <div class="col-md-4">
                    <label for="gr_date" class="form-label">GR Date *</label>
                    <input type="date" class="form-control" id="gr_date" name="gr_date" required value="{{ old('gr_date') }}">
                    @error('gr_date') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a GR date.</div>
                </div>
              
                  <div class="col-md-4">
                    <label for="customer_id" class="form-label">Customer *</label>
                    <select class="form-select" id="customer_id" name="customer_id" required>
                        <option value="">Select Customer</option>
                        @foreach($consignees as $consignee)
                            <option value="{{ $consignee->id }}" {{ old('customer_id') == $consignee->id ? 'selected' : '' }}>
                                {{ $consignee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a customer.</div>
                </div>
            </div>
            <div class="row mb-3">
               <div class="col-md-4">
                    <label for="truck_id" class="form-label">Truck *</label>
                    <select class="form-select" id="truck_id" name="truck_id" required>
                        <option value="">Select Trucks</option>
                        @foreach($trucks as $truck)
                            <option value="{{ $truck->id }}" {{ old('truck_id') == $truck->id ? 'selected' : '' }}>
                                {{ $truck->truck_number }} ({{ $truck->transporter->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a truck.</div>
                </div>
               <div class="col-md-4">
                    <label for="transporter_type" class="form-label">Select Type *</label>
                    <select class="form-select" id="transporter_type" required>
                        <option value="">Select Type</option>
                        <option value="owner">Truck Owner</option>
                        <option value="agency">Agency</option>
                    </select>
                    <div class="invalid-feedback">Please select transporter type.</div>
                </div>
                <div class="col-md-4">
                    <label for="transporter_id" id="transporterLabel" class="form-label">Transporter *</label>
                    <select class="form-select" id="transporter_id" name="transporter_id" required>
                        <option value="">Select Transporter</option>
                        {{-- This will be filled via JavaScript --}} 
                     </select>
                    @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a transporter.</div>
                    <p id="tdsRateDisplay" class="form-control-plaintext text-primary fw-bold">--</p>
                </div>
             
   
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="consignor_id" class="form-label">Consignor *</label>
                    <select class="form-select" id="consignor_id" name="consignor_id" required>
                        <option value="">Select Consignor</option>
                        @foreach($consignees as $consignee) 
                            <option value="{{ $consignee->id }}" {{ old('consignee_id') == $consignee->id ? 'selected' : '' }}>
                                {{ $consignee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('consignor_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a consignor.</div>
                </div>
                <div class="col-md-4">
                    <label for="consignor_contact_number" class="form-label">Consignor Contact Number</label>
                    <input type="text" class="form-control" id="consignor_contact_number" name="consignor_contact_number" readonly>
                </div>
                <div class="col-md-4">
                    <label for="consignee_id" class="form-label">Consignee *</label>
                    <select class="form-select" id="consignee_id" name="consignee_id" required>
                        <option value="">Select Consignee</option>
                        @foreach($consignees as $consignee)
                            <option value="{{ $consignee->id }}" {{ old('consignee_id') == $consignee->id ? 'selected' : '' }}>
                                {{ $consignee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('consignee_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a consignee.</div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                    <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" readonly>
                </div>
            </div>
            <!-- Route Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Route</h5></div></div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="source_city" class="form-label">Source City *</label>
                        <input type="text" class="form-control" id="source_city" name="source_city" required value="{{ old('source_city') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="source_state" class="form-label">Source State *</label>
                        <input type="text" class="form-control" id="source_state" name="source_state" required value="{{ old('source_state') }}" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label for="destination_city" class="form-label">Destination City *</label>
                        <input type="text" class="form-control" id="destination_city" name="destination_city" required value="{{ old('destination_city') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="destination_state" class="form-label">Destination State *</label>
                        <input type="text" class="form-control" id="destination_state" name="destination_state" required value="{{ old('destination_state') }}" readonly>
                    </div>
                </div>
            </div>
            <!-- Goods & Charges Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Goods & Charges</h5></div></div>
                 <div class="row mb-2">
                  {{-- error('good_type') <div class="text-danger">{{ $message }}</div> @enderror --}}
                  {{--  <div class="col-md-4">
                        <label for="weight" class="form-label">Weight *</label>
                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ old('weight') }}">
                        @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>  <div class="col-md-4">
                        <label for="good_type" class="form-label">Goods Type *</label>
                        <select class="form-select" id="good_type" name="good_type" required>
                            <option value="">Select Goods Type</option>
                            @foreach($goodsTypes as $goodsType)
                                <option value="{{ $goodsType->name }}" {{ old('good_type') == $goodsType->name ? 'selected' : '' }}>{{ $goodsType->name }}</option>
                            @endforeach
                        </select>
                        @
                    <div class="col-md-4">
                        <label for="no_of_units" class="form-label">No. of Units *</label>
                        <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="{{ old('no_of_units') }}">
                        @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
                    </div> --}}
                    <input type="hidden" id="lane" name="lane" value="Road">
                    <div class="col-md-4">
                        <label for="good_type" class="form-label ms-3">Goods Type *</label>
                        <select class="form-select " id="good_type" name="good_type" required>
                            <option value="">Select Goods Type</option>
                            @foreach($goodsTypes as $goodsType)
                                <option value="{{ $goodsType->name }}" {{ old('good_type') == $goodsType->name ? 'selected' : '' }}>
                                    {{ $goodsType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a goods type.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="weight" class="form-label ">Weight *</label>
                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ old('weight') }}">
                        @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a weight.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="no_of_units" class="form-label">No. of Units *</label>
                        <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="{{ old('no_of_units') }}">
                        @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter number of units.</div>
                    </div>
                </div>
            

                <div class="row mb-2">
                    <div class="col-md-4">
                        <label for="fr_rate" class="form-label">Freight Rate *</label>
                        <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ old('fr_rate') }}">
                        @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    {{-- <div class="col-md-4">
                        <label for="fr_rate" class="form-label">Freight Rate *</label>
                        <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ old('fr_rate') }}">
                        @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a freight rate.</div>
                    </div> --}}
                    {{-- <div class="col-md-4">
                        <label for="amount" class="form-label">Amount *</label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount') }}">
                        @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div> --}}
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Amount *</label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount') }}">
                        @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter an amount.</div>
                    </div>
                </div>
            </div>
            <!-- Quick Actions Section -->
            <div class="card p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Quick Actions</h5></div></div>
                <div class="row g-2">
                    <div class="col-md-3">
                        {{-- <button class="btn btn-info" type="submit">Submit form</button> --}}
                        <button class="btn btn-success w-100 mb-2" type="submit">💾 Submit Form</button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-secondary w-100 mb-2" type="button" title="Copies most data, clears variable fields">🔄 Save & Create Similar <span class="ms-1" data-bs-toggle="tooltip" title="Copies most data, clears variable fields">ℹ️</span></button>
                    </div>
                    {{-- <div class="col-md-3"> --}}
                        {{-- <button class="btn btn-warning w-100 mb-2" type="button" title="Save this combination for future use">📋 Save as Template <span class="ms-1" data-bs-toggle="tooltip" title="Save this combination for future use">ℹ️</span></button> --}}
                    {{-- </div> --}}
                    <div class="col-md-3">
                        <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-dark w-100 mb-2">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
    <!--begin::Header-->
    {{-- <div class="card-header"><div class="card-title">Add New Lorry Receipt</div></div>
    <hr class="mb-3 mt-0">
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('lorry_receipts.store') }}" id="lorryReceiptForm" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">

        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row mb-3">
               
                 <!--begin::Col-->
                 <div class="col-md-4">
                    <label for="lr_number" class="form-label">LR Number *</label>
                    <input type="text" class="form-control" id="lr_number" name="lr_number" value="{{ old('lr_number') }}" required>
                    @error('lr_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an LR number.</div>
                </div>

                <!--end::Col-->
                <div class="col-md-4 mb-3">
                    <label for="gr_date" class="form-label">GR Date *</label>
                    <input type="date" class="form-control" id="gr_date" name="gr_date" required value="{{ old('gr_date') }}">
                    @error('gr_date') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a GR date.</div>
                </div>
                 <!--begin::Col-->
                 <div class="col-md-4">
                    <label for="customer_id" class="form-label">Customer *</label>
                    <select class="form-select" id="customer_id" name="customer_id" required>
                        <option value="">Select Customer</option>
                        @foreach($consignees as $consignee)
                            <option value="{{ $consignee->id }}" {{ old('customer_id') == $consignee->id ? 'selected' : '' }}>
                                {{ $consignee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a customer.</div>
                </div>
            </div>

                <!--end::Col-->
                <div class="row mb-3"> --}}
                {{-- <!--begin::Col-->
                <div class="col-md-4">
                    <label for="transporter_id" class="form-label" >Transporter *</label>
                    <select class="form-select" id="transporter_id" name="transporter_id" required>
                        <option value="">Select Transporter</option>
                        @foreach($transporters as $transporter)
                            <option value="{{ $transporter->id }}" {{ old('transporter_id') == $transporter->id ? 'selected' : '' }}>
                                {{ $transporter->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a transporter.</div>
                </div>
                <!--end::Col--> --}}
               
                <!--begin::Col-->
                {{-- <div class="col-md-4">
                    <label for="truck_id" class="form-label">Truck *</label>
                    <select class="form-select" id="truck_id" name="truck_id" required>
                        <option value="">Select Trucks</option>
                        @foreach($trucks as $truck)
                            <option value="{{ $truck->id }}" {{ old('truck_id') == $truck->id ? 'selected' : '' }}>
                                {{ $truck->truck_number }} ({{ $truck->transporter->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a truck.</div>
                </div>
                <div class="col-md-4">
                    <label for="transporter_type" class="form-label">Select Type *</label>
                    <select class="form-select" id="transporter_type" required>
                        <option value="">Select Type</option>
                        <option value="owner">Truck Owner</option>
                        <option value="agency">Agency</option>
                    </select>
                    <div class="invalid-feedback">Please select transporter type.</div>
                </div>
                <!--end::Col-->
                <div class="col-md-4">
                    <label for="transporter_id" id="transporterLabel" class="form-label">Transporter *</label>
                    <select class="form-select" id="transporter_id" name="transporter_id" required>
                        <option value="">Select Transporter</option>
                        {{-- This will be filled via JavaScript --}}
                    {{-- </select>
                    @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a transporter.</div>
                </div>

            </div> --}} 

              
               
               
                <!--begin::Col-->
                {{-- <div class="col-md-4">
                    <label for="delivery_status" class="form-label">Delivery Status</label>
                    <select class="form-select" id="delivery_status" name="delivery_status" disabled>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ old('delivery_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="delivery_status">
                    <small class="text-muted">It will be updated from freight booking entries.</small>
                    @error('delivery_status') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a delivery status.</div>
                </div> --}}
                <!--end::Col-->
            {{-- </div> --}}
            <!--end::Row-->
{{-- 
            <div class="row">
                <div class="col-md-4 mb-3 ms-3">
                    <label for="consignor_id" class="form-label">Consignor *</label>
                    <select class="form-select" id="consignor_id" name="consignor_id" required>
                        <option value="">Select Consignor</option>
                        @foreach($consignees as $consignee)
                            <option value="{{ $consignee->id }}" {{ old('consignee_id') == $consignee->id ? 'selected' : '' }}>
                                {{ $consignee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('consignor_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a consignor.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="consignor_contact_number" class="form-label">Consignor Contact Number</label>
                    <input type="text" class="form-control" id="consignor_contact_number" name="consignor_contact_number" readonly>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3 ms-3">
                    <label for="consignee_id" class="form-label">Consignee *</label>
                    <select class="form-select" id="consignee_id" name="consignee_id" required>
                        <option value="">Select Consignee</option>
                        @foreach($consignees as $consignee)
                            <option value="{{ $consignee->id }}" {{ old('consignee_id') == $consignee->id ? 'selected' : '' }}>
                                {{ $consignee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('consignee_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a consignee.</div>
                </div>
                <div class="col-md-4 mb-3 ">
                    <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                    <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" readonly>
                </div>
            </div>

            <h5 class="mt-4 mb-3">Route</h5>
            <hr class="mb-3 mt-0">

            <div class="row">
               
            </div>

            <div class="row">
                <div class="col-md-4 mb-3 ms-3">
                    <label for="source_city" class="form-label">Source City *</label>
                    <input type="text" class="form-control" id="source_city" name="source_city" required value="{{ old('source_city') }}" readonly>
                    @error('source_city') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a source city.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="source_state" class="form-label">Source State *</label>
                    <input type="text" class="form-control" id="source_state" name="source_state" required value="{{ old('source_state') }}" readonly>
                    @error('source_state') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a source state.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3 ms-3">
                    <label for="destination_city" class="form-label">Destination City *</label>
                    <input type="text" class="form-control" id="destination_city" name="destination_city" required value="{{ old('destination_city') }}" readonly>
                    @error('destination_city') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a destination city.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="destination_state" class="form-label">Destination State *</label>
                    <input type="text" class="form-control" id="destination_state" name="destination_state" required value="{{ old('destination_state') }}" readonly>
                    @error('destination_state') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a destination state.</div>
                </div>
            </div>

            <h5 class="mt-4 mb-3">Goods & Charges</h5>
            <hr class="mb-3 mt-0">

            <div class="row">
                <input type="hidden" id="lane" name="lane" value="Road">
                <div class="col-md-4 mb-3">
                    <label for="good_type" class="form-label ms-3">Goods Type *</label>
                    <select class="form-select ms-3" id="good_type" name="good_type" required>
                        <option value="">Select Goods Type</option>
                        @foreach($goodsTypes as $goodsType)
                            <option value="{{ $goodsType->name }}" {{ old('good_type') == $goodsType->name ? 'selected' : '' }}>
                                {{ $goodsType->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a goods type.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="weight" class="form-label ms-3">Weight *</label>
                    <input type="number" step="0.01" class="form-control ms-3" id="weight" name="weight" required value="{{ old('weight') }}">
                    @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a weight.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="no_of_units" class="form-label">No. of Units *</label>
                    <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="{{ old('no_of_units') }}">
                    @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter number of units.</div>
                </div>
            </div>

            <div class="row">
               
                <div class="col-md-4 mb-3 ms-3">
                    <label for="fr_rate" class="form-label">Freight Rate *</label>
                    <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ old('fr_rate') }}">
                    @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a freight rate.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="amount" class="form-label">Amount *</label>
                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount') }}">
                    @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an amount.</div>
                </div>
            </div>

            <div class="row"> --}}
                {{-- <div class="col-md-4 mb-3">
                    <label for="damage_amount" class="form-label">Damage Amount</label>
                    <input type="number" step="0.01" class="form-control" id="damage_amount" name="damage_amount" value="{{ old('damage_amount') }}" disabled>
                    @error('damage_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a damage amount.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="shortage_amount" class="form-label">Shortage Amount</label>
                    <input type="number" step="0.01" class="form-control" id="shortage_amount" name="shortage_amount" value="{{ old('shortage_amount') }}" disabled>
                    @error('shortage_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a shortage amount.</div>
                </div> --}}
                {{-- <div class="col-md-4 mb-3">
                    <label for="memo_id" class="form-label">Memo ID</label>
                    <input type="number" class="form-control" id="memo_id" name="memo_id" value="{{ old('memo_id') }}">
                    @error('memo_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a memo ID.</div>
                </div> --}}
            {{-- </div> --}}

            

        {{-- </div>
        <!--end::Body-->
        <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-info" type="submit">Submit form</button>
            <a href="{{ route('lorry_receipts.index') }}" class="btn btn-info">Back to List</a>
        </div>
        <!--end::Footer-->
    </form> --}}
    <!--end::Form-->
    <!--begin::JavaScript-->
    
    {{-- <script>
        document.addEventListener('DOMContentLoaded', function () {
            const transporterTypeSelect = document.getElementById('transporter_type');
            const truckSelect = document.getElementById('truck_id');
            const transporterSelect = document.getElementById('transporter_id');
            const transporterLabel = document.getElementById('transporterLabel');
        
            const agencies = @json($agencies);
            const truckOwners = @json($truckOwners);
            const trucks = @json($trucks);
        
            function updateTransporterDropdown() {
                const selectedType = transporterTypeSelect.value;
                const selectedTruckId = truckSelect.value;
                let optionsHtml = `<option value="">Select Transporter</option>`;
        
                if (selectedType === 'owner') {
                    transporterLabel.textContent = 'Truck Owner *';
                    // Get selected truck
                    const selectedTruck = trucks.find(truck => truck.id == selectedTruckId);
                    if (selectedTruck && selectedTruck.transporter) {
                        const transporter = selectedTruck.transporter;
                        if (transporter.is_owner == 1) {
                            optionsHtml += `<option value="${transporter.id}" selected>${transporter.name}</option>`;
                        } else {
                            optionsHtml += `<option disabled>No owner assigned to this truck</option>`;
                        }
                    }
                } else if (selectedType === 'agency') {
                    transporterLabel.textContent = 'Agency *';
                    agencies.forEach(agency => {
                        optionsHtml += `<option value="${agency.id}">${agency.name}</option>`;
                    });
                } else {
                    transporterLabel.textContent = 'Transporter *';
                }
        
                transporterSelect.innerHTML = optionsHtml;
            }
        
            transporterTypeSelect.addEventListener('change', updateTransporterDropdown);
            truckSelect.addEventListener('change', () => {
                if (transporterTypeSelect.value === 'owner') {
                    updateTransporterDropdown();
                }
            });
        });
        </script>
         --}}
         <script>
            document.addEventListener('DOMContentLoaded', function () {
                const transporterTypeSelect = document.getElementById('transporter_type');
                const truckSelect = document.getElementById('truck_id');
                const transporterSelect = document.getElementById('transporter_id');
                const transporterLabel = document.getElementById('transporterLabel');
                const tdsRateDisplay = document.getElementById('tdsRateDisplay');
        
                const agencies = @json($agencies);
                const truckOwners = @json($truckOwners);
                const trucks = @json($trucks);
                const tdsPercentages = @json($tdsPercentages);
        
                function updateTransporterDropdown() {
                    const selectedType = transporterTypeSelect.value;
                    const selectedTruckId = truckSelect.value;
                    let optionsHtml = `<option value="">Select Transporter</option>`;
        
                    if (selectedType === 'owner') {
                        transporterLabel.textContent = 'Truck Owner *';
                        const selectedTruck = trucks.find(truck => truck.id == selectedTruckId);
                        if (selectedTruck && selectedTruck.transporter) {
                            const transporter = selectedTruck.transporter;
                            if (transporter.is_owner == 1) {
                                optionsHtml += `<option value="${transporter.id}" selected>${transporter.name}</option>`;
                                fetchTdsInfo(transporter.id); // Fetch TDS immediately
                            } else {
                                optionsHtml += `<option disabled>No owner assigned to this truck</option>`;
                                tdsRateDisplay.textContent = '--';
                            }
                        } else {
                            tdsRateDisplay.textContent = '--';
                        }
                    } else if (selectedType === 'agency') {
                        transporterLabel.textContent = 'Agency *';
                        agencies.forEach(agency => {
                            optionsHtml += `<option value="${agency.id}">${agency.name}</option>`;
                        });
                        tdsRateDisplay.textContent = '--';
                    } else {
                        transporterLabel.textContent = 'Transporter *';
                        tdsRateDisplay.textContent = '--';
                    }
        
                    transporterSelect.innerHTML = optionsHtml;
                }
        
                // ✅ Fetch TDS from server
                function fetchTdsInfo(transporterId) {
                    if (!transporterId) {
                        tdsRateDisplay.textContent = '--';
                        return;
                    }
        
                    fetch(`/transporter/${transporterId}/tds-info`)
                        .then(response => response.json())
                        .then(data => {
                            const { pan_active, is_owner, declaration_given } = data;

           
        
                            if (pan_active == 0 || pan_active === false) {
                                tdsRateDisplay.textContent = `TDS RATE= ${tdsPercentages.default}% (Default - PAN not active)`;
                            } else {
                                if (is_owner == 1) {
                                    if (declaration_given == 1) {
                                        tdsRateDisplay.textContent = `TDS RATE= 0% (Declaration Given)`;
                                    } else {
                                        tdsRateDisplay.textContent = `TDS RATE= ${tdsPercentages.owner}%`;
                                    }
                                } else {
                                    tdsRateDisplay.textContent = `TDS RATE= ${tdsPercentages.agency}%`;
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching transporter TDS info:', error);
                            tdsRateDisplay.textContent = 'Error';
                        });
                }
        
                transporterTypeSelect.addEventListener('change', updateTransporterDropdown);
        
                truckSelect.addEventListener('change', () => {
                    if (transporterTypeSelect.value === 'owner') {
                        updateTransporterDropdown();
                    }
                });
        
                transporterSelect.addEventListener('change', function () {
                    const transporterId = this.value;
                    fetchTdsInfo(transporterId);
                });
            });
        </script>
        
    {{-- <script>
        document.addEventListener('DOMContentLoaded', function () {
            const transporterTypeSelect = document.getElementById('transporter_type');
            const transporterSelect = document.getElementById('transporter_id');
            const transporterLabel = document.getElementById('transporterLabel');
    
            const truckOwners = @json($truckOwners);
            const agencies = @json($agencies);
    
            transporterTypeSelect.addEventListener('change', function () {
                const selectedType = this.value;
                let optionsHtml = `<option value="">Select Transporter</option>`;
    
                if (selectedType === 'owner') {
                    transporterLabel.textContent = 'Truck Owner *';
                    truckOwners.forEach(function (owner) {
                        optionsHtml += `<option value="${owner.id}">${owner.name}</option>`;
                    });
                } else if (selectedType === 'agency') {
                    transporterLabel.textContent = 'Agency *';
                    agencies.forEach(function (agency) {
                        optionsHtml += `<option value="${agency.id}">${agency.name}</option>`;
                    });
                } else {
                    transporterLabel.textContent = 'Transporter *';
                }
    
                transporterSelect.innerHTML = optionsHtml;
            });
        });
    </script> --}}
    
    <script>
      // Example starter JavaScript for disabling form submissions if there are invalid fields
    //   (() => {
        'use strict';
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

        // Handle truck selection based on transporter
        document.addEventListener('DOMContentLoaded', function() {
           

            // Handle consignor and consignee contact number and location details
            const consignorSelect = document.getElementById('consignor_id');
            const consignorContact = document.getElementById('consignor_contact_number');
            const consignorLocation = document.getElementById('consignor_location');
            const consigneeSelect = document.getElementById('consignee_id');
            const consigneeContact = document.getElementById('consignee_contact_number');
            const consigneeLocation = document.getElementById('consignee_location');

            function updateConsignorDetails() {
                const selectedConsignor = consignorSelect.value;
                if (!selectedConsignor) {
                    consignorContact.value = '';
                    consignorLocation.value = '';
                    return;
                }

                fetch(`/api/consignors/${selectedConsignor}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(consignor => {
                    consignorContact.value = consignor.contact_number;
                    consignorLocation.value = `${consignor.city}, ${consignor.state}`;
                })
                .catch(error => console.error('Error:', error));
            }

            function updateConsigneeDetails() {
                const selectedConsignee = consigneeSelect.value;
                if (!selectedConsignee) {
                    consigneeContact.value = '';
                    consigneeLocation.value = '';
                    return;
                }

                fetch(`/api/consignees/${selectedConsignee}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(consignee => {
                    consigneeContact.value = consignee.contact_number;
                    consigneeLocation.value = `${consignee.city}, ${consignee.state}`;
                })
                .catch(error => console.error('Error:', error));
            }

            consignorSelect.addEventListener('change', updateConsignorDetails);
            consigneeSelect.addEventListener('change', updateConsigneeDetails);
        });
       
       
     
    

      
    </script>
    <!--end::JavaScript-->
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Handle consignor contact number and location details
    const consignorSelect = document.getElementById('consignor_id');
    const consignorContact = document.getElementById('consignor_contact_number');
    const sourceCityInput = document.getElementById('source_city');
    const sourceStateInput = document.getElementById('source_state');

    if (consignorSelect && consignorContact && sourceCityInput && sourceStateInput) {
        consignorSelect.addEventListener('change', async function () {
            const consignorId = this.value;
            if (!consignorId) {
                consignorContact.value = '';
                sourceCityInput.value = '';
                sourceStateInput.value = '';
                return;
            }

            try {
                const response = await fetch(`{{ route('consignees.details', '__id__') }}`.replace('__id__', consignorId));
                if (!response.ok) {
                    throw new Error('Failed to fetch consignor details');
                }

                const data = await response.json();
                
                // Update contact number and location fields
                consignorContact.value = data.contact_number || '';
                sourceCityInput.value = data.city_name || '';
                sourceStateInput.value = data.state_name || '';
            } catch (error) {
                console.error('❌ Error:', error);
                consignorContact.value = '';
                sourceCityInput.value = '';
                sourceStateInput.value = '';
            }
        });
    }

    // Handle consignee contact number and location details
    const consigneeSelect = document.getElementById('consignee_id');
    const consigneeContact = document.getElementById('consignee_contact_number');
    const destinationCityInput = document.getElementById('destination_city');
    const destinationStateInput = document.getElementById('destination_state');

    if (consigneeSelect && consigneeContact && destinationCityInput && destinationStateInput) {
        consigneeSelect.addEventListener('change', async function () {
            const consigneeId = this.value;
            if (!consigneeId) {
                consigneeContact.value = '';
                destinationCityInput.value = '';
                destinationStateInput.value = '';
                return;
            }

            try {
                const response = await fetch(`{{ route('consignees.details', '__id__') }}`.replace('__id__', consigneeId));
                if (!response.ok) {
                    throw new Error('Failed to fetch consignee details');
                }

                const data = await response.json();
                
                // Update contact number and location fields
                consigneeContact.value = data.contact_number || '';
                destinationCityInput.value = data.city_name || '';
                destinationStateInput.value = data.state_name || '';
            } catch (error) {
                console.error('❌ Error:', error);
                consigneeContact.value = '';
                destinationCityInput.value = '';
                destinationStateInput.value = '';
            }
        });
    }

    // Calculate amount
    const weightInput = document.getElementById('weight');
    const frRateInput = document.getElementById('fr_rate');
    const amountInput = document.getElementById('amount');
    
    function calculateAmount() {
        const weight = parseFloat(weightInput.value);
        const frRate = parseFloat(frRateInput.value);
    
        if (!isNaN(weight) && !isNaN(frRate)) {
            const amount = (weight * frRate).toFixed(2);
            amountInput.value = amount;
        } else {
            amountInput.value = '';
        }
    }
    
    weightInput.addEventListener('input', calculateAmount);
    frRateInput.addEventListener('input', calculateAmount);
});
</script>
<script>
    document.getElementById('truck_id').addEventListener('change', function () {
        let truckId = this.value;
    
        if (truckId) {
            fetch(`/get-transporter-name/${truckId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.transporter_name) {
                        document.getElementById('transporter_name').value = data.transporter_name;
                        if(data.transporter.is_owner == 1){
                            document.getElementById('ownerTransporterSelect').style.display = 'block';
                        }
                    } else {
                        document.getElementById('ownerTransporterSelect').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('ownerTransporterSelect').style.display = 'none';
                });
        } else {
            document.getElementById('ownerTransporterSelect').style.display = 'none';
        }
    });
    </script>
@endsection
