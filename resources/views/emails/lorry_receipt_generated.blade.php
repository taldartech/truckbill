@php
    $websiteUrl = rtrim((string) config('app.website_url', 'https://www.truckbill.in'), '/');
    $brandGreen = '#419B88';
    $brandBlue = '#419B88';
    $recipientName = $recipientName ?? 'Customer';
    $lrNumber = $lrNumber ?? 'LR-56789';
    $truckNumber = $truckNumber ?? 'RJ14 AB 1234';
    $lrDate = $lrDate ?? 'April 20, 2024';
    $downloadUrl = $downloadUrl ?? ($websiteUrl.'/');
    $supportEmail = $supportEmail ?? 'support@truckbill.in';
    $unsubscribeUrl = $unsubscribeUrl ?? ($websiteUrl.'/');
    $heroUrl = $websiteUrl.'/images/email_hero_illustration.png';
@endphp

@extends('emails.layouts.master')

@section('title', 'New Lorry Receipt Generated')

@section('content')

    <!-- Top Badge -->
    <tr>
        <td style="padding:40px 40px 0 40px;background-color:#ffffff;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#EBF5F1;border-radius:12px;">
                <tr>
                    <td style="padding:20px 24px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td width="36" valign="top">
                                    <!-- Document Icon -->
                                    <span style="font-size:24px;">📄</span>
                                </td>
                                <td valign="middle">
                                    <p style="margin:0 0 6px 0;font-size:20px;line-height:1.2;font-weight:700;color:#183A41;">New Lorry Receipt Generated!</p>
                                    <p style="margin:0;font-size:14px;line-height:1.5;color:#4A626A;">Your Lorry Receipt (LR) has been generated<br>and is ready for download.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Hero Illustration -->
    <tr>
        <td align="center" style="padding:20px 0 0 0;background-color:#ffffff;">
            <img src="{{ $heroUrl }}" alt="Delivery Success" style="display:block;width:100%;max-width:450px;height:auto;border:0;">
        </td>
    </tr>

    <!-- Content Intro -->
    <tr>
        <td style="padding:20px 40px 10px 40px;background-color:#ffffff;">
            <p style="margin:0 0 16px 0;font-size:22px;line-height:1.3;font-weight:800;color:#1B3B42;">Hello {{ $recipientName }},</p>
            <p style="margin:0;font-size:15px;line-height:1.6;color:#546870;">
                We are glad to inform you that a new Lorry Receipt (LR) has been generated for your recent shipment. Please find the details below.
            </p>
        </td>
    </tr>

    <!-- Details Box -->
    <tr>
        <td style="padding:20px 40px;background-color:#ffffff;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#EBF5F1;border-radius:12px;">
                <tr>
                    <td style="padding:24px 30px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="padding:0 0 14px 0;">
                                    <span style="font-size:16px;margin-right:8px;">📄</span>
                                    <span style="font-size:15px;color:#183A41;font-weight:700;">LR No: </span>
                                    <span style="font-size:15px;color:#419B88;font-weight:800;">{{ $lrNumber }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 0 14px 0;">
                                    <span style="font-size:16px;margin-right:8px;">🚛</span>
                                    <span style="font-size:15px;color:#183A41;font-weight:700;">Truck No: </span>
                                    <span style="font-size:15px;color:#419B88;font-weight:800;">{{ $truckNumber }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 0 0 0;">
                                    <span style="font-size:16px;margin-right:8px;">📅</span>
                                    <span style="font-size:15px;color:#183A41;font-weight:700;">Date: </span>
                                    <span style="font-size:15px;color:#419B88;font-weight:800;">{{ $lrDate }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 30px 30px 30px; text-align:center;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto;">
                            <tr>
                                <td align="center" style="border-radius:8px;background-color:#419B88;">
                                    <a href="{{ $downloadUrl }}" target="_blank" style="display:inline-block;padding:14px 30px;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;">
                                        Download Lorry Receipt
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Support Message -->
    <tr>
        <td style="padding:10px 40px 40px 40px;text-align:center;background-color:#ffffff;">
            <p style="margin:0;font-size:14px;line-height:1.6;color:#6A7C85;">
                Click the button above to download the Lorry Receipt.<br>
                If you have any questions or need further assistance, feel free to contact our support team.
            </p>
        </td>
    </tr>
@endsection
