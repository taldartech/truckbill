@php
    $brandGreen = $brandGreen ?? '#4CAF50';
    $brandBlue = $brandBlue ?? '#2196F3';
    $supportEmail = $supportEmail ?? 'support@truckbill.in';
    $websiteUrl = rtrim((string) config('app.website_url', 'https://www.truckbill.in'), '/');
    $unsubscribeUrl = $unsubscribeUrl ?? ($websiteUrl.'/');
    $logoUrl = $logoUrl ?? ($websiteUrl.'/images/truckbill_side_logo.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'TruckBill Notification')</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f8fb;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f5f8fb;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background-color:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e1eef5;">
                    <tr>
                        <td style="padding:0;background:linear-gradient(90deg,{{ $brandGreen }} 0%,{{ $brandBlue }} 100%);background-color:{{ $brandGreen }};">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr><td style="height:18px;line-height:18px;font-size:0;">&nbsp;</td></tr>
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;border-radius:36px;">
                                            <tr>
                                                <td style="padding:8px 18px;white-space:nowrap;">
                                                    <img src="{{ $logoUrl }}" alt="" width="220" style="width:220px;max-width:100%;height:auto;display:inline-block;vertical-align:middle;border:0;" role="presentation">
                                                   
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr><td style="height:18px;line-height:18px;font-size:0;">&nbsp;</td></tr>
                            </table>
                        </td>
                    </tr>

                    @yield('content')

                    <tr>
                        <td style="padding:20px 30px 28px 30px;border-top:1px solid #e2e8f0;background-color:#fafcfe;text-align:center;">
                            <p style="margin:0 0 16px 0;font-size:16px;line-height:1.5;color:#4a5d72;">
                                Need assistance? <a href="mailto:{{ $supportEmail }}" style="color:{{ $brandBlue }};text-decoration:none;font-weight:700;">{{ $supportEmail }}</a>
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#8a97a5;">
                                Visit our website:
                                <a href="{{ $unsubscribeUrl }}" style="color:{{ $brandBlue }};text-decoration:underline;">{{ $unsubscribeUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
