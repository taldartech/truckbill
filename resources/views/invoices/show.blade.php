@extends('layouts.app')

@section('content')
    <div class="fb-detail-page app-index-page">
        @if (! empty($companyLogoUrl))
            <div class="dashboard-page__hero mb-3" style="padding: 1rem 1.15rem;">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="bg-white border rounded p-2 shadow-sm" style="max-width: 200px;">
                        <img src="{{ $companyLogoUrl }}" alt="{{ $invoice->company->name }}" class="d-block mx-auto"
                            style="max-height: 64px; max-width: 180px; width: auto; height: auto; object-fit: contain;">
                    </div>
                    <div>
                        <div class="dashboard-page__live-badge mb-1">
                            <span class="dashboard-page__live-dot"></span>
                            <span class="dashboard-page__live-text">Invoice</span>
                        </div>
                        <h1 class="dashboard-page__title" style="font-size: 1.35rem;">Invoice Details</h1>
                        <p class="dashboard-page__subtitle mb-0">{{ $invoice->company->name }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header fb-detail-card-header">
                <strong class="fb-detail-card-title">
                    Invoice
                    <span class="text-muted fw-semibold">
                        #{{ $invoice->invoice_number
                            ? $invoice->invoice_number.($invoice->manual_invoice_number ? ' ('.$invoice->manual_invoice_number.')' : '')
                            : $invoice->manual_invoice_number }}
                    </span>
                </strong>
                <div class="fb-detail-actions ms-auto">
                    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-icon btn-action-back" title="Back to list">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    @if (auth()->user()->canAccessCompanyPanel() && $invoice->remaining_amount > 0)
                        <button type="button" class="btn btn-sm btn-icon btn-action-pay" data-bs-toggle="modal"
                            data-bs-target="#addPaymentModal" title="Add payment">
                            <i class="bi bi-wallet2"></i>
                        </button>
                    @endif
                    @if ($invoice->payment_status != 'paid' && Auth::user()->canActAsAdmin())
                        <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-sm btn-icon btn-action-edit"
                            title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if ($invoice->is_rcm)
                    <div class="alert alert-warning py-2.5 mb-3 d-flex align-items-center shadow-sm" style="border-radius: 8px;">
                        <i class="bi bi-shield-check fs-4 me-2 text-warning"></i>
                        <div>
                            <strong>GTA Reverse Charge Notice:</strong> Tax is payable on Reverse Charge basis: <strong>YES</strong> (Payable by consignee/recipient u/s 9(3) of CGST Act)
                        </div>
                    </div>
                @endif
                @if (empty($companyLogoUrl))
                    <h2 class="h5 mb-3">Invoice Details</h2>
                @endif
                <div class="row">
                    <div class="col-md-6">
                        <h4>Basic Information</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <tr>
                                    <th>Company:</th>
                                    <td>{{ $invoice->company->name }}</td>
                                </tr>
                                <tr>
                                    <th>Invoice #:</th>
                                    <td>{{ $invoice->invoice_number
                                        ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '')
                                        : $invoice->manual_invoice_number }}</td>
                                </tr>
                                <tr>
                                    <th>Invoice Type:</th>
                                    <td>
                                        {{ ucfirst($invoice->invoice_type) }}
                                        @if ($invoice->is_rcm)
                                            <span class="badge bg-warning text-dark ms-2 fw-semibold">
                                                <i class="bi bi-shield-check me-1"></i>Tax is payable on Reverse Charge basis: YES
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @if ($invoice->custom_invoice)
                                    <tr>
                                        <th>Custom invoice:</th>
                                        <td>
                                            <span class="badge bg-{{ $invoice->custom_invoice === 'rate_difference' ? 'primary' : 'warning' }}">
                                                {{ $invoice->custom_invoice === 'rate_difference' ? 'Rate difference' : 'Shortage & damage' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Generated Date:</th>
                                    <td>{{ $invoice->generated_date }}</td>
                                </tr>
                                <tr>
                                    <th>Customer:</th>
                                    <td>{{ $invoice->consignee ? $invoice->consignee->name : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Payment Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $invoice->payment_status === 'paid' ? 'brand-primary' : ($invoice->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($invoice->payment_status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Paid Amount:</th>
                                    <td class="text-brand-primary fw-bold">&#8377;{{ number_format($invoice->paid_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Remaining Amount:</th>
                                    <td class="fw-bold {{ $invoice->remaining_amount > 0 ? 'text-danger' : 'text-brand-primary' }}">
                                        &#8377;{{ number_format($invoice->remaining_amount, 2) }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        @if ($invoice->custom_invoice)
                            <h4>Custom invoice details</h4>
                            @forelse($customDetails ?? [] as $row)
                                @php $lr = $row->lorryReceipt; @endphp
                                <div class="table-responsive mb-3">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted small">LR No</td>
                                                <td>{{ $lr ? $lr->lr_number : '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted small">Truck</td>
                                                <td>{{ $lr && $lr->truck ? $lr->truck->truck_number : '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted small">Destination</td>
                                                <td>{{ $lr ? ($lr->destination_city ?? '—') : '—' }}</td>
                                            </tr>
                                            @if ($invoice->custom_invoice === 'rate_difference')
                                                <tr>
                                                    <td class="text-muted small">Portal rate</td>
                                                    <td>{{ $row->portal_rate !== null ? number_format($row->portal_rate, 2) : '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted small">Diff</td>
                                                    <td>{{ $row->diff !== null ? number_format($row->diff, 2) : '—' }}</td>
                                                </tr>
                                            @else
                                                <tr>
                                                    <td class="text-muted small">Damage</td>
                                                    <td>{{ $row->damage_qty !== null ? $row->damage_qty : '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted small">Damage rate</td>
                                                    <td>{{ $row->damage_rate !== null ? number_format($row->damage_rate, 2) : '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted small">Shortage</td>
                                                    <td>{{ $row->shortage_qty !== null ? $row->shortage_qty : '—' }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td class="text-muted small">Amount</td>
                                                <td class="fw-semibold">&#8377;{{ number_format($row->amount ?? 0, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted small">Remark</td>
                                                <td>{{ $row->remark ?? '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @empty
                                <p class="text-muted">No line items.</p>
                            @endforelse
                        @else
                            <h4>Associated Memos</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Memo #</th>
                                            <th>LR #</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Rate</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($memos as $memo)
                                        <tr>
                                            <td>{{ $memo->memo_id }}</td>
                                            <td>{{ $memo->lr_number }}</td>
                                            <td>{{ $memo->from_location }}</td>
                                            <td>{{ $memo->to_location }}</td>
                                            <td>&#8377;{{ number_format($memo->rate, 2) }}</td>
                                            <td>&#8377;{{ number_format($memo->amount, 2) }}</td>
                                        </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">No memos associated with this invoice
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h4>Tax Breakup</h4>
                        <div class="table-responsive">
                            <table class="table">
                                @php
                                    $sgst = $invoice->sgst ?? 0;
                                    $cgst = $invoice->cgst ?? 0;
                                    $igst = $invoice->igst ?? 0;
                                    $totalGst = $sgst + $cgst + $igst;
                                    $baseAmount = $invoice->base_amount ?? (($invoice->invoice_type ?? '') === 'Reverse'
                                        ? ($invoice->total_amount ?? 0)
                                        : (($invoice->net_amount ?? 0) - $totalGst));
                                    $netAmount = $invoice->net_amount ?? 0;

                                    $sgstRate = 0;
                                    $cgstRate = 0;
                                    $igstRate = 0;
                                    if ($baseAmount > 0) {
                                        if ($sgst > 0) {
                                            $sgstRate = ($sgst / $baseAmount) * 100;
                                        }
                                        if ($cgst > 0) {
                                            $cgstRate = ($cgst / $baseAmount) * 100;
                                        }
                                        if ($igst > 0) {
                                            $igstRate = ($igst / $baseAmount) * 100;
                                        }
                                    }
                                @endphp
                                <tr>
                                    <th>Base Amount:</th>
                                    <td>&#8377;{{ number_format($baseAmount, 2) }}</td>
                                </tr>
                                @if($sgst > 0)
                                <tr>
                                    <th>SGST ({{ number_format($sgstRate, 2) }}%):</th>
                                    <td>&#8377;{{ number_format($sgst, 2) }}</td>
                                </tr>
                                @endif
                                @if($cgst > 0)
                                <tr>
                                    <th>CGST ({{ number_format($cgstRate, 2) }}%):</th>
                                    <td>&#8377;{{ number_format($cgst, 2) }}</td>
                                </tr>
                                @endif
                                @if($igst > 0)
                                <tr>
                                    <th>IGST ({{ number_format($igstRate, 2) }}%):</th>
                                    <td>&#8377;{{ number_format($igst, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>Net Amount (After GST) {{ ($invoice->invoice_type ?? '') == 'Reverse' ? 'Under RCM' : '' }}:</th>
                                    <td><strong>&#8377;{{ number_format(($invoice->invoice_type ?? '') == 'Reverse' ? $baseAmount : $netAmount, 2) }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4>User Information</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <tr>
                                    <th>Created By:</th>
                                    <td>{{ $invoice->created_by ? $invoice->creator->name . ' (' . $invoice->creator->email . ')' : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Updated By:</th>
                                    <td>{{ $invoice->updated_by ? $invoice->updater->name . ' (' . $invoice->updater->email . ')' : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment History Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h4>Payment History</h4>
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Payment Date</th>
                                                <th>Payment Mode</th>
                                                <th class="text-end">Remitted (₹)</th>
                                                <th class="text-end">TDS 194C (₹)</th>
                                                <th class="text-end">Deductions (₹)</th>
                                                <th class="text-end">Total Settled (₹)</th>
                                                <th>Remark</th>
                                                <th>Recorded By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($invoice->payments->sortByDesc(function($payment) { return $payment->payment_date ?? $payment->created_at; }) as $index => $payment)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : $payment->created_at->format('d-m-Y') }}</td>
                                                    <td>
                                                        <span class="badge bg-info">{{ $payment->mode }}</span>
                                                    </td>
                                                    <td class="text-end fw-semibold text-dark">&#8377;{{ number_format($payment->amount, 2) }}</td>
                                                    <td class="text-end fw-semibold text-primary">
                                                        {{ (float)($payment->tds_amount ?? 0) > 0 ? '₹'.number_format($payment->tds_amount, 2) : '-' }}
                                                    </td>
                                                    <td class="text-end fw-semibold text-danger">
                                                        @if((float)($payment->deduction_amount ?? 0) > 0)
                                                            <span>₹{{ number_format($payment->deduction_amount, 2) }}</span>
                                                            @if($payment->deduction_reason)
                                                                <small class="text-muted d-block text-xs">({{ $payment->deduction_reason }})</small>
                                                            @endif
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="text-end text-brand-primary fw-bold">
                                                        &#8377;{{ number_format((float)$payment->amount + (float)($payment->tds_amount ?? 0) + (float)($payment->deduction_amount ?? 0), 2) }}
                                                    </td>
                                                    <td>{{ $payment->remark ?: '-' }}</td>
                                                    <td>
                                                        @if($payment->creator)
                                                            <small>{{ $payment->creator->name }}</small>
                                                        @else
                                                            <small class="text-muted">N/A</small>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted py-4">
                                                        <i class="bi bi-inbox"></i> No payments recorded yet
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if($invoice->payments->count() > 0)
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="6" class="text-end">Total Settled (Paid + Deductions):</th>
                                                    <th class="text-end text-brand-primary">&#8377;{{ number_format($invoice->paid_amount, 2) }}</th>
                                                    <th colspan="2"></th>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                @if (auth()->user()->canAccessCompanyPanel() && $invoice->remaining_amount > 0)
                    <button type="button" class="btn btn-brand-primary me-2" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="bi bi-currency-rupee"></i> Add Payment
                    </button>
                @endif
                @if ($invoice->payment_status != 'paid' && Auth::user()->canActAsAdmin())
                    <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-warning me-2">Edit</a>
                @endif
                @if ($invoice->payment_status == 'pending' && Auth::user()->canActAsAdmin())
                    <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST"
                        style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger me-2"
                            onclick="return confirm('Delete this invoice?')">Delete</button>
                    </form>
                @endif
                <a href="{{ route('invoices.index') }}" class="btn btn-back">Back to List</a>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade fb-pay-modal" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">
                        <i class="bi bi-currency-rupee"></i> Add Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addPaymentForm">
                    <div class="modal-body">
                        <input type="hidden" id="payment_invoice_id" name="invoice_id" value="{{ $invoice->id }}">
                        <input type="hidden" id="payment_invoice_number" value="{{ $invoice->invoice_number ?: $invoice->manual_invoice_number }}">
                        <input type="hidden" id="payment_total_amount" value="{{ (float) ($invoice->net_amount ?? $invoice->total_amount) }}">
                        @php
                            $hasGst = (($invoice->cgst ?? 0) > 0 || ($invoice->sgst ?? 0) > 0 || ($invoice->igst ?? 0) > 0);
                            $baseAmountForTds = ($hasGst && ($invoice->base_amount ?? 0) > 0) ? (float) $invoice->base_amount : (float) ($invoice->net_amount ?? $invoice->total_amount);
                        @endphp
                        <input type="hidden" id="payment_base_amount" value="{{ $baseAmountForTds }}">
                        <input type="hidden" id="payment_paid_amount" value="{{ (float) $invoice->paid_amount }}">
                        <input type="hidden" id="payment_remaining_amount" value="{{ (float) $invoice->remaining_amount }}">

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label for="payment_date" class="form-label fw-semibold mb-1">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label for="payment_mode" class="form-label fw-semibold mb-1">Mode <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_mode" name="mode" required>
                                    <option value="">Select&hellip;</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="NEFT">NEFT</option>
                                    <option value="RTGS">RTGS</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="payment_bank_ledger_id" class="form-label fw-semibold mb-1">Deposit To Account</label>
                            <select class="form-select form-select-sm" id="payment_bank_ledger_id" name="bank_ledger_id">
                                <option value="">Default Primary Bank / Cash A/c</option>
                                @foreach($bankLedgers ?? [] as $bLedger)
                                    <option value="{{ $bLedger->id }}">{{ $bLedger->name }} ({{ $bLedger->accountGroup?->name ?? 'Bank/Cash' }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label for="payment_amount" class="form-label fw-semibold mb-1">Amount Received (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">&#8377;</span>
                                <input type="number" class="form-control" id="payment_amount" name="amount" step="0.01" min="0" required placeholder="0.00">
                                <button type="button" class="btn btn-outline-secondary" id="fill_full_amount" title="Fill net remaining balance">Full</button>
                            </div>
                            <div class="form-text mt-1">Invoice Balance: &#8377;<span id="disp_remaining_amount">{{ formatIndianNumber($invoice->remaining_amount) }}</span></div>
                        </div>

                        <!-- Customer Deductions Section (TDS & Transit Claims) -->
                        <div class="p-2.5 rounded border mb-2" style="background-color: #f8fafc; border-color: var(--brand-border) !important;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-dark"><i class="bi bi-scissors me-1 text-danger"></i>Customer Deductions (Optional)</span>
                                <span class="badge bg-light text-muted border small">TDS & Claims</span>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted mb-0">TDS u/s 194C (₹)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" min="0" class="form-control" id="payment_tds_amount" name="tds_amount" placeholder="0.00">
                                    </div>
                                    <div class="d-flex gap-1 mt-1">
                                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 calc-tds-btn" style="font-size: 0.75rem;" data-pct="1">1%</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 calc-tds-btn" style="font-size: 0.75rem;" data-pct="2">2%</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 calc-tds-btn" style="font-size: 0.75rem;" data-pct="0">Clear</button>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted mb-0">Shortage / Claims (₹)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" min="0" class="form-control" id="payment_deduction_amount" name="deduction_amount" placeholder="0.00">
                                    </div>
                                    <input type="text" class="form-control form-control-sm mt-1" id="payment_deduction_reason" name="deduction_reason" placeholder="Reason (e.g. Shortage, Penalty)">
                                </div>
                            </div>
                            <div class="p-2 rounded bg-white border small">
                                <div class="d-flex justify-content-between text-muted mb-1">
                                    <span>Total Invoice Cleared:</span>
                                    <span class="fw-bold text-dark" id="summary_total_cleared">₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between text-muted">
                                    <span>Remaining Balance After:</span>
                                    <span class="fw-bold text-primary" id="summary_remaining_balance">&#8377;{{ formatIndianNumber($invoice->remaining_amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-1">
                            <label for="payment_remark" class="form-label fw-semibold mb-1">Remark</label>
                            <textarea class="form-control" id="payment_remark" name="remark" rows="2" placeholder="Optional"></textarea>
                        </div>
                        <div id="payment_error" class="alert alert-danger d-none py-2 mb-0 mt-2"></div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-brand-primary">
                            <i class="bi bi-check-circle"></i> Add payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('invoices.partials.confirmation-assets')

    <script>
        $(document).ready(function() {
            function updateSettlementSummary() {
                var remaining = parseFloat($('#payment_remaining_amount').val()) || 0;
                var amount = parseFloat($('#payment_amount').val()) || 0;
                var tds = parseFloat($('#payment_tds_amount').val()) || 0;
                var ded = parseFloat($('#payment_deduction_amount').val()) || 0;
                var totalCleared = amount + tds + ded;
                var balanceAfter = Math.max(0, remaining - totalCleared);

                $('#summary_total_cleared').text('₹' + totalCleared.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#summary_remaining_balance').text('₹' + balanceAfter.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            }

            $('#payment_amount, #payment_tds_amount, #payment_deduction_amount').on('input', updateSettlementSummary);

            $('.calc-tds-btn').on('click', function() {
                var pct = parseFloat($(this).data('pct')) || 0;
                var baseTotal = parseFloat($('#payment_base_amount').val()) || parseFloat($('#payment_total_amount').val()) || 0;
                if (pct > 0) {
                    var tds = Math.round((baseTotal * (pct / 100)) * 100) / 100;
                    $('#payment_tds_amount').val(tds.toFixed(2)).trigger('input');
                } else {
                    $('#payment_tds_amount').val('').trigger('input');
                }
            });

            // Quick-fill the full remaining balance
            $('#fill_full_amount').on('click', function() {
                var remaining = parseFloat($('#payment_remaining_amount').val()) || 0;
                var tds = parseFloat($('#payment_tds_amount').val()) || 0;
                var ded = parseFloat($('#payment_deduction_amount').val()) || 0;
                var netRemit = Math.max(0, remaining - tds - ded);
                $('#payment_amount').val(netRemit.toFixed(2)).trigger('input').focus();
            });

            // Handle payment form submission
            $('#addPaymentForm').on('submit', function(e) {
                e.preventDefault();
                var invoiceId = $('#payment_invoice_id').val();
                var formData = {
                    mode: $('#payment_mode').val(),
                    amount: parseFloat($('#payment_amount').val()) || 0,
                    tds_amount: parseFloat($('#payment_tds_amount').val()) || 0,
                    deduction_amount: parseFloat($('#payment_deduction_amount').val()) || 0,
                    deduction_reason: $('#payment_deduction_reason').val(),
                    remark: $('#payment_remark').val(),
                    payment_date: $('#payment_date').val() || new Date().toISOString().split('T')[0],
                    bank_ledger_id: $('#payment_bank_ledger_id').val() || null,
                    _token: '{{ csrf_token() }}'
                };

                var invoiceTotal = parseFloat($('#payment_total_amount').val()) || 0;
                var paidAmount = parseFloat($('#payment_paid_amount').val()) || 0;
                var remainingAmount = parseFloat($('#payment_remaining_amount').val()) || 0;
                var totalCleared = formData.amount + formData.tds_amount + formData.deduction_amount;

                if (!formData.mode) {
                    $('#payment_error').removeClass('d-none').text('Please select a payment mode.');
                    return;
                }
                if (totalCleared <= 0) {
                    $('#payment_error').removeClass('d-none').text('Total payment settlement (Remitted + TDS + Deductions) must be greater than zero.');
                    return;
                }
                if (totalCleared > (remainingAmount + 0.01)) {
                    $('#payment_error').removeClass('d-none').text('Total cleared amount (₹' + totalCleared.toFixed(2) + ') cannot exceed remaining balance of ₹' + remainingAmount.toFixed(2));
                    return;
                }

                $('#payment_error').addClass('d-none').text('');

                var submitBtn = $(this).find('button[type="submit"]');
                var confirmationData = {
                    invoiceNumber: $('#payment_invoice_number').val(),
                    paymentDate: formData.payment_date,
                    mode: formData.mode,
                    amount: formData.amount,
                    tdsAmount: formData.tds_amount,
                    deductionAmount: formData.deduction_amount,
                    remark: formData.remark,
                    invoiceTotal: invoiceTotal,
                    paidAmount: paidAmount,
                    balanceBefore: remainingAmount,
                    balanceAfter: Math.max(0, remainingAmount - totalCleared),
                };

                function submitPayment() {
                    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

                    $.ajax({
                        url: '/invoices/' + invoiceId + '/payments',
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            if (response.success) {
                                if (typeof bootstrap !== 'undefined') {
                                    var modal = bootstrap.Modal.getInstance(document.getElementById('addPaymentModal'));
                                    if (modal) {
                                        modal.hide();
                                    }
                                }
                                location.reload();
                            }
                        },
                        error: function(xhr) {
                            var errorMessage = 'Error adding payment. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            $('#payment_error').removeClass('d-none').text(errorMessage);
                            submitBtn.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Add payment');
                        }
                    });
                }

                if (window.InvoiceConfirmation) {
                    window.InvoiceConfirmation.confirmPaymentSummary(confirmationData).then(function(confirmed) {
                        if (confirmed) {
                            submitPayment();
                        }
                    });
                } else if (window.confirm('Record this payment?')) {
                    submitPayment();
                }
            });
        });
    </script>
@endsection
