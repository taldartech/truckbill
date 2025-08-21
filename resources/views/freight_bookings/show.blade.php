@extends('layouts.app')

@section('content')
<style>
/* Consistent styling for view pages */
.info-card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1.5rem;
}

.info-card .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #495057;
    min-width: 140px;
}

.info-value {
    color: #212529;
    text-align: right;
    flex: 1;
}

.badge-status {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}
</style>

<div class="card card-info card-outline mb-4" style="max-width: 1200px; margin: 0 auto;">
    <!--begin::Header-->
    <div class="card-header">
        <div class="card-title h4">Freight Booking Details - #{{ $freight_booking->id }}</div>
        <div class="card-toolbar">
            <a href="{{ route('freight_bookings.edit', $freight_booking->id) }}" class="btn btn-sm btn-primary me-2">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('freight_bookings.index') }}" class="btn btn-sm btn-outline-dark">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    <!--end::Header-->
    
    <!--begin::Body-->
    <div class="card-body">
        <div class="row">
            <!-- Basic Information -->
            <div class="col-md-6">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">FR Number:</span>
                            <span class="info-value">#{{ $freight_booking->id }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">LR Number:</span>
                            <span class="info-value">{{ $freight_booking->lr ? $freight_booking->lr->lr_number : 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Voucher Number:</span>
                            <span class="info-value">{{ $freight_booking->voucher_number ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Company:</span>
                            <span class="info-value">{{ $freight_booking->company ? $freight_booking->company->name : 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">GR Date:</span>
                            <span class="info-value">{{ $freight_booking->lr && $freight_booking->lr->gr_date ? date('d-m-Y', strtotime($freight_booking->lr->gr_date)) : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="col-md-6">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Financial Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">TR Rate:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->tr_fr_rate ?? 0, 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">TR Amount:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->tr_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Advance:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->advance_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Balance:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->balance_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">TDS Amount:</span>
                            <span class="info-value">₹{{ number_format($freight_booking->tds_amount ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Status Information -->
            <div class="col-md-6">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Status Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Delivered:</span>
                            <span class="info-value">
                                @if($freight_booking->is_delivered)
                                    <span class="badge bg-success badge-status">Yes</span>
                                @else
                                    <span class="badge bg-warning badge-status">No</span>
                                @endif
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cancelled:</span>
                            <span class="info-value">
                                @if($freight_booking->is_cancelled)
                                    <span class="badge bg-danger badge-status">Yes</span>
                                @else
                                    <span class="badge bg-secondary badge-status">No</span>
                                @endif
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">PAN Verified:</span>
                            <span class="info-value">
                                @if($freight_booking->pan_verified)
                                    <span class="badge bg-success badge-status">Yes</span>
                                @else
                                    <span class="badge bg-warning badge-status">No</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="col-md-6">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Additional Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">E-way Bill:</span>
                            <span class="info-value">{{ $freight_booking->eway_bill_number ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Good Type:</span>
                            <span class="info-value">{{ $freight_booking->good_type ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Remark:</span>
                            <span class="info-value">{{ $freight_booking->remark ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Created:</span>
                            <span class="info-value">{{ $freight_booking->created_at ? $freight_booking->created_at->format('d-m-Y H:i') : 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Updated:</span>
                            <span class="info-value">{{ $freight_booking->updated_at ? $freight_booking->updated_at->format('d-m-Y H:i') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($freight_booking->lr)
        <div class="row">
            <div class="col-12">
                <div class="info-card card">
                    <div class="card-header">
                        <h5 class="mb-0">Related Lorry Receipt Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Customer:</span>
                                    <span class="info-value">{{ $freight_booking->lr->customer ? $freight_booking->lr->customer->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Consignor:</span>
                                    <span class="info-value">{{ $freight_booking->lr->consignor ? $freight_booking->lr->consignor->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Consignee:</span>
                                    <span class="info-value">{{ $freight_booking->lr->consignee ? $freight_booking->lr->consignee->name : 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Source City:</span>
                                    <span class="info-value">{{ $freight_booking->lr->sourceCity ? $freight_booking->lr->sourceCity->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Destination City:</span>
                                    <span class="info-value">{{ $freight_booking->lr->destinationCity ? $freight_booking->lr->destinationCity->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Weight:</span>
                                    <span class="info-value">{{ $freight_booking->lr->weight ?? 'N/A' }} kg</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Truck Number:</span>
                                    <span class="info-value">{{ $freight_booking->lr->truck ? $freight_booking->lr->truck->truck_number : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Transporter:</span>
                                    <span class="info-value">{{ $freight_booking->lr->transporter ? $freight_booking->lr->transporter->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Delivery Status:</span>
                                    <span class="info-value">
                                        @php
                                            $status = $freight_booking->lr->delivery_status ?? 'Pending';
                                            $badgeClass = match($status) {
                                                'Delivered' => 'bg-success',
                                                'Cancelled' => 'bg-danger',
                                                'In-Progress' => 'bg-info',
                                                default => 'bg-warning'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} badge-status">{{ $status }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    <!--end::Body-->
</div>
@endsection
