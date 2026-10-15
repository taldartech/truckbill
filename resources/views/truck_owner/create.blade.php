@extends('layouts.app')

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .card-header .card-title,
        .card-header .h3,
        .card-header .h4 {
            font-size: 0.88rem !important;
            font-weight: 600 !important;
        }

        .app-form-section__title,
        h5, .h5 {
            font-size: 0.76rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.3rem !important;
        }

        .app-form-section__hint {
            font-size: 0.66rem !important;
            margin-bottom: 0.35rem !important;
        }

        label,
        .form-label,
        [class*="col-"] > label {
            font-size: 0.68rem !important;
            font-weight: 600 !important;
            margin-bottom: 0.12rem !important;
            line-height: 1.25 !important;
            color: #334155 !important;
            display: inline-block !important;
        }

        .form-control, .form-select {
            font-size: 0.72rem !important;
            padding: 0.2rem 0.45rem !important;
            min-height: 29px !important;
            height: 29px !important;
            border-radius: 0.35rem !important;
            line-height: 1.2 !important;
        }

        .form-control-plaintext {
            font-size: 0.72rem !important;
            padding: 0.2rem 0.45rem !important;
            min-height: 29px !important;
            height: auto !important;
            line-height: 1.25 !important;
            border-radius: 0.35rem !important;
        }

        textarea.form-control {
            height: auto !important;
            min-height: 48px !important;
            font-size: 0.72rem !important;
            padding: 0.25rem 0.45rem !important;
        }

        input[type="date"].form-control {
            font-size: 0.70rem !important;
            padding: 0.18rem 0.35rem !important;
            min-height: 29px !important;
            height: 29px !important;
        }

        .form-check-label {
            font-size: 0.68rem !important;
        }

        .form-check-input {
            width: 0.8rem !important;
            height: 0.8rem !important;
            margin-top: 0.15rem !important;
        }

        .form-check-inline {
            margin-right: 0.6rem !important;
            margin-bottom: 0.15rem !important;
        }

        .card.bg-light,
        .app-form-section__body {
            padding: 0.45rem 0.55rem !important;
            border-radius: 0.4rem !important;
            margin-bottom: 0.45rem !important;
        }

        .card-body {
            padding: 0.5rem 0.55rem !important;
        }

        .btn,
        .btn-brand-primary,
        .btn-back,
        .btn-primary,
        .btn-secondary,
        .btn-danger,
        .btn-info,
        .btn-warning,
        .app-index-header-btn {
            font-size: 0.72rem !important;
            font-weight: 600 !important;
            padding: 0.28rem 0.55rem !important;
            min-height: 29px !important;
            height: auto !important;
            line-height: 1.25 !important;
            border-radius: 0.375rem !important;
            white-space: nowrap !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.25rem !important;
        }

        .btn i,
        .btn-brand-primary i,
        .btn-back i {
            font-size: 0.70rem !important;
            margin: 0 !important;
        }

        .small, small, .text-muted {
            font-size: 0.64rem !important;
        }

        .text-danger {
            font-size: 0.64rem !important;
            margin-top: 0.1rem !important;
        }

        .location-row {
            --bs-gutter-x: 0.35rem !important;
        }

        .location-row .form-label {
            font-size: 0.68rem !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            margin-bottom: 0.12rem !important;
            display: block !important;
        }

        .location-row .input-group {
            flex-wrap: nowrap !important;
        }

        .location-row .input-group .form-select {
            font-size: 0.68rem !important;
            padding-left: 0.25rem !important;
            padding-right: 1.05rem !important;
            min-height: 29px !important;
            height: 29px !important;
            border-radius: 0.35rem 0 0 0.35rem !important;
            background-position: right 0.2rem center !important;
            background-size: 9px 7px !important;
        }

        .location-row .input-group .btn {
            padding: 0 0.25rem !important;
            width: 24px !important;
            min-width: 24px !important;
            max-width: 24px !important;
            font-size: 0.85rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 0 0.35rem 0.35rem 0 !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form action="{{ route('truck_owners.store') }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        @php
            $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
            $userCompanyId = auth()->user()->company_id ?? null;
            $shouldRestoreAfterValidation = $errors->any() && (old('pan_number') || old('transporter_master_id'));
            $restoredPanIsDummy = false;
            if ($shouldRestoreAfterValidation && $userCompanyId && old('pan_number')) {
                $restoredPanIsDummy = strtoupper((string) old('pan_number'))
                    === \App\Models\Company::dummyTransporterTruckOwnerPanNumber((int) $userCompanyId);
            }
        @endphp

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-truck me-1"></i> Add New Truck Owner
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('truck_owners.index') }}" class="btn app-index-header-btn" aria-label="Back to truck owners">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                @error('registered_truck_id')
                    <div class="alert alert-danger py-2">{{ $message }}</div>
                @enderror

                @if($canEditCompany)
                    <input type="hidden" name="company_id" value="{{ old('company_id', $userCompanyId) }}">
                @else
                    <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
                @endif
                <input type="hidden" name="transporter_master_id" id="transporter_master_id" value="{{ old('transporter_master_id') }}">
                <input type="hidden" id="pan_verified" value="{{ $shouldRestoreAfterValidation ? '1' : '0' }}">
                <input type="hidden" id="is_dummy_pan" value="{{ $restoredPanIsDummy ? '1' : '0' }}">

                {{-- PAN Check --}}
                <div class="app-form-section" id="panLookupSection">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="fas fa-search"></i> PAN Verification</h5>
                    </div>
                    <div class="app-form-section__body">
                        <p class="app-form-section__hint mb-2">Enter the 10-digit PAN and click Check. The form appears only after a successful PAN check.</p>
                        <div class="mb-2">@include('shared.check-pan-status-link')</div>
                        <div class="row g-2">
                            <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                                <label for="pan_lookup" class="form-label small fw-semibold">PAN Number *</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm text-uppercase" id="pan_lookup" value="{{ old('pan_number') }}" placeholder="10-digit PAN" maxlength="10">
                                    <button type="button" class="btn btn-brand-primary btn-sm px-2 px-sm-3 text-nowrap" id="btnPanLookup" title="Check PAN">
                                        <i class="fas fa-check"></i><span class="d-none d-sm-inline ms-1">Check PAN</span><span class="d-inline d-sm-none ms-1">Check</span>
                                    </button>
                                </div>
                                <div id="panLookupMessage" class="mt-2 small"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="panAlreadyRegisteredSection" class="alert alert-warning d-none" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Already registered.</strong> This PAN is already registered as a truck owner for this company.
                </div>

                <div id="restOfForm" class="{{ $shouldRestoreAfterValidation ? '' : 'd-none' }}">
                    {{-- Master Data (Read-only when found via PAN) --}}
                    <div id="masterDataSection" class="d-none">
                        <div class="app-form-section">
                            <div class="app-form-section__header app-form-section__header--muted">
                                <h5 class="app-form-section__title"><i class="fas fa-lock"></i> Master Data (read-only from PAN)</h5>
                            </div>
                            <div class="app-form-section__body">
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Truck Owner Name</label>
                                        <div class="form-control-plaintext bg-white border rounded px-3 py-2" id="display_name">-</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email</label>
                                        <div class="form-control-plaintext bg-white border rounded px-3 py-2" id="display_email">-</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PAN Number</label>
                                        <div class="form-control-plaintext bg-white border rounded px-3 py-2" id="display_pan">-</div>
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label">GST Number</label>
                                        <div class="form-control-plaintext bg-white border rounded px-3 py-2" id="display_gst">-</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PAN Active</label>
                                        <div class="form-control-plaintext bg-white border rounded px-3 py-2" id="display_pan_active">-</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Address</label>
                                        <div class="form-control-plaintext bg-white border rounded px-3 py-2" id="display_address">-</div>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Country / State / City</label>
                                        <div class="form-control-plaintext bg-white border rounded px-3 py-2" id="display_location">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Single Truck Owner Details section --}}
                    <div class="app-form-section">
                        <div class="app-form-section__header">
                            <h5 class="app-form-section__title"><i class="bi bi-building"></i> Truck Owner Details</h5>
                        </div>
                        <div class="app-form-section__body">
                            <input type="hidden" id="pan_number" name="pan_number" value="{{ old('pan_number') }}">

                            <div id="newMasterFormSection">
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label for="name" class="form-label">Truck Owner Name *</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Enter Truck Owner Name">
                                        @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="Enter Email (optional)">
                                        @error('email') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gst_number" class="form-label">GST Number</label>
                                        <input type="text" class="form-control @error('gst_number') is-invalid @enderror" id="gst_number" name="gst_number" value="{{ old('gst_number') }}" placeholder="Enter GST Number">
                                        @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label">PAN is Active *</label><br>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="pan_active" id="pan_active_yes" value="1" {{ old('pan_active') === '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="pan_active_yes">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="pan_active" id="pan_active_no" value="0" {{ old('pan_active') === '0' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="pan_active_no">No</label>
                                        </div>
                                        @error('pan_active') <div class="text-danger">{{ $message }}</div> @enderror
                                        <div class="mt-1">@include('shared.check-pan-status-link')</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label for="contact_person_name" class="form-label">Contact Person Name</label>
                                    <input type="text" class="form-control @error('contact_person_name') is-invalid @enderror" id="contact_person_name" name="contact_person_name" value="{{ old('contact_person_name') }}" placeholder="Enter Contact Person Name">
                                    @error('contact_person_name') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="mobile_number" class="form-label">Mobile Number *</label>
                                    <input type="tel" class="form-control" id="mobile_number" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="Enter 10-digit mobile" maxlength="10" required>
                                    @error('mobile_number') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Book Balance</label>
                                    <div class="form-control-plaintext bg-light border rounded px-3 py-2">
                                        <strong>0.00</strong>
                                        <small class="text-muted d-block">Read-only field</small>
                                    </div>
                                    <input type="hidden" name="advance_amount" value="0">
                                </div>
                            </div>

                            <div id="locationDetailsSection">
                                <div class="row g-1 g-md-2 mb-2 location-row">
                                    <div class="col-4">
                                        <label for="country_id" class="form-label">Country *</label>
                                        <div class="input-group">
                                            <select class="form-select" id="country_id" name="country_id" required>
                                                <option value="">Select Country</option>
                                                @foreach($countries as $country)
                                                    <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#addCountryModal" title="Add New Country">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                        @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-4">
                                        <label for="state_id" class="form-label">State *</label>
                                        <div class="input-group">
                                            <select class="form-select" id="state_id" name="state_id" required>
                                                <option value="">Select State</option>
                                                @if(old('country_id'))
                                                    @foreach($states as $state)
                                                        <option value="{{ $state->id }}" {{ old('state_id') == $state->id ? 'selected' : '' }}>
                                                            {{ $state->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <button type="button" class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#addStateModal" title="Add New State">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                        @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-4">
                                        <label for="city_id" class="form-label">City *</label>
                                        <div class="input-group">
                                            <select class="form-select" id="city_id" name="city_id" required>
                                                <option value="">Select City</option>
                                                @if(old('state_id'))
                                                    @foreach($cities as $city)
                                                        <option value="{{ $city->id }}" {{ old('city_id') == $city->id ? 'selected' : '' }}>
                                                            {{ $city->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <button type="button" class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#addCityModal" title="Add New City">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                        @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-12">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2" placeholder="Enter complete address">{{ old('address') }}</textarea>
                                        @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">Declaration u/s 194C(6) Received with PAN? *</label><br>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="declaration_given" id="decl_yes" value="1" {{ old('declaration_given') === '1' ? 'checked' : '' }} required>
                                        <label class="form-check-label fw-semibold" for="decl_yes">Yes (0.00% TDS)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="declaration_given" id="decl_no" value="0" {{ old('declaration_given') === '0' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="decl_no">No</label>
                                    </div>
                                    @error('declaration_given') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3" id="declaration_date_col" style="{{ old('declaration_given') === '1' ? '' : 'display:none;' }}">
                                    <label for="declaration_194c6_date" class="form-label">Declaration Date</label>
                                    <input type="date" class="form-control @error('declaration_194c6_date') is-invalid @enderror" id="declaration_194c6_date" name="declaration_194c6_date" value="{{ old('declaration_194c6_date', date('Y-m-d')) }}">
                                    @error('declaration_194c6_date') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-2">
                                    <label for="total_trucks" class="form-label">Total Trucks (<= 10)</label>
                                    <input type="number" class="form-control @error('total_trucks') is-invalid @enderror" id="total_trucks" name="total_trucks" min="0" max="10000" required value="{{ old('total_trucks', 0) }}" placeholder="Total trucks">
                                    @error('total_trucks') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="tds_percentage" class="form-label">TDS % *</label>
                                    <input type="hidden" id="tds_calculated_baseline" value="{{ old('tds_percentage', '0') }}">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control @error('tds_percentage') is-invalid @enderror" id="tds_percentage" name="tds_percentage" value="{{ old('tds_percentage', '0') }}" required>
                                    @error('tds_percentage') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label for="tds_adjustment_remark" class="form-label">TDS adjustment remark</label>
                                    <textarea class="form-control @error('tds_adjustment_remark') is-invalid @enderror" id="tds_adjustment_remark" name="tds_adjustment_remark" rows="2" placeholder="Required when TDS % differs from the calculated rate">{{ old('tds_adjustment_remark') }}</textarea>
                                    @error('tds_adjustment_remark') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-md-5">
                                    <label class="form-label">MSME Status</label>
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" name="is_msme" id="is_msme" value="1" {{ old('is_msme') ? 'checked' : '' }}
                                               onchange="document.getElementById('truck_owner_msme_reg_col').style.display = this.checked ? 'block' : 'none'">
                                        <label class="form-check-label fw-semibold" for="is_msme">Registered MSME (Micro / Small)</label>
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Subject to Sec 43B(h) prompt payment compliance by our company</small>
                                </div>
                                <div class="col-md-5" id="truck_owner_msme_reg_col" style="{{ old('is_msme') ? '' : 'display:none;' }}">
                                    <label for="msme_reg_no" class="form-label">Udyam / MSME Registration #</label>
                                    <input type="text" class="form-control @error('msme_reg_no') is-invalid @enderror" id="msme_reg_no" name="msme_reg_no" value="{{ old('msme_reg_no') }}" placeholder="UDYAM-XX-00-0000000">
                                    @error('msme_reg_no') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>{{-- /#restOfForm --}}
            </div>
        </div>

        <div id="truckOwnerFormActions" class="app-form-actions card {{ $shouldRestoreAfterValidation ? '' : 'd-none' }}">
            <div class="card-body d-flex flex-row flex-nowrap justify-content-end align-items-center gap-2 p-2 p-sm-3">
                <a href="{{ route('truck_owners.index') }}" class="btn btn-secondary btn-sm flex-fill flex-sm-grow-0 text-nowrap w-auto">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-brand-primary btn-sm flex-fill flex-sm-grow-0 text-nowrap w-auto" type="button" onclick="showConfirmationModal()">
                    <i class="bi bi-floppy-fill me-1"></i> Save
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Confirmation Modal -->
<div class="modal fade fb-pay-modal" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="confirmationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Confirm Truck Owner Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please review the details below before submitting the form.
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Basic Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-bold">Name:</td><td id="confirm-name">-</td></tr>
                            <tr><td class="fw-bold">Contact Person:</td><td id="confirm-contact-person">-</td></tr>
                            <tr><td class="fw-bold">Mobile Number:</td><td id="confirm-mobile">-</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-file-alt me-2"></i>Tax Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-bold">GST Number:</td><td id="confirm-gst-number">-</td></tr>

                            <tr><td class="fw-bold">PAN Number:</td><td id="confirm-pan-number">-</td></tr>
                            <tr><td class="fw-bold">PAN Active:</td><td id="confirm-pan-active">-</td></tr>
                            <tr class="table-warning"><td class="fw-bold">🎯 TDS Percentage:</td><td id="confirm-tds-percentage" class="fw-bold text-danger fs-5">-</td></tr>
                        </table>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-map-marker-alt me-2"></i>Location Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-bold">Address:</td><td id="confirm-address">-</td></tr>
                            <tr><td class="fw-bold">Country:</td><td id="confirm-country">-</td></tr>
                            <tr><td class="fw-bold">State:</td><td id="confirm-state">-</td></tr>
                            <tr><td class="fw-bold">City:</td><td id="confirm-city">-</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-truck me-2"></i>Business Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-bold">Declaration Given:</td><td id="confirm-declaration">-</td></tr>
                            <tr><td class="fw-bold">Total Trucks:</td><td id="confirm-total-trucks">-</td></tr>
                            <tr><td class="fw-bold">Book Balance:</td><td id="confirm-advance">-</td></tr>
                        </table>
                    </div>
                </div>

                <div id="noEmailNotificationNotice" class="alert alert-warning d-none mt-3 mb-0">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirm-no-email-notification">
                        <label class="form-check-label" for="confirm-no-email-notification">
                            I understand that no notification will be sent to this truck owner because no email address has been provided.
                        </label>
                    </div>
                </div>

                <div id="dummyPanAckNotice" class="alert alert-warning d-none mt-3 mb-0">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirm-dummy-pan-ack">
                        <label class="form-check-label" for="confirm-dummy-pan-ack">
                            {{ \App\Models\Company::DUMMY_PAN_TDS_ADJUSTMENT_REMARK }}
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-brand-primary" id="confirmSubmitButton" onclick="submitForm()">
                    <i class="fas fa-check me-2"></i>Confirm & Submit
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade fb-pay-modal" id="addCountryModal" tabindex="-1" aria-labelledby="addCountryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="addCountryForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="addCountryModalLabel">Add Country</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div id="countryFormErrors" class="alert alert-danger d-none"></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="country_name" class="form-label">Country Name *</label>
                <input type="text" class="form-control" id="country_name" name="name" required>
              </div>

              <div class="col-md-6">
                <label for="country_code" class="form-label">Country Code *</label>
                <input type="text" class="form-control" id="country_code" name="code" required>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-brand-primary">
              <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              Submit form
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Add State Modal -->
<div class="modal fade fb-pay-modal" id="addStateModal" tabindex="-1" aria-labelledby="addStateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="addStateForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="addStateModalLabel">Add State</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div id="stateFormErrors" class="alert alert-danger d-none"></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="state_name" class="form-label">State Name *</label>
                <input type="text" class="form-control" id="state_name" name="name" required>
              </div>

              <div class="col-md-6">
                <label for="state_code" class="form-label">State Code *</label>
                <input type="text" class="form-control" id="state_code" name="code" required maxlength="10">
              </div>

              <div class="col-md-12">
                <label for="modal_state_country_id" class="form-label">Country *</label>
                <select class="form-select" id="modal_state_country_id" name="country_id" required>
                  <option value="">Select Country</option>
                  @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-brand-primary">
              <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              Submit form
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Add City Modal -->
<div class="modal fade fb-pay-modal" id="addCityModal" tabindex="-1" aria-labelledby="addCityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="addCityForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="addCityModalLabel">Add City</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div id="cityFormErrors" class="alert alert-danger d-none"></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="modal_city_name" class="form-label">City Name *</label>
                <input type="text" class="form-control" id="modal_city_name" name="name" required>
              </div>

              <div class="col-md-6">
                <label for="modal_city_country_id" class="form-label">Country *</label>
                <select class="form-select" id="modal_city_country_id" name="country_id" required>
                  <option value="">Select Country</option>
                  @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                  @endforeach
                </select>

              </div>

              <div class="col-md-6">
                <label for="modal_city_state_id" class="form-label">State *</label>
                <select class="form-select" id="modal_city_state_id" name="state_id" required>
                  <option value="">Select State</option>
                </select>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-brand-primary">
              <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              Submit form
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<script>
    const DEFAULT_DUMMY_TDS_REMARK = @json(\App\Models\Company::DUMMY_PAN_TDS_ADJUSTMENT_REMARK);

    function setIsDummyPan(flag) {
        const isDummyPanInput = document.getElementById('is_dummy_pan');
        if (isDummyPanInput) {
            isDummyPanInput.value = flag ? '1' : '0';
        }
    }

    function isDummyPanSelected() {
        return document.getElementById('is_dummy_pan')?.value === '1';
    }

    function applyDefaultDummyTdsRemark() {
        const remark = document.getElementById('tds_adjustment_remark');
        if (!remark || !isDummyPanSelected()) {
            return;
        }
        if ((remark.value || '').trim() === '') {
            remark.value = DEFAULT_DUMMY_TDS_REMARK;
        }
    }

    function syncTruckOwnerFormActions() {
        const actions = document.getElementById('truckOwnerFormActions');
        const form = document.getElementById('restOfForm');
        if (!actions || !form) {
            return;
        }
        if (form.classList.contains('d-none')) {
            actions.classList.add('d-none');
        } else {
            actions.classList.remove('d-none');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnPanLookup = document.getElementById('btnPanLookup');
        const panLookup = document.getElementById('pan_lookup');
        const panField = document.getElementById('pan_number');
        const panVerifiedInput = document.getElementById('pan_verified');
        const panLookupMessage = document.getElementById('panLookupMessage');
        const masterDataSection = document.getElementById('masterDataSection');
        const newMasterFormSection = document.getElementById('newMasterFormSection');
        const transporterMasterId = document.getElementById('transporter_master_id');
        const locationDetailsSection = document.getElementById('locationDetailsSection');
        const restOfForm = document.getElementById('restOfForm');
        const panAlreadyRegisteredSection = document.getElementById('panAlreadyRegisteredSection');
        let lastCheckedPan = '';
        const shouldRestoreAfterValidation = @json($shouldRestoreAfterValidation);

        syncTruckOwnerFormActions();

        if (panLookup && panField && (panField.value || '').trim() !== '') {
            panLookup.value = panField.value;
            applyDefaultDummyTdsRemark();
        }

        function invalidatePanVerification(changedPan) {
            if (!panVerifiedInput) return;
            if (panVerifiedInput.value !== '1') return;
            if ((changedPan || '').toUpperCase() === (lastCheckedPan || '').toUpperCase()) return;
            panVerifiedInput.value = '0';
            setIsDummyPan(false);
            if (restOfForm) restOfForm.classList.add('d-none');
            syncTruckOwnerFormActions();
            if (panAlreadyRegisteredSection) panAlreadyRegisteredSection.classList.add('d-none');
            panLookupMessage.textContent = 'PAN changed. Please click Check PAN again.';
            panLookupMessage.className = 'mt-2 small text-warning';
        }

        if (btnPanLookup && panLookup) {
            btnPanLookup.addEventListener('click', function() {
                const pan = (panLookup.value || '').trim().toUpperCase();
                // Client-side validation is intentionally relaxed:
                // real PAN regex validation + dummy-PAN acceptance is handled server/API.
                if (pan.length !== 10 || !/^[A-Z0-9]{10}$/.test(pan)) {
                    panLookupMessage.textContent = 'Please enter a 10-character PAN (letters/numbers).';
                    panLookupMessage.className = 'mt-2 small text-danger';
                    return;
                }
                const companyId = document.querySelector('input[name="company_id"]')?.value || '';
                if (!companyId) {
                    panLookupMessage.textContent = 'Company is required.';
                    panLookupMessage.className = 'mt-2 small text-danger';
                    return;
                }
                btnPanLookup.disabled = true;
                panLookupMessage.textContent = 'Checking PAN...';
                panLookupMessage.className = 'mt-2 small text-muted';
                if (panAlreadyRegisteredSection) panAlreadyRegisteredSection.classList.add('d-none');
                if (restOfForm) restOfForm.classList.add('d-none');
                syncTruckOwnerFormActions();

                const url = '/api/transporter-master-by-pan?pan_number=' + encodeURIComponent(pan) + '&company_id=' + encodeURIComponent(companyId) + '&is_owner=1';
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : r.json().then(d => { throw new Error(d.error || 'Request failed'); }))
                .then(data => {
                    if (data.already_registered) {
                        if (panVerifiedInput) panVerifiedInput.value = '0';
                        setIsDummyPan(false);
                        panLookupMessage.textContent = 'This PAN is already registered as a truck owner for this company.';
                        panLookupMessage.className = 'mt-2 small text-danger';
                        if (panAlreadyRegisteredSection) panAlreadyRegisteredSection.classList.remove('d-none');
                        syncTruckOwnerFormActions();
                        return;
                    }
                    setIsDummyPan(!!data.is_dummy_pan);
                    if (restOfForm) restOfForm.classList.remove('d-none');
                    syncTruckOwnerFormActions();
                    if (data.found && data.master) {
                        document.getElementById('display_name').textContent = data.master.name || '-';
                        document.getElementById('display_email').textContent = data.master.email || '-';
                        document.getElementById('display_pan').textContent = data.master.pan_number || '-';
                        document.getElementById('display_gst').textContent = data.master.gst_number || '-';
                        document.getElementById('display_pan_active').textContent = data.master.pan_active ? 'Yes' : 'No';
                        document.getElementById('display_address').textContent = data.master.address || '-';
                        document.getElementById('display_location').textContent = [data.master.country, data.master.state, data.master.city].filter(Boolean).join(' / ') || '-';
                        transporterMasterId.value = data.master.id;
                        masterDataSection.classList.remove('d-none');
                        newMasterFormSection.classList.add('d-none');
                        if (locationDetailsSection) locationDetailsSection.classList.add('d-none');
                        const nameEl = document.getElementById('name');
                        const panEl = document.getElementById('pan_number');
                        const countryEl = document.getElementById('country_id');
                        const stateEl = document.getElementById('state_id');
                        const cityEl = document.getElementById('city_id');
                        if (nameEl) nameEl.removeAttribute('required');
                        if (panEl) panEl.removeAttribute('required');
                        if (countryEl) countryEl.removeAttribute('required');
                        if (stateEl) stateEl.removeAttribute('required');
                        if (cityEl) cityEl.removeAttribute('required');
                        panLookupMessage.textContent = 'PAN verified. Enter contact person and mobile below.';
                        panLookupMessage.className = 'mt-2 small text-success';
                    } else {
                        transporterMasterId.value = '';
                        masterDataSection.classList.add('d-none');
                        newMasterFormSection.classList.remove('d-none');
                        if (locationDetailsSection) locationDetailsSection.classList.remove('d-none');
                        const nameEl = document.getElementById('name');
                        const panEl = document.getElementById('pan_number');
                        const countryEl = document.getElementById('country_id');
                        const stateEl = document.getElementById('state_id');
                        const cityEl = document.getElementById('city_id');
                        if (nameEl) nameEl.setAttribute('required', 'required');
                        if (panEl) { panEl.setAttribute('required', 'required'); panEl.value = pan; }
                        if (countryEl) countryEl.setAttribute('required', 'required');
                        if (stateEl) stateEl.setAttribute('required', 'required');
                        if (cityEl) cityEl.setAttribute('required', 'required');
                        applyDefaultDummyTdsRemark();
                        panLookupMessage.textContent = 'PAN not in system. Please fill in all master details below.';
                        panLookupMessage.className = 'mt-2 small text-info';
                    }
                    if (panVerifiedInput) panVerifiedInput.value = '1';
                    lastCheckedPan = pan;
                    window.__ownerTdsUserEdited = false;
                    if (typeof refreshOwnerTdsBaseline === 'function') {
                        refreshOwnerTdsBaseline();
                    }
                })
                .catch((err) => {
                    if (panVerifiedInput) panVerifiedInput.value = '0';
                    setIsDummyPan(false);
                    panLookupMessage.textContent = err.message || 'Check failed. Please try again.';
                    panLookupMessage.className = 'mt-2 small text-danger';
                    syncTruckOwnerFormActions();
                })
                .finally(() => { btnPanLookup.disabled = false; });
            });
            panLookup.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
                invalidatePanVerification(this.value);
                if (panField) {
                    panField.value = this.value;
                }
            });
        }

        if (shouldRestoreAfterValidation) {
            if (restOfForm) {
                restOfForm.classList.remove('d-none');
            }
            syncTruckOwnerFormActions();
            if (panAlreadyRegisteredSection) {
                panAlreadyRegisteredSection.classList.add('d-none');
            }
            if (panLookupMessage) {
                panLookupMessage.textContent = 'Validation errors found. Please correct the fields below.';
                panLookupMessage.className = 'mt-2 small text-warning';
            }
            if (panLookup && panLookup.value && panLookup.value.length === 10 && btnPanLookup) {
                btnPanLookup.click();
            }
        }

        if (panField) {
            panField.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
                if (panLookup && panLookup.value !== this.value) {
                    panLookup.value = this.value;
                }
                invalidatePanVerification(this.value);
            });
        }
        function updateOwner194cUi() {
            const isYes = document.getElementById('decl_yes')?.checked;
            const dateCol = document.getElementById('declaration_date_col');
            const tdsInput = document.getElementById('tds_percentage');
            if (dateCol) {
                dateCol.style.display = isYes ? '' : 'none';
            }
            if (isYes && tdsInput) {
                tdsInput.value = '0.00';
                tdsInput.readOnly = true;
            } else if (tdsInput) {
                tdsInput.readOnly = false;
            }
        }
        updateOwner194cUi();

        document.querySelectorAll('input[name="declaration_given"]').forEach(function (el) {
            el.addEventListener('change', function () {
                window.__ownerTdsUserEdited = false;
                updateOwner194cUi();
                if (typeof refreshOwnerTdsBaseline === 'function') {
                    refreshOwnerTdsBaseline();
                }
            });
        });
        document.querySelectorAll('input[name="pan_active"]').forEach(function (el) {
            el.addEventListener('change', function () {
                window.__ownerTdsUserEdited = false;
                if (typeof refreshOwnerTdsBaseline === 'function') {
                    refreshOwnerTdsBaseline();
                }
            });
        });
        const ownerPanInput = document.getElementById('pan_number');
        if (ownerPanInput) {
            ownerPanInput.addEventListener('input', function () {
                window.__ownerTdsUserEdited = false;
                if (document.getElementById('transporter_master_id')?.value) return;
                if (typeof refreshOwnerTdsBaseline === 'function') {
                    refreshOwnerTdsBaseline();
                }
            });
        }
        const ownerTdsPct = document.getElementById('tds_percentage');
        if (ownerTdsPct) {
            ownerTdsPct.addEventListener('input', function () { window.__ownerTdsUserEdited = true; });
        }
    });
</script>
<script>
    window.__ownerTdsUserEdited = false;
    function refreshOwnerTdsBaseline() {
        const masterId = document.getElementById('transporter_master_id')?.value;
        let panNumber = '';
        let panActiveLabel = 'No';
        if (masterId) {
            panNumber = document.getElementById('display_pan')?.textContent?.trim() || '';
            panActiveLabel = document.getElementById('display_pan_active')?.textContent?.trim() === 'Yes' ? 'Yes' : 'No';
        } else {
            panNumber = document.getElementById('pan_number')?.value?.trim() || '';
            panActiveLabel = document.querySelector('input[name="pan_active"]:checked')?.value === '1' ? 'Yes' : 'No';
        }
        if (!panNumber || panNumber === '-') return;
        calculateTdsPercentageFromApi(panNumber, panActiveLabel, function (calculatedStr) {
            const calculated = parseFloat(calculatedStr);
            if (isNaN(calculated)) return;
            const baselineEl = document.getElementById('tds_calculated_baseline');
            if (baselineEl) baselineEl.value = calculated.toFixed(2);
            if (!window.__ownerTdsUserEdited) {
                const inp = document.getElementById('tds_percentage');
                if (inp) inp.value = calculated.toFixed(2);
            }
        });
    }

    function resolveNotificationEmail() {
        const masterId = document.getElementById('transporter_master_id')?.value;

        if (masterId) {
            const displayEmail = document.getElementById('display_email')?.textContent?.trim() || '';

            return displayEmail === '' || displayEmail === '-' ? '' : displayEmail;
        }

        return document.getElementById('email')?.value?.trim() || '';
    }

    function isNotificationEmailMissing() {
        return resolveNotificationEmail() === '';
    }

    function updateConfirmationSubmitState(resetChecks = false) {
        const submitButton = document.getElementById('confirmSubmitButton');
        const emailNotice = document.getElementById('noEmailNotificationNotice');
        const emailCheckbox = document.getElementById('confirm-no-email-notification');
        const dummyNotice = document.getElementById('dummyPanAckNotice');
        const dummyCheckbox = document.getElementById('confirm-dummy-pan-ack');
        if (!submitButton) {
            return;
        }

        const missingEmail = isNotificationEmailMissing();
        const needsDummyAck = isDummyPanSelected();

        if (emailNotice && emailCheckbox) {
            emailNotice.classList.toggle('d-none', !missingEmail);
            if (resetChecks || !missingEmail) {
                emailCheckbox.checked = false;
            }
        }

        if (dummyNotice && dummyCheckbox) {
            dummyNotice.classList.toggle('d-none', !needsDummyAck);
            if (resetChecks || !needsDummyAck) {
                dummyCheckbox.checked = false;
            }
        }

        const emailOk = !missingEmail || !!emailCheckbox?.checked;
        const dummyOk = !needsDummyAck || !!dummyCheckbox?.checked;
        submitButton.disabled = !(emailOk && dummyOk);
    }

    function updateNoEmailNotificationNotice() {
        updateConfirmationSubmitState(true);
    }

    document.getElementById('confirm-no-email-notification')?.addEventListener('change', function () {
        updateConfirmationSubmitState(false);
    });
    document.getElementById('confirm-dummy-pan-ack')?.addEventListener('change', function () {
        updateConfirmationSubmitState(false);
    });

    document.getElementById('confirmationModal')?.addEventListener('hidden.bs.modal', function () {
        const emailCheckbox = document.getElementById('confirm-no-email-notification');
        const dummyCheckbox = document.getElementById('confirm-dummy-pan-ack');
        const submitButton = document.getElementById('confirmSubmitButton');

        if (emailCheckbox) {
            emailCheckbox.checked = false;
        }
        if (dummyCheckbox) {
            dummyCheckbox.checked = false;
        }
        if (submitButton) {
            submitButton.disabled = false;
        }
    });

    // Function to show confirmation modal with form data
    function showConfirmationModal() {
        const panVerifiedInput = document.getElementById('pan_verified');
        if (!panVerifiedInput || panVerifiedInput.value !== '1') {
            const panLookupMessage = document.getElementById('panLookupMessage');
            if (panLookupMessage) {
                panLookupMessage.textContent = 'Please check PAN again before submitting.';
                panLookupMessage.className = 'mt-2 small text-danger';
            }
            return;
        }

        // First validate the form
        const form = document.querySelector('.needs-validation');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        // Get form values - use display when master found via PAN
        const masterId = document.getElementById('transporter_master_id')?.value;
        const name = masterId ? (document.getElementById('display_name')?.textContent || '-') : (document.getElementById('name')?.value || '-');
        const contactPerson = document.getElementById('contact_person_name')?.value || '-';
        const mobile = document.getElementById('mobile_number')?.value || '-';
        const gstNumber = masterId ? (document.getElementById('display_gst')?.textContent || '-') : (document.getElementById('gst_number')?.value || '-');
        const panNumber = masterId ? (document.getElementById('display_pan')?.textContent || '-') : (document.getElementById('pan_number')?.value || '-');
        const panActive = masterId ? (document.getElementById('display_pan_active')?.textContent || '-') : (document.querySelector('input[name="pan_active"]:checked')?.value === '1' ? 'Yes' : 'No');
        const address = masterId ? (document.getElementById('display_address')?.textContent || '-') : (document.getElementById('address')?.value || '-');
        const countrySelect = document.getElementById('country_id');
        const country = masterId ? (document.getElementById('display_location')?.textContent || '-') : (countrySelect?.options[countrySelect?.selectedIndex]?.text || '-');
        const stateSelect = document.getElementById('state_id');
        const state = masterId ? '-' : (stateSelect?.options[stateSelect?.selectedIndex]?.text || '-');
        const citySelect = document.getElementById('city_id');
        const city = masterId ? '-' : (citySelect?.options[citySelect?.selectedIndex]?.text || '-');
        const declaration = document.querySelector('input[name="declaration_given"]:checked')?.value === '1' ? 'Yes' : 'No';
        const totalTrucks = document.getElementById('total_trucks')?.value || '0';
        const advance = '0.00';

        // Populate modal with values
        document.getElementById('confirm-name').textContent = name;
        document.getElementById('confirm-contact-person').textContent = contactPerson;
        document.getElementById('confirm-mobile').textContent = mobile;

        document.getElementById('confirm-gst-number').textContent = gstNumber;

        document.getElementById('confirm-pan-number').textContent = panNumber;
        document.getElementById('confirm-pan-active').textContent = panActive;

        const tdsField = document.getElementById('tds_percentage');
        calculateTdsPercentageFromApi(panNumber, panActive, function (calculatedStr) {
            const submitted = parseFloat(tdsField?.value || '0');
            const calculated = parseFloat(calculatedStr);
            let text = (isNaN(submitted) ? '0' : submitted.toFixed(2)) + '%';
            const isDummyPan = panNumber !== '-' && /^[0-9]/.test((panNumber || '').trim());
            if (!isDummyPan && !isNaN(calculated) && Math.abs(submitted - calculated) > 0.01) {
                text += ' (calculated ' + calculated.toFixed(2) + '%)';
            }
            document.getElementById('confirm-tds-percentage').textContent = text;
        });

        document.getElementById('confirm-address').textContent = address;
        document.getElementById('confirm-country').textContent = country;
        document.getElementById('confirm-state').textContent = state;
        document.getElementById('confirm-city').textContent = city;

        document.getElementById('confirm-declaration').textContent = declaration;
        document.getElementById('confirm-total-trucks').textContent = totalTrucks;
        document.getElementById('confirm-advance').textContent = '' + parseFloat(advance).toFixed(2);

        updateNoEmailNotificationNotice();

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        modal.show();
    }

    // Function to calculate TDS percentage from API
    function calculateTdsPercentageFromApi(panNumber, panActive, onDone) {
        const panActiveValue = panActive === 'Yes' ? '1' : '0';
        const declarationGiven = document.querySelector('input[name="declaration_given"]:checked')?.value === '1';

        fetch('/api/calculate-truck-owner-tds-percentage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                pan_number: panNumber === '-' ? '' : panNumber,
                pan_active: panActiveValue,
                declaration_given: declarationGiven ? '1' : '0'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (typeof onDone === 'function') {
                onDone(data.tds_percentage);
            } else {
                document.getElementById('confirm-tds-percentage').textContent = data.tds_percentage + '%';
            }
        })
        .catch(error => {
            console.error('Error calculating TDS percentage:', error);
            document.getElementById('confirm-tds-percentage').textContent = '-';
        });
    }

    // Function to submit the form after confirmation
    function submitForm() {
        if (isNotificationEmailMissing()) {
            const checkbox = document.getElementById('confirm-no-email-notification');
            if (!checkbox?.checked) {
                return;
            }
        }

        if (isDummyPanSelected()) {
            const checkbox = document.getElementById('confirm-dummy-pan-ack');
            if (!checkbox?.checked) {
                return;
            }
        }

        const form = document.querySelector('.needs-validation');

        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        modal.hide();

        // Submit form
        form.submit();
    }

    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (() => {
        'use strict';

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation');

        // Loop over them and prevent submission
        Array.from(forms).forEach((form) => {
            form.addEventListener(
                'submit',
                (event) => {
                    // Allow form submission since we handle validation in showConfirmationModal
                    form.classList.add('was-validated');
                },
                false,
            );
        });
    })();
</script>
<script>
    // Modal form submissions with AJAX
    $(document).ready(function() {
        // Handle Country Modal Form Submission
        $('#addCountryForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            const errorDiv = $('#countryFormErrors');
            const countryName = $('#country_name').val();
            const countryCode = $('#country_code').val();
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorDiv.addClass('d-none');

            // Create FormData object
            const formData = new FormData();
            formData.append('name', countryName);
            formData.append('code', countryCode);
            formData.append('from_modal', '1');
            formData.append('_token', csrfToken);

            // Use jQuery AJAX instead of fetch for better FormData handling
            $.ajax({
                url: '{{ route("master.countries.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(data) {
                    if (data && data.success && data.country) {
                        // Add new country to the main dropdown
                        $('#country_id').append(`<option value="${data.country.id}">${data.country.name}</option>`);

                        // Add new country to modal dropdowns
                        $('#modal_state_country_id').append(`<option value="${data.country.id}">${data.country.name}</option>`);
                        $('#modal_city_country_id').append(`<option value="${data.country.id}">${data.country.name}</option>`);

                        // Select the newly created country
                        $('#country_id').val(data.country.id).trigger('change');

                        // Close modal and remove backdrop
                        $('#addCountryModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        errorDiv.html(errorMessages).removeClass('d-none');
                    } else {
                        errorDiv.html('An error occurred while adding the country.').removeClass('d-none');
                    }
                },
                complete: function() {
                    // Hide loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Handle State Modal Form Submission
        $('#addStateForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            const errorDiv = $('#stateFormErrors');
            const stateName = $('#state_name').val();
            const stateCode = $('#state_code').val();
            const countryId = $('#modal_state_country_id').val();
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorDiv.addClass('d-none');

            // Create FormData object
            const formData = new FormData();
            formData.append('name', stateName);
            formData.append('code', stateCode);
            formData.append('country_id', countryId);
            formData.append('from_modal', '1');
            formData.append('_token', csrfToken);

            // Use jQuery AJAX instead of fetch for better FormData handling
            $.ajax({
                url: '{{ route("master.states.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(data) {
                    if (data && data.success && data.state) {
                        // Update dropdowns
                        updateStateDropdowns(data.state);

                        // Select new state
                        document.getElementById('state_id').value = data.state.id;
                        document.getElementById('state_id').dispatchEvent(new Event('change'));

                        // Close modal and remove backdrop
                        $('#addStateModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        errorDiv.html(errorMessages).removeClass('d-none');
                    } else {
                        errorDiv.html('An error occurred while adding the state.').removeClass('d-none');
                    }
                },
                complete: function() {
                    // Hide loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Handle City Modal Form Submission
        $('#addCityForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            const errorDiv = $('#cityFormErrors');
            const cityName = $('#modal_city_name').val();
            const stateId = $('#modal_city_state_id').val();

            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorDiv.addClass('d-none');

            // Create FormData object
            const formData = new FormData();
            formData.append('name', cityName);
            formData.append('state_id', stateId);

            formData.append('from_modal', '1');
            formData.append('_token', csrfToken);

            // Use jQuery AJAX instead of fetch for better FormData handling
            $.ajax({
                url: '{{ route("master.cities.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(data) {
                    if (data && data.success && data.city) {
                        // Update dropdown
                        updateCityDropdown(data.city);

                        // Select new city
                        document.getElementById('city_id').value = data.city.id;
                        document.getElementById('city_id').dispatchEvent(new Event('change'));

                        // Close modal and remove backdrop
                        $('#addCityModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        errorDiv.html(errorMessages).removeClass('d-none');
                    } else {
                        errorDiv.html('An error occurred while adding the city.').removeClass('d-none');
                    }
                },
                complete: function() {
                    // Hide loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Handle country change in city modal
        $('#modal_city_country_id').on('change', function() {
            const countryId = $(this).val();
            const stateSelect = $('#modal_city_state_id');

            stateSelect.html('<option value="">Select State</option>');

            if (countryId) {
                $.get(`/api/states/${countryId}`)
                    .done(function(data) {
                        data.forEach(function(state) {
                            stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                        });
                    })
                    .fail(function() {
                        showErrorMessage('Error loading states');
                    });
            }
        });

        // Function to show success message
        function showSuccessMessage(message) {
            // Remove any existing alerts
            $('.alert-success').remove();

            // Create and show success alert
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            // Insert after the page title or at the top of the form
            if ($('.card-header').length) {
                $('.card-header').after(alertHtml);
            } else {
                $('form').before(alertHtml);
            }

            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('.alert-success').fadeOut();
            }, 5000);
        }

        // Function to show error message
        function showErrorMessage(message) {
            // Remove any existing alerts
            $('.alert-danger').remove();

            // Create and show error alert
            const alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            // Insert after the page title or at the top of the form
            if ($('.card-header').length) {
                $('.card-header').after(alertHtml);
            } else {
                $('form').before(alertHtml);
            }
        }
    });
</script>
<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (() => {
        'use strict';

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation');

        // Loop over them and prevent submission
        Array.from(forms).forEach((form) => {
            form.addEventListener(
                'submit',
                (event) => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                },
                false,
            );
        });
    })();
</script>
<script>
    // Country, State, City cascading selects
    const countrySelect = document.getElementById('country_id');
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');

    const oldCountry = "{{ old('country_id') }}";
    const oldState = "{{ old('state_id') }}";
    const oldCity = "{{ old('city_id') }}";

    // Fresh approach using fetch API for modal submissions
    document.addEventListener('DOMContentLoaded', function() {

        // Country Modal Handler
        const countryForm = document.getElementById('addCountryForm');
        if (countryForm) {
            countryForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const spinner = submitBtn.querySelector('.spinner-border');
                const errorDiv = document.getElementById('countryFormErrors');

                // Show loading
                submitBtn.disabled = true;
                spinner?.classList.remove('d-none');
                errorDiv?.classList.add('d-none');

                try {
                    const formData = new FormData(this);
                    formData.append('from_modal', '1');

                    const response = await fetch('{{ route("master.countries.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success && data.country) {
                        // Update all dropdowns
                        updateCountryDropdowns(data.country);

                        // Select new country
                        document.getElementById('country_id').value = data.country.id;
                        document.getElementById('country_id').dispatchEvent(new Event('change'));

                        // Clean close modal
                        closeModalClean('addCountryModal');

                        // Reset form
                        this.reset();
                    } else {
                        throw new Error(data.message || 'Failed to add country');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = error.message || 'An error occurred while adding the country.';
                        errorDiv.classList.remove('d-none');
                    }
                } finally {
                    submitBtn.disabled = false;
                    spinner?.classList.add('d-none');
                }
            });
        }

        // State Modal Handler
        const stateForm = document.getElementById('addStateForm');
        if (stateForm) {
            stateForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const spinner = submitBtn.querySelector('.spinner-border');
                const errorDiv = document.getElementById('stateFormErrors');

                // Show loading
                submitBtn.disabled = true;
                spinner?.classList.remove('d-none');
                errorDiv?.classList.add('d-none');

                try {
                    const formData = new FormData(this);
                    formData.append('from_modal', '1');

                    const response = await fetch('{{ route("master.states.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success && data.state) {
                        // Update dropdowns
                        updateStateDropdowns(data.state);

                        // Select new state
                        document.getElementById('state_id').value = data.state.id;
                        document.getElementById('state_id').dispatchEvent(new Event('change'));

                        // Clean close modal
                        closeModalClean('addStateModal');

                        // Reset form
                        this.reset();
                    } else {
                        throw new Error(data.message || 'Failed to add state');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = error.message || 'An error occurred while adding the state.';
                        errorDiv.classList.remove('d-none');
                    }
                } finally {
                    submitBtn.disabled = false;
                    spinner?.classList.add('d-none');
                }
            });
        }

        // City Modal Handler
        const cityForm = document.getElementById('addCityForm');
        if (cityForm) {
            cityForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const spinner = submitBtn.querySelector('.spinner-border');
                const errorDiv = document.getElementById('cityFormErrors');

                // Show loading
                submitBtn.disabled = true;
                spinner?.classList.remove('d-none');
                errorDiv?.classList.add('d-none');

                try {
                    const formData = new FormData(this);
                    formData.append('from_modal', '1');

                    const response = await fetch('{{ route("master.cities.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success && data.city) {
                        // Update dropdown
                        updateCityDropdown(data.city);

                        // Select new city
                        document.getElementById('city_id').value = data.city.id;
                        document.getElementById('city_id').dispatchEvent(new Event('change'));

                        // Clean close modal
                        closeModalClean('addCityModal');

                        // Reset form
                        this.reset();
                    } else {
                        throw new Error(data.message || 'Failed to add city');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = error.message || 'An error occurred while adding the city.';
                        errorDiv.classList.remove('d-none');
                    }
                } finally {
                    submitBtn.disabled = false;
                    spinner?.classList.add('d-none');
                }
            });
        }

        // Note: The country, state, and city cascading selects are handled by dynamic-dropdowns.js
        // No need for duplicate event listeners here

        // Handle city change with validation
        $('#city_id').on('change', function() {
            const cityId = $(this).val();
            const stateId = $('#state_id').val();
            const countryId = $('#country_id').val();

            // Clear validation errors
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();

            // Additional client-side validation
            if (cityId && (!stateId || !countryId)) {
                $(this).addClass('is-invalid');
                $(this).after('<div class="invalid-feedback">Please select country and state first.</div>');
            }
        });
    });

    // Helper functions for modal management
    function updateCountryDropdowns(country) {
        const dropdowns = ['country_id', 'modal_state_country_id', 'modal_city_country_id'];
        dropdowns.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                const option = document.createElement('option');
                option.value = country.id;
                option.textContent = country.name;
                select.appendChild(option);
            }
        });
    }

    function updateStateDropdowns(state) {
        const dropdowns = ['state_id', 'modal_city_state_id'];
        dropdowns.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                const option = document.createElement('option');
                option.value = state.id;
                option.textContent = state.name;
                select.appendChild(option);
            }
        });
    }

    function updateCityDropdown(city) {
        const select = document.getElementById('city_id');
        if (select) {
            const option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.name;
            select.appendChild(option);
        }
    }

    function closeModalClean(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Use Bootstrap's modal instance
            const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
            bsModal.hide();

            // Clean up after hide completes
            modal.addEventListener('hidden.bs.modal', function cleanup() {
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                modal.removeEventListener('hidden.bs.modal', cleanup);
            }, { once: true });
        }
    }
</script>

<script src="/js/dynamic-dropdowns.js?v={{ config('app.ASSET_VERSION') }}"></script>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to convert input to uppercase
        function convertToUpperCase(input) {
            input.value = input.value.toUpperCase();
        }

        // PAN Number field
        const panField = document.getElementById('pan_number');
        if (panField) {
            panField.addEventListener('input', function() {
                convertToUpperCase(this);
            });
        }

        // GST Number field
        const gstField = document.getElementById('gst_number');
        if (gstField) {
            gstField.addEventListener('input', function() {
                convertToUpperCase(this);
            });
        }
    });
</script>
@endpush

@endsection
