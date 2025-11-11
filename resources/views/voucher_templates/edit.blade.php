@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                <div class="card card-info card-outline mb-4">
                    <div class="card-header">
                        <div class="card-title">Edit Voucher Template</div>
                    </div>
                    <form action="{{ route('voucher_templates.update', $company->id) }}" method="POST" class="needs-validation" novalidate="">
                        @csrf
                        @method('PUT')
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="template_name" class="form-label">Name *</label>
                                    <input type="text" class="form-control" id="template_name" name="template_name" required
                                        value="{{ old('template_name', $company->template_name) }}">
                                    @error('template_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="template_address" class="form-label">Address *</label>
                                    <input type="text" class="form-control" id="template_address" name="template_address"
                                        value="{{ old('template_address', $company->template_address) }}">
                                    @error('template_address')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="template_header" class="form-label">Header</label>
                                    <input type="text" class="form-control" id="template_header" name="template_header"
                                        value="{{ old('template_header', $company->template_header) }}">
                                    @error('template_header')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="template_jurisdictional_boundary" class="form-label">Jurisdictional Boundary</label>
                                    <input type="text" class="form-control" id="template_jurisdictional_boundary" name="template_jurisdictional_boundary"
                                        value="{{ old('template_jurisdictional_boundary', $company->template_jurisdictional_boundary) }}">
                                    @error('template_jurisdictional_boundary')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <div class="col-md-12">
                                    <label for="template_note" class="form-label">Note</label>
                                    <textarea class="form-control texteditor" id="template_note" name="template_note">{{ old('template_note', $company->template_note) }}</textarea>
                                    @error('template_note')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-success" type="submit">Update Voucher Template</button>
                            <a href="{{ route('voucher_templates.index') }}" class="btn btn-info">Back to list</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
