@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ $title ?? 'Coming Soon' }}</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-tools fa-5x text-muted"></i>
                    </div>
                    <h5 class="card-title">{{ $message ?? 'This feature is under development' }}</h5>
                    <p class="card-text text-muted">
                        We're working hard to bring you this feature. Please check back soon!
                    </p>
                    <div class="mt-4">
                        <a href="{{ url()->previous() }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Go Back
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
