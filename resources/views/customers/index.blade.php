@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Customers</h2>
    <a href="{{ route('customers.create') }}" class="btn btn-primary mb-3">Add Customer</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>GST</th>
                <th>PAN</th>
                <th>Address</th>
                <th>Contact Person</th>
                <th>Contact Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
            <tr>
                <td>{{ $customer->id }}</td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->gst_number }}</td>
                <td>{{ $customer->pan_number }}</td>
                <td>{{ $customer->address }}</td>
                <td>{{ $customer->contact_person }}</td>
                <td>{{ $customer->contact_number }}</td>
                <td>
                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this customer?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center">No customers found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $customers->links() }}
</div>
@endsection
