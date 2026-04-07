@php
    $brandGreen = '#2FB36F';
    $brandBlue = '#2D8CFF';
    $recipientName = $recipientName ?? 'Customer';
    $lrNumber = $lrNumber ?? 'LR-56789';
    $truckNumber = $truckNumber ?? 'RJ14 AB 1234';
    $lrDate = $lrDate ?? 'April 20, 2024';
    $downloadUrl = $downloadUrl ?? 'https://www.truckbill.in/';
    $supportEmail = $supportEmail ?? 'support@truckbill.in';
    $unsubscribeUrl = $unsubscribeUrl ?? 'https://www.truckbill.in/';
@endphp

@extends('emails.layouts.master')

@section('title', 'New Lorry Receipt Generated')

@section('content')
    <tr>
        <td style="padding:20px 26px 0 26px;background-color:#ffffff;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f0faf5;border:1px solid #c8eadb;border-radius:14px;">
                <tr>
                    <td style="padding:16px 18px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td width="44" valign="top" style="padding-right:10px;">
                                    <table role="presentation" width="40" cellspacing="0" cellpadding="0" border="0" style="background-color:#d8f0e6;border-radius:10px;">
                                        <tr>
                                            <td align="center" style="padding:10px 0;font-size:18px;color:{{ $brandGreen }};font-weight:700;">LR</td>
                                        </tr>
                                    </table>
                                </td>
                                <td valign="top">
                                    <p style="margin:0 0 6px 0;font-size:30px;line-height:1.2;font-weight:800;color:{{ $brandBlue }};">New Lorry Receipt Generated!</p>
                                    <p style="margin:0;font-size:20px;line-height:1.4;color:#3e566f;">Your Lorry Receipt (LR) has been generated and is ready for download.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:18px 28px 0 28px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:linear-gradient(180deg,#eef6ff 0%,#eef9f3 100%);border:1px solid #d4e8f7;border-radius:14px;">
                <tr>
                    <td align="center" style="padding:18px 12px 12px 12px;">
                        <p style="margin:0;font-size:70px;line-height:1;color:{{ $brandBlue }};">&#128666;</p>
                        <p style="margin:8px 0 0 0;font-size:16px;line-height:1.3;font-weight:700;color:{{ $brandGreen }};letter-spacing:0.3px;">LORRY RECEIPT READY</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:22px 30px 0 30px;">
            <p style="margin:0 0 12px 0;font-size:34px;line-height:1.2;font-weight:800;color:{{ $brandBlue }};">Hello {{ $recipientName }},</p>
            <p style="margin:0;font-size:22px;line-height:1.6;color:#41556b;">
                We are glad to inform you that a new Lorry Receipt (LR) has been generated for your recent shipment. Please find the details below.
            </p>
        </td>
    </tr>

    <tr>
        <td style="padding:16px 26px 10px 26px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ecf9f2;border:1px solid #c5ead8;border-radius:12px;">
                <tr>
                    <td style="padding:14px 16px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="padding:10px 0;border-bottom:1px solid #cfe8de;">
                                    <span style="font-size:20px;line-height:1.4;color:#263c55;font-weight:700;">LR No:</span>
                                    <span style="font-size:22px;line-height:1.4;color:{{ $brandGreen }};font-weight:800;padding-left:8px;">{{ $lrNumber }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;border-bottom:1px solid #cfe8de;">
                                    <span style="font-size:20px;line-height:1.4;color:#263c55;font-weight:700;">Truck No:</span>
                                    <span style="font-size:22px;line-height:1.4;color:{{ $brandGreen }};font-weight:800;padding-left:8px;">{{ $truckNumber }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;">
                                    <span style="font-size:20px;line-height:1.4;color:#263c55;font-weight:700;">Date:</span>
                                    <span style="font-size:22px;line-height:1.4;color:{{ $brandGreen }};font-weight:800;padding-left:8px;">{{ $lrDate }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:12px 24px 24px 24px;text-align:center;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto;">
                <tr>
                    <td align="center" style="border-radius:12px;background-color:{{ $brandGreen }};">
                        <a href="{{ $downloadUrl }}" target="_blank" style="display:inline-block;padding:14px 38px;font-size:23px;line-height:1.2;font-weight:800;color:#ffffff;text-decoration:none;border-radius:12px;">
                            Download Lorry Receipt
                        </a>
                    </td>
                </tr>
            </table>
            <p style="margin:16px 0 0 0;font-size:22px;line-height:1.6;color:#54687e;max-width:540px;margin-left:auto;margin-right:auto;">
                Click the button above to download the Lorry Receipt. If you have any questions or need further assistance, feel free to contact our support team.
            </p>
        </td>
    </tr>
@endsection
