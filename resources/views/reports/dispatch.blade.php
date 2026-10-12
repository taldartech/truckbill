@extends('layouts.app')

@section('title', 'Dispatch Report')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    @include('reports.partials.select2-customer-brand')
@endpush

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-3">
        <div class="card-body p-2 p-md-3">
            <form method="GET" action="{{ route('reports.dispatch') }}" id="searchForm" class="tb-report-customer-select2-form" onsubmit="validateForm(event)">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label small mb-1">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label small mb-1">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-12 col-md-4 col-lg-2">
                        <label class="form-label small mb-1">Customer</label>
                        <select name="customer_id" class="form-select form-select-sm select2">
                            <option value="">All Customers</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ (string) ($customerId ?? '') === (string) $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-2">
                        <label class="form-label small mb-1">Transporter</label>
                        <select name="transporter_id" class="form-select form-select-sm select2">
                            <option value="">All Transporters</option>
                            @foreach ($transporters as $transporter)
                                <option value="{{ $transporter->id }}" {{ (string) ($transporterId ?? '') === (string) $transporter->id ? 'selected' : '' }}>
                                    {{ $transporter->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label small mb-1 text-nowrap">Balance As On Date</label>
                        <input type="date" name="balance_as_on_date" class="form-control form-control-sm" value="{{ $balanceAsOnDate ?? '' }}">
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 d-flex align-items-end justify-content-end gap-1 gap-sm-2">
                        <button type="submit" class="btn btn-brand-primary btn-sm" title="Search">
                            <i class="bi bi-search"></i> <span class="d-none d-sm-inline ms-1">Search</span>
                        </button>
                        <a href="{{ route('reports.dispatch') }}?date={{ date('Y-m-d') }}" class="btn btn-secondary btn-sm" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline ms-1">Reset</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$generated)
        <div class="alert alert-warning py-2">
            Please generate the report by selecting Dates.
        </div>
    @elseif (empty($lrs) || $lrs->isEmpty())
        <div class="alert alert-info py-2">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-3">
            <div class="card-header py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Dispatch Report</h3>
                    <div>
                        <a class="btn btn-warning btn-sm" title="Export"
                            href="{{ route('reports.dispatch-export', array_filter([
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'balance_as_on_date' => $balanceAsOnDate ?? null,
                                'customer_id' => $customerId ?? null,
                                'transporter_id' => $transporterId ?? null,
                            ])) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline ms-1">Export</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    @include('reports.exports.dispatch', [
                        'balanceAsOnDate' => $balanceAsOnDate ?? null,
                        'endDate' => $endDate,
                    ])
                </div>
            </div>
            <div class="card-body py-2 px-3 d-flex justify-content-end">
                {{ $lrs->links() }}
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').each(function() {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    placeholder: $(this).data('placeholder') || 'Select'
                });
            });
        });

        function validateForm(event) {
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();

            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be greater than end date');
                event.preventDefault();
                return false;
            }

            return true;
        }
    </script>
@endpush
