@extends('layouts.app')

@section('content')
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
                    @foreach($trucks as $truck)
                        <option value="{{ $truck->id }}" {{ $lr->truck_id == $truck->id ? 'selected' : '' }}>
                            {{ $truck->number }}
                        </option>
                    @endforeach
                </select>
                @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="customer_id" class="form-label">Customer *</label>
                <select class="form-select" id="customer_id" name="customer_id" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ $lr->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
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

        <div class="row">
            <div class="col-md-12 mb-3">
                <button type="button" class="btn btn-secondary" onclick="resetDropdowns()">Reset Dropdowns</button>
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
@endsection