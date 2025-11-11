@extends('layouts.app')

@section('content')
    <div class="container px-0 mt-4">
        <h2 class='mb-3'>Invoice Details</h2>
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
                                    <th>Total Amount:</th>
                                    <td>&#8377;{{ number_format($invoice->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Payment Status:</th>
                                    <td>{{ ucfirst($invoice->payment_status) }}</td>
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
                </div>
                <div class="row">
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
            </div>
            <div class="card-footer text-end">
                @if ($invoice->payment_status != 'paid' && Auth::user()->userRole->name == 'admin')
                    <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-warning me-2">Edit</a>
                @endif
                @if ($invoice->payment_status == 'pending' && Auth::user()->userRole->name == 'admin')
                    <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST"
                        style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Delete this invoice?')">Delete</button>
                    </form>
                @endif
                <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@endsection
