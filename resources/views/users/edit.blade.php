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
<div class="mb-3">
    <label for="branch_id" class="form-label">Branch *</label>
    @if($canEdit)
    <select class="form-select" id="branch_id" name="branch_id" required>
        <option value="">Select Branch</option>
        @foreach($branches as $branch)
            <option 
                value="{{ $branch->id }}" 
                data-company="{{ $branch->company_id }}"
                {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}
                style="display: none;"
            >
                {{ $branch->branch_name }}({{ $branch->head_office == 1 ? 'Head Office' : 'Branch' }})
            </option>
        @endforeach
    </select>
    @else
        <input type="hidden" name="branch_id" value="{{ $user->branch_id }}">
        <div class="form-control-plaintext">{{ $user->branch->branch_name ?? '' }}</div>
    @endif
    @error('branch_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>
        <button type="submit" class="btn btn-success">Update User</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection


     <script>
        function loadBranches(companyId) {
            const branchSelect = document.getElementById('branch_id');
            branchSelect.innerHTML = '<option value="">Loading branches...</option>';
            
            if (!companyId) {
                branchSelect.innerHTML = '<option value="">Select Branch</option>';
                return;
            }
        
            fetch(`/branches/by-company/${companyId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(branches => {
                let options = '<option value="">Select Branch</option>';
                branches.forEach(branch => {
                    options += `<option value="${branch.id}">${branch.branch_name}</option>`;
                });
                branchSelect.innerHTML = options;
                
                // Set the selected branch if there's an old input
                const oldBranchId = "{{ old('branch_id') }}";
                if (oldBranchId) {
                    branchSelect.value = oldBranchId;
                }
            })
            .catch(error => {
                console.error('Error loading branches:', error);
                branchSelect.innerHTML = '<option value="">Error loading branches</option>';
            });
        }
        
        
        // Load branches when the page loads if a company is already selected
        document.addEventListener('DOMContentLoaded', function() {
    const companySelect = document.getElementById('company_id');
    const branchSelect = document.getElementById('branch_id');
    
    function filterBranches(companyId) {
        const options = branchSelect.querySelectorAll('option');
        let hasVisibleOptions = false;
        
        options.forEach(option => {
            if (option.value === '') return; // Skip the "Select Branch" option
            
            const branchCompanyId = option.getAttribute('data-company');
           
            if (companyId && branchCompanyId === companyId) {
                option.style.display = 'block';
                hasVisibleOptions = true;
            } else {
                option.style.display = 'none';
                if (option.selected) {
                    option.selected = false;
                }
            }
        });
        
        // Show a message if no branches are available for the selected company
        const noBranchOption = document.getElementById('no-branch-option');
        if (!hasVisibleOptions) {
            if (!noBranchOption) {
                const newOption = document.createElement('option');
                newOption.id = 'no-branch-option';
                newOption.value = '';
                newOption.textContent = 'No branches available';
                branchSelect.appendChild(newOption);
            }
        } else if (noBranchOption) {
            noBranchOption.remove();
        }
        
        // Reset to "Select Branch" when company changes
        branchSelect.value = '';
    }

    // Add event listener for company change
    companySelect.addEventListener('change', function() {
        filterBranches(this.value);
    });
    
    // Initialize with the selected company if any
   
    if (companySelect.value) {
        filterBranches(companySelect.value);
        branchSelect.value = "{{ old('branch_id', $user->branch_id) }}";
    } else if ("{{ old('company_id') }}") {
        filterBranches("{{ old('company_id') }}");
    }
});
        
        </script>
