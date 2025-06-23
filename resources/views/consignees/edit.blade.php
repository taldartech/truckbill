@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit Consignee</h2>
    <form method="POST" action="{{ route('consignees.update', $consignee->id) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name', $consignee->name) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Address *</label>
                <input type="text" name="address" class="form-control" required value="{{ old('address', $consignee->address) }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">City *</label>
                <input type="text" name="city" class="form-control" required value="{{ old('city', $consignee->city) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">State *</label>
                <input type="text" name="state" class="form-control" required value="{{ old('state', $consignee->state) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Country *</label>
                <input type="text" name="country" class="form-control" required value="{{ old('country', $consignee->country) }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Contact Number *</label>
                <input type="text" name="contact_number" class="form-control" required value="{{ old('contact_number', $consignee->contact_number) }}">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Update Consignee</button>
        <a href="{{ route('consignees.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
