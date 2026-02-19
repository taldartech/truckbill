@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h2>Invoice Details</h2>
        <div class="card">
            <div class="card-body">
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
                                    <th>Manual Invoice #:</th>
                                    <td>{{ $invoice->manual_invoice_number }}</td>
                                </tr>
                                <tr>
                                    <th>Invoice Type:</th>
                                    <td>{{ ucfirst($invoice->invoice_type) }}</td>
                                </tr>
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
                                    $baseAmount = $invoice->total_amount - $totalGst ?? 0;
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
                                    <table class="table table-striped table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Payment Date</th>
                                                <th>Payment Mode</th>
                                                <th>Amount</th>
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
                                                    <td class="text-brand-primary fw-bold">&#8377;{{ number_format($payment->amount, 2) }}</td>
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
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        <i class="bi bi-inbox"></i> No payments recorded yet
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if($invoice->payments->count() > 0)
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="3" class="text-end">Total Paid:</th>
                                                    <th class="text-brand-primary">&#8377;{{ number_format($invoice->paid_amount, 2) }}</th>
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
                @if (in_array(auth()->user()->userRole->name, ['admin', 'moderator']) && $invoice->remaining_amount > 0)
                    <button type="button" class="btn btn-brand-primary me-2" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="bi bi-cash-coin"></i> Add Payment
                    </button>
                @endif
                @if ($invoice->payment_status != 'paid' && Auth::user()->userRole->name == 'admin')
                    <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-warning me-2">Edit</a>
                @endif
                @if ($invoice->payment_status == 'pending' && Auth::user()->userRole->name == 'admin')
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
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-brand-primary text-white">
                    <h5 class="modal-title" id="addPaymentModalLabel">
                        <i class="bi bi-cash-coin"></i> Add Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addPaymentForm">
                    <div class="modal-body">
                        <input type="hidden" id="payment_invoice_id" name="invoice_id" value="{{ $invoice->id }}">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Invoice Number</label>
                            <input type="text" class="form-control" value="{{ $invoice->manual_invoice_number }}" readonly>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Total Amount</label>
                                <input type="text" class="form-control" value="&#8377;{{ number_format($invoice->total_amount, 2) }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Paid Amount</label>
                                <input type="text" class="form-control" value="&#8377;{{ number_format($invoice->paid_amount, 2) }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Remaining Balance</label>
                            <input type="text" class="form-control fw-bold text-danger" value="&#8377;{{ number_format($invoice->remaining_amount, 2) }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="payment_date" class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_mode" class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="payment_mode" name="mode" required placeholder="e.g., Cash, Bank Transfer, Cheque, UPI">
                        </div>
                        <div class="mb-3">
                            <label for="payment_amount" class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="payment_amount" name="amount" step="0.01" min="0.01" max="{{ $invoice->remaining_amount }}" required placeholder="0.00">
                            <div class="form-text">Maximum: &#8377;{{ number_format($invoice->remaining_amount, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="payment_remark" class="form-label">Remark</label>
                            <textarea class="form-control" id="payment_remark" name="remark" rows="3" placeholder="Optional remarks"></textarea>
                        </div>
                        <div id="payment_error" class="alert alert-danger d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-brand-primary">
                            <i class="bi bi-check-circle"></i> Add Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Handle payment form submission
            $('#addPaymentForm').on('submit', function(e) {
                e.preventDefault();
                var invoiceId = $('#payment_invoice_id').val();
                var formData = {
                    mode: $('#payment_mode').val(),
                    amount: parseFloat($('#payment_amount').val()),
                    remark: $('#payment_remark').val(),
                    payment_date: $('#payment_date').val() || new Date().toISOString().split('T')[0],
                    _token: '{{ csrf_token() }}'
                };

                // Validate amount
                var remainingAmount = {{ $invoice->remaining_amount }};
                if (formData.amount > remainingAmount) {
                    $('#payment_error').removeClass('d-none').text('Payment amount cannot exceed remaining balance of ₹' + remainingAmount.toFixed(2));
                    return;
                }

                // Disable submit button
                var submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.ajax({
                    url: '/invoices/' + invoiceId + '/payments',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Close modal
                            if (typeof bootstrap !== 'undefined') {
                                var modal = bootstrap.Modal.getInstance(document.getElementById('addPaymentModal'));
                                if (modal) {
                                    modal.hide();
                                }
                            }
                            // Reload page to show updated payment history
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Error adding payment. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        $('#payment_error').removeClass('d-none').text(errorMessage);
                        submitBtn.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Add Payment');
                    }
                });
            });
        });
    </script>
@endsection
