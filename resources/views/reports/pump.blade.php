@extends('layouts.app')

@section('title', 'Pump Wise Report')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

        .select2-results__option--highlighted {
            background-color: var(--brand-primary);
            color: white;
        }

        .table th,
        .table td {
            white-space: nowrap;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.pump') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row mb-2" style="align-items: end;">
                    <div class="col-md-2">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Pump <span class="text-danger">*</span></label>
                        <select name="pump_id" class="form-select select2" required>
                            <option value="">Select Pump</option>
                            @foreach ($pumps as $pump)
                                <option value="{{ $pump->id }}" {{ $pumpId == $pump->id ? 'selected' : '' }}>
                                    {{ $pump->pump_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending
                            </option>
                            <option value="in-progress" {{ request('payment_status') == 'in-progress' ? 'selected' : '' }}>
                                In Progress</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
               

                
             <div class="col-md-3 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.pump') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                </div>
                 </div>
            </form>
        </div>
    </div>

    @if (!$pumpId)
        <div class="alert alert-warning">
            Please select a pump to generate the report.
        </div>
    @elseif($pumpData->isEmpty())
        <div class="alert alert-info">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Pump Transaction Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.pump-export', [
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'pump_id' => $pumpId,
                                'payment_status' => request('payment_status'),
                            ]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <table class="table table-bordered table-striped" style="font-size: 12px !important;">
                    <thead>
                        <tr>
                            <th>Sr.no</th>
                            <th>LR Number</th>
                            <th>Date</th>
                            <th>Truck No.</th>
                            <th>Destination</th>
                            <th>Diesel Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pumpData as $index => $item)
                            @foreach ($item['lrs'] as $lr)
                                <tr>
                                    <td>{{ $loop->parent->iteration }}</td>

                                    <td>{{ $lr->lr_number ?? 'N/A' }}</td>
                                    <td>{{ $item['date'] ?? 'N/A' }}</td>
                                    <td>{{ $lr->truck->truck_number ?? 'N/A' }}</td>

                                    <td>{{ $lr->destination_city }}, {{ $lr->destination_state ?? 'N/A' }}</td>

                                    <td class="text-end">{{ number_format($item['amount'], 2) }}</td>
                                    <td>
                                        @if ($item['payment_status'] === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($item['payment_status'] === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($item['payment_status'] === 'in-progress')
                                            <span class="badge bg-info">In Progress</span>
                                        @else
                                            <span
                                                class="badge bg-secondary">{{ ucfirst($item['payment_status'] ?? 'N/A') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Grand Total:</th>
                            <th class="text-end">{{ number_format($grandTotalAmount ?? 0, 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                {{ $pumpData->links() }}
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });

        function validateForm(event) {
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            const pumpId = $('select[name="pump_id"]').val();

            if (!pumpId) {
                alert('Please select a pump');
                event.preventDefault();
                return false;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be greater than end date');
                event.preventDefault();
                return false;
            }

            return true;
        }
    </script>
@endpush
