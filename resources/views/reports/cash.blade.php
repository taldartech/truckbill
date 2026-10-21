@extends('layouts.app')

@section('title', 'Cash Payment Report')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger py-2">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-3">
        <div class="card-body p-2 p-md-3">
            <form method="GET" action="{{ route('reports.cash') }}" id="searchForm" onsubmit="validateForm(event)">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Payment Status</label>
                        <select name="payment_status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in-progress" {{ request('payment_status') == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Branch</label>
                        <select name="branch" class="form-select form-select-sm">
                            <option value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ request('branch') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex align-items-end justify-content-end gap-1 gap-sm-2">
                        <button type="submit" class="btn btn-brand-primary btn-sm btn-report-search" title="Search">
                            <i class="bi bi-search"></i> <span class="d-none d-sm-inline ms-1">Search</span>
                        </button>
                        <a href="{{ route('reports.cash') }}" class="btn btn-secondary btn-sm btn-report-reset" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline ms-1">Reset</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!request()->has('start_date') || !request()->has('end_date'))
        <div class="alert alert-warning py-2">
            Please select date range to generate the report.
        </div>
    @elseif((empty($allPayments)) || ($allPayments->isEmpty()))
        <div class="alert alert-info py-2">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-3">
            <div class="card-header py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Cash Payment Report</h3>
                    <div>
                        <a class="btn btn-warning btn-sm" title="Export"
                            href="{{ route('reports.cash-export', [
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'payment_status' => request('payment_status'),
                                'branch' => request('branch'),
                            ]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline ms-1">Export</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap small">
                    <thead>
                        <tr>
                            <th>Sr.no</th>
                            <th>Date</th>
                            <th>LR Number</th>
                            <th>Truck Number</th>
                            <th>Transporter/Truck Owner Name</th>
                            <th>Short Name</th>
                            <th>Beneficiary ID</th>
                            <th>Amount</th>
                            <th>Narration</th>
                            <th>TDS Deducted</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $count = 1;
                        @endphp
                        @foreach ($allPayments as $payment)
                            <tr>
                                <td>{{ $count++ }}</td>
                                <td>{{ \Carbon\Carbon::parse($payment['date'])->format('d-m-Y') }}</td>
                                <td>{{ $payment['lr_number'] ?? 'N/A' }}</td>
                                <td>{{ $payment['truck_number'] ?? 'N/A' }}</td>
                                <td>{{ $payment['transporter_name'] ?? 'N/A' }}</td>
                                <td>{{ $payment['name'] ?? 'N/A' }}</td>
                                <td>{{ $payment['beneficiary_id'] ?? 'N/A' }}</td>
                                <td class="text-end">{{ number_format($payment['amount'], 2) }}</td>
                                <td>GR NO {{ $payment['gr_number'] ?? 'N/A' }}</td>
                                <td>{{ $payment['tds_deducted'] ?? 'N/A' }}</td>
                                <td>
                                    @if ($payment['payment_status'] === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($payment['payment_status'] === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($payment['payment_status'] === 'in-progress')
                                        <span class="badge bg-info">In Progress</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($payment['payment_status'] ?? 'N/A') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-end">Grand Total:</th>
                            <th class="text-end">{{ number_format($grandTotalAmount ?? 0, 2) }}</th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                {{ $allPayments->links() }}
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        function validateForm(event) {
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();

            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be greater than end date');
                event.preventDefault();
                return false;
            }

            return true;
        }
    </script>
@endpush
