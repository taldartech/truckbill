@extends('layouts.app')

@section('content')
<style>
.confirmation-section {
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}
.confirmation-label {
    font-weight: 600;
    color: #555;
}
.confirmation-value {
    font-weight: 500;
}
.badge-lg {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}
</style>

<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title h4">Confirm Freight Booking</div>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Please review the freight booking details below before final submission.
        </div>

        <!-- LR Information Section -->
        <div class="confirmation-section">
            <h5 class="mb-3">Lorry Receipt Information</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">LR Number</div>
                    <div class="confirmation-value text-primary">{{ $lorry_receipt->lr_number }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Transporter</div>
                    <div class="confirmation-value">{{ $lorry_receipt->transporter->name }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Transporter Type</div>
                    <div class="confirmation-value">
                        @if($lorry_receipt->transporter->is_owner)
                            <span class="badge bg-primary text-white">Truck Owner</span>
                        @else
                            <span class="badge bg-info text-white">Agency</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Truck</div>
                    <div class="confirmation-value">{{ $lorry_receipt->truck->truck_number }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Reference By</div>
                    <div class="confirmation-value">{{ $lorry_receipt->reference ? $lorry_receipt->reference->name : 'N/A' }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Customer</div>
                    <div class="confirmation-value">{{ $lorry_receipt->customer ? $lorry_receipt->customer->name : 'N/A' }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Consignor</div>
                    <div class="confirmation-value">{{ $lorry_receipt->consignor ? $lorry_receipt->consignor->name : 'N/A' }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Consignee</div>
                    <div class="confirmation-value">{{ $lorry_receipt->consignee ? $lorry_receipt->consignee->name : 'N/A' }}</div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Weight</div>
                    <div class="confirmation-value">{{ number_format($lorry_receipt->weight, 3) }} MT</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Goods Type</div>
                    <div class="confirmation-value">{{ $lorry_receipt->good_type ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Source</div>
                    <div class="confirmation-value">{{ $lorry_receipt->source_city }}, {{ $lorry_receipt->source_state }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">Destination</div>
                    <div class="confirmation-value">{{ $lorry_receipt->destination_city }}, {{ $lorry_receipt->destination_state }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="confirmation-label">TDS Percentage</div>
                    <div class="confirmation-value">{{ number_format($lorry_receipt->transporter->tds_percentage, 2) }}%</div>
                </div>
            </div>
        </div>

        <!-- Freight Booking Details Section -->
        <div class="confirmation-section">
            <h5 class="mb-3">Freight Booking Details</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="confirmation-label">TR Freight Rate</div>
                    <div class="confirmation-value">₹{{ number_format($formData['tr_fr_rate'], 2) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="confirmation-label">TR Amount</div>
                    <div class="confirmation-value">₹{{ number_format($formData['tr_amount'], 2) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="confirmation-label">TDS Amount</div>
                    <div class="confirmation-value">₹{{ number_format($formData['tds_amount'], 2) }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="confirmation-label">Balance Amount</div>
                    <div class="confirmation-value">₹{{ number_format($formData['balance_amount'], 2) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="confirmation-label">E-Way Bill Number</div>
                    <div class="confirmation-value">{{ $formData['eway_bill_number'] ?? 'Not provided' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="confirmation-label">E-Way Bill Expiry Date</div>
                    <div class="confirmation-value">{{ $formData['eway_bill_expiry'] ? date('d-m-Y', strtotime($formData['eway_bill_expiry'])) : 'Not provided' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="confirmation-label">PAN Verified</div>
                    <div class="confirmation-value">
                        @if($formData['pan_verified'])
                            <span class="badge bg-brand-primary">Yes</span>
                        @else
                            <span class="badge bg-danger">No</span>
                        @endif
                    </div>
                </div>
            </div>
            @if(!empty($formData['remark']))
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="confirmation-label">Remark</div>
                    <div class="confirmation-value">{{ $formData['remark'] }}</div>
                </div>
            </div>
            @endif
        </div>

        <!-- Financial Summary Section -->
        <div class="card bg-light mb-4">
            <div class="card-body">
                <h5 class="card-title">Financial Summary</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between">
                            <span>TR Amount:</span>
                            <span class="fw-bold">₹{{ number_format($formData['tr_amount'], 2) }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between">
                            <span>TDS Amount:</span>
                            <span class="fw-bold text-danger">- ₹{{ number_format($formData['tds_amount'], 2) }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between">
                            <span>Balance Amount:</span>
                            <span class="fw-bold text-brand-primary">₹{{ number_format($formData['balance_amount'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form for submission -->
        <form method="POST" action="{{ route('freight_bookings.store') }}">
            @csrf
            <!-- Hidden fields to pass all form data -->
            @foreach($formData as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="confirmed" value="1">

            <div class="d-flex justify-content-between mt-4">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left me-1"></i> Back to Edit
                </a>
                <div>
                    <button type="submit" name="action" value="save" class="btn btn-brand-primary me-2">
                        <i class="fas fa-check-circle me-1"></i> Confirm & Submit
                    </button>
                    <button type="submit" name="action" value="save_and_view" class="btn btn-view">
                        <i class="bi bi-eye me-1"></i> Save & do payment
                    </button>

                </div>
            </div>
        </form>
    </div>
</div>
@endsection
