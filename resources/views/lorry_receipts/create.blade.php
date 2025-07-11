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
                <div class="col-md-4" id="agencyTransporterMain" style="display: none">
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
                <div class="col-md-4" id="ownerAgencySection" style="display: none;">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input " checked type="checkbox" name="owner_agency[]" id="truck_owner" value="owner">
                        <label class="form-check-label" for="truck_owner">Truck Owner</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="owner_agency[]" id="agency" value="agency">
                        <label class="form-check-label" for="agency">Agency</label>
                    </div>

                    {{-- <div id="ownerTransporterSelect" class="mt-3" style="display: none;">
                        <label for="owner_transporter_id" class="form-label">Select Truck Owner</label>
                        <select class="form-select" id="owner_transporter_id" name="owner_transporter_id">
                            <option value="">Select Transporter</option>
                        </select>
                        <div class="invalid-feedback">Please select an owner transporter.</div>
                    </div> --}}

                    <div id="ownerTransporterSelect" class="col-md-4 mt-3" style="display: none;">
                        <label class="form-label">Truck Owner</label>
                        <input type="text" id="transporter_name" class="form-control" readonly>
                    </div>


                   
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
        // Handle truck owner/agency selection
        const truckSelect = document.getElementById('truck_id');
        const truckOwnerCheckbox = document.getElementById('truck_owner');
        const agencyCheckbox = document.getElementById('agency');
        const ownerTransporterSelect = document.getElementById('owner_transporter_id');
        const ownerAgencySection = document.getElementById('ownerAgencySection');

        // Function to update owner transporters based on selected truck
        async function updateOwnerTransporters() {
            const truckId = truckSelect.value;
            if (!truckId) {
                ownerTransporterSelect.innerHTML = '<option value="">Select Transporter</option>';
                return;
            }

            try {
                const response = await fetch(`/api/trucks/${truckId}/owner-transporters`);
                const data = await response.json();
                
                // Clear existing options
                ownerTransporterSelect.innerHTML = '<option value="">Select Transporter</option>';
                
                // Add owner transporters
                data.forEach(transporter => {
                    const option = document.createElement('option');
                    option.value = transporter.id;
                    option.textContent = transporter.name;
                    ownerTransporterSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error fetching owner transporters:', error);
            }
        }
 // Function to update owner transporters based on selected truck
// Update the agency checkbox event listener
agencyCheckbox.addEventListener('change', () => {
    const mainTransporterDiv = document.getElementById('agencyTransporterMain');
    if (agencyCheckbox.checked) {
        mainTransporterDiv.style.display = 'block';
    } else {
        mainTransporterDiv.style.display = 'none';
        mainTransporterDiv.querySelector('select').value = ''; // Clear the selected value
    }
});
       

        // Event listeners
        truckSelect.addEventListener('change', () => {
            const ownerAgencySection = document.getElementById('ownerAgencySection');
            if (truckSelect.value) {
                ownerAgencySection.style.display = 'block';
                updateOwnerTransporters();
                updateAgencyTransporters();
            } else {
                ownerAgencySection.style.display = 'none';
                // Reset checkboxes
                truckOwnerCheckbox.checked = false;
                agencyCheckbox.checked = false;
                // Hide transporter select boxes
                document.getElementById('agencyTransporterMain').style.display = 'none';
                document.getElementById('ownerTransporterSelect').style.display = 'none';
            }
        });
        truckOwnerCheckbox.addEventListener('change', () => {
            const ownerTransporterDiv = document.getElementById('ownerTransporterSelect');
            if (truckOwnerCheckbox.checked) {
                ownerTransporterDiv.style.display = 'block';
                updateOwnerTransporters(); // Fetch and populate the options
            } else {
                ownerTransporterDiv.style.display = 'none';
                ownerTransporterSelect.value = '';
            }
        });

        agencyCheckbox.addEventListener('change', () => {
            const agencyTransporterDiv = document.getElementById('agencyTransporterSelect');
            if (agencyCheckbox.checked) {
                agencyTransporterDiv.style.display = 'block';
                // updateAgencyTransporters(); // Fetch and populate the options
            } else {
                agencyTransporterDiv.style.display = 'none';
                agencyTransporterSelect.value = '';
            }
        });

        // Initial update
        updateOwnerTransporters();
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
                        document.getElementById('ownerTransporterSelect').style.display = 'block';
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
