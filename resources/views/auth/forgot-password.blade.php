@extends('layouts.auth')

@section('title', 'Forgot Password - TruckBill')

@section('content')
    <h2 class="page-title">Reset your password</h2>
    
    <p style="text-align: center; color: #475569; margin-bottom: 20px; font-size: 14px;">
        Enter your email address and we'll send you a link to reset your password.
    </p>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <input type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       placeholder="name@company.com">
            </div>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-auth">
            Send Reset Link
        </button>

        <a href="{{ route('login') }}" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </form>
@endsection
