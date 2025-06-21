@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit Company</h2>
    <form action="{{ route('company.update', $company->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Company Name *</label>
            <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $company->name) }}">
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="gst_number" class="form-label">GST Number</label>
            <input type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number', $company->gst_number) }}">
            @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="pan_number" class="form-label">PAN Number</label>
            <input type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number', $company->pan_number) }}">
            @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="gst_scheme" class="form-label">GST Scheme</label>
            <select class="form-select" id="gst_scheme" name="gst_scheme">
                <option value="">Select</option>
                <option value="reverse charge" {{ old('gst_scheme', $company->gst_scheme) == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                <option value="forward charge" {{ old('gst_scheme', $company->gst_scheme) == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
            </select>
            @error('gst_scheme') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="gst_type" class="form-label">GST Type</label>
            <select class="form-select" id="gst_type" name="gst_type">
                <option value="">Select</option>
                <option value="composition" {{ old('gst_type', $company->gst_type) == 'composition' ? 'selected' : '' }}>Composition</option>
                <option value="regular" {{ old('gst_type', $company->gst_type) == 'regular' ? 'selected' : '' }}>Regular</option>
            </select>
            @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea class="form-control" id="address" name="address">{{ old('address', $company->address) }}</textarea>
            @error('address') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="contact_person" class="form-label">Contact Person</label>
            <input type="text" class="form-control" id="contact_person" name="contact_person" value="{{ old('contact_person', $company->contact_person) }}">
            @error('contact_person') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="contact_number" class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="contact_number" name="contact_number" value="{{ old('contact_number', $company->contact_number) }}">
            @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-success">Update Company</button>
        <a href="{{ route('company.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
