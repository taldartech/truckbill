@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
<div class="card card-info card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title">{{ $goodsType ? 'Edit Goods Type' : 'Create Goods Type' }}</h3>
    </div>
    <form method="POST" action="{{ $goodsType ? route('master.goods-types.update', $goodsType) : route('master.goods-types.store') }}" class="needs-validation" novalidate>
        @csrf
        @if($goodsType)
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name *</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $goodsType->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <a href="{{ route('master.goods-types.index') }}" class="btn btn-back">Back to List</a>
            <button type="submit" class="btn btn-brand-primary">Submit form</button>
        </div>
    </form>
        </div>
    </div>
</div>

<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
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
