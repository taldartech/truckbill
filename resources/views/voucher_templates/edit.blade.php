@extends('layouts.app')

@section('content')
    {{-- 
      Wrap everything in a container. 
      'py-4' adds padding on the top and bottom for spacing.
    --}}
    <div class="container-fluid px-0">
        {{-- 
          'justify-content-center' will center the column horizontally
        --}}
        <div class="row justify-content-center">
            {{--
              This column wrapper controls the card's width.
              - On mobile (xs, sm): It's 100% width.
              - On desktop (lg): It's 10 columns wide (approx 83%).
              - On extra-large (xl) screens: It's 8 columns wide (approx 66%).
              This replaces your fixed 'width: 70%'.
            --}}
            <div class="col-lg-10 col-xl-8">
                
                {{-- Removed the inline style from the card --}}
                <div class="card card-info card-outline mb-4">
                    <div class="card-header">
                        <div class="card-title">Edit Voucher Template</div>
                    </div>
                    <form action="{{ route('voucher_templates.update', $company->id) }}" method="POST" class="needs-validation"
                        novalidate="">
                        @csrf
                        @method('PUT')
                        
                        {{-- Added 'm-3' (margin) to the alert so it's not stuck to the edges --}}
                        @if (session('success'))
                            <div class="alert alert-success m-3">{{ session('success') }}</div>
                        @endif

                        <div class="card-body">
                            <div class="row g-3">
                                {{-- 
                                  Changed to 'col-12 col-md-6'.
                                  This means it's 100% width (col-12) on mobile 
                                  and 50% width (col-md-6) on medium screens and up.
                                --}}
                                <div class="col-12 col-md-6">
                                    <label for="template_name" class="form-label">Name *</label>
                                    <input type="text" class="form-control" id="template_name" name="template_name" required
                                        value="{{ old('template_name', $company->template_name) }}">
                                    @error('template_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                
                                {{-- Same 'col-12 col-md-6' for responsiveness --}}
                                <div class="col-12 col-md-6">
                                    <label for="template_address" class="form-label">Address *</label>
                                    <input type="text" class="form-control" id="template_address" name="template_address"
                                        value="{{ old('template_address', $company->template_address) }}">
                                    @error('template_address')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                
                                {{-- Same 'col-12 col-md-6' for responsiveness --}}
                                <div class="col-12 col-md-6">
                                    <label for="template_header" class="form-label">Header</label>
                                    <input type="text" class="form-control" id="template_header" name="template_header"
                                        value="{{ old('template_header', $company->template_header) }}">
                                    @error('template_header')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                
                                {{-- Same 'col-12 col-md-6' for responsiveness --}}
                                <div class="col-12 col-md-6">
                                    <label for="template_jurisdictional_boundary" class="form-label">Jurisdictional
                                        Boundary</label>
                                    <input type="text" class="form-control" id="template_jurisdictional_boundary"
                                        name="template_jurisdictional_boundary"
                                        value="{{ old('template_jurisdictional_boundary', $company->template_jurisdictional_boundary) }}">
                                    @error('template_jurisdictional_boundary')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                
                                {{-- 
                                  Changed from 'col-md-12' to just 'col-12'.
                                  This is clearer and ensures it's always 100% width.
                                --}}
                                <div class="col-12">
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