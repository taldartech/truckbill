@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2>{{ $goodsType ? 'Edit Goods Type' : 'Create Goods Type' }}</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ $goodsType ? route('master.goods-types.update', $goodsType) : route('master.goods-types.store') }}">
                @csrf
                @if($goodsType)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $goodsType->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>



                <div class="d-flex justify-content-end">
                    <a href="{{ route('master.goods-types.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
