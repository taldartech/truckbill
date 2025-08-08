{{-- @extends('layouts.app')

@section('content')
@php
    $transporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)->get();
    $consignees = \App\Models\Consignee::where('company_id', auth()->user()->company_id)->get();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function() {
    const transporterSelect = document.getElementById('transporter_id');
    const truckSelect = document.getElementById('truck_id');

    // Function to update trucks based on selected transporter
    function updateTrucks() {
        const selectedTransporter = transporterSelect.value;
        console.log('Selected Transporter:', selectedTransporter);
        
        if (!selectedTransporter) {
            truckSelect.innerHTML = '<option value="">Select Truck</option>';
            return;
        }

        // Make AJAX request to get trucks for selected transporter
        fetch(`/api/transporters/${selectedTransporter}/trucks`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(trucks => {
            console.log('Received trucks:', trucks);
            let options = '<option value="">Select Truck</option>';
            trucks.forEach(truck => {
                console.log('Adding truck:', truck);
                options += `<option value="${truck.id}" ${truck.id == {{ $lr->truck_id }} ? 'selected' : ''}>${truck.truck_number}</option>`;
            });
            truckSelect.innerHTML = options;
        })
        .catch(error => {
            console.error('Error:', error);
            truckSelect.innerHTML = '<option value="">Error loading trucks</option>';
        });
    }

    // Update trucks when transporter changes
    transporterSelect.addEventListener('change', updateTrucks);

    // Initialize trucks based on current transporter
    if (transporterSelect.value) {
        updateTrucks();
    }
});
</script>
<div class="container mt-4">
    <h2>Edit Lorry Receipt</h2>
    <form action="{{ route('lorry_receipts.update', $lr->id) }}" method="POST">
        @csrf
        @method('PUT')

        <h5 class="mt-4 mb-3">Trip & Parties</h5>
        <hr class="mb-3 mt-0">
        
        <div class="row">
                        <div class="col-md-4 mb-3">
                <label for="transporter_id" class="form-label">Transporter *</label>
                <select class="form-select" id="transporter_id" name="transporter_id" required>
                    <option value="">Select Transporter</option>
                    @foreach($transporters as $transporter)
                        <option value="{{ $transporter->id }}" {{ $lr->transporter_id == $transporter->id ? 'selected' : '' }}>
                            {{ $transporter->name }}
                        </option>
                    @endforeach
                </select>
                @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="truck_id" class="form-label">Truck *</label>
                <select class="form-select" id="truck_id" name="truck_id" required>
                    <option value="">Select Truck</option>
                </select>
                @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="customer_id" class="form-label">Customer *</label>
                <select class="form-select" id="customer_id" name="customer_id" required>
                    <option value="">Select Customer</option>
                    @foreach($consignees as $consignee)
                        <option value="{{ $consignee->id }}" {{ $lr->consignee_id == $consignee->id ? 'selected' : '' }}>{{ $consignee->name }}</option>
                    @endforeach
                </select>
                @error('customer_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="lr_number" class="form-label">LR Number *</label>
                <input type="text" class="form-control" id="lr_number" name="lr_number" required value="{{ $lr->lr_number }}">
                @error('lr_number') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="delivery_status" class="form-label">Delivery Status</label>
                <select class="form-select" id="delivery_status" name="delivery_status" disabled>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ $lr->delivery_status == $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="delivery_status" value="{{ $lr->delivery_status }}">
                <small class="text-muted">It will be updated from freight booking entries.</small>
                @error('delivery_status') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="consignor_id" class="form-label">Consignor *</label>
                <select class="form-select" id="consignor_id" name="consignor_id" required>
                    <option value="">Select Consignor</option>
                    @foreach($consignees as $consignee)
                        <option value="{{ $consignee->id }}" {{ $lr->consignor_id == $consignee->id ? 'selected' : '' }}>
                            {{ $consignee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="consignor_contact_number" class="form-label">Consignor Contact Number</label>
                <input type="text" class="form-control" id="consignor_contact_number" name="consignor_contact_number" value="{{ $lr->consignor->contact_number ?? '' }}" readonly>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="consignee_id" class="form-label">Consignee *</label>
                <select class="form-select" id="consignee_id" name="consignee_id" required>
                    <option value="">Select Consignee</option>
                    @foreach($consignees as $consignee)
                        <option value="{{ $consignee->id }}" {{ $lr->consignee_id == $consignee->id ? 'selected' : '' }}>
                            {{ $consignee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" value="{{ $lr->consignee->contact_number ?? '' }}" readonly>
            </div>
        </div>

        

        <h5 class="mt-4 mb-3">Route & Dates</h5>
        <hr class="mb-3 mt-0">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="gr_date" class="form-label">GR Date *</label>
                <input type="date" class="form-control" id="gr_date" name="gr_date" required value="{{ $lr->gr_date }}">
                @error('gr_date') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="source_city" class="form-label">Source City *</label>
                <input type="text" class="form-control" id="source_city" name="source_city" required value="{{ $lr->source_city }}" readonly>
                @error('source_city') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="source_state" class="form-label">Source State *</label>
                <input type="text" class="form-control" id="source_state" name="source_state" required value="{{ $lr->source_state }}" readonly>
                @error('source_state') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="destination_city" class="form-label">Destination City *</label>
                <input type="text" class="form-control" id="destination_city" name="destination_city" required value="{{ $lr->destination_city }}" readonly>
                @error('destination_city') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="destination_state" class="form-label">Destination State *</label>
                <input type="text" class="form-control" id="destination_state" name="destination_state" required value="{{ $lr->destination_state }}" readonly>
                @error('destination_state') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <h5 class="mt-4 mb-3">Goods & Charges</h5>
        <hr class="mb-3 mt-0">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="lane" class="form-label">Lane *</label>
                <input type="text" class="form-control" id="lane" name="lane" value="{{ $lr->lane }}" readonly required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="good_type" class="form-label">Goods Type *</label>
                <select class="form-select" id="good_type" name="good_type" required>
                    <option value="">Select Goods Type</option>
                    @foreach($goodsTypes as $goodsType)
                        <option value="{{ $goodsType->name }}" {{ $lr->good_type == $goodsType->name ? 'selected' : '' }}>
                            {{ $goodsType->name }}
                        </option>
                    @endforeach
                </select>
                @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="weight" class="form-label">Weight *</label>
                <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ $lr->weight }}">
                @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="no_of_units" class="form-label">No. of Units *</label>
                <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="{{ $lr->no_of_units }}">
                @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="fr_rate" class="form-label">Freight Rate *</label>
                <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ $lr->fr_rate }}">
                @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="amount" class="form-label">Amount *</label>
                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ $lr->amount }}">
                @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
           
            
            <div class="col-md-4 mb-3">
                <label for="damage_amount" class="form-label">Damage Amount</label>
                <input type="number" step="0.01" class="form-control" id="damage_amount" name="damage_amount" value="{{ $lr->damage_amount }}" disabled>
                @error('damage_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="shortage_amount" class="form-label">Shortage Amount</label>
                <input type="number" step="0.01" class="form-control" id="shortage_amount" name="shortage_amount" value="{{ $lr->shortage_amount }}" disabled>
                @error('shortage_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            
           
            
        </div>
           
            <div class="col-md-4 mb-3">
                <label for="memo_id" class="form-label">Memo ID</label>
                <input type="number" class="form-control" id="memo_id" name="memo_id" value="{{ $lr->memo_id }}">
                @error('memo_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Update LR</button>
        <a href="{{ route('lorry_receipts.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Handle consignor details
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
                const response = await fetch(`{{ route('consignees.details', '__id__') }}`);
                if (!response.ok) throw new Error('Failed to fetch consignor details');
                const consignor = await response.json();

                consignorContact.value = consignor.contact_number;
                sourceCityInput.value = consignor.city;
                sourceStateInput.value = consignor.state;
            } catch (error) {
                console.error('❌ Error fetching consignor details:', error);
                consignorContact.value = '';
                sourceCityInput.value = '';
                sourceStateInput.value = '';
            }
        });
    }

    // Handle consignee details
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
                if (!response.ok) throw new Error('Failed to fetch consignee details');
                const consignee = await response.json();

                consigneeContact.value = consignee.contact_number;
                destinationCityInput.value = consignee.city;
                destinationStateInput.value = consignee.state;
                            if (data.city_id) {
                                const cityResponse = await fetch(`{{ route('cities.show', '__id__') }}`.replace('__id__', data.city_id));
                                if (cityResponse.ok) {
                                    const cityData = await cityResponse.json();
                                    destinationCityInput.value = cityData.name || '';
                                } else {
                                    console.error('❌ Failed to fetch city:', await cityResponse.text());
                                    destinationCityInput.value = '';
                                }
                            }

                            // Fetch state name
                            if (data.state_id) {
                                const stateResponse = await fetch(`{{ route('states.show', '__id__') }}`.replace('__id__', data.state_id));
                                if (stateResponse.ok) {
                                    const stateData = await stateResponse.json();
                                    destinationStateInput.value = stateData.name || '';
                                } else {
                                    console.error('❌ Failed to fetch state:', await stateResponse.text());
                                    destinationStateInput.value = '';
                                }
                            }
                        } catch (error) {
                            console.error('❌ Error fetching city/state:', error);
                            destinationCityInput.value = '';
                            destinationStateInput.value = '';
                        }
                    };

                    fetchCityState();
                }
            } catch (error) {
                console.error('❌ Error:', error);
                consigneeContact.value = '';
                destinationCityInput.value = '';
                destinationStateInput.value = '';
            }
        });
    }

    // Calculate amount
    const frRateInput = document.getElementById('fr_rate');
    const noOfUnitsInput = document.getElementById('no_of_units');
    const amountInput = document.getElementById('amount');
    
    function calculateAmount() {
        const frRate = parseFloat(frRateInput.value) || 0;
        const noOfUnits = parseInt(noOfUnitsInput.value) || 0;
        
        if (!isNaN(frRate) && !isNaN(noOfUnits)) {
            const amount = (frRate * noOfUnits).toFixed(2);
            amountInput.value = amount;
        } else {
            amountInput.value = '';
        }
    }
    
    frRateInput.addEventListener('input', calculateAmount);
    noOfUnitsInput.addEventListener('input', calculateAmount);
});
</script>
@endsection

