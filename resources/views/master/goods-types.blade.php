@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Goods Types</h2>
        <a href="{{ route('master.goods-types.create') }}" class="btn btn-primary">Add Goods Type</a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($goodsTypes as $goodsType)
                    <tr>
                        <td>{{ $goodsType->id }}</td>
                        <td>{{ $goodsType->name }}</td>
                        <td>
                            <a href="{{ route('master.goods-types.edit', $goodsType->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form action="{{ route('master.goods-types.destroy', $goodsType->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
