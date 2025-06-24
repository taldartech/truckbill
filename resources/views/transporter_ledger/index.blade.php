@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Transporter Ledger</h2>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('transporter_ledger.create') }}" class="btn btn-primary">Add Ledger Entry</a>
        <div class="d-flex gap-2">
            <select id="transporterFilter" class="form-select">
                <option value="">All Transporters</option>
                @foreach($transporters as $transporter)
                    <option value="{{ $transporter->id }}">{{ $transporter->name }}</option>
                @endforeach
            </select>
            <div class="balance-display">
                <span class="badge bg-info">Balance: <span id="balanceAmount">0.00</span></span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Transporter</th>
                        <th>Amount</th>
                        <th>Transaction Type</th>
                        <th>Remark</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $ledger)
                        <tr>
                            <td>{{ $ledger->id }}</td>
                            <td>{{ $ledger->transporter->name }}</td>
                            <td class="{{ $ledger->transaction_type === 'debit' ? 'text-success' : 'text-danger' }}">
                                {{ $ledger->transaction_type === 'debit' ? '+' : '-' }}{{ $ledger->formatted_amount }}
                            </td>
                            <td>{{ $ledger->transaction_type }}</td>
                            <td>{{ $ledger->remark }}</td>
                            <td>{{ $ledger->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('transporter_ledger.edit', $ledger) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('transporter_ledger.destroy', $ledger) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No ledger entries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $ledgers->links() }}
        </div>
    </div>
    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const transporterFilter = document.getElementById('transporterFilter');
    const balanceAmount = document.getElementById('balanceAmount');
    const tableBody = document.querySelector('tbody');

    function updateBalance(transporterId) {
        fetch(`/transporter_ledger/balance/${transporterId}`)
            .then(response => response.json())
            .then(data => {
                balanceAmount.textContent = data.formatted_balance;
            });
    }

    transporterFilter.addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('transporter_id', this.value);
        window.location.href = url.toString();
    });

    // Initial balance update
    updateBalance(transporterFilter.value);
});
</script>
@endpush
@endsection
