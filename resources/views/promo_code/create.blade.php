@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                <form action="{{ route('promo_codes.store') }}" method="POST" class="needs-validation" novalidate="">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">Add New Promo Code</div>
                        </div>
                        @csrf
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="code" class="form-label">Code</label>
                                    <input type="text" class="form-control" id="code" name="code" required
                                        value="{{ old('code') }}">
                                    @error('code')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="discount_type" class="form-label">Discount Type</label>
                                    <select class="form-select" id="discount_type" name="discount_type" required>
                                        <option value="">Select</option>
                                        <option value="percentage"
                                            {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage
                                        </option>
                                        <option value="absolute" {{ old('discount_type') == 'absolute' ? 'selected' : '' }}>
                                            Absolute
                                        </option>
                                    </select>
                                    @error('discount_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="discount_value" class="form-label">Discount Value</label>
                                    <input type="number" step="0.01" class="form-control" id="discount_value" name="discount_value"
                                        required value="{{ old('discount_value') }}">
                                    @error('discount_value')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required
                                        value="{{ old('start_date') }}">
                                    @error('start_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                        value="{{ old('end_date') }}">
                                    @error('end_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="max_uses" class="form-label">Max Uses (optional)</label>
                                    <input type="number" class="form-control" id="max_uses" name="max_uses"
                                        value="{{ old('max_uses') }}">
                                    @error('max_uses')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    @error('is_active')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-info" type="submit">Create Promo Code</button>
                            <a href="{{ route('promo_codes.index') }}" class="btn btn-secondary">Back to List</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (() => {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach((form) => {
                form.addEventListener(
                    'submit',
                    (event) => {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }

                        form.classList.add('was-validated');
                    },
                    false,
                );
            });
        })();
    </script>
@endsection
