@extends('layouts.app')

@section('title', 'Pump Report')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Select2 custom styling for dropdowns */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 12px;
            padding-right: 20px;
            color: #212529;
            line-height: 36px;
            background: var(--bs-body-bg);
            border: var(--bs-border-width) solid var(--bs-border-color);
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single:focus-visible {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
            outline: 0;
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 12px;
        }

        .select2-dropdown {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }

        .select2-results__option--highlighted {
            background-color: var(--bs-primary);
            color: white;
        }
    </style>
@endpush

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.pump') }}" id="searchForm" onsubmit="validateForm(event)">
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
                        <label class="form-label">Pump</label>
                        <select name="pump_id" class="form-select select2">
                            <option value="">Select Pump</option>
                            @foreach ($pumps as $pump)
                                <option @if ($pumpId == $pump->id) selected @endif value="{{ $pump->id }}">
                                    {{ $pump->pump_name }}
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
                        <a href="{{ route('reports.pump') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (isset($pumpTransactions) && $pumpTransactions->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Pump Transaction Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.pump-export', ['start_date' => $startDate, 'end_date' => $endDate, 'pump_id' => $pumpId, 'truck_id' => $truckId]) }}">
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
                            <th>Date</th>
                            <th>LR No</th>
                          
                            <th>Truck No</th>
                         
                           <th>Destination</th>
                            <th>Diesel Amount</th>
                          
                            
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pumpTransactions as $index => $transaction)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y') }}</td>

                                <td>{{ $transaction->lr->lr_number ?? 'N/A' }}</td>
                                <td>{{ $transaction->truck->truck_number ?? 'N/A' }}</td>
                                <td>{{ $transaction->destination }}</td>
                                <td>{{ number_format($transaction->amount, 2) }}</td>
                                
                                
                                
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(request()->has('start_date') && request()->has('end_date'))
        <div class="alert alert-info">
            No pump transactions found for the selected criteria.
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

        function validateForm(event) {
            event.preventDefault();
            
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            
            if (!startDate || !endDate) {
                alert('Please select both start and end dates.');
                return false;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be after end date.');
                return false;
            }

            // If validation passes, submit the form
            event.target.submit();
        }
    </script>
@endpush