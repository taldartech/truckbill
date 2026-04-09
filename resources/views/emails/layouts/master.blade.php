@php
    $brandGreen = $brandGreen ?? '#419B88'; // Matches the emerald green in the reference
    $brandBlue = $brandBlue ?? '#419B88'; 
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
    <title>@yield('title', 'TruckBill Notification')</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body, table, td, p, a, h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#F5F8FA;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F5F8FA;padding:0 0 60px 0;">
        <tr>
            <td align="center">
                <!-- Top Two-Tone Background Strip -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="height:60px;background-color:#419B88;"></td>
                    </tr>
                    <tr>
                        <td style="height:60px;background-color:#F5F8FA;"></td>
                    </tr>
                </table>

                <!-- Main Wrapper pulled up to overlap the background strip -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;margin-top:-90px;margin-left:auto;margin-right:auto;">
                    
                    <!-- Floating Pill Logo Header -->
                    <tr>
                        <td align="center" style="padding:0 0 24px 0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;border-radius:40px;box-shadow:0 8px 16px rgba(0,0,0,0.06);">
                                <tr>
                                    <td align="center" style="padding:16px 40px;">
                                        <a href="{{ $websiteUrl }}" target="_blank" style="display:inline-block;">
                                            <img src="{{ $logoUrl }}" alt="TruckBill" style="height:38px;width:auto;display:block;border:0;" role="presentation">
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Card -->
                    <tr>
                        <td style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 24px -8px rgba(0,0,0,0.05);border:1px solid #EBEFF2;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                @yield('content')
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer Area -->
                    <tr>
                        <td align="center" style="padding:32px 20px 0 20px;">
                            <p style="margin:0 0 16px 0;font-size:14px;color:#546875;">
                                Need assistance? <a href="mailto:{{ $supportEmail }}" style="color:#3B82F6;text-decoration:none;font-weight:600;">{{ $supportEmail }}</a>
                            </p>
                            
                            <!-- Social Icons -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;">
                                <tr>
                                    <td style="padding:0 6px;"><a href="#" style="display:inline-block;width:32px;height:32px;border-radius:50%;background-color:#63B3ED;text-align:center;line-height:32px;color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;">f</a></td>
                                    <td style="padding:0 6px;"><a href="#" style="display:inline-block;width:32px;height:32px;border-radius:50%;background-color:#63B3ED;text-align:center;line-height:32px;color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;">t</a></td>
                                    <td style="padding:0 6px;"><a href="#" style="display:inline-block;width:32px;height:32px;border-radius:50%;background-color:#63B3ED;text-align:center;line-height:32px;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;">in</a></td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:12px;color:#8B9BA7;line-height:1.6;max-width:500px;">
                                You're receiving this email because you signed up for TruckBill. If you no longer wish to receive emails, 
                                <br><a href="{{ $unsubscribeUrl }}" style="color:#3B82F6;text-decoration:underline;">unsubscribe here.</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
