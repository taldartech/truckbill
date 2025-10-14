<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Party Bill - {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 20px 15px; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 9pt;
            margin: 0;
            padding: 0;
        }
        .header { 
            text-align: center; 
            margin-bottom: 10px;
        }
        .header h2 { 
            margin: 0 0 5px 0; 
            padding: 0; 
            font-size: 16px;
        }
        .header p { 
            margin: 2px 0; 
            font-size: 10px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px;
            page-break-inside: avoid;
            table-layout: fixed;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 3px; 
            font-size: 8pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold;
        }
        .text-right { 
            text-align: right; 
        }
        .text-center { 
            text-align: center; 
        }
        .page-break { 
            page-break-after: always; 
        }
        .signature { 
            margin-top: 30px; 
        }
        .footer { 
            margin-top: 10px; 
            font-size: 8pt; 
            text-align: center;
        }
        .no-border {
            border: none !important;
        }
        .company-name {
            font-size: 14px !important;
            font-weight: bold;
        }
        .company-address {
            font-size: 10px !important;
        }
    </style>
</head>
<body style="width: 100%;">
    @php
        $company = auth()->user()->company;
        $customer = $invoice->consignee;
        $totalAmount = 0;
        $lrs = $lrs ?? []; // Ensure $lrs is defined
    @endphp

   

    @include('reports.exports.party-bill')

   

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("Arial");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 20;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>