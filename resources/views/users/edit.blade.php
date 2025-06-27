@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit User</h2>
    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Name *</label>
            <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $user->name) }}">
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email *</label>
            <input type="email" class="form-control" id="email" name="email" required value="{{ old('email', $user->email) }}">
            @error('email') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password">
            <small class="form-text text-muted">Leave blank to keep current password.</small>
            @error('password') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
        </div>
        @php
    $canEdit = auth()->check() && (auth()->user()->userRole->name === 'super_admin' || auth()->user()->userRole->name === 'admin');
@endphp
<div class="mb-3">
    <label for="role" class="form-label">Role *</label>
    @if($canEdit)
        <select class="form-select" id="role" name="role" required>
            <option value="">Select</option>
            @foreach($roles as $role)
                <option value="{{ $role }}" {{ old('role', $user->userRole->name ?? '') == $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
            @endforeach
        </select>
    @else
        <input type="hidden" name="role" value="{{ $user->userRole->name ?? '' }}">
        <div class="form-control-plaintext">{{ ucfirst($user->userRole->name ?? '') }}</div>
    @endif
    @error('role') <div class="text-danger">{{ $message }}</div> @enderror
</div>
        <div class="mb-3">
    <label for="company_id" class="form-label">Company *</label>
    @if($canEdit)
        <select class="form-select" id="company_id" name="company_id" required>
            <option value="">Select</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
            @endforeach
        </select>
    @else
        <input type="hidden" name="company_id" value="{{ $user->company_id }}">
        <div class="form-control-plaintext">{{ $user->company->name ?? '' }}</div>
    @endif
    @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>
        <button type="submit" class="btn btn-success">Update User</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
