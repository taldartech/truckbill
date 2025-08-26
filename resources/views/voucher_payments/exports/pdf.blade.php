<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Voucher Payments Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
            color: #333;
        }
        .company-info {
            text-align: center;
            margin-bottom: 15px;
            color: #666;
        }
        .report-info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #eee;
            padding: 5px 0;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Voucher Payments Report</h1>
        @if($company)
        <div class="company-info">
            {{ $company->name }}<br>
            {{ $company->address }}<br>
            {{ $company->city }}, {{ $company->state }} - {{ $company->pincode }}<br>
            {{ $company->phone }} | {{ $company->email }}
        </div>
        @endif
        
        <div class="report-info">
            Generated on: {{ now()->format('d-M-Y H:i:s') }}
            @if(!empty($filters['start_date']) && !empty($filters['end_date']))
                <br>Date Range: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($filters['end_date'])->format('d-M-Y') }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Voucher #</th>
                <th>Date</th>
                <th class="text-right">Amount</th>
                <th>Type</th>
                <th>Mode</th>
                <th>Status</th>
                <th>Truck No</th>
                <th>Transporter</th>
            </tr>
        </thead>
        <tbody>
            @forelse($voucherPayments as $payment)
                <tr>
                    <td>{{ $payment->voucher_number }}</td>
                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-M-Y') : 'N/A' }}</td>
                    <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->payment_type }}</td>
                    <td>{{ $payment->payment_mode }}</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td>{{ $payment->freight_booking->lr->truck->truck_number ?? 'N/A' }}</td>
                    <td>{{ $payment->freight_booking->transporter->name ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No voucher payments found</td>
                </tr>
            @endforelse
            @if($voucherPayments->count() > 0)
                <tr>
                    <td colspan="2" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right"><strong>{{ number_format($voucherPayments->sum('amount'), 2) }}</strong></td>
                    <td colspan="5"></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Page <span class="page-number"></span>
    </div>

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
