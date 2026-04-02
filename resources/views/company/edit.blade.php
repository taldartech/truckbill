
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
                    border-radius: 12px;
                    padding: 10px;
                    transition: box-shadow .15s ease, border-color .15s ease, transform .15s ease;
                    border: 2px solid #dee2e6;
                    background: #fff;
                    position: relative;
                    cursor: pointer;
                    height: 100%;
                }
                .invoice-preview-col.is-selected {
                    border-color: var(--bs-primary);
                    box-shadow: 0 12px 22px rgba(13, 110, 253, .18);
                    transform: translateY(-2px);
                }
                .invoice-layout-shell {
                    border: 1px solid #dbeafe;
                    border-radius: 14px;
                    padding: 14px;
                    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
                }
                .invoice-layout-topbar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 12px;
                }
                .invoice-layout-selected {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 12px;
                    font-weight: 600;
                    color: #1d4ed8;
                    background: #eaf2ff;
                    border: 1px solid #bfdbfe;
                    border-radius: 999px;
                    padding: 5px 10px;
                }
                .invoice-template-meta {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 8px;
                    margin-top: 10px;
                }
                .invoice-template-name {
                    font-weight: 600;
                    color: #212529;
                }
                .invoice-template-use {
                    font-size: 12px;
                    color: #6c757d;
                    margin-top: 2px;
                }
                .invoice-selected-badge {
                    display: none;
                }
                .invoice-preview-col.is-selected .invoice-selected-badge {
                    display: inline-flex;
                }
                .invoice-preview-frame {
                    height: 55vh;
                    max-height: 470px;
                    min-height: 320px;
                    background: #fff;
                    border: 1px solid #cbd5e1;
                    box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
                    position: relative;
                }
                .invoice-preview-frame.is-empty {
                    height: auto;
                    min-height: 140px;
                }
                .invoice-preview-placeholder {
                    display: none;
                    align-items: center;
                    justify-content: center;
                    height: 100%;
                    padding: 14px;
                    color: #6c757d;
                    font-weight: 600;
                    text-align: center;
                    background: #fff;
                    z-index: 2;
                }
                .invoice-preview-frame.is-empty .invoice-preview-placeholder {
                    display: flex;
                }
                .invoice-preview-frame iframe {
                    display: block;
                    width: 100%;
                    height: 100%;
                    border: 0;
                    background: #fff;
                }
                .invoice-preview-frame.is-empty iframe {
                    display: none;
                }
                .invoice-option-letter {
                    width: 28px;
                    height: 28px;
                    border-radius: 999px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: #f1f3f5;
                    font-weight: 700;
                    color: #495057;
                }
                .invoice-preview-col.is-selected .invoice-option-letter {
                    background: var(--bs-primary);
                    color: #fff;
                }
                .invoice-preview-col:hover {
                    border-color: #93c5fd;
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
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-3">
              
                <input type="hidden" id="invoice_template" name="invoice_template" value="{{ old('invoice_template', $company->invoice_template ?? 'classic') }}">
                <div class="col-12">
                    <div class="invoice-layout-shell">
                        <div class="invoice-layout-topbar">
                            <span class="fw-semibold text-dark">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                Party Bill Layout Studio
                            </span>
                            <span class="invoice-layout-selected">
                                <i class="bi bi-stars"></i>
                                Selected: <span id="selectedTemplateLabelEdit">A (Classic)</span>
                            </span>
                        </div>
                        <p class="small text-muted mb-3">
                            Select a card to load live preview. Preview starts only after selection for faster page load.
                        </p>
                        <div class="invoice-preview-frame rounded overflow-hidden bg-light mb-3 is-empty" id="invoicePreviewContainerEdit">
                            <div class="invoice-preview-placeholder" id="invoicePreviewPlaceholderEdit">
                                Select a template card (A / B / C) to load the live preview.
                            </div>
                            <iframe id="selectedInvoiceTemplateIframe" title="Selected invoice preview" loading="lazy"
                                src="about:blank"></iframe>
                        </div>
                        <a href="#"
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-danger w-100 mb-3" id="selectedInvoicePdfButton" style="pointer-events:none; opacity:.6;">
                            <i class="bi bi-file-earmark-pdf me-1"></i> View Selected Template PDF
                        </a>
                        <div class="row g-3" id="invoiceTemplatePreviewRow">
                            <div class="col-12 col-lg-4 invoice-preview-col"
                                data-template-key="classic"
                                data-template-label="A (Classic)"
                                data-preview-url="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'classic']) }}"
                                data-pdf-url="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'classic', 'format' => 'pdf']) }}">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">A</span> Classic</div>
                                        <div class="invoice-template-use">Balanced layout with clear sections</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 invoice-preview-col"
                                data-template-key="modern"
                                data-template-label="B (Modern)"
                                data-preview-url="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'modern']) }}"
                                data-pdf-url="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'modern', 'format' => 'pdf']) }}">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">B</span> Modern</div>
                                        <div class="invoice-template-use">Prominent heading and visual emphasis</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 invoice-preview-col"
                                data-template-key="compact"
                                data-template-label="C (Compact)"
                                data-preview-url="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'compact']) }}"
                                data-pdf-url="{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'compact', 'format' => 'pdf']) }}">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">C</span> Compact</div>
                                        <div class="invoice-template-use">Dense layout to fit more details</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-3">
                            <strong>Tip:</strong> Click any card to set it as selected. You can also change template from dropdown.
                        </small>
                    </div>
                </div>
                <!--end::Col-->
                <input type="hidden" id="lr_copy_template" name="lr_copy_template" value="{{ old('lr_copy_template', $company->lr_copy_template ?? 'classic') }}">
                <div class="col-12">
                    <div class="invoice-layout-shell">
                        <div class="invoice-layout-topbar">
                            <span class="fw-semibold text-dark">
                                <i class="bi bi-truck text-primary me-1"></i>
                                Good Receipt (Bilty) LR Copy Layout
                            </span>
                            <span class="invoice-layout-selected">
                                <i class="bi bi-stars"></i>
                                Selected: <span id="selectedLrTemplateLabelEdit">A (Classic)</span>
                            </span>
                        </div>
                        <p class="small text-muted mb-3">
                            Template used for LR copy PDF from the Good Receipt list and LR copy report.
                        </p>
                        <div class="invoice-preview-frame rounded overflow-hidden bg-light mb-3 is-empty" id="lrPreviewContainerEdit">
                            <div class="invoice-preview-placeholder" id="lrPreviewPlaceholderEdit">
                                Select a template card (A / B / C) to load the live preview.
                            </div>
                            <iframe id="selectedLrTemplateIframe" title="Selected LR copy preview" loading="lazy"
                                src="about:blank"></iframe>
                        </div>
                        <a href="#"
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-danger w-100 mb-3" id="selectedLrPdfButton" style="pointer-events:none; opacity:.6;">
                            <i class="bi bi-file-earmark-pdf me-1"></i> View Selected Template PDF
                        </a>
                        <div class="row g-3" id="lrTemplatePreviewRow">
                            <div class="col-12 col-lg-4 lr-preview-col invoice-preview-col"
                                data-template-key="classic"
                                data-template-label="A (Classic)"
                                data-preview-url="{{ route('company.lr-copy-template-preview', ['company' => $company->id, 'template' => 'classic']) }}"
                                data-pdf-url="{{ route('company.lr-copy-template-preview', ['company' => $company->id, 'template' => 'classic', 'format' => 'pdf']) }}">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">A</span> Classic</div>
                                        <div class="invoice-template-use">Default — original table bilty layout</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 lr-preview-col invoice-preview-col"
                                data-template-key="modern"
                                data-template-label="B (Modern)"
                                data-preview-url="{{ route('company.lr-copy-template-preview', ['company' => $company->id, 'template' => 'modern']) }}"
                                data-pdf-url="{{ route('company.lr-copy-template-preview', ['company' => $company->id, 'template' => 'modern', 'format' => 'pdf']) }}">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">B</span> Modern</div>
                                        <div class="invoice-template-use">Form A — green card layout</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 lr-preview-col invoice-preview-col"
                                data-template-key="compact"
                                data-template-label="C (Compact)"
                                data-preview-url="{{ route('company.lr-copy-template-preview', ['company' => $company->id, 'template' => 'compact']) }}"
                                data-pdf-url="{{ route('company.lr-copy-template-preview', ['company' => $company->id, 'template' => 'compact', 'format' => 'pdf']) }}">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">C</span> Compact</div>
                                        <div class="invoice-template-use">Form C — compact amber</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-3">
                            <strong>Tip:</strong> Click a card to select; LR copy downloads use this template unless the report overrides it.
                        </small>
                    </div>
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
            const selectedIframe = document.getElementById('selectedInvoiceTemplateIframe');
            const selectedPdfButton = document.getElementById('selectedInvoicePdfButton');
            const selectedTemplateLabel = document.getElementById('selectedTemplateLabelEdit');
            const invoicePreviewContainerEdit = document.getElementById('invoicePreviewContainerEdit');

            if (!invoiceTemplateSelect || !previewCols.length) {
                return;
            }

            const syncTemplateSelection = (key, shouldLoadPreview = false) => {
                const selectedKey = key || 'classic';
                invoiceTemplateSelect.value = selectedKey;
                previewCols.forEach((col) => {
                    col.classList.toggle('is-selected', col.dataset.templateKey === selectedKey);
                });

                const selectedCol = previewCols.find((col) => col.dataset.templateKey === selectedKey);
                if (selectedTemplateLabel && selectedCol?.dataset.templateLabel) {
                    selectedTemplateLabel.textContent = selectedCol.dataset.templateLabel;
                }
                if (shouldLoadPreview && selectedIframe) {
                    selectedIframe.src = selectedCol?.dataset.previewUrl
                        || `{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'classic']) }}`;
                }
                if (shouldLoadPreview && selectedPdfButton) {
                    selectedPdfButton.href = selectedCol?.dataset.pdfUrl
                        || `{{ route('company.invoice-template-preview', ['company' => $company->id, 'template' => 'classic', 'format' => 'pdf']) }}`;
                }
            };

            syncTemplateSelection(invoiceTemplateSelect.value);

            previewCols.forEach((col) => {
                col.addEventListener('click', function () {
                    syncTemplateSelection(col.dataset.templateKey, true);
                    if (invoicePreviewContainerEdit) {
                        invoicePreviewContainerEdit.classList.remove('is-empty');
                    }
                    if (selectedPdfButton) {
                        selectedPdfButton.style.pointerEvents = 'auto';
                        selectedPdfButton.style.opacity = '1';
                    }
                });
            });

            const lrTemplateSelect = document.getElementById('lr_copy_template');
            const lrPreviewCols = Array.from(document.querySelectorAll('#lrTemplatePreviewRow .lr-preview-col'));
            const selectedLrIframe = document.getElementById('selectedLrTemplateIframe');
            const selectedLrPdfButton = document.getElementById('selectedLrPdfButton');
            const selectedLrTemplateLabel = document.getElementById('selectedLrTemplateLabelEdit');
            const lrPreviewContainerEdit = document.getElementById('lrPreviewContainerEdit');

            if (lrTemplateSelect && lrPreviewCols.length) {
                const syncLrTemplateSelection = (key, shouldLoadPreview = false) => {
                    const selectedKey = key || 'classic';
                    lrTemplateSelect.value = selectedKey;
                    lrPreviewCols.forEach((col) => {
                        col.classList.toggle('is-selected', col.dataset.templateKey === selectedKey);
                    });
                    const selectedCol = lrPreviewCols.find((col) => col.dataset.templateKey === selectedKey);
                    if (selectedLrTemplateLabel && selectedCol?.dataset.templateLabel) {
                        selectedLrTemplateLabel.textContent = selectedCol.dataset.templateLabel;
                    }
                    if (shouldLoadPreview && selectedLrIframe) {
                        selectedLrIframe.src = selectedCol?.dataset.previewUrl
                            || `{{ route('company.lr-copy-template-preview', ['company' => $company->id, 'template' => 'classic']) }}`;
                    }
                    if (shouldLoadPreview && selectedLrPdfButton) {
                        selectedLrPdfButton.href = selectedCol?.dataset.pdfUrl
                            || `{{ route('company.lr-copy-template-preview', ['company' => $company->id, 'template' => 'classic', 'format' => 'pdf']) }}`;
                    }
                };

                syncLrTemplateSelection(lrTemplateSelect.value);

                lrPreviewCols.forEach((col) => {
                    col.addEventListener('click', function () {
                        syncLrTemplateSelection(col.dataset.templateKey, true);
                        if (lrPreviewContainerEdit) {
                            lrPreviewContainerEdit.classList.remove('is-empty');
                        }
                        if (selectedLrPdfButton) {
                            selectedLrPdfButton.style.pointerEvents = 'auto';
                            selectedLrPdfButton.style.opacity = '1';
                        }
                    });
                });
            }
        });
    </script>
    <!--end::JavaScript-->
        </div>
    </div>
</div>
@endsection