@section('scripts')
<script>
    // Initialize dropdowns
    const transporterSelect = document.getElementById('transporter_id');
    const truckSelect = document.getElementById('truck_id');
    const consignorSelect = document.getElementById('consignor_id');
    const consigneeSelect = document.getElementById('consignee_id');

    // Function to update options
    function updateOptions() {
        const selectedConsignor = consignorSelect.value;
        const selectedConsignee = consigneeSelect.value;

        // Get all options
        const consignorOptions = Array.from(consignorSelect.options);
        const consigneeOptions = Array.from(consigneeSelect.options);

        // Remove the selected option from the other dropdown
        consignorOptions.forEach(option => {
            if (option.value === selectedConsignee) {
                option.style.display = 'none';
            } else {
                option.style.display = '';
            }
        });

        consigneeOptions.forEach(option => {
            if (option.value === selectedConsignor) {
                option.style.display = 'none';
            } else {
                option.style.display = '';
            }
        });
    }

    

  


    
    consignorSelect.addEventListener('change', () => {
        updateOptions();
        // Update contact number
        const selectedOption = consignorSelect.options[consignorSelect.selectedIndex];
        document.getElementById('consignor_contact_number').value = selectedOption.getAttribute('data-contact');
    });

    consigneeSelect.addEventListener('change', () => {
        updateOptions();
        // Update contact number
        const selectedOption = consigneeSelect.options[consigneeSelect.selectedIndex];
        document.getElementById('consignee_contact_number').value = selectedOption.getAttribute('data-contact');
    });

    
    updateOptions();
