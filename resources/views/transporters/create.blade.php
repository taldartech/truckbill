@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Add New Transporter</h2>
    <form action="{{ route('transporters.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="company_id" class="form-label">Company *</label>
            <select class="form-select" id="company_id" name="company_id" required>
                <option value="">Select</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Name *</label>
            <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="gst_number" class="form-label">GST Number</label>
            <input type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
            @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="gst_type" class="form-label">GST Type *</label>
            <select class="form-select" id="gst_type" name="gst_type" required>
                <option value="">Select</option>
                <option value="composition" {{ old('gst_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                <option value="regular" {{ old('gst_type') == 'regular' ? 'selected' : '' }}>Regular</option>
            </select>
            @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="pan_number" class="form-label">PAN Number</label>
            <input type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number') }}">
            @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Type *</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_owner" id="owner" value="1" {{ old('is_owner') === '1' ? 'checked' : '' }} required>
                <label class="form-check-label" for="owner">Owner</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_owner" id="agency" value="0" {{ old('is_owner') === '0' ? 'checked' : '' }} required>
                <label class="form-check-label" for="agency">Agency</label>
            </div>
            @error('is_owner') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Declaration Given? *</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="declaration_given" id="decl_yes" value="1" {{ old('declaration_given') === '1' ? 'checked' : '' }} required>
                <label class="form-check-label" for="decl_yes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="declaration_given" id="decl_no" value="0" {{ old('declaration_given') === '0' ? 'checked' : '' }} required>
                <label class="form-check-label" for="decl_no">No</label>
            </div>
            @error('declaration_given') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="total_trucks" class="form-label">Total Trucks *</label>
            <input type="number" class="form-control" id="total_trucks" name="total_trucks" min="0" required value="{{ old('total_trucks', 0) }}">
            @error('total_trucks') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="advance_amount" class="form-label">Advance Amount</label>
            <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" value="{{ old('advance_amount', 0) }}">
            @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary">Create Transporter</button>
        <a href="{{ route('transporters.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
