@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Vouchers</h2>
    <a href="{{ route('vouchers.create') }}" class="btn btn-primary mb-3">Add Voucher</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">All</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
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
            <tr>
                <td>{{ $voucher->id }}</td>
                <td>{{ $voucher->freightBooking ? $voucher->freightBooking->id : '-' }}</td>
                <td>{{ $voucher->truck ? $voucher->truck->truck_number : '-' }}</td>
                <td>{{ $voucher->transporter ? $voucher->transporter->name : '-' }}</td>
                <td>{{ $voucher->pan_verified ? 'Yes' : 'No' }}</td>
                <td>{{ ucfirst($voucher->status) }}</td>
                <td>
                    <a href="{{ route('vouchers.edit', $voucher->id) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('vouchers.destroy', $voucher->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this voucher?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">No vouchers found.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $vouchers->links() }}
</div>
@endsection
