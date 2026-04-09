@extends('emails.layouts.master')

@section('title', 'Reset Password')

@section('content')
    @php
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $email], false));
    @endphp

    <tr>
        <td style="padding:24px 24px 10px 24px;">
            <p style="margin:0 0 12px;font-size:18px;line-height:1.4;font-weight:700;color:#1f2d3d;">Hello,</p>
            <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#374151;">
                You are receiving this email because we received a password reset request for your account.
            </p>

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 18px auto;">
                <tr>
                    <td align="center" style="border-radius:10px;background-color:{{ $brandBlue ?? '#2196F3' }};">
                        <a href="{{ $resetUrl }}" target="_blank" style="display:inline-block;padding:12px 22px;font-size:14px;line-height:1.2;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">
                            Reset Password
                        </a>
                    </td>
                </tr>
            </table>

            <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#374151;">
                This password reset link will expire in {{ $count }} minutes.
            </p>
            <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#374151;">
                If you did not request a password reset, no further action is required.
            </p>

            <hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;">

            <p style="margin:0;font-size:12px;line-height:1.6;color:#8a97a5;">
                If you're having trouble clicking the "Reset Password" button, copy and paste this URL into your browser:<br>
                <a href="{{ $resetUrl }}" style="color:{{ $brandBlue ?? '#2196F3' }};text-decoration:underline;">{{ $resetUrl }}</a>
            </p>
        </td>
    </tr>
@endsection
