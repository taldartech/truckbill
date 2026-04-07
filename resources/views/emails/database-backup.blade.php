@extends('emails.layouts.master')

@section('title', 'Daily Database Backup')

@section('content')
    <tr>
        <td style="padding:24px 24px 10px 24px;">
            <p style="margin:0 0 12px 0;font-size:22px;line-height:1.3;font-weight:700;color:#1f2d3d;">
                {{ $appName }} - Daily Database Backup
            </p>
            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#374151;">Hello,</p>
            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#374151;">
                Please find today's database backup attached with this email.
            </p>

            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5e7eb;border-radius:6px;background-color:#f9fafb;margin:0 0 16px;">
                <tr>
                    <td style="padding:12px 14px;font-size:13px;color:#374151;">
                        <strong>File:</strong> {{ $attachmentName }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 14px 12px;font-size:13px;color:#374151;">
                        <strong>Generated At:</strong> {{ $generatedAt }}
                    </td>
                </tr>
            </table>

            <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#4b5563;">
                This is an automated system email. Please keep this backup secure and do not forward it outside authorized recipients.
            </p>
            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#374151;">
                Regards,<br>Team {{ $appName }}
            </p>
        </td>
    </tr>
@endsection
