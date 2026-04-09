@extends('emails.layouts.master')

@section('title', 'Reset Password')

@section('content')
    @php
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $email], false));
    @endphp

    <tr>
        <td style="padding:40px;text-align:center;">
            
            <div style="margin-bottom:24px;">
                <span style="font-size:48px;">🔒</span>
            </div>
            
            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;font-weight:800;color:#183A41;">Reset your password</h1>
            
            <p style="margin:0 auto 32px;font-size:16px;line-height:1.6;color:#546875;max-width:400px;">
                We received a request to reset the password for your TruckBill account.
            </p>

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 32px auto;">
                <tr>
                    <td align="center" style="border-radius:12px;background-color:#419B88;box-shadow:0 4px 12px rgba(65,155,136,0.3);">
                        <a href="{{ $resetUrl }}" target="_blank" style="display:inline-block;padding:16px 36px;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:12px;">
                            Reset Password
                        </a>
                    </td>
                </tr>
            </table>

            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#8B9BA7;">
                This link will expire in {{ $count }} minutes.<br>
                If you did not request this, please ignore this email.
            </p>

            <div style="border-top:1px solid #EBEFF2;padding-top:24px;margin-top:16px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#8B9BA7;">
                    Having trouble? Copy and paste this URL into your browser:<br>
                    <a href="{{ $resetUrl }}" style="color:#3B82F6;text-decoration:underline;word-break:break-all;margin-top:8px;display:inline-block;">{{ $resetUrl }}</a>
                </p>
            </div>
        </td>
    </tr>
@endsection
