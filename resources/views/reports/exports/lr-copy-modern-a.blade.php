<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>LR Copy (Modern A)</title>
    <style>
        @page { margin: 6px 8px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8.5px;
            margin: 0;
            padding: 0;
            color: #0f172a;
        }
        p { margin: 2px 0; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .fs-5 { font-size: 11px; }
        .fs-6 { font-size: 8px; }
        .values { font-family: DejaVu Sans, monospace; }
        .text-end { text-align: right; }
        .lr-bilty-wrap {
            margin: 0 0 8px 0;
            padding: 0;
            page-break-inside: auto;
        }
        .lr-bilty-wrap:last-child {
            margin-bottom: 0;
        }
        .lr-sheet--a {
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #10b981;
            background: #ffffff;
            box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.15);
        }
        .lr-sheet--a .lr-sheet__ribbon {
            background: #059669;
            color: #ffffff;
            font-weight: 800;
            font-size: 9px;
            letter-spacing: 0.6px;
            padding: 7px 12px;
            text-align: center;
        }
        .lr-sheet--a .lr-sheet__inner {
            padding: 6px 7px 8px;
            background: #f8fffc;
        }
        .lr-sheet--a .lr-bilty-table {
            border: 1px solid #34d399;
        }
        .lr-sheet--a .lr-bilty-table td,
        .lr-sheet--a .lr-bilty-table th {
            border: 1px solid #a7f3d0;
            padding: 3px 4px;
            vertical-align: top;
        }
        .lr-sheet--a .lr-bilty-table tr:nth-child(2) td {
            background: #ecfdf5;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@php
    if ($lr->linked_lr_id) {
        $lrs = \App\Models\LorryReceipt::query()
            ->with(['truck.transporter', 'consignor', 'consignee'])
            ->where('linked_lr_id', $lr->linked_lr_id)
            ->get();
    } else {
        $lrs = [$lr];
    }
    $company = $company ?? auth()->user()->company;
@endphp
@foreach ($lrs as $index => $lr)
    @if ($index > 0)
        <div class="page-break"></div>
    @endif
    <div class="lr-theme-a">
        <div class="lr-bilty-wrap lr-sheet lr-sheet--a">
            <div class="lr-sheet__ribbon">FORM A · GOOD RECEIPT (BILTY) · LR COPY</div>
            <div class="lr-sheet__inner">
                @include('reports.exports.partials.lr-copy-bilty-block', ['lr' => $lr, 'company' => $company, 'copyLabel' => 'CONSIGNOR COPY'])
            </div>
        </div>
        <div class="lr-bilty-wrap lr-sheet lr-sheet--a">
            <div class="lr-sheet__ribbon">FORM A · GOOD RECEIPT (BILTY) · LR COPY</div>
            <div class="lr-sheet__inner">
                @include('reports.exports.partials.lr-copy-bilty-block', ['lr' => $lr, 'company' => $company, 'copyLabel' => 'DRIVER COPY'])
            </div>
        </div>
    </div>
@endforeach
</body>
</html>
