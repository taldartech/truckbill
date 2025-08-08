@extends('layouts.app')

@section('content')
@if(session('success'))
    <div class="alert alert-success mt-2">
        {{ session('success') }}
    </div>
@endif
<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header">
        <div class="d-flex justify-content-between w-100">
            <div class="card-title">Advance Payments</div>
            <a href="{{ route('advance-payments.create') }}" class="btn btn-primary">Add New</a>
        </div>
    </div>
    <!--end::Header-->
    <!--begin::Body-->
    <div class="card-body">
        <table class="table table-bordered" id="advance-payments-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Transporter</th>
                    <th>Advance Amount</th>
                    <th>Payment Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
    <!--end::Body-->
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#advance-payments-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('advance-payments.index') }}',
        columns: [
            { data: 'id' },
            { data: 'transporter' },
            { data: 'advance_amount' },
            { data: 'payment_date' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
});
</script>
@endpush
