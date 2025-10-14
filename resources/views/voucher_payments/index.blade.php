@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Voucher Payments Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Always visible container --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        {{-- <a href="{{ route('voucher_payments.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a> --}}
    </div>

    {{-- Hidden filter form --}}
    <form method="GET" action="{{ route('voucher_payments.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        {{-- Filter checkboxes --}}
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="truck_no" id="filter_truck_no" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_truck_no">Truck No</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="transporter" id="filter_transporter" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_transporter">Transporter</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="source" id="filter_source" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_source">Source</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="destination" id="filter_destination" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_destination">Destination</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="lr_no" id="filter_lr_no" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_lr_no">Lorry Receipt No</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="freight_booking_no" id="filter_freight_booking_no" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_freight_booking_no">Freight Booking No</label>
                </div>
            </div>
        </div>

        {{-- Conditionally visible filter fields --}}
        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-3 d-none" id="field_truck_no">
                <label class="form-label">Truck No</label>
                <input type="text" name="truck_no" class="form-control" value="{{ request('truck_no') }}">
            </div>
            <div class="col-md-3 d-none" id="field_transporter">
                <label class="form-label">Transporter</label>
                <select name="transporter" class="form-select">
                    <option value="">All Transporters</option>
                    @foreach(\App\Models\Transporter::all() as $transporter)
                        <option value="{{ $transporter->id }}" {{ request('transporter') == $transporter->id ? 'selected' : '' }}>
                            {{ $transporter->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_source">
                <label class="form-label">Source</label>
                <select name="source" class="form-select">
                    <option value="">All Sources</option>
                    @foreach(\App\Models\City::orderBy('name')->get() as $city)
                        <option value="{{ $city->id }}" {{ request('source') == $city->id ? 'selected' : '' }}>
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_destination">
                <label class="form-label">Destination</label>
                <select name="destination" class="form-select">
                    <option value="">All Destinations</option>
                    @foreach(\App\Models\City::orderBy('name')->get() as $city)
                        <option value="{{ $city->id }}" {{ request('destination') == $city->id ? 'selected' : '' }}>
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_lr_no">
                <label class="form-label">Lorry Receipt No</label>
                <input type="text" name="lr_no" class="form-control" value="{{ request('lr_no') }}">
            </div>
            <div class="col-md-3 d-none" id="field_freight_booking_no">
                <label class="form-label">Freight Booking No</label>
                <input type="text" name="freight_booking_no" class="form-control" value="{{ request('freight_booking_no') }}">
            </div>
        </div>

        {{-- Form action buttons --}}
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('voucher_payments.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    {{-- Main content card --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('voucher_payments.daily') }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar-day"></i> Daily Report
        </a>
        <!-- <a href="{{ route('voucher_payments.export.excel') }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Export to Excel
        </a>
        <a href="{{ route('voucher_payments.export.pdf') }}" class="btn btn-danger">
            <i class="bi bi-file-pdf"></i> Export to PDF
        </a> -->
    </div>
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Voucher Payments List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                      
                        <th>Voucher #</th>
                        <th>Lorry Receipt No</th>
                        <th>Customer</th>
                        <th>Truck No</th>
                        <th>Transporter</th>
                        <th>Source</th>
                        <th>Destination</th>
                       
                       
                        @if(auth()->check() && auth()->user()->userRole->name === 'admin')
                        <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($voucherGroups as $group)
                        @php
                            $payment = $payments[$group->voucher_number] ?? null;
                            if (!$payment) continue;
                        @endphp
                        <tr class="align-middle">
                            
                            <td>
                                <a href="{{ route('voucher_payments.show', $payment->voucher_number) }}" class="fw-bold text-decoration-none">
                                    {{ $payment->voucher_number }}
                                    <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                </a>
                            </td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->lr)
                                    <span class="badge text-dark">{{ $payment->freight_booking->lr->lr_number }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->customer)
                                    <span class="badge text-dark">{{ $payment->freight_booking->lr->customer->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->truck)
                                    <span class="badge text-dark">{{ $payment->freight_booking->lr->truck->truck_number }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->transporter)
                                    @if($payment->freight_booking->transporter->is_owner)
                                        <span class="badge bg-light text-dark"><i class="bi bi-truck"></i> {{ $payment->freight_booking->transporter->name }}</span>
                                    @else
                                        <span class="badge bg-primary"><i class="bi bi-building"></i> {{ $payment->freight_booking->transporter->name }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->consignor && $payment->freight_booking->lr->consignor->city)
                                    <span class="badge text-dark">{{ $payment->freight_booking->lr->consignor->city->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->freight_booking && $payment->freight_booking->lr && $payment->freight_booking->lr->consignee && $payment->freight_booking->lr->consignee->city)
                                    <span class="badge text-dark">{{ $payment->freight_booking->lr->consignee->city->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            
                            
                            @if(auth()->check() && auth()->user()->userRole->name === 'admin')
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('voucher_payments.show', $payment->voucher_number) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">
                                <div class="empty-state">
                                    <i class="bi bi-box"></i>
                                    <p class="mt-2">No payments found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $voucherGroups->links() }}
        </div>
    </div>
</div>

<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        const field = document.getElementById(fieldId);
        const input = field.querySelector('input, select');

        if (checkbox.checked) {
            field.classList.remove('d-none');
            if (input) input.disabled = false;
        } else {
            field.classList.add('d-none');
            if (input) input.disabled = true;
        }
    }

    // Initialize filter fields based on URL parameters
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Check each filter checkbox if its parameter exists in URL
        document.querySelectorAll('#advanced-search-box input[type="checkbox"]').forEach(checkbox => {
            const paramName = checkbox.value;
            if (urlParams.has(paramName) && urlParams.get(paramName)) {
                checkbox.checked = true;
                toggleField(checkbox);
            }
        });

        // Show the filter box if any filter is applied
        if (Array.from(urlParams.keys()).some(key => [
            'voucher_number', 'payment_type', 'payment_mode', 'transaction_id', 'status'
        ].includes(key))) {
            document.getElementById('advanced-search-box').classList.remove('d-none');
        }
    });
</script>
@endsection
