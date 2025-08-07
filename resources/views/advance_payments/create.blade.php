@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Create Advance Payment</h4>

    <form method="POST" action="{{ route('advance-payments.store') }}">
        @csrf

        <div class="mb-3">
            <label for="transporter_id" class="form-label">Transporter</label>
            <select name="transporter_id" class="form-select" required>
                <option value="">Select</option>
                @foreach ($transporters as $transporter)
                    <option value="{{ $transporter->id }}">{{ $transporter->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="advance_amount" class="form-label">Advance Amount</label>
            <input type="number" step="0.01" name="advance_amount" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="payment_date" class="form-label">Payment Date</label>
            <input type="date" name="payment_date" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('advance-payments.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection