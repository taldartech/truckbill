@extends('emails.layouts.master')

@section('title', 'Daily Database Backup')

@section('content')
    <tr>
        <td style="padding:40px;text-align:center;">
            
            <div style="margin-bottom:24px;">
                <span style="font-size:48px;">💾</span>
            </div>

            <p style="margin:0 0 16px 0;font-size:22px;line-height:1.3;font-weight:800;color:#183A41;">
                {{ $appName }} Backup
            </p>
            <p style="margin:0 auto 24px;font-size:15px;line-height:1.6;color:#546875;max-width:400px;">
                Please find today's database backup attached with this email.
            </p>

            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #D6E8E2;border-radius:12px;background-color:#EBF5F1;margin:0 0 24px;">
                <tr>
                    <td style="padding:16px 20px 8px;font-size:15px;color:#4A626A;">
                        <strong style="color:#183A41;">File:</strong> {{ $attachmentName }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 20px 16px;font-size:15px;color:#4A626A;">
                        <strong style="color:#183A41;">Generated At:</strong> {{ $generatedAt }}
                    </td>
                </tr>
            </table>

            <p style="margin:0 0 16px;font-size:13px;line-height:1.6;color:#8B9BA7;">
                This is an automated system email. Please keep this backup secure and do not forward it outside authorized recipients.
            </p>
            <p style="margin:0;font-size:14px;line-height:1.6;color:#546875;font-weight:600;">
                Team {{ $appName }}
            </p>
        </td>
    </tr>
@endsection
