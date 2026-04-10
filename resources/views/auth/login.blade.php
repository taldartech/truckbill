@extends('layouts.auth')

@section('title', 'Login - TruckBill')

@section('content')
    <h2 class="page-title">Welcome back! Please sign in to continue.</h2>

    @error('email')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
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
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <input type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       id="password"
                       name="password"
                       required
                       placeholder="Enter your password">
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                    <i class="far fa-eye" id="toggle-icon-password"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember" style="font-size: 14px; color: #475569;">
                    Remember me
                </label>
            </div>
            <div>
                <a href="{{ route('password.request') }}" class="link-primary">
                    Forgot password?
                </a>
            </div>
        </div>

        <button type="submit" class="btn-auth">
            Sign In <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
        </button>
    </form>
@endsection
