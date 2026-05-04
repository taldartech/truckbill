@extends('emails.layouts.master')

@section('title', 'Customer portal access')

@section('content')
    <tr>
        <td style="padding:32px;background-color:#ffffff;">
            <p style="margin:0 0 14px;font-size:16px;line-height:1.6;color:#183A41;">
                Hi {{ $userName }},
            </p>

            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#546875;">
                Your customer portal access is ready for <strong>{{ $consigneeName }}</strong>.
            </p>

            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 18px;border:1px solid #D6E8E2;border-radius:10px;background-color:#F6FBF9;">
                <tr>
                    <td style="padding:14px 16px;font-size:14px;line-height:1.5;color:#183A41;">
                        <strong>Login URL:</strong> <a href="{{ $loginUrl }}" style="color:#1B5E57;text-decoration:underline;">{{ $loginUrl }}</a><br>
                        <strong>Email:</strong> {{ $email }}<br>
                        @if($isNewUser && $plainPassword)
                            <strong>Temporary Password:</strong> {{ $plainPassword }}
                        @endif
                    </td>
                </tr>
            </table>

            @if($isNewUser)
                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#546875;">
                    Please sign in and change your password immediately.
                </p>
            @else
                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#546875;">
                    Your existing customer login now has access to this account as well.
                </p>
            @endif

            <p style="margin:0;font-size:14px;line-height:1.6;color:#546875;">
                Regards,<br>
                <strong>Team {{ config('app.name') }}</strong>
            </p>
        </td>
    </tr>
@endsection
