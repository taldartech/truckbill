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


            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-3">
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
                <!--end::Col-->
                <div class="col-md-6">
                    <label for="invoice_template" class="form-label">Invoice Template</label>
                    <select class="form-select" id="invoice_template" name="invoice_template" required>
                        <option value="classic" {{ old('invoice_template', 'classic') == 'classic' ? 'selected' : '' }}>A (Classic)</option>
                        <option value="modern" {{ old('invoice_template') == 'modern' ? 'selected' : '' }}>B (Modern)</option>
                        <option value="compact" {{ old('invoice_template') == 'compact' ? 'selected' : '' }}>C (Compact)</option>
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
                                    Party Bill — invoice format (demo preview)
                                </span>
                                <p class="small text-muted mb-0 mt-2">
                                    Demo invoice before you save. <strong>View PDF</strong> uses the same engine as Party Bill export (full PDF in the browser).
                                    <strong>View full page</strong> opens the HTML preview in a new tab. Company fields above update both links.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3" id="invoiceTemplatePreviewRow">
                        <div class="col-12 col-lg-4 invoice-preview-col" data-template-key="classic">
                            <div class="invoice-preview-frame border rounded overflow-hidden bg-light">
                                <iframe class="invoice-demo-iframe" data-template="classic" title="Classic invoice preview" loading="lazy"
                                    src="{{ route('invoice.template.demo', ['template' => 'classic', 'name' => old('name', 'Your Company'), 'address' => old('address', 'Sample address'), 'pin_code' => old('pin_code', '314002')]) }}"></iframe>
                            </div>
                            <div class="small fw-semibold mt-2">A (Classic)</div>
                            <a href="{{ route('invoice.template.demo', ['template' => 'classic', 'format' => 'pdf', 'name' => old('name', 'Your Company'), 'address' => old('address', 'Sample address'), 'pin_code' => old('pin_code', '314002')]) }}"
                               data-template="classic"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-danger w-100 mt-2 invoice-open-pdf-demo">
                                <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
                            </a>
                            {{-- <a href="{{ route('invoice.template.demo', ['template' => 'classic', 'name' => old('name', 'Your Company'), 'address' => old('address', 'Sample address'), 'pin_code' => old('pin_code', '314002')]) }}"
                               data-template="classic"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-outline-secondary w-100 mt-1 invoice-open-full-demo">
                                <i class="bi bi-window me-1"></i> View full page
                            </a> --}}
                        </div>
                        <div class="col-12 col-lg-4 invoice-preview-col" data-template-key="modern">
                            <div class="invoice-preview-frame border rounded overflow-hidden bg-light">
                                <iframe class="invoice-demo-iframe" data-template="modern" title="Modern invoice preview" loading="lazy"
                                    src="{{ route('invoice.template.demo', ['template' => 'modern', 'name' => old('name', 'Your Company'), 'address' => old('address', 'Sample address'), 'pin_code' => old('pin_code', '314002')]) }}"></iframe>
                            </div>
                            <div class="small fw-semibold mt-2">B (Modern)</div>
                            <a href="{{ route('invoice.template.demo', ['template' => 'modern', 'format' => 'pdf', 'name' => old('name', 'Your Company'), 'address' => old('address', 'Sample address'), 'pin_code' => old('pin_code', '314002')]) }}"
                               data-template="modern"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-danger w-100 mt-2 invoice-open-pdf-demo">
                                <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
                            </a>
                            {{-- <a href="{{ route('invoice.template.demo', ['template' => 'modern', 'name' => old('name', 'Your Company'), 'address' => old('address', 'Sample address'), 'pin_code' => old('pin_code', '314002')]) }}"
                               data-template="modern"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-outline-secondary w-100 mt-1 invoice-open-full-demo">
                                <i class="bi bi-window me-1"></i> View full page
                            </a> --}}
                        </div>
                        <div class="col-12 col-lg-4 invoice-preview-col" data-template-key="compact">
                            <div class="invoice-preview-frame border rounded overflow-hidden bg-light">
                                <iframe class="invoice-demo-iframe" data-template="compact" title="Compact invoice preview" loading="lazy"
                                    src="{{ route('invoice.template.demo', ['template' => 'compact', 'name' => old('name', 'Your Company'), 'address' => old('address', 'Sample address'), 'pin_code' => old('pin_code', '314002')]) }}"></iframe>
                            </div>
                            <div class="small fw-semibold mt-2">C (Compact)</div>
                            <a href="{{ route('invoice.template.demo', ['template' => 'compact', 'format' => 'pdf', 'name' => old('name', 'Your Company'), 'address' => old('address', 'Sample address'), 'pin_code' => old('pin_code', '314002')]) }}"
                               data-template="compact"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-danger w-100 mt-2 invoice-open-pdf-demo">
                                <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
                            </a>
                            {{-- <a href="{{ route('invoice.template.demo', ['template' => 'compact', 'name' => old('name', 'Your Company'), 'address' => old('address', 'Sample address'), 'pin_code' => old('pin_code', '314002')]) }}"
                               data-template="compact"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm btn-outline-secondary w-100 mt-1 invoice-open-full-demo">
                                <i class="bi bi-window me-1"></i> View full page
                            </a> --}}
                        </div>
                    </div>
                    <small class="text-muted d-block mt-3">
                        <strong>Tip:</strong> The selected template highlights the matching column; click a column to select it. The iframe refreshes when you change name, address, or other company fields.
                    </small>
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

            const syncTemplateSelection = (key) => {
                const selectedKey = key || 'classic';
                if (invoiceTemplateSelect) {
                    invoiceTemplateSelect.value = selectedKey;
                }
                previewCols.forEach((col) => {
                    col.classList.toggle('is-selected', col.dataset.templateKey === selectedKey);
                });
            };

            const refreshInvoiceDemoIframes = () => {
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
                document.querySelectorAll('.invoice-demo-iframe').forEach((iframe) => {
                    const template = iframe.dataset.template || 'classic';
                    iframe.src = buildUrl(template, false);
                });
                document.querySelectorAll('.invoice-open-full-demo').forEach((link) => {
                    const template = link.dataset.template || 'classic';
                    link.href = buildUrl(template, false);
                });
                document.querySelectorAll('.invoice-open-pdf-demo').forEach((link) => {
                    const template = link.dataset.template || 'classic';
                    link.href = buildUrl(template, true);
                });
            };

            if (invoiceTemplateSelect && previewCols.length) {
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
            }

            let demoTimer;
            const scheduleDemoRefresh = () => {
                clearTimeout(demoTimer);
                demoTimer = setTimeout(refreshInvoiceDemoIframes, 400);
            };

            ['name', 'address', 'pin_code', 'gst_number', 'pan_number'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', scheduleDemoRefresh);
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
