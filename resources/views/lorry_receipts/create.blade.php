@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add New Lorry Receipt</div></div>
    <hr class="mb-3 mt-0">
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('lorry_receipts.store') }}" id="lorryReceiptForm" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">

        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row g-3">
                 <!--begin::Col-->
                 <div class="col-md-4">
                    <label for="lr_number" class="form-label">LR Number *</label>
                    <input type="text" class="form-control" id="lr_number" name="lr_number" value="{{ old('lr_number') }}" required>
                    @error('lr_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an LR number.</div>
                </div>
                <!--end::Col-->
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
                <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="transporter_id" class="form-label">Transporter *</label>
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
                <!--end::Col-->
                <!--begin::Col-->
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
                <!--end::Col-->
               
               
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
            </div>
            <!--end::Row-->

            <div class="row">
                <div class="col-md-6 mb-3">
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
                <div class="col-md-6 mb-3">
                    <label for="consignor_contact_number" class="form-label">Consignor Contact Number</label>
                    <input type="text" class="form-control" id="consignor_contact_number" name="consignor_contact_number" readonly>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
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
                <div class="col-md-6 mb-3">
                    <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                    <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" readonly>
                </div>
            </div>

            <h5 class="mt-4 mb-3">Route & Dates</h5>
            <hr class="mb-3 mt-0">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="gr_date" class="form-label">GR Date *</label>
                    <input type="date" class="form-control" id="gr_date" name="gr_date" required value="{{ old('gr_date') }}">
                    @error('gr_date') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a GR date.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
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
                <div class="col-md-4 mb-3">
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
                <div class="col-md-4 mb-3">
                    <label for="lane" class="form-label">Lane *</label>
                    <input type="text" class="form-control" id="lane" name="lane" value="Road" readonly required>
                    @error('lane') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a lane.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="good_type" class="form-label">Goods Type *</label>
                    <select class="form-select" id="good_type" name="good_type" required>
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
                    <label for="weight" class="form-label">Weight *</label>
                    <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ old('weight') }}">
                    @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a weight.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="no_of_units" class="form-label">No. of Units *</label>
                    <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="{{ old('no_of_units') }}">
                    @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter number of units.</div>
                </div>
                <div class="col-md-4 mb-3">
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

            <div class="row">
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
                <div class="col-md-4 mb-3">
                    <label for="memo_id" class="form-label">Memo ID</label>
                    <input type="number" class="form-control" id="memo_id" name="memo_id" value="{{ old('memo_id') }}">
                    @error('memo_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a memo ID.</div>
                </div>
            </div>

            

        </div>
        <!--end::Body-->
        <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-info" type="submit">Submit form</button>
            <a href="{{ route('lorry_receipts.index') }}" class="btn btn-info">Back to List</a>
        </div>
        <!--end::Footer-->
    </form>
    <!--end::Form-->
    <!--begin::JavaScript-->
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
            // const transporterSelect = document.getElementById('transporter_id');
            // const truckSelect = document.getElementById('truck_id');

            // // Function to update trucks based on selected transporter
            // function updateTrucks() {
            //     const selectedTransporter = transporterSelect.value;
            //     console.log('Selected Transporter:', selectedTransporter);
                
            //     if (!selectedTransporter) {
            //         truckSelect.innerHTML = '<option value="">Select Truck</option>';
            //         return;
            //     }

            //     // Make AJAX request to get trucks for selected transporter
            //     fetch(`/api/transporters/${selectedTransporter}/trucks`, {
            //         headers: {
            //             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            //             'Content-Type': 'application/json'
            //         }
            //     })
            //     .then(response => {
            //         console.log('Response status:', response.status);
            //         if (!response.ok) {
            //             throw new Error('Network response was not ok');
            //         }
            //         return response.json();
            //     })
            //     .then(trucks => {
            //         console.log('Received trucks:', trucks);
            //         let options = '<option value="">Select Truck</option>';
            //         trucks.forEach(truck => {
            //             console.log('Adding truck:', truck);
            //             options += `<option value="${truck.id}">${truck.truck_number}</option>`;
            //         });
            //         truckSelect.innerHTML = options;
            //     })
            //     .catch(error => {
            //         console.error('Error:', error);
            //         truckSelect.innerHTML = '<option value="">Error loading trucks</option>';
            //     });
            // }

            // // Update trucks when transporter changes
            // transporterSelect.addEventListener('change', updateTrucks);

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
    
@endsection
