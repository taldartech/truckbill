@extends('layouts.app')

@section('content')
    <div class="card card-info card-outline mb-4" style="width: 70%; align-items: center; margin: auto;">
        <div class="card-header">
            <div class="card-title">Edit Company Bank Details</div>
        </div>
        <form action="{{ route('company_bank_details.update', $company->id) }}" method="POST" class="needs-validation"
            novalidate="">
            @csrf
            @method('PUT')
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12 mb-3">
                        <h6 class="text-muted">Company: {{ $company->name }}</h6>
                    </div>
                    <div class="col-md-12">
                        <label for="bank" class="form-label">Bank</label>
                        <input type="text" class="form-control" id="bank" name="bank"
                            value="{{ old('bank', $company->bank) }}" placeholder="Bank name">
                        @error('bank')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <div class="col-md-6">
                        <label for="account_number" class="form-label">Account Number</label>
                        <input type="text" class="form-control" id="account_number" name="account_number"
                            value="{{ old('account_number', $company->account_number) }}" placeholder="Account number">
                        @error('account_number')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <div class="col-md-6">
                        <label for="ifsc" class="form-label">IFSC</label>
                        <input type="text" class="form-control" id="ifsc" name="ifsc"
                            value="{{ old('ifsc', $company->ifsc) }}" placeholder="e.g. SBIN0001234" maxlength="11">
                        @error('ifsc')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-success" type="submit">Update Bank Details</button>
                <a href="{{ route('company_bank_details.index') }}" class="btn btn-info">Back to list</a>
            </div>
        </form>
    </div>
@endsection
