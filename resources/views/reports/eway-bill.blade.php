@extends('layouts.app')

@section('title', 'Eway Bill Report')

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.eway-bill') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.eway-bill') }}?date={{ date('Y-m-d') }}"
                            class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
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
                        <h3 class="card-title mb-0">Eway Bill Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.eway-bill-export', ['start_date' => $startDate, 'end_date' => $endDate]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <table class="table table-bordered table-striped" style="font-size: 12px !important;">
                    <thead>
                        <tr>
                          <th>Sr No</th>
                          <th>LR No</th>
                          <th>GR Date</th>
                        
                          <th>Truck No</th>
                          <th>Weight</th>
                          <th>LR Rate</th>
                          <th>LR Amount</th>
                          <th>Consignor</th>
                          <th>Consignee</th>
                          <th>Destination</th>
                          <th>Eway Bill No.</th>
                          <th>Eway Bill Expiry Date</th>
                          <th>Transporter/Truck Owner Name</th>
                          <th>Transporter/Truck Owner Mobile No.</th>
                          

                         
                        
                          
                          
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
                                        $matchingMode = $paymentSums->keys()->first(function ($key) use ($normalizedMode) {
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
                                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}</td>
                               
                                <td>{{ $lr->truck->truck_number ?? 'N/A' }}</td>
                                <td>{{ $lr->weight }}</td>
                                <td>{{ $lr->fr_rate }}</td>
                                <td>{{ $lr->amount }}</td>
                                <td>{{ $lr->consignor->name ?? 'N/A' }}</td>
                                <td>{{ $lr->consignee->name ?? 'N/A' }}</td>
                                <td>{{ $lr->destination_city }}</td>
                                <td>{{ $freightBooking->eway_bill_no ?? '0' }}</td>
                                <td>{{ $freightBooking->eway_bill_expiry ?? '0' }}</td>
                                <td>{{$lr->transporter->name ?? 'N/A'}}</td>
                                <td>{{$lr->transporter->mobile_number ?? 'N/A'}}</td>
                               
                               
                             
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
