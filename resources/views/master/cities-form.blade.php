@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h3>{{ isset($city) ? 'Edit City' : 'Add New City' }}</h3>
        </div>
        <div class="card-body">
            <form action="{{ isset($city) ? route('master.cities.update', $city->id) : route('master.cities.store') }}" method="POST">
                @csrf
                @if(isset($city))
                    @method('PUT')
                @endif
                
                <div class="mb-3">
                    <label for="name" class="form-label">City Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           value="{{ old('name', isset($city) ? $city->name : '') }}">
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="state_id" class="form-label">State *</label>
                    <select class="form-select" id="state_id" name="state_id" required>
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" 
                                    {{ old('state_id', isset($city) ? $city->state_id : '') == $state->id ? 'selected' : '' }}>
                                {{ $state->name }} ({{ $state->country->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('state_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('master.cities.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
