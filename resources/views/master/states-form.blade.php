@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h3>{{ isset($state) ? 'Edit State' : 'Add New State' }}</h3>
        </div>
        <div class="card-body">
            <form action="{{ isset($state) ? route('master.states.update', $state->id) : route('master.states.store') }}" method="POST">
                @csrf
                @if(isset($state))
                    @method('PUT')
                @endif
                
                <div class="mb-3">
                    <label for="name" class="form-label">State Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           value="{{ old('name', isset($state) ? $state->name : '') }}">
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="country_id" class="form-label">Country *</label>
                    <select class="form-select" id="country_id" name="country_id" required>
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" 
                                    {{ old('country_id', isset($state) ? $state->country_id : '') == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="code" class="form-label">Code *</label>
                    <input type="text" class="form-control" id="code" name="code" 
                           value="{{ old('code', isset($state) ? $state->code : '') }}" required>
                    @error('code')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('master.states.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
