@extends('layouts.app')

@section('title', 'Lorry Receipt Details')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Lorry Receipt #{{ $lorryReceipt->lr_number }}</h5>
                <a href="{{ route('customer.lorry-receipts.index') }}" class="btn btn-back">Back</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>GR Date:</strong> {{ $lorryReceipt->gr_date ? \Carbon\Carbon::parse($lorryReceipt->gr_date)->format('d-m-Y') : '-' }}</div>
                    <div class="col-md-4"><strong>Status:</strong> {{ $lorryReceipt->delivery_status ?? '-' }}</div>
                    <div class="col-md-4"><strong>Truck:</strong> {{ $lorryReceipt->truck->truck_number ?? '-' }}</div>
                    <div class="col-md-4"><strong>Transporter:</strong> {{ $lorryReceipt->transporter->name ?? '-' }}</div>
                    <div class="col-md-4"><strong>Source:</strong> {{ $lorryReceipt->source_city ?? '-' }}</div>
                    <div class="col-md-4"><strong>Destination:</strong> {{ $lorryReceipt->destination_city ?? '-' }}</div>
                    <div class="col-md-4"><strong>Consignor:</strong> {{ $lorryReceipt->consignor->name ?? '-' }}</div>
                    <div class="col-md-4"><strong>Consignee:</strong> {{ $lorryReceipt->consignee->name ?? '-' }}</div>
                    <div class="col-md-4"><strong>No. of Units:</strong> {{ $lorryReceipt->no_of_units ?? '-' }}</div>
                    <div class="col-md-4"><strong>Weight:</strong> {{ $lorryReceipt->weight ?? '-' }}</div>
                    <div class="col-md-4"><strong>Goods Type:</strong> {{ $lorryReceipt->good_type ?? '-' }}</div>
                    <div class="col-md-4"><strong>Amount:</strong> {{ number_format((float) ($lorryReceipt->amount ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
