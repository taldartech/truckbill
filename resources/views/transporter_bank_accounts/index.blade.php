@extends('layouts.app')

@section('content')
<div class="container mt-4 app-index-page app-index-page--standard">
    @if(session('success'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
            </span>
        </div>
    @endif

    <div class="card app-index-list-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Transporter Bank Account Setup</h3>
        </div>

        <div class="app-index-dt-toolbar">
            <form method="GET" action="{{ route('transporter_bank_accounts.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Company <span class="text-danger">*</span></label>
                    <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Select Company --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($selectedCompanyId)
                    <div class="col-md-5">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Name, GST, PAN, or beneficiary ID..." value="{{ $search }}">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-brand-primary btn-sm" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                        @if($search)
                            <a href="{{ route('transporter_bank_accounts.index', ['company_id' => $selectedCompanyId]) }}" class="btn btn-secondary btn-sm">
                                Clear
                            </a>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        <div class="card-body">
            @if($selectedCompanyId)
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 10px">ID</th>
                                <th>Name</th>
                                <th>Short Name</th>
                                <th>GST Number</th>
                                <th>PAN Number</th>
                                <th>Bank Beneficiary ID</th>
                                <th class="app-index-actions-cell">Actions</th>
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
                                <td class="app-index-actions-cell">
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#beneficiaryModal{{ $transporter->id }}">
                                        <i class="bi bi-bank"></i> {{ $transporter->beneficiary_id ? 'Update' : 'Add' }} Beneficiary ID
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade fb-pay-modal" id="beneficiaryModal{{ $transporter->id }}" tabindex="-1">
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
                            <tr class="app-index-empty-row">
                                <td colspan="7" class="text-center text-muted py-4">
                                    No transporters found for this company.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transporters instanceof \Illuminate\Pagination\LengthAwarePaginator && $transporters->hasPages())
                    <div class="app-index-partload-pagination d-flex justify-content-center">
                        {{ $transporters->links() }}
                    </div>
                @endif
            @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-building display-6 d-block mb-2"></i>
                    <h5>Select a Company</h5>
                    <p class="mb-0">Please select a company to view transporters and manage beneficiary IDs.</p>
                </div>
            @endif
        </div>
    </div>
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
