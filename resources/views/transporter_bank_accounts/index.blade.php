@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Transporter Bank Accounts</h2>
    <a href="{{ route('transporter_bank_accounts.create') }}" class="btn btn-primary mb-3">Add Bank Account</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Transporter</th>
                        <th>Bank Name</th>
                        <th>Account Number</th>
                        <th>IFSC Code</th>
                        <th>Beneficiary Name</th>
                        <th>Bulk Payment ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td>{{ $account->id }}</td>
                            <td>{{ $account->transporter->name }}</td>
                            <td>{{ $account->bank_name }}</td>
                            <td>{{ $account->account_number }}</td>
                            <td>{{ $account->ifsc_code }}</td>
                            <td>{{ $account->beneficiary_name }}</td>
                            <td>{{ $account->bulk_payment_id }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('transporter_bank_accounts.edit', $account) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('transporter_bank_accounts.destroy', $account) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No bank accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $accounts->links() }}
    
    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
@endsection
