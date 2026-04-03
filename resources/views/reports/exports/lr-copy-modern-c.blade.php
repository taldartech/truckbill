<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>LR Copy (Modern C)</title>
    <style>
        @page { margin: 5px 6px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8px;
            margin: 0;
            padding: 0;
            color: #1c1917;
        }
        p { margin: 1px 0; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .fs-5 { font-size: 10px; }
        .fs-6 { font-size: 7.5px; }
        .values { font-family: DejaVu Sans, monospace; }
        .text-end { text-align: right; }
        .lr-bilty-page-pair {
            page-break-inside: avoid;
        }
        .lr-bilty-wrap {
            margin: 0 0 2px 0;
            padding: 0;
            page-break-inside: auto;
        }
        .lr-bilty-wrap:last-child {
            margin-bottom: 0;
        }
        .lr-pdf-header {
            border-bottom: 1px solid #fbbf24;
        }
        .lr-pdf-header--c {
            background: #fffbeb;
        }
        .lr-pdf-header__table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .lr-pdf-header__table td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }
        .lr-pdf-header__logo-cell {
            width: 48px;
            padding: 2px 6px 2px 0 !important;
        }
        .lr-pdf-header__doc-title {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.4px;
            margin: 0 0 2px 0;
            color: #0f172a;
        }
        .lr-pdf-header__company-name {
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 1px;
        }
        .lr-pdf-header__company-meta {
            font-size: 7.5px;
            color: #57534e;
            line-height: 1.25;
        }
        .lr-pdf-header__text-cell {
            padding: 5px 6px 5px 0 !important;
        }
        /* Form C: amber “utility” dashed frame */
        .lr-sheet--c {
            border: 2px dashed #d97706;
            border-radius: 4px;
            overflow: hidden;
            background: #fffbeb;
        }
        .lr-sheet--c .lr-sheet__ribbon {
            background: #fef3c7;
            color: #92400e;
            font-weight: 800;
            font-size: 8px;
            letter-spacing: 0.4px;
            padding: 5px 10px;
            text-align: center;
            border-bottom: 1px solid #fbbf24;
        }
        .lr-sheet--c .lr-sheet__inner {
            padding: 5px 6px 7px;
            border-left: 5px solid #ea580c;
            background: #fffef7;
        }
        .lr-sheet--c .lr-bilty-table {
            border: 1px solid #78716c;
        }
        .lr-sheet--c .lr-bilty-table td,
        .lr-sheet--c .lr-bilty-table th {
            border: 1px solid #57534e;
            padding: 2px 3px;
            vertical-align: top;
        }
        .lr-cut-line {
            margin: 3px 0 4px;
            padding: 5px 8px;
            text-align: center;
            font-size: 7px;
            font-weight: 800;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #92400e;
            border-top: 1px dashed #d97706;
            border-bottom: 1px dashed #d97706;
            background: #fef3c7;
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
            ->where('company_id', $company->id)
            ->get();
    } else {
        $lrs = [$lr];
    }
    $company = $company ?? auth()->user()->company;
    $lrCopyLogoSrc = $company->logoFilesystemPathForPdf() ?: $company->logoPublicUrl();
@endphp
@foreach ($lrs as $index => $lr)
    @if ($index > 0)
        <div class="page-break"></div>
    @endif
    <div class="lr-theme-c lr-bilty-page-pair">
        <div class="lr-bilty-wrap lr-sheet lr-sheet--c">
            @include('reports.exports.partials.lr-copy-pdf-header-strip', ['company' => $company, 'logoSrc' => $lrCopyLogoSrc, 'theme' => 'c'])
            <div class="lr-sheet__ribbon">FORM C · COMPACT BILTY · LR COPY</div>
            <div class="lr-sheet__inner">
                @include('reports.exports.partials.lr-copy-bilty-block', ['lr' => $lr, 'company' => $company, 'copyLabel' => 'CONSIGNOR COPY'])
            </div>
        </div>
        <div class="lr-cut-line">Cut here — separate copies</div>
        <div class="lr-bilty-wrap lr-sheet lr-sheet--c">
            @include('reports.exports.partials.lr-copy-pdf-header-strip', ['company' => $company, 'logoSrc' => $lrCopyLogoSrc, 'theme' => 'c'])
            <div class="lr-sheet__ribbon">FORM C · COMPACT BILTY · LR COPY</div>
            <div class="lr-sheet__inner">
                @include('reports.exports.partials.lr-copy-bilty-block', ['lr' => $lr, 'company' => $company, 'copyLabel' => 'DRIVER COPY'])
            </div>
        </div>
    </div>
@endforeach
</body>
</html>
