@extends('layouts.app')

@section('title', 'Dispatch Report')

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.dispatch') }}" id="searchForm" onsubmit="validateForm(event)">
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
                        <a href="{{ route('reports.dispatch') }}?date={{ date('Y-m-d') }}"
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
                        <h3 class="card-title mb-0">Dispatch Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.dispatch-export', ['start_date' => $startDate, 'end_date' => $endDate]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                    </div>
                </div>
            </div>
            <style>
                .table-sticky {
                    width: 100%;
                    /* Set your desired width */
                    max-height: 400px;
                    /* Adjust as needed */
                    overflow-y: auto;
                    /* Enables vertical scroll */
                    overflow-x: auto;
                    /* Enables horizontal scroll */
                    display: block;
                    padding-top: 0px;
                }

                .table-sticky table {
                    border-collapse: collapse;
                    width: max-content;
                    /* Adjust if you want wider tables */
                    min-width: 100%;
                }

                .table-sticky thead th {
                    position: sticky;
                    top: 0;
                    background: #fff;
                    /* Ensures visibility */
                    z-index: 2;
                    /* Keeps header above table rows */
                    border-bottom: 1px solid #ddd;
                }
            </style>
            <div class="card-body table-sticky">
                <table class="table table-bordered table-striped" style="font-size: 12px !important;">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>GR Date</th>
                            <th>LR No</th>
                            <th>Truck No</th>
                            <th>Destination</th>

                            <th>Invoice No.</th>

                            <th>Weight</th>
                            <th>Bags</th>
                            <th>Fr rate</th>
                            <th> Amount</th>
                            <th>Unloading st.</th>
                            <th>Distt. </th>
                            <th>State</th>
                            <th>Consignor</th>
                            <th>Consignee</th>
                            <th>GST Number</th>
                            <th>Tr Rate</th>
                            <th>Tr Freight</th>

                            <th>TDS</th>
                            <th>Cash Advance</th>
                            <th>Bank</th>
                            <th>Adjust In Advance</th>
                            <th>Diesel</th>
                            <th>Total</th>

                            <th>Damage Amount</th>
                            <th>Shortage Amount</th>
                            <th>Detention</th>
                            <th>Deviation</th>
                            <th>Misc.</th>
                            <th>Balance</th>
                            <th>Diff</th>
                            <th>Memo No.</th>
                            <th>Bill No.</th>
                            <th>Driver No.</th>
                            <th>Transport Name</th>
                            <th>Reference By</th>
                            <th>Payment Made to</th>
                            <th>TDS Deducted</th>
                            <th>E-way Bill No.</th>
                            <th>E-way Bill Expiry Date</th>
                            <th>Remarks</th>
                            <th>PAN No</th>
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
                                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}
                                </td>
                                <td>{{ $lr->lr_number }}</td>
                                <td>{{ $lr->truck->truck_number ?? 'N/A' }}</td>
                                <td>{{ $lr->destination_city }}</td>

                                <td>{{ $lr->invoice_number }}</td>

                                <td>{{ $lr->weight }}</td>
                                <td>{{ $lr->no_of_units }}</td>
                                <td>{{ $lr->fr_rate }}</td>
                                <td>{{ $lr->amount }}</td>
                                <td>{{ $lr->unloading_station ?? 'N/A' }}</td>
                                <td>{{ $lr->destination_city }}</td>
                                <td>{{ $lr->destination_state }}</td>
                                <td>{{ $lr->consignor->name ?? 'N/A' }}</td>
                                <td>{{ $lr->consignee->name ?? 'N/A' }}</td>
                                <td>{{ $lr->customer->gst_number ?? 'N/A' }}</td>
                                <td>{{ $freightBooking->tr_fr_rate ?? 'N/A' }}</td>
                                <td>{{ $freightBooking->tr_amount ?? 'N/A' }}</td>
                                <td>{{ $freightBooking->tds_amount ?? 'N/A' }}</td>
                                <td>{{ $payments['cash'] }}</td>
                                <td>{{ $payments['bank transfer'] }}</td>
                                <td>{{ $payments['adjust in advance'] }}</td>
                                <td>{{ $payments['fuel(paid to pump)'] }}</td>

                                @php
                                    $total =
                                        $payments['cash'] +
                                        $payments['bank transfer'] +
                                        $payments['adjust in advance'] +
                                        $payments['fuel(paid to pump)'] +
                                        ($freightBooking->tds_amount ?? 0);
                                @endphp
                                <td>{{ $total }}</td>
                                <td>{{ $freightBooking->damage_amount ?? '0' }}</td>
                                <td>{{ $freightBooking->shortage_amount ?? '0' }}</td>
                                <td>{{ $freightBooking->detention ?? '0' }}</td>
                                <td>{{ $freightBooking->deviation ?? '0' }}</td>
                                <td>{{ $freightBooking->misc ?? '0' }}</td>
                                <td>{{ $freightBooking->balance_amount ?? '0' }}</td>
                                <td>{{ $lr->fr_rate - ($freightBooking->tr_fr_rate ?? 0) }}</td>

                                <td>{{ $lr->memo_id ?? 'N/A' }}</td>
                                {{-- use this memo_id to get invoice number from invoice table --}}
                                {{-- this memo_ids are stored in array from in invoice table --}}
                                @php
                                    $lrIds = $lr->id;
                                    // in invoice table lr_ids are stored in array form

                                    $invoice = App\Models\Invoice::whereJsonContains('lr_ids', $lrIds)->first();
                                    //   in truck table their is dirver id from that find mobile number
                                    $truck = App\Models\Truck::where('driver_id', $lr->truck->driver_id)->first();

                                @endphp

                                <td>{{ $invoice->manual_invoice_number ?? 'N/A' }}</td>
                                <td>{{ $truck->driver->mobile ?? 'N/A' }}</td>
                                <td>{{ $lr->transporter->name ?? 'N/A' }}</td>
                                <td>{{ $lr->reference->name ?? 'N/A' }}</td>
                                <td>{{ $lr->transporter_type ?? 'N/A' }}</td>
                                <td>{{ $lr->transporter->tds_percentage == 0 ? 'No' : 'Yes' }}</td>
                                <td>{{ $freightBooking->eway_bill_no ?? 'N/A' }}</td>
                                <td>{{ $freightBooking->eway_bill_expiry_date ?? 'N/A' }}</td>
                                <td>{{ $lr->delivery_status ?? 'N/A' }}</td>
                                <td>{{ $lr->transporter->pan_number ?? 'N/A' }}</td>
                                {{-- Rest of the row remains the same --}}
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
