@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit Lorry Receipt</h2>
    <form action="{{ route('lorry_receipts.update', $lr->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="truck_id" class="form-label">Truck *</label>
                <select class="form-select" id="truck_id" name="truck_id" required>
                    <option value="">Select</option>
                    @foreach($trucks as $truck)
                        <option value="{{ $truck->id }}" {{ old('truck_id', $lr->truck_id) == $truck->id ? 'selected' : '' }}>{{ $truck->truck_number }}</option>
                    @endforeach
                </select>
                @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="transporter_id" class="form-label">Transporter *</label>
                <select class="form-select" id="transporter_id" name="transporter_id" required>
                    <option value="">Select</option>
                    @foreach($transporters as $transporter)
                        <option value="{{ $transporter->id }}" {{ old('transporter_id', $lr->transporter_id) == $transporter->id ? 'selected' : '' }}>{{ $transporter->name }}</option>
                    @endforeach
                </select>
                @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="eway_bill_number" class="form-label">Eway Bill Number</label>
                <input type="text" class="form-control" id="eway_bill_number" name="eway_bill_number" value="{{ old('eway_bill_number', $lr->eway_bill_number) }}">
                @error('eway_bill_number') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="delivery_status" class="form-label">Delivery Status *</label>
                <select class="form-select" id="delivery_status" name="delivery_status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ old('delivery_status', $lr->delivery_status) == $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
                @error('delivery_status') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="consignor_id" class="form-label">Consignor ID *</label>
                <input type="number" class="form-control" id="consignor_id" name="consignor_id" required value="{{ old('consignor_id', $lr->consignor_id) }}">
                @error('consignor_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="consignee_id" class="form-label">Consignee ID *</label>
                <input type="number" class="form-control" id="consignee_id" name="consignee_id" required value="{{ old('consignee_id', $lr->consignee_id) }}">
                @error('consignee_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" value="{{ old('consignee_contact_number', $lr->consignee_contact_number) }}">
                @error('consignee_contact_number') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="source_city" class="form-label">Source City *</label>
                <input type="text" class="form-control" id="source_city" name="source_city" required value="{{ old('source_city', $lr->source_city) }}">
                @error('source_city') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="destination_city" class="form-label">Destination City *</label>
                <input type="text" class="form-control" id="destination_city" name="destination_city" required value="{{ old('destination_city', $lr->destination_city) }}">
                @error('destination_city') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="source_state" class="form-label">Source State *</label>
                <input type="text" class="form-control" id="source_state" name="source_state" required value="{{ old('source_state', $lr->source_state) }}">
                @error('source_state') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="destination_state" class="form-label">Destination State *</label>
                <input type="text" class="form-control" id="destination_state" name="destination_state" required value="{{ old('destination_state', $lr->destination_state) }}">
                @error('destination_state') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="weight" class="form-label">Weight *</label>
                <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ old('weight', $lr->weight) }}">
                @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="no_of_units" class="form-label">No. of Units *</label>
                <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="{{ old('no_of_units', $lr->no_of_units) }}">
                @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="good_type" class="form-label">Goods Type *</label>
                <input type="text" class="form-control" id="good_type" name="good_type" required value="{{ old('good_type', $lr->good_type) }}">
                @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="fr_rate" class="form-label">Freight Rate *</label>
                <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ old('fr_rate', $lr->fr_rate) }}">
                @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="amount" class="form-label">Amount *</label>
                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount', $lr->amount) }}">
                @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="linked_lr_id" class="form-label">Linked LR ID</label>
                <input type="number" class="form-control" id="linked_lr_id" name="linked_lr_id" value="{{ old('linked_lr_id', $lr->linked_lr_id) }}">
                @error('linked_lr_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="gr_date" class="form-label">GR Date *</label>
                <input type="date" class="form-control" id="gr_date" name="gr_date" required value="{{ old('gr_date', $lr->gr_date) }}">
                @error('gr_date') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="gr_number" class="form-label">GR Number *</label>
                <input type="text" class="form-control" id="gr_number" name="gr_number" required value="{{ old('gr_number', $lr->gr_number) }}">
                @error('gr_number') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="memo_id" class="form-label">Memo ID</label>
                <input type="number" class="form-control" id="memo_id" name="memo_id" value="{{ old('memo_id', $lr->memo_id) }}">
                @error('memo_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <button type="submit" class="btn btn-success">Update LR</button>
        <a href="{{ route('lorry_receipts.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
