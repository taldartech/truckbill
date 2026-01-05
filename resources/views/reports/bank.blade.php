@extends('layouts.app')

@section('title', 'Bank Payment Report')

@push('styles')
    <style>
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
            <form method="GET" action="{{ route('reports.bank') }}" id="searchForm" onsubmit="validateForm(event)">
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
                </div>

                <div class="row">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.bank') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!request()->has('start_date') || !request()->has('end_date'))
        <div class="alert alert-warning">
            Please select date range to generate the report.
        </div>
    @elseif(empty($allPayments) || $allPayments->isEmpty())
        <div class="alert alert-info">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Bank Payment Report</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.bank-export', [
                                'start_date' => $startDate,
                                'end_date' => $endDate,
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
                            $grandTotal = 0;
                            $count = 1;
                        @endphp
                        @foreach ($allPayments as $index => $payment)
                            @php $grandTotal += $payment['amount']; @endphp
                            <tr>
                                <td>{{ $count++ }}</td>
                                <td>{{ \Carbon\Carbon::parse($payment['date'])->format('d-m-Y') }}</td>
                                <td>{{ $payment['lr_number'] ?? 'N/A' }}</td>
                                <td>{{ $payment['truck_number'] ?? 'N/A' }}</td>
                                <td>{{ $payment['transporter_name'] ?? 'N/A' }}</td>
                                <td>{{ $payment['name'] ?? 'N/A' }} </td>
                                <td>{{ $payment['beneficiary_id'] ?? 'N/A' }}</td>
                                <td class="text-end">{{ number_format($payment['amount'], 2) }}</td>
                                <td> 'GR NO ' .{{ $payment['gr_number'] ?? 'N/A' }}</td>
                                <td>{{ $payment['tds_deducted'] ?? 'N/A' }}</td>
                                <td>
                                    @if ($payment['payment_status'] === 'paid')
                                        <span class="badge bg-brand-primary">Paid</span>
                                    @elseif($payment['payment_status'] === 'pending')
                                        <span class="badge bg-brand-warning">Pending</span>
                                    @elseif($payment['payment_status'] === 'in-progress')
                                        <span class="badge bg-brand-secondary">In Progress</span>
                                    @else
                                        <span
                                            class="badge bg-secondary">{{ ucfirst($payment['payment_status'] ?? 'N/A') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-end">Grand Total:</th>
                            <th class="text-end">{{ number_format($grandTotal, 2) }}</th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
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

        // Set default dates if not set
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

            const startDate = document.querySelector('input[name="start_date"]');
            const endDate = document.querySelector('input[name="end_date"]');

            if (startDate && !startDate.value) {
                startDate.valueAsDate = firstDay;
            }
            if (endDate && !endDate.value) {
                endDate.valueAsDate = today;
            }
        });
    </script>
@endpush
