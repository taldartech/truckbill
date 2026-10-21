@extends('layouts.app')

@section('title', 'Shortage Damage Report')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.shortage-damage') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                    </div>

                    <div class="col-12 col-md-6 d-flex align-items-end justify-content-end gap-1 gap-sm-2 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-brand-primary btn-sm btn-report-search" title="Search">
                            <i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Search</span>
                        </button>
                        <a href="{{ route('reports.shortage-damage') }}?date={{ date('Y-m-d') }}"
                            class="btn btn-secondary btn-sm btn-report-reset" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i><span class="d-none d-sm-inline ms-1">Reset</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$generated)
        <div class="alert alert-warning">
            Please generate the report by selecting at Dates.
        </div>
    @elseif (empty($lrs) || $lrs->isEmpty())
        <div class="alert alert-info">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Shortage Damage Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning btn-sm ms-2"
                            href="{{ route('reports.shortage-damage-export', ['start_date' => $startDate, 'end_date' => $endDate]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline ms-1">Export</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap small">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>LR No</th>
                            <th>GR Date</th>
                            <th>Truck No</th>
                            <th>Loading Weight</th>
                            <th>Unloading Weight</th>
                            <th>LR Rate</th>
                            <th>LR Amount</th>
                            <th>Consignor</th>
                            <th>Consignee</th>
                            <th>Destination</th>
                            <th>Shortage Amount</th>
                            <th>Damage Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lrs as $index => $lr)
                            @php
                                // Get the first freight booking (or null if none exists)
                                $freightBooking = $lr->freightBookings->first();
                                // Initialize payments array with all payment types
                                $payments = [
                                    'cash' => 0,
                                    'adjust in advance' => 0,
                                    'bank transfer' => 0,
                                    'fuel(paid to pump)' => 0,
                                ];

                                // If we have a freight booking with loaded voucher payments
                                if ($freightBooking && $freightBooking->relationLoaded('voucherPayments')) {
                                    // Group payments by payment mode and sum amounts
                                    $paymentSums = $freightBooking->voucherPayments
                                        ->groupBy('payment_mode')
                                        ->map(function ($payments) {
                                            return $payments->sum('amount');
                                        });

                                    // Map the grouped payments to our expected keys
                                    foreach ($payments as $mode => $amount) {
                                        $normalizedMode = strtolower(trim($mode));
                                        $matchingMode = $paymentSums
                                            ->keys()
                                            ->first(function ($key) use ($normalizedMode) {
                                                return strtolower(trim($key)) === $normalizedMode;
                                            });
                                        if ($matchingMode !== null) {
                                            $payments[$mode] = $paymentSums[$matchingMode];
                                        }
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $lr->lr_number }}</td>
                                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}
                                </td>
                                <td>{{ $lr->truck->truck_number ?? 'N/A' }}</td>
                                <td>{{ $lr->weight }}</td>
                                <td>{{ number_format($lr->unloading_weight ?? $lr->weight, 3) }}</td>
                                <td>{{ $lr->fr_rate }}</td>
                                <td>{{ $lr->amount }}</td>
                                <td>{{ $lr->consignor->name ?? 'N/A' }}</td>
                                <td>{{ $lr->consignee->name ?? 'N/A' }}</td>
                                <td>{{ $lr->destination_city }}</td>
                                <td>{{ $freightBooking->shortage_amount ?? '0' }}</td>
                                <td>{{ $freightBooking->damage_amount ?? '0' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                {{ $lrs->links() }}
            </div>
        </div>
    @endif
@endsection
