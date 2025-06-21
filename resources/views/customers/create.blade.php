@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Add Customer</h2>
    <form method="POST" action="{{ route('customers.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">GST Number</label>
                <input type="text" name="gst_number" class="form-control" value="{{ old('gst_number') }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">PAN Number</label>
                <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="{{ old('address') }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
