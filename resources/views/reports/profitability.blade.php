@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">


<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        padding-left: 12px;
        padding-right: 20px;
        color: var(--brand-text-primary);
        line-height: 36px;
        background: var(--brand-card-bg);
        border: 1px solid var(--brand-border);
        border-radius: 0.375rem;
    }

    .select2-container--bootstrap-5 .select2-selection--single:focus-visible {
        border-color: var(--brand-primary);
        box-shadow: 0 0 0 0.25rem var(--brand-primary-light);
        outline: 0;
        border-radius: 0.375rem;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 12px;
    }

    .select2-dropdown {
        border: 1px solid var(--brand-border);
        border-radius: 0.375rem;
    }

    /* Search input */
    .select2-search--dropdown {
        padding: 8px;
    }

    .select2-search--dropdown .select2-search__field {
        border: 2px solid #212121;
        border-radius: 0;
        margin: 0;
        width: 100%;
        box-sizing: border-box;
    }

    .select2-results__option--highlighted {
        background-color: var(--brand-primary);
        color: white;
    }

    .table th,
    .table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    @media (max-width: 767.98px) {
        .profitability-filter-row > [class*="col-"] {
            flex: 0 0 50% !important;
            max-width: 50% !important;
            width: 50% !important;
        }

        .form-label {
            font-size: 0.68rem !important;
            font-weight: 600 !important;
            margin-bottom: 0.15rem !important;
            line-height: 1.2 !important;
        }

        .form-control,
        input[type="date"].form-control,
        .form-select {
            font-size: 0.72rem !important;
            padding: 0.2rem 0.4rem !important;
            min-height: 30px !important;
            height: 30px !important;
            border-radius: 0.35rem !important;
            line-height: 1.2 !important;
        }

        .select2-container,
        .select2-container--bootstrap-5,
        .select2-container--default {
            width: 100% !important;
            max-width: 100% !important;
        }

        .select2-container .select2-selection,
        .select2-container--bootstrap-5 .select2-selection,
        .select2-container--default .select2-selection--single {
            min-height: 30px !important;
            height: 30px !important;
        }

        .select2-container .select2-selection__rendered,
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered,
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            font-size: 0.72rem !important;
            padding-left: 8px !important;
            padding-right: 18px !important;
        }

        .select2-container .select2-selection__arrow,
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow,
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 28px !important;
            right: 6px !important;
        }

        .card-body {
            padding: 0.6rem 0.65rem !important;
        }

        .card-header .card-title {
            font-size: 0.88rem !important;
            font-weight: 600 !important;
        }

        .overall-summary-card .card-body {
            padding: 0.4rem 0.5rem !important;
        }

        .overall-summary-card small {
            font-size: 0.65rem !important;
        }

        .overall-summary-card h5 {
            font-size: 0.82rem !important;
            font-weight: 700 !important;
        }

        .table th, .table td {
            font-size: 0.70rem !important;
            padding: 0.3rem 0.4rem !important;
        }
    }
