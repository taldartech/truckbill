@extends('layouts.app')

@section('content')
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form id="companyForm" data-confirm-mode="update" action="{{ route('company.update', $company->id) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-pencil-square me-1"></i> Edit Company
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('company.index') }}" class="btn app-index-header-btn" aria-label="Back to companies">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="mb-3">
                        <span class="badge text-bg-dark border app-index-flash">
                            <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
                        </span>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-building"></i> Company Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label for="name" class="form-label">Company Name *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" required value="{{ old('name', $company->name) }}">
                                @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="short_name" class="form-label">Short Name *</label>
                                <input type="text" class="form-control text-uppercase" id="short_name" value="{{ $company->short_name }}" disabled>
                                <div class="app-form-section__hint mt-1 mb-0" id="invoice-preview">
                                    Example invoice number: <span id="invoicePreviewText">{{ $company->short_name ? strtoupper($company->short_name).'/'.now()->year.'/0001' : '—' }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="company_logo" class="form-label">Company Logo</label>
                                <input type="file" class="form-control @error('company_logo') is-invalid @enderror" id="company_logo" name="company_logo" accept="image/jpeg,image/png,image/jpg">
                                @error('company_logo') <div class="text-danger">{{ $message }}</div> @enderror
                                @if($company->logoPublicUrl())
                                    <div class="mt-2">
                                        <img src="{{ $company->logoPublicUrl() }}" alt="Company logo" style="max-height: 48px; width: auto; object-fit: contain;">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-receipt"></i> GST &amp; Tax</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label for="gst_number" class="form-label">GST Number</label>
                                <input type="text" class="form-control text-uppercase @error('gst_number') is-invalid @enderror" id="gst_number" name="gst_number" maxlength="15" value="{{ old('gst_number', $company->gst_number) }}">
                                @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="pan_number" class="form-label">PAN Number *</label>
                                <input type="text" class="form-control text-uppercase @error('pan_number') is-invalid @enderror" id="pan_number" name="pan_number" required maxlength="10" value="{{ old('pan_number', $company->pan_number) }}">
                                @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label for="gst_scheme" class="form-label">GST Scheme *</label>
                                @if($hasInvoicesInFy ?? false)
                                    <input type="hidden" name="gst_scheme" value="{{ old('gst_scheme', $company->gst_scheme) }}">
                                    <select class="form-select" id="gst_scheme" disabled>
                                        <option value="reverse charge" {{ old('gst_scheme', $company->gst_scheme) == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                                        <option value="forward charge" {{ old('gst_scheme', $company->gst_scheme) == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                                    </select>
                                    <div class="app-form-section__hint mt-1 mb-0 text-warning">
                                        <i class="fas fa-lock"></i> Cannot change GST scheme. Invoices have been created for the current financial year (April {{ $fyStart->year }} - March {{ $fyEnd->year }}).
                                    </div>
                                @else
                                    <select class="form-select @error('gst_scheme') is-invalid @enderror" id="gst_scheme" name="gst_scheme" required>
                                        <option value="reverse charge" {{ old('gst_scheme', $company->gst_scheme) == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                                        <option value="forward charge" {{ old('gst_scheme', $company->gst_scheme) == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                                    </select>
                                @endif
                                @error('gst_scheme') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="gst_type" class="form-label">GST Type *</label>
                                <input type="text" class="form-control" id="gst_type" value="{{ ucfirst($company->gst_type ?: 'regular') }}" disabled>
                            </div>
                            <div class="col-md-4">
                                <label for="gst_slab" class="form-label">GST Slab *</label>
                                <select class="form-select @error('gst_slab') is-invalid @enderror" id="gst_slab" name="gst_slab" required>
                                    <option value="">Select GST slab</option>
                                    @foreach($companySlabs as $slab)
                                        <option value="{{ trim($slab) }}" {{ (string) old('gst_slab', $company->gst_slab) === (string) trim($slab) ? 'selected' : '' }}>
                                            {{ trim($slab) }}%
                                        </option>
                                    @endforeach
                                </select>
                                @error('gst_slab') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-geo-alt"></i> Contact &amp; Location</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label for="contact_person" class="form-label">Contact Person *</label>
                                <input type="text" class="form-control @error('contact_person') is-invalid @enderror" id="contact_person" name="contact_person" required value="{{ old('contact_person', $company->contact_person) }}">
                                @error('contact_person') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number *</label>
                                <input type="text" class="form-control @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" required maxlength="10" inputmode="numeric" value="{{ old('contact_number', $company->contact_number) }}">
                                @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label for="country_id" class="form-label">Country *</label>
                                <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id" required onchange="fetchStates(this.value)">
                                    <option value="">Select country</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ (string) $selectedCountryId === (string) $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                                @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="state_id" class="form-label">State *</label>
                                <select class="form-select @error('state_id') is-invalid @enderror" id="state_id" name="state_id" required onchange="fetchCities(this.value)">
                                    <option value="">Select state</option>
                                    @foreach($states as $state)
                                        <option value="{{ $state->id }}" {{ (string) old('state_id', $company->state_id) === (string) $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                    @endforeach
                                </select>
                                @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="city_id" class="form-label">City *</label>
                                <select class="form-select @error('city_id') is-invalid @enderror" id="city_id" name="city_id" required>
                                    <option value="">Select city</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->id }}" {{ (string) old('city_id', $company->city_id) === (string) $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                                    @endforeach
                                </select>
                                @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2" required>{{ old('address', $company->address) }}</textarea>
                                @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="pin_code" class="form-label">Pin Code *</label>
                                <input type="text" class="form-control @error('pin_code') is-invalid @enderror" id="pin_code" name="pin_code" required maxlength="10" value="{{ old('pin_code', $company->pin_code) }}">
                                @error('pin_code') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-bank"></i> Bank Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label for="bank" class="form-label">Bank</label>
                                <input type="text" class="form-control @error('bank') is-invalid @enderror" id="bank" name="bank" value="{{ old('bank', $company->bank) }}" placeholder="Bank name">
                                @error('bank') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="account_number" class="form-label">Account Number</label>
                                <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number', $company->account_number) }}" placeholder="Account number">
                                @error('account_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="ifsc" class="form-label">IFSC</label>
                                <input type="text" class="form-control text-uppercase @error('ifsc') is-invalid @enderror" id="ifsc" name="ifsc" value="{{ old('ifsc', $company->ifsc) }}" placeholder="e.g. SBIN0001234" maxlength="11">
                                @error('ifsc') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body">
                <a href="{{ route('company.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-brand-primary" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Update Company
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();

    document.addEventListener('DOMContentLoaded', function() {
        ['gst_number', 'pan_number', 'ifsc'].forEach((id) => {
            document.getElementById(id)?.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });
        });
    });

    function fetchStates(countryId) {
        fetch(`/api/states/${countryId}`)
            .then(response => response.json())
            .then(data => {
                const stateSelect = document.getElementById('state_id');
                stateSelect.innerHTML = '<option value="">Select state</option>';
                data.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.id;
                    option.textContent = state.name;
                    stateSelect.appendChild(option);
                });
                const citySelect = document.getElementById('city_id');
                citySelect.innerHTML = '<option value="">Select city</option>';
            })
            .catch(error => console.error('Error fetching states:', error));
    }

    function fetchCities(stateId) {
        fetch(`/api/cities/${stateId}`)
            .then(response => response.json())
            .then(data => {
                const citySelect = document.getElementById('city_id');
                citySelect.innerHTML = '<option value="">Select city</option>';
                data.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching cities:', error));
    }
</script>
<script src="{{ asset('js/company-form.js') }}"></script>
@endpush
