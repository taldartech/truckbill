<!DOCTYPE html>
<html lang="en">

<head>
    <title>LR COPY</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192"  href="{{ asset('favicon/android-icon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">
    <!-- End Favicon -->
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        p {
            margin: 2px 0;
        }

        .fw-bold {
            font-weight: bold;
        }

        .fs-5 {
            font-size: 1rem;
        }

        .fs-6 {
            font-size: 0.75rem;
        }

        .values {
            font-family: 'Hubballi', monospace, sans-serif;
        }

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
            border: 1px solid #000;
            background: #ffffff;
            box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.15);
        }
        .lr-sheet--a .lr-sheet__ribbon {
            letter-spacing: 0.6px;
            padding: 2px 7px;
            text-align: center;
        }
        .lr-sheet--a .lr-sheet__inner {
            padding: 6px 7px 8px;
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
        $company = $company ?? auth()->user()?->company;
        $company?->loadMissing(['city', 'state', 'country']);
    @endphp
    @foreach ($lrs as $index => $lr)
        @if ($index > 0)
            <div style="page-break-after: always;"></div>
        @endif
        <div class="lr-theme-a">
            <div class="lr-bilty-wrap lr-sheet lr-sheet--a">
            @include('reports.exports.partials.lr-copy-bilty-block', ['lr' => $lr, 'company' => $company, 'copyLabel' => 'CONSIGNOR COPY'])
        </div>
        <div class="lr-bilty-wrap lr-sheet lr-sheet--a" style="margin-top: 25px!important;">
            @include('reports.exports.partials.lr-copy-bilty-block', ['lr' => $lr, 'company' => $company, 'copyLabel' => 'DRIVER COPY'])
        </div>
    @endforeach
</body>

</html>
