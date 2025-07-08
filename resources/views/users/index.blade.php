@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">User Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('users.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>

    <form method="GET" action="{{ route('users.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
        <div class="row g-2">
            <div class="col-md-12">
                <label class="form-label fw-bold">Choose Filter Fields:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="name" id="filter_name" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_name">Name</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="email" id="filter_email" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_email">Email</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="role" id="filter_role" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_role">Role</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="company_id" id="filter_company" onchange="toggleField(this)">
                    <label class="form-check-label" for="filter_company">Company</label>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-2" id="filter-fields">
            <div class="col-md-2 d-none" id="field_name">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ request('name') }}">
            </div>
            <div class="col-md-2 d-none" id="field_email">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ request('email') }}">
            </div>
            <div class="col-md-2 d-none" id="field_role">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="">All</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-none" id="field_company_id">
                <label class="form-label">Company</label>
                <select name="company_id" class="form-select">
                    <option value="">All</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Users List</h3></div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Company</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="align-middle">
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->userRole ? ucfirst($user->userRole->name) : '-' }}</td>
                            <td>{{ $user->company ? $user->company->name : '-' }}</td>
                            <td class="d-flex align-items-center">
                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-info me-2" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.toggle('d-none');
        }
    }
</script>
@endsection
