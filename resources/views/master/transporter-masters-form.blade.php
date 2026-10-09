@extends('layouts.app')

@section('content')
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form
        action="{{ $transporterMaster ? route('master.transporter-masters.update', $transporterMaster) : route('master.transporter-masters.store') }}"
        method="POST"
        class="needs-validation"
        novalidate
    >
        @csrf
        @if($transporterMaster)
            @method('PUT')
        @endif

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-person-badge me-1"></i> {{ $transporterMaster ? 'Edit Transporter Master' : 'Add Transporter Master' }}
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('master.transporter-masters.index') }}" class="btn app-index-header-btn" aria-label="Back to transporter masters">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($transporterMaster && $associatedCompanies->isNotEmpty())
                    <div class="app-form-section">
                        <div class="app-form-section__header app-form-section__header--muted">
                            <h5 class="app-form-section__title"><i class="bi bi-buildings"></i> Associated Companies</h5>
                        </div>
                        <div class="app-form-section__body">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($associatedCompanies as $company)
                                    <span class="badge bg-secondary">{{ $company->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-building"></i> Transporter Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label for="name" class="form-label">Transport Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="{{ old('name', $transporterMaster?->name) }}">
                                @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="{{ old('email', $transporterMaster?->email) }}">
                                @error('email') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="pan_number" class="form-label">PAN Number *</label>
                                <input type="text" class="form-control text-uppercase" id="pan_number" name="pan_number" required maxlength="10"
                                       value="{{ old('pan_number', $transporterMaster?->pan_number) }}">
                                @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label for="gst_number" class="form-label">GST Number</label>
                                <input type="text" class="form-control text-uppercase" id="gst_number" name="gst_number" maxlength="15"
                                       value="{{ old('gst_number', $transporterMaster?->gst_number) }}">
                                @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="gst_type" class="form-label">GST Type</label>
                                <select class="form-select" id="gst_type" name="gst_type">
                                    <option value="">Select GST Type</option>
                                    <option value="composition" {{ old('gst_type', $transporterMaster?->gst_type) === 'composition' ? 'selected' : '' }}>Composition</option>
                                    <option value="regular" {{ old('gst_type', $transporterMaster?->gst_type) === 'regular' ? 'selected' : '' }}>Regular</option>
                                </select>
                                @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">PAN is Active *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pan_active" id="pan_active_yes" value="1"
                                           {{ old('pan_active', $transporterMaster?->pan_active ? '1' : '0') === '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pan_active_yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pan_active" id="pan_active_no" value="0"
                                           {{ old('pan_active', $transporterMaster?->pan_active ? '1' : '0') === '0' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pan_active_no">No</label>
                                </div>
                                @error('pan_active') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label d-block">MSME Status</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" name="is_msme" id="is_msme" value="1"
                                           {{ old('is_msme', $transporterMaster?->is_msme) ? 'checked' : '' }}
                                           onchange="document.getElementById('msme_reg_container').style.display = this.checked ? 'block' : 'none'">
                                    <label class="form-check-label small" for="is_msme">MSME Registered</label>
                                </div>
                            </div>
                            <div class="col-md-3" id="msme_reg_container" style="{{ old('is_msme', $transporterMaster?->is_msme) ? '' : 'display:none;' }}">
                                <label for="msme_reg_no" class="form-label">Udyam / MSME Reg #</label>
                                <input type="text" class="form-control" id="msme_reg_no" name="msme_reg_no"
                                       value="{{ old('msme_reg_no', $transporterMaster?->msme_reg_no) }}" placeholder="UDYAM-XX-00-0000000">
                                @error('msme_reg_no') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label for="country_id" class="form-label">Country</label>
                                <select class="form-select" id="country_id" name="country_id">
                                    <option value="">Select Country</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}"
                                            {{ (string) old('country_id', $transporterMaster?->country_id) === (string) $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="state_id" class="form-label">State</label>
                                <select class="form-select" id="state_id" name="state_id">
                                    <option value="">Select State</option>
                                </select>
                                @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="city_id" class="form-label">City</label>
                                <select class="form-select" id="city_id" name="city_id">
                                    <option value="">Select City</option>
                                </select>
                                @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $transporterMaster?->address) }}</textarea>
                                @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body">
                <a href="{{ route('master.transporter-masters.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-brand-primary" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Save
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const countrySelect = document.getElementById('country_id');
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');
    const selectedStateId = @json(old('state_id', $transporterMaster?->state_id));
    const selectedCityId = @json(old('city_id', $transporterMaster?->city_id));

    function loadStates(countryId, preselectStateId = null) {
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';

        if (!countryId) {
            return;
        }

        fetch(`/api/states/${countryId}`)
            .then(response => response.json())
            .then(states => {
                states.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.id;
                    option.textContent = state.name;
                    if (preselectStateId && Number(preselectStateId) === Number(state.id)) {
                        option.selected = true;
                    }
                    stateSelect.appendChild(option);
                });

                if (preselectStateId) {
                    loadCities(preselectStateId, selectedCityId);
                }
            });
    }

    function loadCities(stateId, preselectCityId = null) {
        citySelect.innerHTML = '<option value="">Select City</option>';

        if (!stateId) {
            return;
        }

        fetch(`/api/cities/${stateId}`)
            .then(response => response.json())
            .then(cities => {
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    if (preselectCityId && Number(preselectCityId) === Number(city.id)) {
                        option.selected = true;
                    }
                    citySelect.appendChild(option);
                });
            });
    }

    countrySelect.addEventListener('change', function () {
        loadStates(this.value);
    });

    stateSelect.addEventListener('change', function () {
        loadCities(this.value);
    });

    if (countrySelect.value) {
        loadStates(countrySelect.value, selectedStateId);
    }

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
});
</script>
@endsection
