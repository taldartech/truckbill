@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h3>{{ isset($country) ? 'Edit Country' : 'Add New Country' }}</h3>
        </div>
        <div class="card-body">
            <form action="{{ isset($country) ? route('master.countries.update', $country->id) : route('master.countries.store') }}" method="POST">
                @csrf
                @if(isset($country))
                    @method('PUT')
                @endif
                
                <div class="mb-3">
                    <label for="name" class="form-label">Country Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           value="{{ old('name', isset($country) ? $country->name : '') }}">
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="code" class="form-label">Country Code *</label>
                    <input type="text" class="form-control" id="code" name="code" required 
                           value="{{ old('code', isset($country) ? $country->code : '') }}">
                    @error('code')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('master.countries.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
