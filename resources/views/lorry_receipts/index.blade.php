@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Lorry Receipts</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="GET" action="{{ route('lorry_receipts.index') }}" class="row g-3 mb-3">
        <div class="col-md-2">
            <input type="text" name="gr_number" class="form-control" placeholder="GR Number" value="{{ request('gr_number') }}">
        </div>
        <!-- <div class="col-md-2">
            <select name="truck_id" class="form-select">
                <option value="">All Trucks</option>
                {{-- @foreach($trucks as $truck) --}}
                    {{-- <option value="{{ $truck->id }}" {{ request('truck_id') == $truck->id ? 'selected' : '' }}>{{ $truck->truck_number }}</option> --}}
                {{-- @endforeach --}}
            </select>
        </div>
        <div class="col-md-2">
            <select name="transporter_id" class="form-select">
                <option value="">All Transporters</option>
                {{-- @foreach($transporters as $transporter) --}}
                    {{-- <option value="{{ $transporter->id }}" {{ request('transporter_id') == $transporter->id ? 'selected' : '' }}>{{ $transporter->name }}</option> --}}
                {{-- @endforeach --}}
            </select>
        </div> -->
        <div class="col-md-2">
            <select name="delivery_status" class="form-select">
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('delivery_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="gr_date_from" class="form-control" value="{{ request('gr_date_from') }}">
        </div>
        <div class="col-md-2">
            <input type="date" name="gr_date_to" class="form-control" value="{{ request('gr_date_to') }}">
        </div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-secondary ms-2">Reset</a>
            <a href="{{ route('lorry_receipts.create') }}" class="btn btn-primary ms-2">Add New LR</a>
        </div>
    </form>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>GR Number</th>
                <th>Consignor</th>
                <th>Consignee</th>
                <th>Status</th>
                <th>GR Date</th>
                <th>Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lrs as $lr)
                <tr>
                    <td>{{ $lr->id }}</td>
                    <td>{{ $lr->gr_number }}</td>
                    <td>{{ $lr->consignor?->name ?? 'N/A' }}</td>
                    <td>{{ $lr->consignee?->name ?? 'N/A' }}</td>
                    <td>{{ $lr->delivery_status ?? 'Pending' }}</td>
                    <td>{{ $lr->gr_date }}</td>
                    <td>{{ $lr->amount }}</td>
                    <td>
                        <a href="{{ route('lorry_receipts.edit', $lr->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('lorry_receipts.destroy', $lr->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No Lorry Receipts found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
@endsection
