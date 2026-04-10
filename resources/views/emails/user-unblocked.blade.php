@extends('emails.layouts.master')

@section('title', 'Account Unlocked')

@section('content')
    <tr>
        <td style="padding:40px;text-align:center;background-color:#ffffff;">
            <div style="margin-bottom:20px;">
                <span style="font-size:48px;" aria-hidden="true">✅</span>
            </div>

            <p style="margin:0 0 16px 0;font-size:22px;line-height:1.3;font-weight:800;color:#183A41;">
                Your account has been unlocked
            </p>

            <p style="margin:0 auto 24px;font-size:15px;line-height:1.6;color:#546875;max-width:440px;">
                Hello {{ $userName }},<br><br>
                Your TruckBill user account is now <strong>unlocked</strong>. You can sign in again at any time.
            </p>

            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:440px;margin:0 auto 24px;border:1px solid #D6E8E2;border-radius:12px;background-color:#EBF5F1;">
                <tr>
                    <td style="padding:16px 20px 8px;font-size:15px;color:#4A626A;text-align:left;">
                        <strong style="color:#183A41;">Unlocked by:</strong> {{ $unblockedByName }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 20px 8px;font-size:15px;color:#4A626A;text-align:left;">
                        <strong style="color:#183A41;">Note:</strong> {{ $reason }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 20px 16px;font-size:15px;color:#4A626A;text-align:left;">
                        <strong style="color:#183A41;">Unlocked at:</strong> {{ $unblockedAt }}
                    </td>
                </tr>
            </table>

            <p style="margin:0 0 16px;font-size:13px;line-height:1.6;color:#8B9BA7;max-width:440px;margin-left:auto;margin-right:auto;">
                Need help? Use the support email shown in the footer below.
            </p>

            <p style="margin:0;font-size:14px;line-height:1.6;color:#546875;font-weight:600;">
                Team {{ config('app.name') }}
            </p>
        </td>
    </tr>
@endsection
