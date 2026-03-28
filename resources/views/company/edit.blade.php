
@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
<div class="card card-info card-outline mb-4">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Register Company</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form action="{{ route('company.update', $company->id) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate="">
        @csrf
        @method('PUT')
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <!--begin::Body-->
        <div class="card-body">
            <style>
                .invoice-preview-col {
                    border-radius: 8px;
                    padding: 6px;
                    transition: box-shadow .15s ease, border-color .15s ease;
                    border: 2px solid transparent;
                }
                .invoice-preview-col.is-selected {
                    border-color: var(--bs-primary);
                    box-shadow: 0 0 0 1px rgba(13, 110, 253, .2);
                }
                .invoice-preview-frame iframe {
                    display: block;
                    width: 100%;
                    height: 420px;
                    border: 0;
                    background: #fff;
                }
            </style>
            <!--begin::Row-->
            <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="name" class="form-label">Company Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $company->name) }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="short_name" class="form-label">Short Name</label>
                    <input
                        type="text"
                        class="form-control"
                        id="short_name"
                        name="short_name"
                        value="{{ $company->short_name }}"
                        disabled>
                    @error('short_name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="gst_number" class="form-label">GST Number</label>
                    <input type="text" class="form-control" id="gst_number" name="gst_number"  value="{{ old('gst_number', $company->gst_number) }}">
                    @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input type="text" class="form-control" id="pan_number" name="pan_number" required value="{{ old('pan_number', $company->pan_number) }}">
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="gst_scheme" class="form-label">GST Scheme</label>
                    @if($hasInvoicesInFy ?? false)
                        <input type="hidden" name="gst_scheme" value="{{ old('gst_scheme', $company->gst_scheme) }}">
                        <select class="form-select" id="gst_scheme" disabled>
                            <option value="reverse charge" {{ old('gst_scheme', $company->gst_scheme) == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                            <option value="forward charge" {{ old('gst_scheme', $company->gst_scheme) == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                        </select>
                        <small class="form-text text-warning">
                            <i class="fas fa-lock"></i> Cannot change GST scheme. Invoices have been created for the current financial year (April {{ $fyStart->year }} - March {{ $fyEnd->year }}).
                        </small>
                    @else
                        <select class="form-select" id="gst_scheme" name="gst_scheme" required value="{{ old('gst_scheme', $company->gst_scheme) }}">
                            <option value="reverse charge" {{ old('gst_scheme', $company->gst_scheme) == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                            <option value="forward charge" {{ old('gst_scheme', $company->gst_scheme) == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                        </select>
                    @endif
                    @error('gst_scheme') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                 <div class="col-md-6">
                    <label for="gst_type" class="form-label">GST Type *</label>
                    <input type="text" class="form-control" id="gst_type" name="gst_type" required value="Regular" disabled>
                    @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" required>{{ old('address', $company->address) }}</textarea>
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="country_id" class="form-label">Country</label>
                    <select class="form-select" id="country_id" name="country_id" required onchange="fetchStates(this.value)">
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option {{ old('country_id', $company->country_id) == $country->id ? 'selected' : '' }} value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="state_id" class="form-label">State</label>
                    <select class="form-select" id="state_id" name="state_id" required onchange="fetchCities(this.value)">
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option {{ old('state_id', $company->state_id) == $state->id ? 'selected' : '' }} value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="city_id" class="form-label">City</label>
                    <select class="form-select" id="city_id" name="city_id" required>
                        <option value="">Select City</option>
                        @foreach($cities as $city)
                            <option {{ old('city_id', $company->city_id) == $city->id ? 'selected' : '' }} value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="pin_code" class="form-label">Pin Code</label>
                    <input type="text" class="form-control" id="pin_code" name="pin_code" required value="{{ old('pin_code', $company->pin_code) }}">
                    @error('pin_code') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" required value="{{ old('contact_person', $company->contact_person) }}">
                    @error('contact_person') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" required value="{{ old('contact_number', $company->contact_number) }}">
                    @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="company_type" class="form-label">Company Type</label>
                    <select class="form-select" id="company_type" name="company_type" required>
                        <option value="">Select Company Type</option>
                        <option value="Broker" {{ old('company_type', $company->company_type) == 'Broker' ? 'selected' : '' }}>Broker</option>
                        <option value="Agency" {{ old('company_type', $company->company_type) == 'Agency' ? 'selected' : '' }}>Agency</option>
                        <option value="Both" {{ old('company_type', $company->company_type) == 'Both' ? 'selected' : '' }}>Both</option>
                    </select>
                    @error('company_type') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>

                </div>
                <!--end::Col-->
                <div class="col-md-6">
                    <label for="invoice_template" class="form-label">Invoice Template</label>
                    <select class="form-select" id="invoice_template" name="invoice_template" required>
                        <option value="classic" {{ old('invoice_template', $company->invoice_template ?? 'classic') == 'classic' ? 'selected' : '' }}>A (Classic)</option>
                        <option value="modern" {{ old('invoice_template', $company->invoice_template) == 'modern' ? 'selected' : '' }}>B (Modern)</option>
                        <option value="compact" {{ old('invoice_template', $company->invoice_template) == 'compact' ? 'selected' : '' }}>C (Compact)</option>
                    </select>
                    @error('invoice_template') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <div class="col-12">
                    <div class="border rounded-3 p-3 mb-3 bg-body-secondary bg-opacity-25">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-1">
                            <div>
                                <span class="fw-semibold text-dark d-block">
                                    <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                    Party Bill — invoice format (preview)
                                </span>
                                <p class="small text-muted mb-0 mt-2">
                                    <strong>View PDF</strong> opens the same PDF as Party Bill export (DomPDF). The iframe above is a quick preview; <strong>View full page</strong> opens the HTML in a new tab.
                                    Choose A / B / C in <strong>Invoice Template</strong> — that is the default for exports.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3" id="invoiceTemplatePreviewRow">
                        <div class="col-12 col-lg-4 invoice-preview-col" data-template-key="classic">
                            <div class="invoice-preview-frame border rounded overflow-hidden bg-light">
                                <iframe title="Classic invoice preview" loading="lazy"
                                    src="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'classic']) }}"></iframe>
                            </div>
                            <div class="small fw-semibold mt-2">A (Classic)</div>
                            <a href="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'classic', 'format' => 'pdf']) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-danger w-100 mt-2">
                                <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
                            </a>
                            {{-- <a href="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'classic']) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-outline-secondary w-100 mt-1">
                                <i class="bi bi-window me-1"></i> View full page
                            </a> --}}
                        </div>
                        <div class="col-12 col-lg-4 invoice-preview-col" data-template-key="modern">
                            <div class="invoice-preview-frame border rounded overflow-hidden bg-light">
                                <iframe title="Modern invoice preview" loading="lazy"
                                    src="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'modern']) }}"></iframe>
                            </div>
                            <div class="small fw-semibold mt-2">B (Modern)</div>
                            <a href="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'modern', 'format' => 'pdf']) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-danger w-100 mt-2">
                                <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
                            </a>
                            {{-- <a href="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'modern']) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-outline-secondary w-100 mt-1">
                                <i class="bi bi-window me-1"></i> View full page
                            </a> --}}
                        </div>
                        <div class="col-12 col-lg-4 invoice-preview-col" data-template-key="compact">
                            <div class="invoice-preview-frame border rounded overflow-hidden bg-light">
                                <iframe title="Compact invoice preview" loading="lazy"
                                    src="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'compact']) }}"></iframe>
                            </div>
                            <div class="small fw-semibold mt-2">C (Compact)</div>
                            <a href="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'compact', 'format' => 'pdf']) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-danger w-100 mt-2">
                                <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
                            </a>
                            {{-- <a href="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'compact']) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-outline-secondary w-100 mt-1">
                                <i class="bi bi-window me-1"></i> View full page
                            </a> --}}
                        </div>
                    </div>
                    {{-- <small class="text-muted d-block mt-3">
                        <strong>Tip:</strong> Dropdown se jo template select hai, wahi column highlight hota hai. Column par click karne se wahi template dropdown me select ho jata hai.
                    </small> --}}
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="company_logo" class="form-label">Company Logo</label>
                    <input type="file" class="form-control" id="company_logo" name="company_logo">
                    <img src="{{ asset('storage/' . $company->company_logo) }}" alt="Company Logo" style="width: 100px; height: 100px;">
                    @error('company_logo') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="gst_slab" class="form-label">GST Slab</label>
                    <select class="form-select" id="gst_slab" name="gst_slab" required>
                        <option value="{{ old('gst_slab', $company->gst_slab) }}">Select GST Slab</option>
                        @foreach($companySlabs as $slab)
                            <option value="{{ trim($slab) }}" {{ old('gst_slab', $company->gst_slab) == trim($slab) ? 'selected' : '' }}>
                                {{ trim($slab) }}%
                            </option>
                        @endforeach
                    </select>

                    @error('gst_slab')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
        </div>
        <!--end::Body-->
        <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-success" type="submit">Update Company</button>
            <a href="{{ route('company.index') }}" class="btn btn-brand-primary">Back to list</a>
        </div>
        <!--end::Footer-->
    </form>
    <!--end::Form-->
    <!--begin::JavaScript-->
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
        function fetchStates(countryId) {
            fetch(`/api/states/${countryId}`)
                .then(response => response.json())
                .then(data => {
                    const stateSelect = document.getElementById('state_id');
                    stateSelect.innerHTML = '<option value="">Select State</option>';
                    data.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.id;
                        option.textContent = state.name;
                        stateSelect.appendChild(option);
                    });
                    // Clear city dropdown
                    const citySelect = document.getElementById('city_id');
                    citySelect.innerHTML = '<option value="">Select City</option>';
                })
                .catch(error => console.error('Error fetching states:', error));
        }
        function fetchCities(stateId) {
            fetch(`/api/cities/${stateId}`)
                .then(response => response.json())
                .then(data => {
                    const citySelect = document.getElementById('city_id');
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    data.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        citySelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching cities:', error));
        }

        document.addEventListener('DOMContentLoaded', function () {
            const invoiceTemplateSelect = document.getElementById('invoice_template');
            const previewCols = Array.from(document.querySelectorAll('#invoiceTemplatePreviewRow .invoice-preview-col'));

            if (!invoiceTemplateSelect || !previewCols.length) {
                return;
            }

            const syncTemplateSelection = (key) => {
                const selectedKey = key || 'classic';
                invoiceTemplateSelect.value = selectedKey;
                previewCols.forEach((col) => {
                    col.classList.toggle('is-selected', col.dataset.templateKey === selectedKey);
                });
            };

            syncTemplateSelection(invoiceTemplateSelect.value);
            invoiceTemplateSelect.addEventListener('change', function () {
                syncTemplateSelection(invoiceTemplateSelect.value);
            });

            previewCols.forEach((col) => {
                col.style.cursor = 'pointer';
                col.addEventListener('click', function () {
                    syncTemplateSelection(col.dataset.templateKey);
                });
            });
        });
    </script>
    <!--end::JavaScript-->
        </div>
    </div>
</div>
@endsection