</script>
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
@endsection --}}

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
    <form method="POST" action="{{ route('lorry_receipts.update', $lorry_receipt->id) }}" id="lorryReceiptForm" class="needs-validation" novalidate="">
        @csrf
        @method('PUT')
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        <div class="card-body">
            <!-- General Details -->
            <div class="row mb-3">
              
                 <div class="col-md-4">
                    <label for="lr_number" class="form-label">LR Number *</label>
                    <input type="text" class="form-control" id="lr_number" name="lr_number" value="{{ old('lr_number') ?? $lorry_receipt->lr_number }}" readonly>
                    @error('lr_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
               
                  <div class="col-md-4">
                    <label for="gr_date" class="form-label">GR Date *</label>
                    <input type="date" class="form-control" id="gr_date" name="gr_date" required value="{{ old('gr_date') ?? $lorry_receipt->gr_date }}">
                    @error('gr_date') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a GR date.</div>
                </div>
              
                  <div class="col-md-4">
                    <label for="customer_id" class="form-label">Customer *</label>
                    <select class="form-select" id="customer_id" name="customer_id" required>
                        <option value="">Select Customer</option>
                        @foreach($consignees as $consignee)
                            <option value="{{ $consignee->id }}" {{ old('customer_id') ?? $lorry_receipt->consignee_id == $consignee->id ? 'selected' : '' }}>
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
                            <option value="{{ $truck->id }}" {{ old('truck_id') ?? $lorry_receipt->truck_id == $truck->id ? 'selected' : '' }}>
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
                        <option value="owner" {{ old('transporter_type') ?? $lorry_receipt->transporter_type == 'owner' ? 'selected' : '' }}>Truck Owner</option>
                        <option value="agency" {{ old('transporter_type') ?? $lorry_receipt->transporter_type == 'agency' ? 'selected' : '' }}>Agency</option>
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
                            <option value="{{ $consignee->id }}" {{ old('consignor_id') ?? $lorry_receipt->consignor_id == $consignee->id ? 'selected' : '' }}>
                                {{ $consignee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('consignor_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a consignor.</div>
                </div>
                <div class="col-md-4">
                    <label for="consignor_contact_number" class="form-label">Consignor Contact Number</label>
                    <input type="text" class="form-control" id="consignor_contact_number" name="consignor_contact_number" readonly value="{{ old('consignor_contact_number') ?? $lorry_receipt->consignor_contact_number }}">
                </div>
                <div class="col-md-4">
                    <label for="consignee_id" class="form-label">Consignee *</label>
                    <select class="form-select" id="consignee_id" name="consignee_id" required>
                        <option value="">Select Consignee</option>
                        @foreach($consignees as $consignee)
                            <option value="{{ $consignee->id }}" {{ old('consignee_id') ?? $lorry_receipt->consignee_id == $consignee->id ? 'selected' : '' }}>
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
                    <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" readonly value="{{ old('consignee_contact_number') ?? $lorry_receipt->consignee_contact_number }}">
                </div>
            </div>
            <!-- Route Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Route</h5></div></div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="source_city" class="form-label">Source City *</label>
                        <input type="text" class="form-control" id="source_city" name="source_city" required value="{{ old('source_city') ?? $lorry_receipt->source_city }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="source_state" class="form-label">Source State *</label>
                        <input type="text" class="form-control" id="source_state" name="source_state" required value="{{ old('source_state') ?? $lorry_receipt->source_state }}" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label for="destination_city" class="form-label">Destination City *</label>
                        <input type="text" class="form-control" id="destination_city" name="destination_city" required value="{{ old('destination_city') ?? $lorry_receipt->destination_city }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="destination_state" class="form-label">Destination State *</label>
                        <input type="text" class="form-control" id="destination_state" name="destination_state" required value="{{ old('destination_state') ?? $lorry_receipt->destination_state }}" readonly>
                    </div>
                </div>
            </div>
            <!-- Goods & Charges Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Goods & Charges</h5></div></div>
                 <div class="row mb-2">
                 
                    <input type="hidden" id="lane" name="lane" value="Road">
                    <div class="col-md-4">
                        <label for="good_type" class="form-label ms-3">Goods Type *</label>
                        <select class="form-select " id="good_type" name="good_type" required>
                            <option value="">Select Goods Type</option>
                            @foreach($goodsTypes as $goodsType)
                            <option value="{{ $goodsType->name }}" 
                                {{ old('good_type', $lorry_receipt->good_type) == $goodsType->name ? 'selected' : '' }}>
                                {{ $goodsType->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a goods type.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="weight" class="form-label ">Weight *</label>
                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ old('weight' , $lorry_receipt->weight) }}">
                        @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a weight.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="no_of_units" class="form-label">No. of Units *</label>
                        <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="{{ old('no_of_units' , $lorry_receipt->no_of_units) }}">
                        @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter number of units.</div>
                    </div>
                </div>
            

                <div class="row mb-2">
                    <div class="col-md-4">
                        <label for="fr_rate" class="form-label">Freight Rate *</label>
                        <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ old('fr_rate' , $lorry_receipt->fr_rate) }}">
                        @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="damage_amount" class="form-label">Damage Amount</label>
                        <input type="number" step="0.01" class="form-control" id="damage_amount" name="damage_amount" value="{{ $lorry_receipt->damage_amount }}" disabled>
                        @error('damage_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="shortage_amount" class="form-label">Shortage Amount</label>
                        <input type="number" step="0.01" class="form-control" id="shortage_amount" name="shortage_amount" value="{{ $lorry_receipt->shortage_amount }}" disabled>
                        @error('shortage_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    
                   
                </div>
                
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Amount *</label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount' , $lorry_receipt->amount) }}">
                        @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter an amount.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="memo_id" class="form-label">Memo ID</label>
                        <input type="number" class="form-control" id="memo_id" name="memo_id" value="{{ $lorry_receipt->memo_id }}">
                        @error('memo_id') <div class="text-danger">{{ $message }}</div> @enderror
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
                                optionsHtml += `<option value="${transporter.id}">${transporter.name}</option>`;
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
                            const { pan_active, is_owner } = data;
        
                            if (pan_active == 0 || pan_active === false) {
                                tdsRateDisplay.textContent = `TDS RATE= ${tdsPercentages.default}% (Default - PAN not active)`;
                            } else {
                                if (is_owner == 1) {
                                    tdsRateDisplay.textContent = `TDS RATE= ${tdsPercentages.owner}%`;
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

