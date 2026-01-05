@extends('layouts.app')

@section('title', 'Pending Invoice')

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
            <form method="GET" action="{{ route('reports.pending-invoice') }}" id="searchForm">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select select2">
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option @if ($customerId == $customer->id) selected @endif value="{{ $customer->id }}">
                                    {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 text-end d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.pending-invoice') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Pending Invoice</h3>
                </div>
                <div class="d-flex">
                    <a class="btn btn-warning ms-2" target="_blank"
                        href="{{ route('reports.pending-invoice-export', [
                            'customer_id' => $customerId,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ]) }}">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export
                    </a>

                </div>
            </div>
        </div>
        <div class="card-body" style="overflow-x: auto;">
            @if ($lrs->count() > 0)
                @php
                    $totalAmount = 0;
                    $damageAmount = 0;
                    $shortageAmount = 0;
                @endphp
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Truck No</th>
                                <th>Customer</th>
                                <th>From Place</th>
                                <th>To Place</th>
                                <th>Consignment No</th>
                                <th>Date</th>
                                <th>Lane</th>
                                <th>QTY(MT)</th>
                                <th>Rate</th>
                                <th>Damage</th>
                                <th>Shortage</th>
                                <th>Amt.</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lrs as $lr)
                                @php
                                    $damageAmount += $lr->damage_amount;
                                    $shortageAmount += $lr->shortage_amount;
                                    $amount = $lr->weight * $lr->fr_rate;
                                    $totalAmount += $amount;
                                @endphp
                                <tr>
                                    <td>{{ $lr->truck->truck_number ?? 'N/A' }}</td>
                                    <td>{{ $lr->customer->name ?? 'N/A' }}</td>
                                    <td>{{ $lr->source_city }}</td>
                                    <td>{{ $lr->destination_city }}</td>
                                    <td>{{ $lr->lr_number }}</td>
                                    <td>{{ date('d-m-Y', strtotime($lr->gr_date)) }}</td>
                                    <td>{{ $lr->lane }}</td>
                                    <td style="text-align: right">{{ number_format($lr->weight, 3) }}</td>
                                    <td style="text-align: right">{{ number_format($lr->fr_rate, 2) }}</td>
                                    <td style="text-align: right">{{ number_format($lr->damage_amount, 2) }}</td>
                                    <td style="text-align: right">{{ number_format($lr->shortage_amount, 2) }}</td>
                                    <td style="text-align: right">{{ number_format($amount, 2) }}</td>

                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">No records found for the selected criteria.</div>
            @endif
        </div>
    </div>

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
@endpush
