@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Freight Bookings</h2>
    <a href="{{ route('freight_bookings.create') }}" class="btn btn-primary mb-3">Add Freight Booking</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <label for="lr_id" class="form-label">LR</label>
            <select name="lr_id" id="lr_id" class="form-select">
                <option value="">All</option>
                @foreach($lrs as $lr)
                    <option value="{{ $lr->id }}" {{ request('lr_id') == $lr->id ? 'selected' : '' }}>{{ $lr->lr_number }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="is_delivered" class="form-label">Delivered?</label>
            <select name="is_delivered" id="is_delivered" class="form-select">
                <option value="">All</option>
                <option value="1" {{ request('is_delivered') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ request('is_delivered') === '0' ? 'selected' : '' }}>No</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="is_cancelled" class="form-label">Cancelled?</label>
            <select name="is_cancelled" id="is_cancelled" class="form-select">
                <option value="">All</option>
                <option value="1" {{ request('is_cancelled') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ request('is_cancelled') === '0' ? 'selected' : '' }}>No</option>
            </select>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </div>
    </form>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>LR No</th>
                <th>TR Rate</th>
                <th>TR Amount</th>
                <th>Advance</th>
                <th>Balance</th>
                <th>GR Date</th>
                <th>Delivered?</th>
                <th>Cancelled?</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($bookings as $booking)
            <tr>
                <td>{{ $booking->id }}</td>
                <td>{{ $booking->lr ? $booking->lr->lr_number : '-' }}</td>
                <td>{{ $booking->tr_fr_rate }}</td>
                <td>{{ $booking->tr_amount }}</td>
                <td>{{ $booking->advance_amount }}</td>
                <td>{{ $booking->balance_amount }}</td>
                <td>{{ $booking->lr ? $booking->lr->gr_date : '-' }}</td>
                <td>{{ $booking->is_delivered ? 'Yes' : 'No' }}</td>
                <td>{{ $booking->is_cancelled ? 'Yes' : 'No' }}</td>
                <td>
                    <a href="{{ route('freight_bookings.edit', $booking->id) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('freight_bookings.destroy', $booking->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this booking?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="9">No bookings found.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $bookings->links() }}
    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
@endsection
