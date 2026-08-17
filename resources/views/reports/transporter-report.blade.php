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
                    <select name="transporter_id" id="transporterFilter" class="form-select select2">
                        <option value="">Select Transporter/Truck Owner</option>
                        @foreach ($transporters as $transporter)
                        <option @if ($transporterId==$transporter->id) selected @endif value="{{ $transporter->id }}">
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
                    <select name="truck_id" id="truckFilter" class="form-select select2">
                        <option value="">Select Truck No</option>
                        @foreach ($trucks as $truck)
                        <option @if ($truckId==$truck->id) selected @endif value="{{ $truck->id }}">
                            {{ $truck->truck_number }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reference By</label>
                    <select name="reference_by" id="referenceFilter" class="form-select select2">
                        <option value="">Select Reference By</option>
                        @foreach ($references as $reference)
                        <option @if ($referenceId==$reference->id) selected @endif value="{{ $reference->id }}">
                            {{ $reference->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <!-- <div class="col-md-3 text-end"> -->
                    <div class="col-md-3 d-flex align-items-end justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('reports.transporter-report') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>


            <div class="row mb-2">
                <div class="col-12">
                    <div id="filterValidationMessage" class="alert alert-warning d-none py-2 mb-0"></div>
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
                <h3 class="card-title mb-0">Transporter/Truck wise Report</h3>
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
                    <th>Negative Adjusted</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lrs as $index => $lr)
                @php
                $fr = $lr->freightBooking;
                $payments = array_fill_keys($paymentNames, 0);
                $total_balance = 0;
                if ($fr) {
                $vouchers = $fr->voucherPayments ?? collect();
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
                    <td>{{ number_format($fr?->negative_adjusted_amount ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3 d-flex justify-content-end">
        {{ $lrs->links() }}
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
        const $startDate = $('input[name="start_date"]');
        const $endDate = $('input[name="end_date"]');
        const $transporter = $('select[name="transporter_id"]');
        const $truck = $('select[name="truck_id"]');
        const $reference = $('select[name="reference_by"]');
        const $validation = $('#filterValidationMessage');

        function showValidation(message) {
            $validation.text(message).removeClass('d-none');
        }

        function hideValidation() {
            $validation.addClass('d-none').text('');
        }

        function rangeExceedsThreeMonths(startDate, endDate) {
            const start = new Date(startDate + 'T00:00:00');
            const end = new Date(endDate + 'T00:00:00');

            if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
                return false;
            }

            const minAllowedStart = new Date(end);
            minAllowedStart.setMonth(minAllowedStart.getMonth() - 3);

            return start < minAllowedStart;
        }

        function transporterDisplayName(item) {
            if (item.name != null && String(item.name).length > 0) {
                return String(item.name);
            }
            const master = item.transporter_master;
            if (master && master.name != null && String(master.name).length > 0) {
                return String(master.name);
            }
            return '';
        }

        function rebuildSelect($select, options, placeholder, selectedValue, labelKey) {
            const previousValue = selectedValue || '';
            $select.empty().append(new Option(placeholder, ''));

            (options || []).forEach(function(item) {
                const label = labelKey(item);
                $select.append(new Option(label, String(item.id), false, false));
            });

            const isStillAvailable = (options || []).some(function(item) {
                return String(item.id) === String(previousValue);
            });

            $select.val(isStillAvailable ? String(previousValue) : '');
            $select.trigger('change.select2');
        }

        function refreshFilterOptions() {
            const startDate = $startDate.val();
            const endDate = $endDate.val();

            if (!startDate || !endDate) {
                showValidation('Start Date and End Date are required for transporter filter options.');
                return;
            }

            if (rangeExceedsThreeMonths(startDate, endDate)) {
                showValidation('Maximum allowed date range is 3 months.');
                return;
            }

            hideValidation();

            const currentSelections = {
                transporter: $transporter.val(),
                truck: $truck.val(),
                reference: $reference.val()
            };

            $.ajax({
                url: '/api/reports/transporter-filter-options',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    transporter_id: currentSelections.transporter || '',
                    truck_id: currentSelections.truck || '',
                    reference_by: currentSelections.reference || ''
                },
                success: function(response) {
                    const transporters = response.transporters || [];
                    const trucks = response.trucks || [];
                    const references = response.references || [];

                    rebuildSelect(
                        $transporter,
                        transporters,
                        'Select Transporter/Truck Owner',
                        currentSelections.transporter,
                        function(item) {
                            return transporterDisplayName(item) + (item.is_owner ? ' (Owner)' : ' (Transporter)');
                        }
                    );

                    rebuildSelect($truck, trucks, 'Select Truck No', currentSelections.truck, function(item) {
                        return item.truck_number;
                    });

                    rebuildSelect(
                        $reference,
                        references,
                        'Select Reference By',
                        currentSelections.reference,
                        function(item) {
                            return transporterDisplayName(item);
                        }
                    );
                },
                error: function(xhr) {
                    let message = 'Error fetching filter options. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const firstErrorKey = Object.keys(xhr.responseJSON.errors)[0];
                        if (firstErrorKey && xhr.responseJSON.errors[firstErrorKey].length) {
                            message = xhr.responseJSON.errors[firstErrorKey][0];
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    showValidation(message);
                }
            });
        }

        $transporter.on('change', refreshFilterOptions);
        $truck.on('change', refreshFilterOptions);
        $reference.on('change', refreshFilterOptions);
        $startDate.on('change', refreshFilterOptions);
        $endDate.on('change', refreshFilterOptions);

        if ($startDate.val() && $endDate.val()) {
            refreshFilterOptions();
        }
    });
</script>
@endpush