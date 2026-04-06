<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Database Backup</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, sans-serif; color:#111827;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f3f4f6; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                <tr>
                    <td style="background-color:#111827; padding:18px 24px; color:#ffffff; font-size:18px; font-weight:700;">
                        {{ $appName }} - Daily Database Backup
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 12px; font-size:14px; line-height:1.6;">Hello,</p>
                        <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                            Please find today's database backup attached with this email.
                        </p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5e7eb; border-radius:6px; background-color:#f9fafb; margin:0 0 16px;">
                            <tr>
                                <td style="padding:12px 14px; font-size:13px; color:#374151;">
                                    <strong>File:</strong> {{ $attachmentName }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 14px 12px; font-size:13px; color:#374151;">
                                    <strong>Generated At:</strong> {{ $generatedAt }}
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 12px; font-size:13px; line-height:1.6; color:#4b5563;">
                            This is an automated system email. Please keep this backup secure and do not forward it outside authorized recipients.
                        </p>
                        <p style="margin:0; font-size:14px; line-height:1.6;">
                            Regards,<br>
                            Team {{ $appName }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