</style>
<div class="container mt-4">

    <div class="card">

        <div class="card-header">
            <h3 class="card-title mb-0">Profitability Report</h3>
        </div>

        <div class="card-body">

            {{-- Filters --}}
            <form method="GET" action="{{ route('reports.profitability') }}" class="mb-4">
                <div class="row g-2 g-md-3 align-items-end profitability-filter-row">

                    {{-- Date From --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Date From</label>
                        <input type="date"
                               name="start_date"
                               class="form-control form-control-sm"
                               value="{{ $startDate }}">
                    </div>

                    {{-- Date To --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Date To</label>
                        <input type="date"
                               name="end_date"
                               class="form-control form-control-sm"
                               value="{{ $endDate }}">
                    </div>

                    {{-- Customer --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Customer</label>
                        <select name="customer_id" class="form-select form-select-sm select2">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Transporter --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Transporter</label>
                        <select name="transporter_id" class="form-select form-select-sm select2">
                            <option value="">All Transporters</option>
                            @foreach($transporters as $transporter)
                                <option value="{{ $transporter->id }}"
                                    {{ request('transporter_id') == $transporter->id ? 'selected' : '' }}>
                                    {{ $transporter->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Vehicle --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Vehicle</label>
                        <select name="truck_id" class="form-select form-select-sm select2">
                            <option value="">All Vehicles</option>
                            @foreach($trucks as $truck)
                                <option value="{{ $truck->id }}"
                                    {{ request('truck_id') == $truck->id ? 'selected' : '' }}>
                                    {{ $truck->truck_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm select2">
                            <option value="">All Status</option>
                            <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="In-Progress" {{ request('status') == 'In-Progress' ? 'selected' : '' }}>In-Progress</option>
                            <option value="Delivered" {{ request('status') == 'Delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    {{-- Profit / Loss --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Profit / Loss</label>
                        <select name="profit_loss" class="form-select form-select-sm select2">
                            <option value="">All</option>
                            <option value="profit" {{ request('profit_loss') == 'profit' ? 'selected' : '' }}>Profit</option>
                            <option value="loss" {{ request('profit_loss') == 'loss' ? 'selected' : '' }}>Loss</option>
                        </select>
                    </div>

                    {{-- Filter Button --}}
                    <div class="col-6 col-md-2 d-flex align-items-end gap-1 gap-sm-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill d-inline-flex align-items-center justify-content-center" style="min-height: 31px;">
                            Filter
                        </button>
                        <a href="{{ route('reports.profitability-export', request()->query()) }}"
                           class="btn btn-sm flex-fill d-inline-flex align-items-center justify-content-center"
                           style="background-color: #f9a900; color: #000; white-space: nowrap; min-height: 31px;">
                            Export
                        </a>
                    </div>
                </div>
            </form>


            {{-- Overall Summary --}}
            <div class="row g-2 g-md-3 mb-4">

                <div class="col-6 col-md-3">
                    <div class="card border overall-summary-card">
                        <div class="card-body">
                            <small class="text-muted">Total Revenue</small>
                            <h5 class="mb-0">
                                ₹{{ number_format($totalRevenue, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card border overall-summary-card">
                        <div class="card-body">
                            <small class="text-muted">Total Cost</small>
                            <h5 class="mb-0">
                                ₹{{ number_format($totalCost, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card border overall-summary-card">
                        <div class="card-body">
                            <small class="text-muted">Total Profit</small>
                            <h5 class="mb-0 {{ $totalProfit < 0 ? 'text-danger' : 'text-success' }}">
                                ₹{{ number_format($totalProfit, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card border overall-summary-card">
                        <div class="card-body">
                            <small class="text-muted">Margin</small>
                            <h5 class="mb-0">
                                {{ number_format($totalMargin, 2) }}%
                            </h5>
                        </div>
                    </div>
                </div>

            </div>


            {{-- Trip Level Report --}}
            <h5 class="mb-3">Bilty-wise Profitability</h5>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th> LR / Bilty</th>
                            <th> Date </th>
                            <th>Customer</th>
                            <th>Transporter</th>
                            <th>Vehicle</th>
                            <th>Revenue</th>
                            <th>Cost</th>
                            <th>Profit</th>
                            <th>Margin %</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        {{-- IMPORTANT: controller sends $lrs, not $rows --}}
                        @forelse($lrs as $row)

                                <td>
                            {{ $row['lr']->lr_number ?? '-' }}
                             </td>                   

                                <td>
                                    {{ $row['lr']->gr_date ?? '-' }}
                                </td>

                                <td>
                                    {{ $row['lr']->customer?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $row['lr']->transporter?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $row['lr']->truck?->truck_number ?? '-' }}
                                </td>

                                <td>
                                    ₹{{ number_format($row['revenue'], 2) }}
                                </td>

                                <td>
                                    ₹{{ number_format($row['cost'], 2) }}
                                </td>

                                <td class="{{ $row['profit'] < 0 ? 'text-danger' : 'text-success' }}">
                                    ₹{{ number_format($row['profit'], 2) }}
                                </td>

                                <td>
                                    {{ number_format($row['margin'], 2) }}%
                                </td>

                                <td>
                                    {{ $row['lr']->delivery_status ?? '-' }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="10" class="text-center">
                                    No profitability data found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            <div class="mt-3">
                {{ $lrs->links() }}
            </div>


            {{-- Customer-wise Summary --}}
         @if(!request('customer_id') && $customerSummary->isNotEmpty())

                <div class="mt-5">

                    <h5 class="mb-3">
                        Customer-wise Profitability
                    </h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover">

                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th>Bilty</th>
                                    <th>Revenue</th>
                                    <th>Cost</th>
                                    <th>Profit</th>
                                    <th>Margin %</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach($customerSummary as $summary)

                                    <tr>

                                        <td>
                                            {{ $summary['customer'] }}
                                        </td>

                                        <td>
                                            {{ $summary['trips'] }}
                                        </td>

                                        <td>
                                            ₹{{ number_format($summary['revenue'], 2) }}
                                        </td>

                                        <td>
                                            ₹{{ number_format($summary['cost'], 2) }}
                                        </td>

                                        <td class="{{ $summary['profit'] < 0 ? 'text-danger' : 'text-success' }}">
                                            ₹{{ number_format($summary['profit'], 2) }}
                                        </td>

                                        <td>
                                            {{ number_format($summary['margin'], 2) }}%
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif


            {{-- Transporter-wise Summary --}}
           @if(!request('transporter_id') && $transporterSummary->isNotEmpty())

                <div class="mt-5">

                    <h5 class="mb-3">
                        Transporter-wise Profitability
                    </h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover">

                            <thead class="table-light">
                                <tr>
                                    <th>Transporter</th>
                                    <th>Bilty</th>
                                    <th>Revenue</th>
                                    <th>Cost</th>
                                    <th>Profit</th>
                                    <th>Margin %</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach($transporterSummary as $summary)

                                    <tr>

                                        <td>
                                            {{ $summary['transporter'] }}
                                        </td>

                                        <td>
                                            {{ $summary['trips'] }}
                                        </td>

                                        <td>
                                            ₹{{ number_format($summary['revenue'], 2) }}
                                        </td>

                                        <td>
                                            ₹{{ number_format($summary['cost'], 2) }}
                                        </td>

                                        <td class="{{ $summary['profit'] < 0 ? 'text-danger' : 'text-success' }}">
                                            ₹{{ number_format($summary['profit'], 2) }}
                                        </td>

                                        <td>
                                            {{ number_format($summary['margin'], 2) }}%
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('.select2').each(function () {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
    });
</script>

@endsection