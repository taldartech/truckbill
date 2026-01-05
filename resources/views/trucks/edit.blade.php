@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit Truck</h2>
    <form action="{{ route('trucks.update', $truck->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="transporter_id" class="form-label">Truck Owner *</label>
            <select class="form-select" id="transporter_id" name="transporter_id" required>
                <option value="">Select</option>
                @foreach($transporters as $transporter)
                    <option value="{{ $transporter->id }}" {{ old('transporter_id', $truck->transporter_id) == $transporter->id ? 'selected' : '' }}>{{ $transporter->name }}</option>
                @endforeach
            </select>
            @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="truck_number" class="form-label">Truck Number *</label>
            <input type="text" class="form-control" placeholder="Enter Truck Number" id="truck_number" name="truck_number" required value="{{ old('truck_number', $truck->truck_number) }}">
            @error('truck_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
    <label for="truck_type" class="form-label">Truck Type *</label>
    <select class="form-select" id="truck_type" name="truck_type" required>
        <option value="">Select</option>
        <option value="Open" {{ old('truck_type', $truck->truck_type) == 'Open' ? 'selected' : '' }}>Open</option>
        <option value="Closed" {{ old('truck_type', $truck->truck_type) == 'Closed' ? 'selected' : '' }}>Closed</option>
        <option value="Container" {{ old('truck_type', $truck->truck_type) == 'Container' ? 'selected' : '' }}>Container</option>
        <option value="Trailer" {{ old('truck_type', $truck->truck_type) == 'Trailer' ? 'selected' : '' }}>Trailer</option>
        <option value="Tanker" {{ old('truck_type', $truck->truck_type) == 'Tanker' ? 'selected' : '' }}>Tanker</option>
        <option value="Refrigerated" {{ old('truck_type', $truck->truck_type) == 'Refrigerated' ? 'selected' : '' }}>Refrigerated</option>
        <option value="Flatbed" {{ old('truck_type', $truck->truck_type) == 'Flatbed' ? 'selected' : '' }}>Flatbed</option>
        <option value="Tipper" {{ old('truck_type', $truck->truck_type) == 'Tipper' ? 'selected' : '' }}>Tipper</option>
        <option value="Other" {{ old('truck_type', $truck->truck_type) == 'Other' ? 'selected' : '' }}>Other</option>
    </select>
    @error('truck_type') <div class="text-danger">{{ $message }}</div> @enderror
</div>
        <div class="mb-3">
    <label for="driver_id" class="form-label">Driver</label>
    <div class="input-group">
        <select class="form-select" id="driver_id" name="driver_id">
            <option value="">Select</option>
            @foreach($drivers as $driver)
                <option value="{{ $driver->id }}" {{ old('driver_id', $truck->driver_id) == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->mobile }})</option>
            @endforeach
        </select>
        <a href="{{ route('drivers.create') }}" class="btn btn-outline-secondary" target="_blank">Add New Driver</a>
    </div>
    @error('driver_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>
        <div class="mb-3">
            <label for="registration_date" class="form-label">Registration Date *</label>
            <input type="date" class="form-control" placeholder="Enter Registration Date" id="registration_date" name="registration_date" required value="{{ old('registration_date', $truck->registration_date) }}">
            @error('registration_date') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-brand-primary">Update Truck</button>
        <a href="{{ route('trucks.index') }}" class="btn btn-back ms-2">Back to List</a>
    </form>
</div>
@endsection
