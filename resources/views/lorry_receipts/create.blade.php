@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Add New Lorry Receipt</h2>
    <form action="{{ route('lorry_receipts.store') }}" method="POST">
        @csrf

        <h5 class="mt-4 mb-3">Trip & Parties</h5>
        <hr class="mb-3 mt-0">
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="customer_id" class="form-label">Customer *</label>
                <select class="form-select" id="customer_id" name="customer_id" required>
    <option value="">Select Customer</option>
    @foreach($customers as $customer)
        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
    @endforeach
</select>
                @error('customer_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="lr_number" class="form-label">LR Number *</label>
                <input type="text" class="form-control" id="lr_number" name="lr_number" required value="{{ old('lr_number') }}">
                @error('lr_number') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="delivery_status" class="form-label">Delivery Status</label>
                <select class="form-select" id="delivery_status" name="delivery_status" disabled>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ old('delivery_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="delivery_status">
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
        <option value="{{ $consignee->id }}" {{ old('consignee_id') == $consignee->id ? 'selected' : '' }}>
            {{ $consignee->name }}
        </option>
    @endforeach
</select>
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
            </div>
            <div class="col-md-6 mb-3">
                <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" readonly>
            </div>

                
            </div>
        </div>

        <h5 class="mt-4 mb-3">Route & Dates</h5>
        <hr class="mb-3 mt-0">


        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="gr_date" class="form-label">GR Date *</label>
                <input type="date" class="form-control" id="gr_date" name="gr_date" required value="{{ old('gr_date') }}">
                @error('gr_date') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="source_city" class="form-label">Source City *</label>
                <input type="text" class="form-control" id="source_city" name="source_city" required value="{{ old('source_city') }}" readonly>
                @error('source_city') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="destination_city" class="form-label">Destination City *</label>
                <input type="text" class="form-control" id="destination_city" name="destination_city" required value="{{ old('destination_city') }}" readonly>
                @error('destination_city') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="source_state" class="form-label">Source State *</label>
                <input type="text" class="form-control" id="source_state" name="source_state" required value="{{ old('source_state') }}" readonly>
                @error('source_state') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="destination_state" class="form-label">Destination State *</label>
                <input type="text" class="form-control" id="destination_state" name="destination_state" required value="{{ old('destination_state') }}" readonly>
                @error('destination_state') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <h5 class="mt-4 mb-3">Goods & Charges</h5>
        <hr class="mb-3 mt-0">


        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="lane" class="form-label">Lane *</label>
                <input type="text" class="form-control" id="lane" name="lane" value="Road" readonly required>
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
            </div>
            <div class="col-md-4 mb-3">
                <label for="weight" class="form-label">Weight *</label>
                <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ old('weight') }}">
                @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="no_of_units" class="form-label">No. of Units *</label>
                <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="{{ old('no_of_units') }}">
                @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="fr_rate" class="form-label">Freight Rate *</label>
                <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ old('fr_rate') }}">
                @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="amount" class="form-label">Amount *</label>
                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount') }}" readonly>
                @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="gr_number" class="form-label">GR Number *</label>
                <input type="text" class="form-control" id="gr_number" name="gr_number" required value="{{ old('gr_number') }}">
                @error('gr_number') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="damage_qty" class="form-label">Damage Quantity</label>
                <input type="number" step="0.01" class="form-control" id="damage_qty" name="damage_qty" value="{{ old('damage_qty') }}" disabled>
                @error('damage_qty') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="damage_rate" class="form-label">Damage Rate</label>
                <input type="number" step="0.01" class="form-control" id="damage_rate" name="damage_rate" value="{{ old('damage_rate') }}" disabled>
                @error('damage_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="shortage_qty" class="form-label">Shortage Quantity</label>
                <input type="number" step="0.01" class="form-control" id="shortage_qty" name="shortage_qty" value="{{ old('shortage_qty') }}" disabled>
                @error('shortage_qty') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="shortage_rate" class="form-label">Shortage Rate</label>
                <input type="number" step="0.01" class="form-control" id="shortage_rate" name="shortage_rate" value="{{ old('shortage_rate') }}" disabled>
                @error('shortage_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="service_tax" class="form-label">Service Tax</label>
                <input type="number" step="0.01" class="form-control" id="service_tax" name="service_tax" value="{{ old('service_tax') }}" disabled>
                @error('service_tax') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="swachh_bharat_cess" class="form-label">Swachh Bharat Cess</label>
                <input type="number" step="0.01" class="form-control" id="swachh_bharat_cess" name="swachh_bharat_cess" value="{{ old('swachh_bharat_cess') }}" disabled>
                @error('swachh_bharat_cess') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="memo_id" class="form-label">Memo ID</label>
                <input type="number" class="form-control" id="memo_id" name="memo_id" value="{{ old('memo_id') }}">
                @error('memo_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Create LR</button>
        <a href="{{ route('lorry_receipts.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
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
                sourceCityInput.value = data.city_id ? 'Loading...' : '';
                sourceStateInput.value = data.state_id ? 'Loading...' : '';

                // Fetch city and state names using IDs
                if (data.city_id || data.state_id) {
                    const fetchCityState = async () => {
                        try {
                            // Fetch city name
                            if (data.city_id) {
                                const cityResponse = await fetch(`{{ route('cities.show', '__id__') }}`.replace('__id__', data.city_id));
                                if (cityResponse.ok) {
                                    const cityData = await cityResponse.json();
                                    sourceCityInput.value = cityData.name || '';
                                } else {
                                    console.error('❌ Failed to fetch city:', await cityResponse.text());
                                    sourceCityInput.value = '';
                                }
                            }

                            // Fetch state name
                            if (data.state_id) {
                                const stateResponse = await fetch(`{{ route('states.show', '__id__') }}`.replace('__id__', data.state_id));
                                if (stateResponse.ok) {
                                    const stateData = await stateResponse.json();
                                    sourceStateInput.value = stateData.name || '';
                                } else {
                                    console.error('❌ Failed to fetch state:', await stateResponse.text());
                                    sourceStateInput.value = '';
                                }
                            }
                        } catch (error) {
                            console.error('❌ Error fetching city/state:', error);
                            sourceCityInput.value = '';
                            sourceStateInput.value = '';
                        }
                    };

                    fetchCityState();
                }
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
                destinationCityInput.value = data.city_id ? 'Loading...' : '';
                destinationStateInput.value = data.state_id ? 'Loading...' : '';

                // Fetch city and state names using IDs
                if (data.city_id || data.state_id) {
                    const fetchCityState = async () => {
                        try {
                            // Fetch city name
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
