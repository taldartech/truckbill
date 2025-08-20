@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Freight Booking Details</h3>
        <div>
            <a href="{{ route('freight_bookings.edit', $freight_booking->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('freight_bookings.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Basic Information -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>ID:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->id }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>LR Number:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->lr ? $freight_booking->lr->lr_number : 'N/A' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Voucher Number:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->voucher_number ?? 'N/A' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Company:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->company ? $freight_booking->company->name : 'N/A' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>GR Date:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->lr && $freight_booking->lr->gr_date ? date('d-m-Y', strtotime($freight_booking->lr->gr_date)) : 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Information -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Financial Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>TR Rate:</strong></div>
                        <div class="col-sm-8">₹{{ number_format($freight_booking->tr_fr_rate ?? 0, 2) }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>TR Amount:</strong></div>
                        <div class="col-sm-8">₹{{ number_format($freight_booking->tr_amount ?? 0, 2) }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Advance:</strong></div>
                        <div class="col-sm-8">₹{{ number_format($freight_booking->advance_amount ?? 0, 2) }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Balance:</strong></div>
                        <div class="col-sm-8">₹{{ number_format($freight_booking->balance_amount ?? 0, 2) }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>TDS Amount:</strong></div>
                        <div class="col-sm-8">₹{{ number_format($freight_booking->tds_amount ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Status Information -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Status Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Delivered:</strong></div>
                        <div class="col-sm-8">
                            @if($freight_booking->is_delivered)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-warning">No</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Cancelled:</strong></div>
                        <div class="col-sm-8">
                            @if($freight_booking->is_cancelled)
                                <span class="badge bg-danger">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>PAN Verified:</strong></div>
                        <div class="col-sm-8">
                            @if($freight_booking->pan_verified)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-warning">No</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Additional Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>E-way Bill:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->eway_bill_number ?? 'N/A' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Good Type:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->good_type ?? 'N/A' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Remark:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->remark ?? 'N/A' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Created:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->created_at ? $freight_booking->created_at->format('d-m-Y H:i') : 'N/A' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Updated:</strong></div>
                        <div class="col-sm-8">{{ $freight_booking->updated_at ? $freight_booking->updated_at->format('d-m-Y H:i') : 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($freight_booking->lr)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Related Lorry Receipt Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="row mb-2">
                                <div class="col-sm-6"><strong>Customer:</strong></div>
                                <div class="col-sm-6">{{ $freight_booking->lr->customer ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6"><strong>Consignor:</strong></div>
                                <div class="col-sm-6">{{ $freight_booking->lr->consignor ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6"><strong>Consignee:</strong></div>
                                <div class="col-sm-6">{{ $freight_booking->lr->consignee ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="row mb-2">
                                <div class="col-sm-6"><strong>Source:</strong></div>
                                <div class="col-sm-6">{{ $freight_booking->lr->source_city ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6"><strong>Destination:</strong></div>
                                <div class="col-sm-6">{{ $freight_booking->lr->destination_city ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6"><strong>Weight:</strong></div>
                                <div class="col-sm-6">{{ $freight_booking->lr->weight ?? 'N/A' }} kg</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="row mb-2">
                                <div class="col-sm-6"><strong>Truck:</strong></div>
                                <div class="col-sm-6">{{ $freight_booking->lr->truck_number ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6"><strong>Transporter:</strong></div>
                                <div class="col-sm-6">{{ $freight_booking->lr->transporter_name ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6"><strong>Delivery Status:</strong></div>
                                <div class="col-sm-6">
                                    @php
                                        $status = $freight_booking->lr->delivery_status ?? 'Pending';
                                        $badgeClass = match($status) {
                                            'Delivered' => 'bg-success',
                                            'Cancelled' => 'bg-danger',
                                            'In-Progress' => 'bg-info',
                                            default => 'bg-warning'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
