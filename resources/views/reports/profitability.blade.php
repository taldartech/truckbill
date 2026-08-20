@extends('layouts.app')

@section('content')

<div class="container mt-4">

    <div class="card">

        <div class="card-header">
            <h3 class="card-title mb-0">Profitability Report</h3>
        </div>

        <div class="card-body">

            {{-- Filters --}}
            <form method="GET"
                  action="{{ route('reports.profitability') }}"
                  class="row g-3 mb-4">

                {{-- Date From --}}
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date"
                           name="start_date"
                           class="form-control"
                           value="{{ $startDate }}">
                </div>

                {{-- Date To --}}
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date"
                           name="end_date"
                           class="form-control"
                           value="{{ $endDate }}">
                </div>

                {{-- Customer --}}
                <div class="col-md-2">
                    <label class="form-label">Customer</label>

                    <select name="customer_id" class="form-control">
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
                <div class="col-md-2">
                    <label class="form-label">Transporter</label>

                    <select name="transporter_id" class="form-control">
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
                <div class="col-md-2">
                    <label class="form-label">Vehicle</label>

                    <select name="truck_id" class="form-control">
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
          <div class="col-md-2">
          <label class="form-label">Status</label>

          <select name="status" class="form-control">
          <option value="">All Status</option>

            <option value="Pending"
            {{ request('status') == 'Pending' ? 'selected' : '' }}>
            Pending
            </option>

            <option value="In-Progress"
            {{ request('status') == 'In-Progress' ? 'selected' : '' }}>
            In-Progress
            </option>

            <option value="Delivered"
            {{ request('status') == 'Delivered' ? 'selected' : '' }}>
            Delivered
            </option>

           <option value="Cancelled"
            {{ request('status') == 'Cancelled' ? 'selected' : '' }}>
            Cancelled
           </option>
           </select>
           </div>

                {{-- Profit / Loss --}}
                <div class="col-md-2">
                    <label class="form-label">Profit / Loss</label>

                    <select name="profit_loss" class="form-control">
                        <option value="">All</option>

                        <option value="profit"
                            {{ request('profit_loss') == 'profit' ? 'selected' : '' }}>
                            Profit
                        </option>

                        <option value="loss"
                            {{ request('profit_loss') == 'loss' ? 'selected' : '' }}>
                            Loss
                        </option>
                    </select>
                </div>

                {{-- Filter Button --}}
               <div class="col-md-2 d-flex align-items-end gap-3">

               <button type="submit" class="btn btn-primary">
               Filter
               </button>

             <a href="{{ route('reports.profitability-export', request()->query()) }}"
               class="btn"
             style="background-color: #f9a900; color: #000; white-space: nowrap;">
              Export Excel
               </a>
           </div>

            </form>


            {{-- Overall Summary --}}
            <div class="row mb-4">

                <div class="col-md-3">
                    <div class="card border">
                        <div class="card-body">
                            <small class="text-muted">Total Revenue</small>
                            <h5 class="mb-0">
                                ₹{{ number_format($totalRevenue, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border">
                        <div class="card-body">
                            <small class="text-muted">Total Cost</small>
                            <h5 class="mb-0">
                                ₹{{ number_format($totalCost, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border">
                        <div class="card-body">
                            <small class="text-muted">Total Profit</small>
                            <h5 class="mb-0 {{ $totalProfit < 0 ? 'text-danger' : 'text-success' }}">
                                ₹{{ number_format($totalProfit, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border">
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

@endsection