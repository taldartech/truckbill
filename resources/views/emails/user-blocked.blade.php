@extends('emails.layouts.master')

@section('title', 'Account Locked')

@section('content')
    <tr>
        <td style="padding:40px;text-align:center;background-color:#ffffff;">
            <div style="margin-bottom:20px;">
                <span style="font-size:48px;" aria-hidden="true">🔒</span>
            </div>

            <p style="margin:0 0 16px 0;font-size:22px;line-height:1.3;font-weight:800;color:#183A41;">
                Your account has been locked
            </p>

            <p style="margin:0 auto 24px;font-size:15px;line-height:1.6;color:#546875;max-width:440px;">
                Hello {{ $userName }},<br><br>
                Your TruckBill user account is now <strong>locked</strong>. You will not be able to sign in until it is unlocked.
            </p>

            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:440px;margin:0 auto 24px;border:1px solid #D6E8E2;border-radius:12px;background-color:#EBF5F1;">
                <tr>
                    <td style="padding:16px 20px 8px;font-size:15px;color:#4A626A;text-align:left;">
                        <strong style="color:#183A41;">Locked by:</strong> {{ $blockedByName }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 20px 8px;font-size:15px;color:#4A626A;text-align:left;">
                        <strong style="color:#183A41;">Reason:</strong> {{ $reason }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 20px 16px;font-size:15px;color:#4A626A;text-align:left;">
                        <strong style="color:#183A41;">Locked at:</strong> {{ $blockedAt }}
                    </td>
                </tr>
            </table>

            <p style="margin:0 0 16px;font-size:13px;line-height:1.6;color:#8B9BA7;max-width:440px;margin-left:auto;margin-right:auto;">
                If you believe this was done in error, please contact us using the support email in the footer below.
            </p>

            <p style="margin:0;font-size:14px;line-height:1.6;color:#546875;font-weight:600;">
                Team {{ config('app.name') }}
            </p>
        </td>
    </tr>
@endsection
