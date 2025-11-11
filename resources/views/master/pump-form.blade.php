@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">{{ isset($pump) ? 'Edit Pump' : 'Add New Pump' }}</h3>
    </div>
    <form action="{{ isset($pump) ? route('master.pump.update', $pump->id) : route('master.pump.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @if(isset($pump))
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="pump_name" class="form-label">Pump Name *</label>
                    <input type="text" class="form-control" id="pump_name" name="pump_name" required 
                           value="{{ old('pump_name', isset($pump) ? $pump->pump_name : '') }}">
                    @error('pump_name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>

                <div class="col-md-6">
                    <label for="contact_name" class="form-label">Contact Person *</label>
                    <input type="text" class="form-control" id="contact_name" name="contact_name" required 
                           value="{{ old('contact_name', isset($pump) ? $pump->contact_name : '') }}">
                    @error('contact_name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>

                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number *</label>
                    <input type="text" class="form-control" maxlength="10" id="contact_number" name="contact_number" required 
                           value="{{ old('contact_number', isset($pump) ? $pump->contact_number : '') }}">
                    @error('contact_number')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>

                <div class="col-md-6">
                    <label for="remark" class="form-label">Remark</label>
                    <textarea class="form-control" id="remark" name="remark">{{ old('remark', isset($pump) ? $pump->remark : '') }}</textarea>
                    @error('remark')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-info">Submit form</button>
            <a href="{{ route('master.pump.index') }}" class="btn btn-info">Back to List</a>
        </div>
    </form>
</div>

<script>
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>
@endsection
