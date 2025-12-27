<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>@yield('title', 'Notification')</title>

    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">

    <style>
        /* CSS Reset */
        html, body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
            background: #f1f2f6; /* Modern light gray background */
            font-family: 'Open Sans', 'Lato', sans-serif;
        }

        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        table, td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }

        img {
            -ms-interpolation-mode: bicubic;
        }

        a {
            text-decoration: none;
        }

        /* Custom Styles */
        .primary {
            background: #007bff; /* Standard primary blue, widely accepted */
        }

        .bg_white {
            background: #ffffff;
        }

        /* Card Container */
        .email-container {
            max-width: 600px;
            margin: 40px auto; /* More vertical breathing room */
            background: #ffffff;
            border-radius: 8px; /* Rounded corners for card look */
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); /* Subtle shadow for depth */
            overflow: hidden; /* Clips children to border radius */
        }

        /* Header */
        .header {
            padding: 40px 0 20px 0;
            text-align: center;
            background-color: #ffffff;
        }

        .logo h1 {
            margin: 0;
        }

        .logo h1 a {
            color: #2d3436;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Content Body */
        .content-body {
            padding: 0 40px 40px 40px;
            text-align: left;
            color: #636e72;
            font-size: 16px;
            line-height: 1.6;
        }

        h1, h2, h3 {
            color: #2d3436;
            font-weight: 600;
            margin-top: 0;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        p {
            margin-bottom: 20px;
        }

        /* Button */
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #4a69bd; /* Nice refined blue */
            color: #ffffff !important;
            box-shadow: 0 4px 6px rgba(74, 105, 189, 0.3);
        }

        .btn-primary:hover {
            background: #3c5aa6;
        }

        /* Footer */
        .footer {
            background-color: #f1f2f6;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #dfe6e9;
        }

        .footer p {
            color: #b2bec3;
            font-size: 13px;
            margin: 5px 0;
        }

        .footer a {
            color: #636e72;
            margin: 0 10px;
            font-size: 13px;
        }

        /* Unsubscribe / Legal */
        .legal-text {
            font-size: 11px;
            color: #dcdde1;
            margin-top: 20px;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            .content-body {
                padding: 30px 20px !important;
            }
            .header {
                padding: 30px 0 10px 0 !important;
            }
        }
    </style>
</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #f1f2f6;">
    <center style="width: 100%; background-color: #f1f2f6;">
        <div style="display: none; font-size: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
            @yield('preheader', '')
        </div>

        <!-- Main Card Container -->
        <div class="email-container">

            <!-- Header Section -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td class="header">
                        <div class="logo">
                            <a href="#">
                                <img src="https://res.cloudinary.com/dyy2hc1zi/image/upload/v1766750445/logo_vlxyip.png" alt="TRUCKBILL" width="200" style="width: 200px; max-width: 200px; height: auto; display: block; margin: 0 auto; border: 0;">
                            </a>
                            <h1 style="margin-top: 15px;"><a href="#">TRUCKBILL</a></h1>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Content Section -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td class="content-body bg_white">
                        @yield('content')
                    </td>
                </tr>
            </table>
        </div>
        <!-- End Email Container -->

        <!-- Spacer for bottom padding in some clients -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td height="40" style="font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
        </table>

    </center>
</body>
</html>
