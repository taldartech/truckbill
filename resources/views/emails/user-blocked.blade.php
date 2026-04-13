@extends('emails.layouts.master')

@section('title', 'Account deactivated')

@section('content')
    <tr>
        <td style="padding:40px;text-align:center;background-color:#ffffff;">
            <div style="margin-bottom:20px;">
                <span style="font-size:48px;" aria-hidden="true">👋</span>
            </div>

            <p style="margin:0 0 16px 0;font-size:22px;line-height:1.3;font-weight:800;color:#183A41;">
                We noticed that your TruckBill account is currently deactivated
            </p>

            <p style="margin:0 auto 16px;font-size:15px;line-height:1.6;color:#546875;max-width:440px;text-align:left;">
                Hi {{ $userName }},<br><br>
                Many transporters face hidden profit loss due to billing errors, TDS gaps, and manual tracking—and TruckBill is designed to help you avoid exactly that, while running your business with clarity and peace.
            </p>

            <p style="margin:0 auto 24px;font-size:15px;line-height:1.6;color:#546875;max-width:440px;text-align:left;">
                We’d love to help you get back on track.
            </p>

            {{-- <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:440px;margin:0 auto 24px;border:1px solid #D6E8E2;border-radius:12px;background-color:#EBF5F1;">
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
            </table> --}}

            <p style="margin:0 auto 24px;font-size:15px;line-height:1.6;color:#183A41;max-width:440px;text-align:left;font-weight:600;">
                👉 Simply reply to this email or reach out to our team on <a href="tel:+919001010007" style="color:#1B5E57;text-decoration:underline;">+91 9001010007</a>, and we’ll assist you with quick reactivation and support.
            </p>

            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#546875;max-width:440px;margin-left:auto;margin-right:auto;text-align:left;">
                Let’s ensure your business runs smoothly—without stress and without losses.
            </p>

            <p style="margin:0;font-size:14px;line-height:1.6;color:#546875;">
                Warm regards,<br>
                <span style="font-weight:600;">Team {{ config('app.name') }}</span>
            </p>
        </td>
    </tr>
@endsection
