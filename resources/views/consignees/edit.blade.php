@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Edit Consignee</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('consignees.update', $consignee->id) }}" id="consigneeForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                <input type="hidden" name="country_id" id="country_id_hidden" value="{{ old('country_id', $consignee->country_id) }}">
                <input type="hidden" name="state_id" id="state_id_hidden" value="{{ old('state_id', $consignee->state_id) }}">
                <input type="hidden" name="city_id" id="city_id_hidden" value="{{ old('city_id', $consignee->city_id) }}">

                <div class="mb-3">
                    <label for="name" class="form-label">Name *</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $consignee->name) }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address *</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required>{{ old('address', $consignee->address) }}</textarea>
                    @error('address')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="country_id" class="form-label">Country *</label>
                        <select class="form-select" id="country_id" name="country_id" required>
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id', $consignee->country_id) == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="state_id" class="form-label">State *</label>
                        <select class="form-select" id="state_id" name="state_id" required>
                            <option value="">Select State</option>
                            @if(old('country_id', $consignee->country_id))
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ old('state_id', $consignee->state_id) == $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('state_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="city_id" class="form-label">City *</label>
                        <select class="form-select" id="city_id" name="city_id" required>
                            <option value="">Select City</option>
                            @if(old('state_id', $consignee->state_id))
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ old('city_id', $consignee->city_id) == $city->id ? 'selected' : '' }}>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('city_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="contact_number" class="form-label">Contact Number *</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="{{ old('contact_number', $consignee->contact_number) }}" required>
                    @error('contact_number')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Update Consignee</button>
                    <a href="{{ route('consignees.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Handle country change
        $('#country_id').change(function() {
            const countryId = $(this).val();
            $('#country_id_hidden').val(countryId);
            
            if (countryId) {
                $.ajax({
                    url: '/api/states/' + countryId,
                    type: 'GET',
                    success: function(data) {
                        $('#state_id').empty().append('<option value="">Select State</option>');
                        $('#city_id').empty().append('<option value="">Select City</option>');
                        
                        data.states.forEach(function(state) {
                            $('#state_id').append('<option value="' + state.id + '">' + state.name + '</option>');
                        });
                    }
                });
            } else {
                $('#state_id').empty().append('<option value="">Select State</option>');
                $('#city_id').empty().append('<option value="">Select City</option>');
                $('#country_id_hidden').val('');
                $('#state_id_hidden').val('');
                $('#city_id_hidden').val('');
            }
        });

        // Handle state change
        $('#state_id').change(function() {
            const stateId = $(this).val();
            $('#state_id_hidden').val(stateId);
            
            if (stateId) {
                $.ajax({
                    url: '/api/cities/' + stateId,
                    type: 'GET',
                    success: function(data) {
                        $('#city_id').empty().append('<option value="">Select City</option>');
                        
                        data.cities.forEach(function(city) {
                            $('#city_id').append('<option value="' + city.id + '">' + city.name + '</option>');
                        });
                    }
                });
            } else {
                $('#city_id').empty().append('<option value="">Select City</option>');
                $('#city_id_hidden').val('');
            }
        });

        // Handle city change
        $('#city_id').change(function() {
            const cityId = $(this).val();
            $('#city_id_hidden').val(cityId);
        });
    });
</script>
@endsection
