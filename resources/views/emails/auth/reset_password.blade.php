@extends('emails.layouts.master')

@section('title', 'Reset Password')

@section('content')
    <h2>Hello!</h2>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    
    <p style="text-align: center; margin: 30px 0;">
        <a href="{{ url(route('password.reset', ['token' => $token, 'email' => $email], false)) }}" class="btn btn-primary" style="display: inline-block; padding: 10px 20px; color: #ffffff; text-decoration: none; border-radius: 5px;">Reset Password</a>
    </p>
    
    <p>This password reset link will expire in {{ $count }} minutes.</p>
    
    <p>If you did not request a password reset, no further action is required.</p>

    <hr style="border:none; border-top:1px solid #eee; margin: 20px 0;">

    <p style="font-size: 12px; color: #999;">If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser: <br>
    <a href="{{ url(route('password.reset', ['token' => $token, 'email' => $email], false)) }}">{{ url(route('password.reset', ['token' => $token, 'email' => $email], false)) }}</a>
    </p>
@endsection
