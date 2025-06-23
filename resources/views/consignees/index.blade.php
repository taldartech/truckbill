@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Consignees</h2>
            
        </div>
        <a href="{{ route('consignees.create') }}" class="btn btn-primary">Add Consignee</a>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
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
                        @foreach($consignees as $consignee)
                            <tr>
                                <td>{{ $consignee->id }}</td>
                                <td>{{ $consignee->name }}</td>
                                <td>{{ $consignee->city }}</td>
                                <td>{{ $consignee->state }}</td>
                                <td>{{ $consignee->country }}</td>
                                <td>{{ $consignee->contact_number }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('consignees.edit', $consignee) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form action="{{ route('consignees.destroy', $consignee) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $consignees->links() }}
        </div>
    </div>
</div>
@endsection
