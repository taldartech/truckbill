@extends('layouts.app')

@section('title', 'Transporter & Truck wise Report')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Select2 custom styling for truck dropdown */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .select2-result-truck {
            padding: 4px 0;
        }

        .truck-info {
            font-size: 14px;
            line-height: 1.4;
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

        .select2-results__option--highlighted {
            background-color: var(--brand-primary);
            color: white;
        }
    </style>
@endpush

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.transporter-report') }}" id="searchForm">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Transporter/Truck Owner</label>
                        <select name="transporter_id" class="form-select select2" onchange="fetchTruck()">
                            <option value="">Select Transporter/Truck Owner</option>
                            @foreach ($transporters as $transporter)
                                <option @if ($transporterId == $transporter->id) selected @endif value="{{ $transporter->id }}">
                                    {{ $transporter->name }} (@if ($transporter->is_owner)
                                        Owner
                                    @else
                                        Transporter
                                    @endif)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Truck No</label>
                        <select name="truck_id" class="form-select select2" onchange="fetchReference()">
                            <option value="">Select Truck No</option>
                            @foreach ($trucks as $truck)
                                <option @if ($truckId == $truck->id) selected @endif value="{{ $truck->id }}">
                                    {{ $truck->truck_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference By</label>
                        <select name="reference_by" class="form-select select2">
                            <option value="">Select Reference By</option>
                            @foreach ($references as $reference)
                                <option @if ($referenceId == $reference->id) selected @endif value="{{ $reference->id }}">
                                    {{ $reference->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.transporter-report') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!request()->filled('start_date') && !request()->filled('end_date'))
        <div class="alert alert-warning">
            Please select dates to generate the report.
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
                        <h3 class="card-title mb-0">Tranporter/Truck wise Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.transporter-report-export', ['start_date' => $startDate, 'end_date' => $endDate, 'transporter_id' => $transporterId, 'truck_id' => $truckId, 'reference_by' => $referenceId]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                    </div>
                </div>
            </div>
            @php
                $paymentNames = \App\Models\PaymentMode::pluck('name')->toArray();
                $payments = array_fill_keys($paymentNames, 0);
            @endphp
            <div class="card-body" style="overflow-x: auto;">
                <table class="table table-bordered table-striped" style="font-size: 12px !important;">
                    <thead>
                        <tr>
                            <th>GR Date</th>
                            <th>LR No</th>
                            <th>GR No</th>
                            <th>Truck No</th>
                            <th>Destination</th>
                            <th>Loading Weight</th>
                            <th>Unloading Weight</th>
                            <th>Bags</th>
                            <th>Consignor Name</th>
                            <th>Consignee Name</th>
                            <th>TR Rate</th>
                            <th>Tr Freight</th>
                            <th>TDS</th>
                            @foreach ($paymentNames as $paymentName)
                                <th>{{ $paymentName }}</th>
                            @endforeach
                            <th>Damage Amount</th>
                            <th>Short Amount</th>
                            <th>Misc Expenses</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lrs as $index => $lr)
                            @php
                                $fr = $lr->freightBookings->last();
                                $payments = array_fill_keys($paymentNames, 0);
                                $total_balance = 0;
                                if ($fr) {
                                    $vouchers = \App\Models\VoucherPayment::where('freight_booking_id', $fr->id)->get();
                                    foreach ($vouchers as $voucher) {
                                        $paymentMode = $voucher->payment_mode;
                                        if (isset($payments[$paymentMode])) {
                                            $payments[$paymentMode] += $voucher->amount;
                                        }
                                    }
                                    $total_balance = $fr->balance_amount;
                                }
                            @endphp
                            <tr>
                                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}
                                </td>
                                <td>{{ $lr->lr_number }}</td>
                                <td>{{ $lr->gr_number }}</td>
                                <td>{{ $lr->truck_id ? $lr->truck->truck_number : 'N/A' }}</td>
                                <td>{{ $lr->destination_city . ', ' . $lr->destination_state }}</td>
                                <td>{{ $lr->weight }}</td>
                                <td>{{ number_format($lr->unloading_weight ?? $lr->weight, 3) }}</td>
                                <td>{{ $lr->no_of_units }}</td>
                                <td>{{ $lr->consignor_id ? $lr->consignor->name : 'N/A' }}</td>
                                <td>{{ $lr->consignee_id ? $lr->consignee->name : 'N/A' }}</td>
                                <td>{{ number_format($fr?->tr_fr_rate ?? 0, 2) }}</td>
                                <td>{{ number_format($fr?->tr_amount ?? 0, 2) }}</td>
                                <td>{{ number_format($fr?->tds_amount ?? 0, 2) }}</td>
                                @foreach ($paymentNames as $paymentName)
                                    <td>{{ number_format($payments[$paymentName], 2) }}</td>
                                @endforeach
                                <td>{{ number_format($lr->damage_amount ?? 0, 2) }}</td>
                                <td>{{ number_format($lr->shortage_amount ?? 0, 2) }}</td>
                                <td>{{ number_format($fr?->misc_expense ?? 0, 2) }}</td>
                                <td>{{ number_format($total_balance, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').each(function() {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            });
        });
    </script>
    <script>
        function fetchTruck() {
            var transporterId = $('select[name="transporter_id"]').val();
            if (transporterId) {
                $.ajax({
                    url: '/api/trucks/' + transporterId + '/lrs',
                    type: 'GET',
                    success: function(response) {
                        var truckSelect = $('select[name="truck_id"]');
                        truckSelect.empty();
                        truckSelect.append('<option value="">Select Truck No</option>');
                        response.forEach(function(truck) {
                            truckSelect.append('<option value="' + truck.id + '">' + truck
                                .truck_number + '</option>');
                        });
                        truckSelect.trigger('change');
                    },
                    error: function() {
                        alert('Error fetching trucks. Please try again.');
                    }
                });
            } else {
                var truckSelect = $('select[name="truck_id"]');
                truckSelect.empty();
                truckSelect.append('<option value="">Select Truck No</option>');
                truckSelect.trigger('change');
            }
            var referenceSelect = $('select[name="reference_by"]');
            referenceSelect.empty();
            referenceSelect.append('<option value="">Select Reference By</option>');
            referenceSelect.trigger('change');
        }

        function fetchReference() {
            var transporterId = $('select[name="transporter_id"]').val();
            var truckId = $('select[name="truck_id"]').val();

            if (truckId) {
                $.ajax({
                    url: '/api/references/' + transporterId + '/' + truckId + '/lrs',
                    type: 'GET',
                    success: function(response) {
                        var referenceSelect = $('select[name="reference_by"]');
                        referenceSelect.empty();
                        referenceSelect.append('<option value="">Select Reference By</option>');
                        response.forEach(function(lr) {
                            referenceSelect.append('<option value="' + lr.id + '">' + lr
                                .name + '</option>');
                        });
                        referenceSelect.trigger('change');
                    },
                    error: function() {
                        alert('Error fetching references. Please try again.');
                    }
                });
            } else {
                var referenceSelect = $('select[name="reference_by"]');
                referenceSelect.empty();
                referenceSelect.append('<option value="">Select Reference By</option>');
                referenceSelect.trigger('change');
            }
        }
    </script>
@endpush
