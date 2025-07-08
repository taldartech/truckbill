@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Lorry Receipts</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('lorry_receipts.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Add New LR
        </a>
    </div>

    <form method="GET" action="{{ route('lorry_receipts.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        {{-- Filter checkboxes --}}
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="lr_number" id="filter_lr_number" onchange="toggleField(this)" >
                    <label class="form-check-label" for="filter_lr_number">LR Number</label>
                </div>
               
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="delivery_status" id="filter_status" onchange="toggleField(this)" >
                    <label class="form-check-label" for="filter_status">Status</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="gr_date" id="filter_date" onchange="toggleField(this)" >
                    <label class="form-check-label" for="filter_date">GR Date</label>
                </div>
            </div>
        </div>

        {{-- Conditionally visible filter fields --}}
        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-3 d-none" id="field_lr_number">
                <label class="form-label">LR Number</label>
                <input type="text" name="lr_number" class="form-control" value="{{ request('lr_number') }}">
            </div>
           
            <div class="col-md-3 d-none" id="field_delivery_status">
                <label class="form-label">Status</label>
                <select name="delivery_status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('delivery_status') == $status ? 'selected' : '' }}>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-none" id="field_gr_date">
                <label class="form-label">GR Date Range</label>
                <div class="row g-2">
                    <div class="col">
                        <input type="date" name="gr_date_from" class="form-control" value="{{ request('gr_date_from') }}">
                    </div>
                    <div class="col">
                        <input type="date" name="gr_date_to" class="form-control" value="{{ request('gr_date_to') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Form action buttons --}}
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Lorry Receipts List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>LR Number</th>
                        <th>Consignor</th>
                        <th>Consignee</th>
                        <th>Status</th>
                        <th>Action</th>

                        <th>GR Date</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lrs as $lr)
                    <tr class="align-middle">
                        <td>{{ $lr->id }}</td>
                        <td>{{ $lr->lr_number }}</td>
                        <td>{{ $lr->consignor?->name ?? 'N/A' }}</td>
                        <td>{{ $lr->consignee?->name ?? 'N/A' }}</td>
                        {{-- <td>{{ $lr->delivery_status ?? 'Pending' }}</td> --}}
                        <td>
                            @php
                                $rawStatus = $lr->delivery_status ?? 'Pending';
                                $status = strtolower(trim($rawStatus)); // Normalize status: trim spaces + lowercase
                                $badgeClass = match($status) {
                                    'cancelled'    => 'badge text-bg-danger',
                                    'pending'      => 'badge text-bg-primary',
                                    'delivered'    => 'badge text-bg-success',
                                    'in-progress'  => 'badge text-bg-warning',
                                    default        => 'badge text-bg-secondary',
                                };
                            @endphp
                            <span class="{{ $badgeClass }}">{{ ucfirst($rawStatus) }}</span>
                        </td>
                        <td>
                            @php
                                $actionText = match(strtolower(trim($lr->delivery_status ?? 'pending'))) {
                                    'cancelled' => 'Create new Freight Booking',
                                    'pending' => 'Create Freight Booking',
                                    'delivered' => 'Show Freight Booking',
                                    'in-progress' => 'Show Freight Booking',
                                    default => 'Create Freight Booking'
                                };
                                $actionRoute = in_array(strtolower(trim($lr->delivery_status ?? 'pending')), ['delivered', 'in-progress']) ? 
                                    route('freight_bookings.index', ['lorry_receipt_id' => $lr->id]) : 
                                    route('freight_bookings.create', ['lorry_receipt_id' => $lr->id]);
                                    $buttonClass = match($status) {
                                        'delivered'   => 'btn-info',
                                        'in-progress' => 'btn-warning',
                                        default       => 'btn-success'
                                    };
                            @endphp
                            <div class="d-flex gap-1">
                                {{-- <a href="{{ $actionRoute }}" class="btn btn-sm @php
                                    $status = strtolower(trim($lr->delivery_status ?? 'pending'));
                                    echo match($status) {
                                        'delivered' => 'btn-info',
                                        'in-progress' => 'btn-warning',
                                        default => 'btn-success'
                                    };
                                @endphp">
                                    <i class="bi bi-plus"></i> {{ $actionText }}
                                </a> --}}
                                <a href="{{ $actionRoute }}" 
       class="btn btn-sm {{ $buttonClass }}"
       title="{{ $actionText }}"
       data-bs-toggle="tooltip" 
       data-bs-placement="top">
        <i class="bi bi-truck"></i> 
    </a>
                                @if(strtolower(trim($lr->delivery_status ?? 'pending')) === 'delivered')
                                <a href="{{ route('vouchers.index', ['lorry_receipt_id' => $lr->id]) }}" 
                                    class="btn btn-sm btn-success" 
                                    title="create Vouchers" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top">
                                     <i class="bi bi-receipt"></i> 
                                 </a>
                                 <a href="{{ route('invoices.index', ['lorry_receipt_id' => $lr->id]) }}" 
                                    class="btn btn-sm btn-secondary" 
                                    title="create Invoices" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top">
                                    <i class="bi bi-file-earmark-text"></i> 
                                 </a>
                                 
                                @endif
                            </div>
                            </a>
                        </td>
                        
                        <td>{{ $lr->gr_date }}</td>
                        <td>{{ $lr->amount }}</td>
                        
                        <td>
                            <div class="d-flex gap-1">
                                {{-- <a href="{{ route('lorry_receipts.edit', $lr->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-pencil"></i>
                                </a> --}}
                                <a href="{{ route('lorry_receipts.edit', $lr->id) }}" 
                                    class="btn btn-sm btn-info" 
                                    title="Edit Lorry Receipt" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top">
                                    <i class="bi bi-pencil"></i>
                                 </a>
                                <form action="{{ route('lorry_receipts.destroy', $lr->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this lorry receipt?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="empty-state">
                                <i class="bi bi-box"></i>
                                <p class="mt-2">No lorry receipts found.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $lrs->links() }}
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
