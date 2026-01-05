@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                <div class="card card-info card-outline mb-4">
                    <div class="card-header">
                        <div class="card-title">Edit Voucher Template</div>
                    </div>
                    <form action="{{ route('voucher_templates.update', $voucher_template) }}" method="POST" class="needs-validation" novalidate="">
                        @csrf
                        @method('PUT')
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="bi bi-check-circle"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="template_name" class="form-label">Name *</label>
                                    <input type="text" class="form-control @error('template_name') is-invalid @enderror" id="template_name" name="template_name" required
                                        value="{{ old('template_name', $voucher_template->template_name) }}">
                                    @error('template_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="template_address" class="form-label">Address *</label>
                                    <input type="text" class="form-control @error('template_address') is-invalid @enderror" id="template_address" name="template_address" required
                                        value="{{ old('template_address', $voucher_template->template_address) }}">
                                    @error('template_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="template_header" class="form-label">Header</label>
                                    <input type="text" class="form-control @error('template_header') is-invalid @enderror" id="template_header" name="template_header"
                                        value="{{ old('template_header', $voucher_template->template_header) }}">
                                    @error('template_header')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="template_jurisdictional_boundary" class="form-label">Jurisdictional Boundary</label>
                                    <input type="text" class="form-control @error('template_jurisdictional_boundary') is-invalid @enderror" id="template_jurisdictional_boundary" name="template_jurisdictional_boundary"
                                        value="{{ old('template_jurisdictional_boundary', $voucher_template->template_jurisdictional_boundary) }}">
                                    @error('template_jurisdictional_boundary')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-12">
                                    <label for="template_note" class="form-label">Note</label>
                                    <textarea class="form-control texteditor @error('template_note') is-invalid @enderror" id="template_note" name="template_note" rows="5">{{ old('template_note', $voucher_template->template_note) }}</textarea>
                                    @error('template_note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-success" type="submit">Update Voucher Template</button>
                            <a href="{{ route('voucher_templates.index') }}" class="btn btn-brand-primary">Back to list</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
