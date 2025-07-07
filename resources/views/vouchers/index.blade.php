@extends('layouts.app')
@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Voucher Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Always visible container --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('vouchers.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    {{-- Hidden filter form --}}
    <form method="GET" action="{{ route('vouchers.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        {{-- Filter checkboxes --}}
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="freight_booking_id" id="filter_freight_booking" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_freight_booking">Freight Booking</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="truck_id" id="filter_truck" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_truck">Truck</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="transporter_id" id="filter_transporter" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_transporter">Transporter</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="pan_verified" id="filter_pan" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_pan">PAN Verified</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="status" id="filter_status" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_status">Status</label>
                </div>
            </div>
        </div>

        {{-- Conditionally visible filter fields --}}
        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-3 d-none" id="field_freight_booking_id">
                <label class="form-label">Freight Booking</label>
                <select name="freight_booking_id" class="form-select">
                    <option value="">All</option>
                    @foreach($freightBookings as $fb)
                        <option value="{{ $fb->id }}" {{ request('freight_booking_id') == $fb->id ? 'selected' : '' }}>
                            LR #{{ $fb->lr_id }} - Rate: {{ number_format($fb->tr_fr_rate, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_truck_id">
                <label class="form-label">Truck</label>
                <select name="truck_id" class="form-select">
                    <option value="">All Trucks</option>
                    @foreach($trucks as $truck)
                        <option value="{{ $truck->id }}" {{ request('truck_id') == $truck->id ? 'selected' : '' }}>
                            {{ $truck->registration_number }} - {{ $truck->truck_type }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_transporter_id">
                <label class="form-label">Transporter</label>
                <select name="transporter_id" class="form-select">
                    <option value="">All Transporters</option>
                    @foreach($transporters as $transporter)
                        <option value="{{ $transporter->id }}" {{ request('transporter_id') == $transporter->id ? 'selected' : '' }}>
                            {{ $transporter->name }} - GST: {{ $transporter->gst_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_pan_verified">
                <label class="form-label">PAN Verified</label>
                <select name="pan_verified" class="form-select">
                    <option value="">All</option>
                    <option value="1" {{ request('pan_verified') === '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ request('pan_verified') === '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_status">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in-progress" {{ request('status') == 'in-progress' ? 'selected' : '' }}>In-Progress</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
        </div>

        {{-- Form action buttons --}}
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Vouchers List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Freight Booking</th>
                        <th>Truck</th>
                        <th>Transporter</th>
                        <th>PAN Verified</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                        <tr class="align-middle">
                            <td>{{ $voucher->id }}</td>
                            <td>
                                @if($voucher->freightBooking)
                                    LR #{{ $voucher->freightBooking->lr_id }} - 
                                    Rate: {{ number_format($voucher->freightBooking->tr_fr_rate, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($voucher->truck)
                                    {{ $voucher->truck->registration_number }} - 
                                    {{ $voucher->truck->truck_type }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($voucher->transporter)
                                    {{ $voucher->transporter->name }} - 
                                    GST: {{ $transporter->gst_number }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $voucher->pan_verified ? 'Yes' : 'No' }}</td>
                            <td>{{ ucfirst($voucher->status) }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('vouchers.edit', $voucher->id) }}" class="btn btn-sm btn-info">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('vouchers.destroy', $voucher->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this voucher?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="empty-state">
                                    <i class="bi bi-box"></i>
                                    <p class="mt-2">No vouchers found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $vouchers->links() }}
        </div>
    </div>
</div>

<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        const field = document.getElementById(fieldId);
        if (checkbox.checked) {
            field.classList.remove('d-none');
        } else {
            field.classList.add('d-none');
            const input = field.querySelector('input, select');
            if (input) input.value = '';
        }
    }

    // Initialize filter fields based on URL parameters
    document.addEventListener('DOMContentLoaded', function() {
        // Get all filter checkboxes
        const checkboxes = document.querySelectorAll('.form-check-input');
        
        // Check each checkbox based on URL parameters
        checkboxes.forEach(checkbox => {
            const value = checkbox.value;
            const fieldId = 'field_' + value;
            const field = document.getElementById(fieldId);
            const urlValue = new URLSearchParams(window.location.search).get(value);
            
            if (urlValue) {
                checkbox.checked = true;
                field.classList.remove('d-none');
            }
        });
    });
</script>
@endsection
