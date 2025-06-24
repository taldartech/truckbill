@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Consignees</h2>
    <a href="{{ route('consignees.create') }}" class="btn btn-primary mb-3">Add Consignee</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>City</th>
                <th>State</th>
                <th>Country</th>
                <th>Contact Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($consignees as $consignee)
            <tr>
                <td>{{ $consignee->id }}</td>
                <td>{{ $consignee->name }}</td>
                <td>{{ $consignee->city }}</td>
                <td>{{ $consignee->state }}</td>
                <td>{{ $consignee->country }}</td>
                <td>{{ $consignee->contact_number }}</td>
                <td>
                    
                        <a href="{{ route('consignees.edit', $consignee->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('consignees.destroy', $consignee->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center">No consignees found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $consignees->links() }}
    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
@endsection
