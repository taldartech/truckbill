@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h4>Advance Payments</h4>
        <a href="{{ route('advance-payments.create') }}" class="btn btn-primary">Add New</a>
    </div>

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
