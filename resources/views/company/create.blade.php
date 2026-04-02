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
    <form action="{{ route('company.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate="">
        @csrf
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
                    <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="short_name" class="form-label">Short Name *</label>
                    <input type="text"
                        class="form-control @error('short_name') is-invalid @enderror"
                        id="short_name"
                        name="short_name"
                        required
                        value="{{ old('short_name') }}">
                    @error('short_name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>

                    <div class="text-muted small mt-2" id="invoice-preview">
                        Example Invoice Number: <span id="invoicePreviewText"></span>
                    </div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="gst_number" class="form-label">GST Number</label>
                    <input type="text" class="form-control" id="gst_number" name="gst_number"  value="{{ old('gst_number') }}">
                    @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input type="text" class="form-control" id="pan_number" name="pan_number" required value="{{ old('pan_number') }}">
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="gst_scheme" class="form-label">GST Scheme</label>
                    <select class="form-select" id="gst_scheme" name="gst_scheme" required>

                        <option value="reverse charge" {{ old('gst_scheme') == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                        <option value="forward charge" selected {{ old('gst_scheme') == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                    </select>
                    @error('gst_scheme') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                 {{-- <div class="col-md-6">
                    <label for="gst_type" class="form-label">GST Type *</label>
                    <input type="text" class="form-control" id="gst_type" name="gst_type" value="Regular" disabled>
                    @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
                </div> --}}
                <div class="col-md-6">
                    <label for="gst_type" class="form-label">GST Type</label>
                    <select class="form-select" id="gst_type" name="gst_type" required>
                        <option value="">Select</option>
                        <option value="regular" {{ old('gst_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                        <option value="composition" {{ old('gst_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                    </select>
                    @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" required>{{ old('address') }}</textarea>
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="country_id" class="form-label">Country</label>
                    <select class="form-select" id="country_id" name="country_id" required onchange="fetchStates(this.value)">
                        <option value="">Select Country</option>
                        @foreach(\App\Models\Country::all() as $country)
                            <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
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
                    </select>
                    @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="pin_code" class="form-label">Pin Code</label>
                    <input type="text" class="form-control" id="pin_code" name="pin_code" required value="{{ old('pin_code') }}">
                    @error('pin_code') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" required value="{{ old('contact_person') }}">
                    @error('contact_person') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" required   value="{{ old('contact_number') }}">
                    @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
                <div class="col-md-6">
                    <label for="company_type" class="form-label">Company Type</label>
                    <select class="form-select" id="company_type" name="company_type" required>
                        <option value="">Select Company Type</option>
                        <option value="Broker" {{ old('company_type') == 'Broker' ? 'selected' : '' }}>Broker</option>
                        <option value="Agency" {{ old('company_type') == 'Agency' ? 'selected' : '' }}>Agency</option>
                        <option value="Both" {{ old('company_type') == 'Both' ? 'selected' : '' }}>Both</option>
                    </select>
                    @error('company_type') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>

            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-3">
                
                <!--end::Col-->
                <input type="hidden" id="invoice_template" name="invoice_template" value="{{ old('invoice_template', 'classic') }}">
                <input type="hidden" id="lr_copy_template" name="lr_copy_template" value="{{ old('lr_copy_template', 'classic') }}">
                <div class="col-12">
                    <div class="invoice-layout-shell">
                        <div class="invoice-layout-topbar">
                            <span class="fw-semibold text-dark">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                Party Bill Layout Studio
                            </span>
                            <span class="invoice-layout-selected">
                                <i class="bi bi-stars"></i>
                                Selected: <span id="selectedTemplateLabelCreate">A (Classic)</span>
                            </span>
                        </div>
                        <p class="small text-muted mb-3">
                            Select a card to load live preview. Preview starts only after selection for faster form load.
                        </p>
                        <div class="invoice-preview-frame rounded overflow-hidden bg-light mb-3 is-empty" id="invoicePreviewContainerCreate">
                            <div class="invoice-preview-placeholder" id="invoicePreviewPlaceholderCreate">
                                Select a template card (A / B / C) to load the live preview.
                            </div>
                            <iframe class="invoice-demo-iframe" id="selectedInvoiceDemoIframe" data-template="classic" title="Selected invoice preview" loading="lazy"
                                src="about:blank"></iframe>
                        </div>
                        <a href="#"
                            data-template="classic"
                            target="_blank" rel="noopener noreferrer"
                            class="btn btn-danger w-100 mb-3 invoice-open-pdf-demo" id="selectedInvoicePdfButton" style="pointer-events:none; opacity:.6;">
                            <i class="bi bi-file-earmark-pdf me-1"></i> View Selected Template PDF
                        </a>
                        <div class="row g-3" id="invoiceTemplatePreviewRow">
                            <div class="col-12 col-lg-4 invoice-preview-col" data-template-key="classic" data-template-label="A (Classic)">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">A</span> Classic</div>
                                        <div class="invoice-template-use">Balanced layout with clear sections</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 invoice-preview-col" data-template-key="modern" data-template-label="B (Modern)">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">B</span> Modern</div>
                                        <div class="invoice-template-use">Prominent heading and visual emphasis</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 invoice-preview-col" data-template-key="compact" data-template-label="C (Compact)">
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
                            <strong>Tip:</strong> Click any card to set it as selected. Demo previews auto-refresh when company fields are updated.
                        </small>
                    </div>
                </div>
                <!--end::Col-->
                <div class="col-12">
                    <div class="invoice-layout-shell">
                        <div class="invoice-layout-topbar">
                            <span class="fw-semibold text-dark">
                                <i class="bi bi-truck text-primary me-1"></i>
                                Good Receipt (Bilty) LR Copy Layout
                            </span>
                            <span class="invoice-layout-selected">
                                <i class="bi bi-stars"></i>
                                Selected: <span id="selectedLrTemplateLabelCreate">A (Classic)</span>
                            </span>
                        </div>
                        <p class="small text-muted mb-3">
                            Template for LR copy PDFs. Demo uses sample data until the company is saved.
                        </p>
                        <div class="invoice-preview-frame rounded overflow-hidden bg-light mb-3 is-empty" id="lrPreviewContainerCreate">
                            <div class="invoice-preview-placeholder" id="lrPreviewPlaceholderCreate">
                                Select a template card (A / B / C) to load the live preview.
                            </div>
                            <iframe class="lr-demo-iframe" id="selectedLrDemoIframe" data-template="classic" title="Selected LR copy preview" loading="lazy"
                                src="about:blank"></iframe>
                        </div>
                        <a href="#"
                            data-template="classic"
                            target="_blank" rel="noopener noreferrer"
                            class="btn btn-danger w-100 mb-3 lr-open-pdf-demo" id="selectedLrPdfButton" style="pointer-events:none; opacity:.6;">
                            <i class="bi bi-file-earmark-pdf me-1"></i> View Selected Template PDF
                        </a>
                        <div class="row g-3" id="lrTemplatePreviewRowCreate">
                            <div class="col-12 col-lg-4 lr-preview-col-create invoice-preview-col" data-template-key="classic" data-template-label="A (Classic)">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">A</span> Classic</div>
                                        <div class="invoice-template-use">Default — original table bilty layout</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 lr-preview-col-create invoice-preview-col" data-template-key="modern" data-template-label="B (Modern)">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">B</span> Modern</div>
                                        <div class="invoice-template-use">Form A — green card layout</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 lr-preview-col-create invoice-preview-col" data-template-key="compact" data-template-label="C (Compact)">
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
                            <strong>Tip:</strong> Same A/B/C keys as Party Bill; LR copy uses separate layout files.
                        </small>
                    </div>
                </div>
                <!--end::Col-->
                <div class="col-md-6">
                    <label for="company_logo" class="form-label">Company Logo</label>
                    <input type="file" class="form-control" id="company_logo" name="company_logo">
                    @error('company_logo') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="gst_slab" class="form-label">Company Slab</label>
                    <select class="form-select" id="gst_slab" name="gst_slab" required>
                        <option value="">Select Company Slab</option>
                        @foreach($companySlabs as $slab)
                            <option value="{{ trim($slab) }}" {{ old('gst_slab') == trim($slab) ? 'selected' : '' }}>
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
            <button class="btn btn-brand-primary" type="submit">Register Company</button>
            <a href="/dashboard" class="btn btn-brand-primary">Back to Dashboard</a>
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

        function normalizeShortName(value) {
            return (value || '')
                .toString()
                .trim()
                .replace(/[^a-zA-Z0-9]/g, '')
                .toUpperCase();
        }

        function generateShortNameFromCompanyName(companyName) {
            const name = (companyName || '').toString().trim();
            if (!name) {
                return '';
            }

            const words = name.split(/\s+/).filter(Boolean);
            const initials = [];

            for (const w of words) {
                const match = w.match(/[a-zA-Z]/);
                if (match) {
                    initials.push(match[0].toUpperCase());
                }
                if (initials.length >= 2) {
                    break;
                }
            }

            if (initials.length >= 2) {
                return initials.join('');
            }

            const lettersOnly = name.replace(/[^a-zA-Z]/g, '').toUpperCase();
            return lettersOnly.slice(0, 5);
        }

        function updateInvoicePreview() {
            const shortNameInput = document.getElementById('short_name');
            const previewText = document.getElementById('invoicePreviewText');
            if (!shortNameInput || !previewText) {
                return;
            }

            const shortName = normalizeShortName(shortNameInput.value);
            const year = new Date().getFullYear();
            previewText.textContent = shortName ? `${shortName}/${year}/0001` : `—/${year}/0001`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const shortNameInput = document.getElementById('short_name');
            if (nameInput && shortNameInput) {
                let userEditedShortName = !!(shortNameInput.value && shortNameInput.value.trim().length > 0);
                if (shortNameInput.value && !userEditedShortName) {
                    userEditedShortName = false;
                }

                shortNameInput.value = normalizeShortName(shortNameInput.value);
                updateInvoicePreview();

                if (!shortNameInput.value) {
                    const generated = generateShortNameFromCompanyName(nameInput.value);
                    shortNameInput.value = normalizeShortName(generated);
                    updateInvoicePreview();
                }

                nameInput.addEventListener('input', function() {
                    if (userEditedShortName) {
                        return;
                    }
                    const generated = generateShortNameFromCompanyName(nameInput.value);
                    shortNameInput.value = normalizeShortName(generated);
                    updateInvoicePreview();
                });

                shortNameInput.addEventListener('input', function() {
                    userEditedShortName = true;
                    shortNameInput.value = normalizeShortName(shortNameInput.value);
                    updateInvoicePreview();
                });
            }

            const invoiceTemplateSelect = document.getElementById('invoice_template');
            const previewCols = Array.from(document.querySelectorAll('#invoiceTemplatePreviewRow .invoice-preview-col'));
            const demoDemoUrl = @json(route('invoice.template.demo'));
            const lrDemoUrl = @json(route('lr-copy.template.demo'));
            const selectedTemplateLabel = document.getElementById('selectedTemplateLabelCreate');
            let hasLoadedInvoicePreview = false;
            let hasLoadedLrPreview = false;
            const invoicePreviewContainerCreate = document.getElementById('invoicePreviewContainerCreate');
            const selectedPdfButton = document.getElementById('selectedInvoicePdfButton');
            const lrTemplateSelectCreate = document.getElementById('lr_copy_template');
            const lrPreviewColsCreate = Array.from(document.querySelectorAll('#lrTemplatePreviewRowCreate .lr-preview-col-create'));
            const selectedLrTemplateLabelCreate = document.getElementById('selectedLrTemplateLabelCreate');
            const lrPreviewContainerCreate = document.getElementById('lrPreviewContainerCreate');
            const selectedLrPdfButton = document.getElementById('selectedLrPdfButton');

            const syncTemplateSelection = (key) => {
                const selectedKey = key || 'classic';
                if (invoiceTemplateSelect) {
                    invoiceTemplateSelect.value = selectedKey;
                }
                previewCols.forEach((col) => {
                    col.classList.toggle('is-selected', col.dataset.templateKey === selectedKey);
                });
                const selectedCol = previewCols.find((col) => col.dataset.templateKey === selectedKey);
                if (selectedTemplateLabel && selectedCol?.dataset.templateLabel) {
                    selectedTemplateLabel.textContent = selectedCol.dataset.templateLabel;
                }
            };

            const refreshInvoiceDemoIframes = () => {
                if (!hasLoadedInvoicePreview) {
                    return;
                }
                const name = (document.getElementById('name')?.value || '').trim() || 'Your Company';
                const address = (document.getElementById('address')?.value || '').trim() || 'Sample address';
                const pin = (document.getElementById('pin_code')?.value || '').trim() || '314002';
                const gst = (document.getElementById('gst_number')?.value || '').trim() || '29AAACH7409R1ZX';
                const pan = (document.getElementById('pan_number')?.value || '').trim() || 'AABAD1452A';
                const buildUrl = (template, asPdf) => {
                    const params = new URLSearchParams({
                        template: template,
                        name: name,
                        address: address,
                        pin_code: pin,
                        gst_number: gst,
                        pan_number: pan,
                    });
                    if (asPdf) {
                        params.set('format', 'pdf');
                    }
                    return `${demoDemoUrl}?${params.toString()}`;
                };
                const selectedTemplate = invoiceTemplateSelect?.value || 'classic';
                const selectedIframe = document.getElementById('selectedInvoiceDemoIframe');
                const selectedPdfButton = document.getElementById('selectedInvoicePdfButton');

                if (selectedIframe) {
                    selectedIframe.dataset.template = selectedTemplate;
                    selectedIframe.src = buildUrl(selectedTemplate, false);
                }
                if (selectedPdfButton) {
                    selectedPdfButton.dataset.template = selectedTemplate;
                    selectedPdfButton.href = buildUrl(selectedTemplate, true);
                }
            };

            const syncLrTemplateSelectionCreate = (key) => {
                const selectedKey = key || 'classic';
                if (lrTemplateSelectCreate) {
                    lrTemplateSelectCreate.value = selectedKey;
                }
                lrPreviewColsCreate.forEach((col) => {
                    col.classList.toggle('is-selected', col.dataset.templateKey === selectedKey);
                });
                const selectedCol = lrPreviewColsCreate.find((col) => col.dataset.templateKey === selectedKey);
                if (selectedLrTemplateLabelCreate && selectedCol?.dataset.templateLabel) {
                    selectedLrTemplateLabelCreate.textContent = selectedCol.dataset.templateLabel;
                }
            };

            const refreshLrDemoIframes = () => {
                if (!hasLoadedLrPreview) {
                    return;
                }
                const name = (document.getElementById('name')?.value || '').trim() || 'Your Company';
                const address = (document.getElementById('address')?.value || '').trim() || 'Sample address';
                const pin = (document.getElementById('pin_code')?.value || '').trim() || '314002';
                const gst = (document.getElementById('gst_number')?.value || '').trim() || '29AAACH7409R1ZX';
                const pan = (document.getElementById('pan_number')?.value || '').trim() || 'AABAD1452A';
                const buildUrl = (template, asPdf) => {
                    const params = new URLSearchParams({
                        template: template,
                        name: name,
                        address: address,
                        pin_code: pin,
                        gst_number: gst,
                        pan_number: pan,
                    });
                    if (asPdf) {
                        params.set('format', 'pdf');
                    }
                    return `${lrDemoUrl}?${params.toString()}`;
                };
                const selectedTemplate = lrTemplateSelectCreate?.value || 'classic';
                const selectedIframe = document.getElementById('selectedLrDemoIframe');

                if (selectedIframe) {
                    selectedIframe.dataset.template = selectedTemplate;
                    selectedIframe.src = buildUrl(selectedTemplate, false);
                }
                if (selectedLrPdfButton) {
                    selectedLrPdfButton.dataset.template = selectedTemplate;
                    selectedLrPdfButton.href = buildUrl(selectedTemplate, true);
                }
            };

            if (invoiceTemplateSelect && previewCols.length) {
                syncTemplateSelection(invoiceTemplateSelect.value);
                previewCols.forEach((col) => {
                    col.addEventListener('click', function () {
                        syncTemplateSelection(col.dataset.templateKey);
                        hasLoadedInvoicePreview = true;
                        if (invoicePreviewContainerCreate) {
                            invoicePreviewContainerCreate.classList.remove('is-empty');
                        }
                        if (selectedPdfButton) {
                            selectedPdfButton.style.pointerEvents = 'auto';
                            selectedPdfButton.style.opacity = '1';
                        }
                        refreshInvoiceDemoIframes();
                    });
                });
            }

            if (lrTemplateSelectCreate && lrPreviewColsCreate.length) {
                syncLrTemplateSelectionCreate(lrTemplateSelectCreate.value);
                lrPreviewColsCreate.forEach((col) => {
                    col.addEventListener('click', function () {
                        syncLrTemplateSelectionCreate(col.dataset.templateKey);
                        hasLoadedLrPreview = true;
                        if (lrPreviewContainerCreate) {
                            lrPreviewContainerCreate.classList.remove('is-empty');
                        }
                        if (selectedLrPdfButton) {
                            selectedLrPdfButton.style.pointerEvents = 'auto';
                            selectedLrPdfButton.style.opacity = '1';
                        }
                        refreshLrDemoIframes();
                    });
                });
            }

            let demoTimer;
            const scheduleDemoRefresh = () => {
                clearTimeout(demoTimer);
                demoTimer = setTimeout(() => {
                    refreshInvoiceDemoIframes();
                    refreshLrDemoIframes();
                }, 400);
            };

            ['name', 'address', 'pin_code', 'gst_number', 'pan_number'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', function () {
                        if (!hasLoadedInvoicePreview && !hasLoadedLrPreview) {
                            return;
                        }
                        scheduleDemoRefresh();
                    });
                }
            });
        });

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
    </script>
    <!--end::JavaScript-->
        </div>
    </div>
</div>
@endsection
