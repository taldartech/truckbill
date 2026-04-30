<div class="card glass-card mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-12">
                <label class="form-label mb-1">Customer Accounts (Global View)</label>
                <div class="text-muted">
                    {{ $consigneeOptions->map(fn ($consigneeOption) => $consigneeOption->name.($consigneeOption->company ? ' - '.$consigneeOption->company->name : ''))->implode(', ') }}
                </div>
            </div>
        </div>
    </div>
</div>
