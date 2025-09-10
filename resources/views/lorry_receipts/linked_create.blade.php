@extends('layouts.app')

@section('content')

<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <!--begin::Header-->
    <div class="card-header">
        <div class="d-flex w-100">
            <div class="card-title h4">Add New Lorry Receipt</div>
            <div class="ms-auto">
                <a href="{{ route('consignees.create') }}" class="btn btn-info">
                    <i class="bi bi-plus"></i> Create Consignee/Consignor/Customer</a>
            </div>
        </div>
    </div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('lorry_receipts.store') }}" id="lorryReceiptForm" class="needs-validation"
        novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        <div class="card-body">
            <!-- General Details -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row">
                    <div class="col">
                        <h5 class="mb-3">General Details</h5>
                    </div>
                </div>
                <div class="row mb-3">
                   
                    <div class="col-md-4">
                        <label for="gr_date" class="form-label">GR Date *</label>
                        <input type="date" class="form-control" id="gr_date" name="gr_date" required
                            value="{{ old('gr_date') }}">
                        @error('gr_date')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a GR date.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="customer_id" class="form-label">Customer *</label>
                        <select class="form-select" id="customer_id" name="customer_id" required
                            data-placeholder="Search and select customer...">
                            <option value="">Select Customer</option>
                            @foreach ($consignees as $consignee)
                                <option value="{{ $consignee->id }}"
                                    {{ old('customer_id') == $consignee->id ? 'selected' : '' }}>
                                    {{ $consignee->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div>
                </div>
                <!-- Memo ID field removed from creation form as it cannot be present at creation time -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="truck_id" class="form-label">Truck *</label>
                        <select class="form-select" id="truck_id" name="truck_id" required
                            data-placeholder="Search and select truck...">
                            <option value="">Select Trucks</option>
                            @foreach ($trucks as $truck)
                                <option value="{{ $truck->id }}"
                                    {{ old('truck_id') == $truck->id ? 'selected' : '' }}>
                                    {{ $truck->truck_number }}
                                    @if ($truck->driver)
                                        - Driver: {{ $truck->driver->name }}
                                    @endif
                                    ({{ $truck->transporter ? $truck->transporter->name : 'No Transporter' }})
                                </option>
                            @endforeach
                        </select>
                        @error('truck_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a truck.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="transporter_type" class="form-label" id="transporterTypeLabel">Select Payment Type
                            *</label>
                        <select class="form-select" id="transporter_type" name="transporter_type" required>
                            <option value="">Select Type</option>
                            <option value="owner">Truck Owner</option>
                            <option value="agency">Agency</option>
                        </select>
                        <div class="invalid-feedback">Please select transporter type.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="transporter_id" id="transporterLabel" class="form-label">Transporter *</label>
                        <select class="form-select" id="transporter_id" name="transporter_id" required>
                            <option value="">Select Transporter</option>
                            {{-- This will be filled via JavaScript --}}
                        </select>
                        @error('transporter_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a transporter.</div>
                        <div id="tdsRateDisplay" class="mt-2" style="display: none;">
                            <span class="badge bg-warning text-dark fs-6">
                                🎯 TDS Rate: <span id="tdsPercentageValue">--</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="reference_by" class="form-label">Reference By</label>
                        <select class="form-select" id="reference_by" name="reference_by"
                            data-placeholder="Search and select reference...">
                            <option value="">Select Reference</option>
                            @foreach ($agencies as $agency)
                                <option value="{{ $agency->id }}"
                                    {{ old('reference_by') == $agency->id ? 'selected' : '' }}>
                                    {{ $agency->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('reference_by')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a reference.</div>
                    </div>
                </div>
            </div>

            <!-- Consignor & Consignee Details -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row">
                    <div class="col">
                        <h5 class="mb-3">Consignor & Consignee Details</h5>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="consignor_id" class="form-label">Consignor *</label>
                        <select class="form-select" id="consignor_id" name="consignor_id" required
                            data-placeholder="Search and select consignor...">
                            <option value="">Select Consignor</option>
                            @foreach ($consignees as $consignee)
                                <option value="{{ $consignee->id }}"
                                    {{ old('consignee_id') == $consignee->id ? 'selected' : '' }}>
                                    {{ $consignee->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('consignor_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a consignor.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="consignor_contact_number" class="form-label">Consignor Contact Number</label>
                        <input type="text" class="form-control" id="consignor_contact_number"
                            name="consignor_contact_number" readonly>
                    </div>

                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="consignee_id" class="form-label">Consignee *</label>
                        <select class="form-select" id="consignee_id" name="consignee_id" required
                            data-placeholder="Search and select consignee...">
                            <option value="">Select Consignee</option>
                            @foreach ($consignees as $consignee)
                                <option value="{{ $consignee->id }}"
                                    {{ old('consignee_id') == $consignee->id ? 'selected' : '' }}>
                                    {{ $consignee->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('consignee_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a consignee.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                        <input type="text" class="form-control" id="consignee_contact_number"
                            name="consignee_contact_number" readonly>
                    </div>
                </div>
            </div>
            <!-- Route Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row">
                    <div class="col">
                        <h5 class="mb-3">Route Details</h5>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="source_city" class="form-label">Source City *</label>
                        <input type="text" class="form-control" id="source_city" name="source_city" required
                            value="{{ old('source_city') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="source_state" class="form-label">Source State *</label>
                        <input type="text" class="form-control" id="source_state" name="source_state" required
                            value="{{ old('source_state') }}" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="destination_city" class="form-label">Destination City *</label>
                        <input type="text" class="form-control" id="destination_city" name="destination_city"
                            required value="{{ old('destination_city') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="destination_state" class="form-label">Destination State *</label>
                        <input type="text" class="form-control" id="destination_state" name="destination_state"
                            required value="{{ old('destination_state') }}" readonly>
                    </div>
                </div>
            </div>
            <!-- Goods & Charges Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row">
                    <div class="col">
                        <h5 class="mb-3">Goods & Charges</h5>
                    </div>
                </div>
                <div class="row mb-3">
                    <input type="hidden" id="lane" name="lane" value="Road">
                    <div class="col-md-4">
                        <label for="good_type" class="form-label">Goods Type *</label>
                        <div class="input-group">
                            <select class="form-select" id="good_type" name="good_type" required>
                                <option value="">Select Goods Type</option>
                                @foreach ($goodsTypes as $goodsType)
                                    <option value="{{ $goodsType->name }}"
                                        {{ old('good_type') == $goodsType->name ? 'selected' : '' }}>
                                        {{ $goodsType->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#addGoodsTypeModal" title="Add New Goods Type">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        @error('good_type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a goods type.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="no_of_units" class="form-label">No. of Units <small
                                class="text-muted">(Optional)</small></label>
                        <input type="number" class="form-control" id="no_of_units" name="no_of_units"
                            value="{{ old('no_of_units') }}" min="1" max="100000"
                            title="Maximum 100,000 units allowed">
                        @error('no_of_units')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please enter a valid number of units (max 100,000).</div>
                        <small class="text-muted">📦 Max: 100,000 units</small>
                    </div>

                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="weight" class="form-label">Weight * <small class="text-muted">(in
                                Tons)</small></label>
                        <input type="number" step="0.01" class="form-control" id="weight" name="weight"
                            required value="{{ old('weight') }}" min="0.1" max="100"
                            title="Weight must be between 0.1 and 100 tons">
                        @error('weight')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please enter a weight between 0.1 and 100 tons.</div>
                        <small class="text-muted">📏 Range: 0.1 - 100 tons</small>
                    </div>
                    <div class="col-md-4">
                        <label for="fr_rate" class="form-label">Freight Rate * <small class="text-muted">(₹ per
                                ton)</small></label>
                        <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate"
                            required value="{{ old('fr_rate') }}" min="1" max="20000"
                            title="Freight rate must be between ₹1 and ₹20,000 per ton">
                        @error('fr_rate')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please enter a freight rate between ₹1 and ₹20,000.</div>
                        <small class="text-muted">💰 Range: ₹1 - ₹20,000 per ton</small>
                    </div>
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Amount * <small
                                class="text-muted">(Auto-calculated)</small></label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount"
                            required value="{{ old('amount') }}"
                            title="Auto-calculated: Weight × Freight Rate (editable)">
                        @error('amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please enter an amount.</div>
                        <small class="text-muted">💡 Calculated as: Weight × Freight Rate</small>
                    </div>
                </div>
            </div>
            <!-- Submit Actions -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row">
                    <div class="col">
                        <h5 class="mb-3">Submit Actions</h5>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-4 offset-md-4">
                        <button class="btn btn-success w-100" type="submit">💾 Submit Form</button>
                    </div>

                    <div class="col-md-4">
                        <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-dark w-100">📄 Back to
                            List</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection