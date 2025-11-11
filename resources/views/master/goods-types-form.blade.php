@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
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
            <a href="{{ route('master.goods-types.index') }}" class="btn btn-info">Back to List</a>
            <button type="submit" class="btn btn-info">Submit form</button>
        </div>
    </form>
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
