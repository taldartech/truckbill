@extends('layouts.app')

@section('title', 'Chart of Accounts & Ledgers - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert" style="border-radius: 8px;">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Main Card -->
    <div class="card app-index-list-card shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-diagram-3 me-2"></i>Chart of Accounts & General Ledgers ({{ $company->name }})
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <button type="button" class="btn app-index-header-btn" data-bs-toggle="modal" data-bs-target="#newLedgerModal">
                    <i class="bi bi-plus-circle"></i> New Ledger
                </button>
                <a href="{{ route('accounting.export-tally-masters') }}" class="btn app-index-header-btn">
                    <i class="bi bi-file-earmark-code"></i> Export Masters XML
                </a>
            </div>
        </div>
        <div class="card-body p-3">
            <!-- Groups Accordion -->
            <div class="accordion" id="coaAccordion">
                @foreach($groups as $index => $group)
                <div class="accordion-item mb-2 border rounded overflow-hidden" style="border-color: var(--brand-border) !important;">
                    <h2 class="accordion-header" id="heading-{{ $group->id }}">
                        <button class="accordion-button fw-bold py-2.5 px-3 {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $group->id }}" style="background-color: var(--brand-table-header-bg); color: var(--brand-text-primary);">
                            <i class="bi bi-folder2-open me-2" style="color: var(--brand-warning);"></i>
                            <span class="fs-6 me-2">{{ $group->name }}</span>
                            <span class="badge border" style="background-color: var(--brand-soft-primary); color: var(--brand-primary); font-size: 0.75rem;">
                                {{ ucfirst($group->primary_type) }}
                            </span>
                            <span class="badge bg-white text-dark border ms-auto me-3">{{ $group->ledgers->count() }} Ledgers</span>
                        </button>
                    </h2>
                    <div id="collapse-{{ $group->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#coaAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table app-index-table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Ledger Name</th>
                                            <th>Code</th>
                                            <th>GSTIN</th>
                                            <th>PAN</th>
                                            <th class="text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($group->ledgers as $ledger)
                                        <tr>
                                            <td class="ps-4">
                                                <a href="{{ route('accounting.ledger-statement', $ledger->id) }}" class="fw-semibold text-decoration-none" style="color: var(--brand-text-primary);">
                                                    {{ $ledger->name }}
                                                </a>
                                                @if($ledger->is_builtin)
                                                    <span class="badge bg-light text-muted border ms-1 small">System</span>
                                                @endif
                                            </td>
                                            <td><code>{{ $ledger->code ?: '-' }}</code></td>
                                            <td>{{ $ledger->gstin ?: '-' }}</td>
                                            <td>{{ $ledger->pan_number ?: '-' }}</td>
                                            <td class="text-end pe-4">
                                                <a href="{{ route('accounting.ledger-statement', $ledger->id) }}" class="btn btn-sm btn-secondary py-1 px-2" title="View Statement">
                                                    <i class="bi bi-file-earmark-text me-1"></i>Statement
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted small">No ledgers under this primary group.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- New Ledger Modal -->
<div class="modal fade" id="newLedgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
            <form method="POST" action="{{ route('accounting.ledgers.store') }}">
                @csrf
                <div class="modal-header py-3" style="background: var(--brand-gradient); color: #fff;">
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Create New Ledger Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Parent Account Group *</label>
                        <select name="account_group_id" class="form-select form-select-sm" required>
                            <option value="">-- Choose Account Group --</option>
                            @foreach($allGroups as $ag)
                                <option value="{{ $ag->id }}">{{ $ag->name }} ({{ ucfirst($ag->primary_type) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Ledger Name *</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Office Rent A/c, HDFC Current A/c" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Shorthand Code</label>
                            <input type="text" name="code" class="form-control form-control-sm" placeholder="e.g. RENT">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Balance Type</label>
                            <select name="opening_balance_type" class="form-select form-select-sm" required>
                                <option value="debit">Debit (Dr)</option>
                                <option value="credit">Credit (Cr)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Opening Balance (₹)</label>
                        <input type="number" step="0.01" min="0" name="opening_balance" class="form-control form-control-sm" value="0.00">
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">GSTIN</label>
                            <input type="text" name="gstin" class="form-control form-control-sm" placeholder="Optional GSTIN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">PAN Number</label>
                            <input type="text" name="pan_number" class="form-control form-control-sm" placeholder="Optional PAN">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary btn-sm px-4">Save Ledger</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
