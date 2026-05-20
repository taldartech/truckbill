@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-3 mb-4">
        <div class="row align-items-center mb-3">
            <div class="col">
                <h4 class="mb-0">
                    <i class="bi bi-pencil-square me-2"></i>Edit invoice
                </h4>
                <div class="text-muted small mt-1">
                    @if ($invoice->invoice_number)
                        {{ $invoice->invoice_number }}
                        @if ($invoice->manual_invoice_number)
                            <span class="text-muted">·</span> {{ $invoice->manual_invoice_number }}
                        @endif
                    @elseif ($invoice->manual_invoice_number)
                        {{ $invoice->manual_invoice_number }}
                    @else
                        Invoice #{{ $invoice->id }}
                    @endif
                </div>
            </div>
            <div class="col-auto d-flex flex-wrap gap-2">
                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-eye me-1"></i>View
                </a>
                <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to invoices
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card card-outline card-secondary shadow-sm mb-3">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Invoice summary
                            <span class="badge bg-{{ $invoice->payment_status === 'paid' ? 'success' : ($invoice->payment_status === 'partial' ? 'warning' : 'secondary') }} ms-2">
                                {{ ucfirst($invoice->payment_status) }}
                            </span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <tbody class="small">
                                    <tr>
                                        <th class="bg-light" style="width: 160px;">Company</th>
                                        <td>{{ $invoice->company?->name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Customer</th>
                                        <td>{{ $invoice->consignee?->name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Invoice type</th>
                                        <td>{{ ucfirst((string) $invoice->invoice_type) }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Total amount</th>
                                        <td class="fw-semibold">&#8377;{{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Memos</th>
                                        <td>
                                            @forelse ($invoice->memo_ids ?? [] as $mid)
                                                <span class="badge text-bg-light border me-1">#{{ $mid }}</span>
                                            @empty
                                                <span class="text-muted">—</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-primary shadow-sm mb-3">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0"><i class="bi bi-sliders me-2"></i>Editable fields</h5>
                    </div>
                    <form method="POST" action="{{ route('invoices.update', $invoice->id) }}" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="manual_invoice_number" class="form-label fw-semibold">
                                        Reference / manual invoice # <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="manual_invoice_number" name="manual_invoice_number"
                                        class="form-control @error('manual_invoice_number') is-invalid @enderror"
                                        value="{{ old('manual_invoice_number', $invoice->manual_invoice_number) }}"
                                        placeholder="Your reference / printed bill no."
                                        autocomplete="off"
                                        required>
                                    <div id="manual-invoice-number-feedback" class="small mt-1"></div>
                                    @error('manual_invoice_number')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="generated_date" class="form-label fw-semibold">
                                        Bill date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" id="generated_date" name="generated_date"
                                        class="form-control @error('generated_date') is-invalid @enderror @if ($invoice->payment_status !== 'pending') bg-light @endif"
                                        value="{{ old('generated_date', $invoice->generated_date ? \Illuminate\Support\Carbon::parse($invoice->generated_date)->format('Y-m-d') : '') }}"
                                        @if ($invoice->payment_status !== 'pending') readonly @endif
                                        required>
                                    @error('generated_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @if ($invoice->payment_status !== 'pending')
                                        <div class="form-text">
                                            <i class="bi bi-lock me-1"></i>Bill date is locked when payment is not pending.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex flex-wrap gap-2 justify-content-between align-items-center py-3">
                            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-brand-primary px-4">
                                <i class="bi bi-check2-circle me-1"></i>Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const manualInvoiceInput = document.getElementById('manual_invoice_number');
            const manualInvoiceFeedback = document.getElementById('manual-invoice-number-feedback');
            const checkUrl = @json(url('/api/check-manual-invoice-number-uniqueness'));
            const invoiceId = {{ $invoice->id }};
            let manualInvoiceTimeout;

            if (!manualInvoiceInput || !manualInvoiceFeedback) {
                return;
            }

            function checkManualInvoiceUniqueness(manualNumber, currentInvoiceId) {
                manualInvoiceFeedback.innerHTML =
                    '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Checking availability…</span>';
                manualInvoiceInput.classList.remove('is-valid', 'is-invalid');

                fetch(checkUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            manual_invoice_number: manualNumber,
                            invoice_id: currentInvoiceId,
                        }),
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.valid) {
                            manualInvoiceFeedback.innerHTML =
                                '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + data.message + '</span>';
                            manualInvoiceInput.classList.remove('is-invalid');
                            manualInvoiceInput.classList.add('is-valid');
                            manualInvoiceInput.setCustomValidity('');
                        } else {
                            manualInvoiceFeedback.innerHTML =
                                '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>' + data.message + '</span>';
                            manualInvoiceInput.classList.remove('is-valid');
                            manualInvoiceInput.classList.add('is-invalid');
                            manualInvoiceInput.setCustomValidity(data.message);
                        }
                    })
                    .catch(function() {
                        manualInvoiceFeedback.innerHTML =
                            '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Unable to check availability.</span>';
                        manualInvoiceInput.classList.remove('is-valid', 'is-invalid');
                        manualInvoiceInput.setCustomValidity('');
                    });
            }

            manualInvoiceInput.addEventListener('input', function() {
                const manualNumber = this.value.trim();
                clearTimeout(manualInvoiceTimeout);
                manualInvoiceTimeout = setTimeout(function() {
                    checkManualInvoiceUniqueness(manualNumber, invoiceId);
                }, 500);
            });

            manualInvoiceInput.addEventListener('blur', function() {
                const manualNumber = this.value.trim();
                if (manualNumber) {
                    clearTimeout(manualInvoiceTimeout);
                    checkManualInvoiceUniqueness(manualNumber, invoiceId);
                }
            });
        });
    </script>
@endsection
