@extends('layouts.app')

@section('title', 'Consignor & Consignee wise Report')

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
            <form method="GET" action="{{ route('reports.consignor-consignee') }}" id="searchForm"
                onsubmit="validateForm(event)">
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
                        <label class="form-label">Consignor</label>
                        <select name="consignor_id" class="form-select select2">
                            <option value="">Select Consignor</option>
                            @foreach ($consignees as $consignor)
                                <option @if ($consignorId == $consignor->id) selected @endif value="{{ $consignor->id }}">
                                    {{ $consignor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Consignee</label>
                        <select name="consignee_id" class="form-select select2">
                            <option value="">Select Consignee</option>
                            @foreach ($consignees as $consignee)
                                <option @if ($consigneeId == $consignee->id) selected @endif value="{{ $consignee->id }}">
                                    {{ $consignee->name }}</option>
                            @endforeach
                        </select>

                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('voucher_payments.daily') }}?date={{ date('Y-m-d') }}"
                            class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$consignorId && !$consigneeId)
        <div class="alert alert-warning">
            Please select at least one of Consignor or Consignee to generate the report.
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
                        <h3 class="card-title mb-0">Consigner & Consignee wise Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.consignor-consignee-export', ['start_date' => $startDate, 'end_date' => $endDate, 'consignor_id' => $consignorId, 'consignee_id' => $consigneeId]) }}">
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
                            <th>Inv No of Factory</th>
                            <th>Truck No</th>
                            <th>Weight</th>
                            <th>LR Rate</th>
                            <th>LR Amt</th>
                            <th>Consignor Name</th>
                            <th>Consignee Name</th>
                            <th>Destination</th>
                            <th>Transport/ Truck Owner Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lrs as $index => $lr)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}
                                </td>
                                <td>{{ $lr->lr_number }}</td>
                                <td>{{ $lr->invoice_number }}</td>
                                <td>{{ $lr->truck_id ? $lr->truck->truck_number : 'N/A' }}</td>
                                <td>{{ $lr->weight }}</td>
                                <td>{{ number_format($lr->fr_rate, 2) }}</td>
                                <td>{{ number_format($lr->amount, 2) }}</td>
                                <td>{{ $lr->consignor_id ? $lr->consignor->name : 'N/A' }}</td>
                                <td>{{ $lr->consignee_id ? $lr->consignee->name : 'N/A' }}</td>
                                <td>{{ $lr->destination_city . ', ' . $lr->destination_state }}</td>
                                <td>{{ $lr->transporter_id ? $lr->transporter->name : 'N/A' }}</td>
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
        function validateForm(event) {
            event.preventDefault();
            // Get the values of the date inputs
            const consignorId = $('select[name="consignor_id"]').val();
            const consigneeId = $('select[name="consignee_id"]').val();

            if (!consignorId && !consigneeId) {
                alert('Please select at least one of Consignor or Consignee.');
                return false;
            }

            if (consignorId && consigneeId && consignorId === consigneeId) {
                alert('Consignor and Consignee cannot be the same.');
                return false;
            }

            // If validation passes, submit the form
            event.target.submit();
        }
    </script>
@endpush
