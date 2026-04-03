@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Transporter Bank Account Setup</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Company Selection -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Select Company</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('transporter_bank_accounts.index') }}">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Company <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select Company --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($selectedCompanyId)
    
    <!-- Search Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Search Transporters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('transporter_bank_accounts.index') }}">
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                <div class="row">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by name, GST number, PAN number, or beneficiary ID..." value="{{ $search }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        @if($search)
                            <a href="{{ route('transporter_bank_accounts.index', ['company_id' => $selectedCompanyId]) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear Search
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Transporters List</h3>
            @if($search)
                <small class="text-muted">Search results for: "{{ $search }}"</small>
            @endif
        </div>
        <div class="card-body" style="overflow-x: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Name</th>
                        <th>Short Name</th>
                        <th>GST Number</th>
                        <th>PAN Number</th>
                        <th>Bank Beneficiary ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transporters as $transporter)
                    <tr class="align-middle">
                        <td>{{ $transporter->id }}</td>
                        <td>{{ $transporter->name }}</td>
                        <td>{{ $transporter->short_name ?? '-' }}</td>
                        <td>{{ $transporter->gst_number ?? '-' }}</td>
                        <td>{{ $transporter->pan_number ?? '-' }}</td>
                        <td>
                            @if($transporter->beneficiary_id)
                                <span class="badge bg-success">{{ $transporter->beneficiary_id }}</span>
                            @else
                                <span class="badge bg-warning">Not Set</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#beneficiaryModal{{ $transporter->id }}">
                                <i class="bi bi-bank"></i> {{ $transporter->beneficiary_id ? 'Update' : 'Add' }} Beneficiary ID
                            </button>
                        </td>
                    </tr>

                    <!-- Beneficiary ID Modal -->
                    <div class="modal fade" id="beneficiaryModal{{ $transporter->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ $transporter->beneficiary_id ? 'Update' : 'Add' }} Beneficiary ID</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('transporter_bank_accounts.updateBeneficiaryId', $transporter->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Transporter</label>
                                            <input type="text" class="form-control" value="{{ $transporter->name }}" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Short Name <span class="text-danger">*</span></label>
                                            <input type="text" name="short_name" class="form-control {{ $errors->has('short_name') ? 'is-invalid' : '' }}" value="{{ old('short_name', $transporter->short_name) }}" required>
                                            @error('short_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Beneficiary ID <span class="text-danger">*</span></label>
                                            <input type="text" name="beneficiary_id" class="form-control {{ $errors->has('beneficiary_id') ? 'is-invalid' : '' }}" value="{{ old('beneficiary_id', $transporter->beneficiary_id) }}" required>
                                            @error('beneficiary_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">{{ $transporter->beneficiary_id ? 'Update' : 'Save' }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="empty-state">
                                <i class="bi bi-truck"></i>
                                <p class="mt-2">No transporters found for this company.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            
            <!-- Pagination -->
            @if($transporters instanceof \Illuminate\Pagination\LengthAwarePaginator && $transporters->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $transporters->links() }}
                </div>
            @endif
        </div>
    </div>
    @else
    <div class="card mb-4">
        <div class="card-body text-center">
            <div class="empty-state">
                <i class="bi bi-building fa-3x text-muted mb-3"></i>
                <h5>Select a Company</h5>
                <p class="text-muted">Please select a company from the dropdown above to view transporters and manage their beneficiary IDs.</p>
            </div>
        </div>
    </div>
    @endif
</div>

@if($errors->has('beneficiary_id') || $errors->has('short_name'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let message = '';
        @if($errors->has('beneficiary_id'))
            message = '{{ $errors->first('beneficiary_id') }}';
        @elseif($errors->has('short_name'))
            message = '{{ $errors->first('short_name') }}';
        @endif
        if (message) {
            alert(message);
        }
    });
</script>
@endif

@endsection
