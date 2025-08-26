<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Freight Bookings Report</title>
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
        .date-range {
            text-align: center;
            margin-bottom: 15px;
            color: #666;
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
        <h1>Freight Bookings Report</h1>
        <div class="date-range">
            @if($startDate && $endDate)
                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            @else
                All Bookings
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>LR No</th>
                <th>Date</th>
                <th>From</th>
                <th>To</th>
                <th>Transporter</th>
                <th>Vehicle No</th>
                <th class="text-right">Amount (₹)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $index => $booking)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $booking->lr_no }}</td>
                    <td>{{ $booking->date ? $booking->date->format('d-M-Y') : 'N/A' }}</td>
                    <td>{{ optional($booking->sourceCity)->name }}</td>
                    <td>{{ optional($booking->destinationCity)->name }}</td>
                    <td>{{ optional($booking->transporter)->name }}</td>
                    <td>{{ optional($booking->truck)->vehicle_number ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($booking->total_amount, 2) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No bookings found for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
        @if($bookings->count() > 0)
        <tfoot>
            <tr>
                <td colspan="7" class="text-right"><strong>Total:</strong></td>
                <td class="text-right"><strong>₹{{ number_format($bookings->sum('total_amount'), 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i:s') }} | Page <span class="page-number"></span>
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
