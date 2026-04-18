
@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
<div class="card card-info card-outline mb-4">
    <div class="card-header"><div class="card-title">Edit company</div></div>
    <!--begin::Form-->
    <form action="{{ route('company.update', $company->id) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate="">
        @csrf
        @method('PUT')
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
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
                .pdf-template-studio .pdf-studio-toolbar {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 12px;
                    align-items: flex-end;
                    margin-bottom: 12px;
                }
                .pdf-template-studio .pdf-studio-toolbar .form-label {
                    margin-bottom: 4px;
                    font-size: 12px;
                    font-weight: 600;
                    color: #495057;
                }
                .pdf-template-studio .pdf-studio-help {
                    font-size: 13px;
                    line-height: 1.45;
                    color: #495057;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 10px;
                    padding: 10px 12px;
                    margin-bottom: 12px;
                }
                .pdf-template-studio .invoice-preview-frame {
                    height: 62vh;
                    min-height: 520px;
                    max-height: 720px;
                }
                .pdf-template-studio .pdf-studio-preview-wrap {
                    position: relative;
                    border-radius: 0.375rem;
                    overflow: hidden;
                    background: #f1f5f9;
                }
                .pdf-template-studio .pdf-studio-loading {
                    position: absolute;
                    inset: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(255, 255, 255, 0.85);
                    font-size: 13px;
                    font-weight: 600;
                    color: #475569;
                    z-index: 2;
                    pointer-events: none;
                }
                .pdf-template-studio .pdf-studio-loading.d-none {
                    display: none !important;
                }
            </style>
            @php
                $pdfStudioInvoiceKey = old('invoice_template', $company->invoice_template ?? 'classic');
                if (! in_array($pdfStudioInvoiceKey, ['classic', 'modern', 'compact'], true)) {
                    $pdfStudioInvoiceKey = 'classic';
                }
                $pdfStudioInitialPreviewUrl = route('company.invoice-template-preview', ['company' => $company->id, 'template' => $pdfStudioInvoiceKey]);
                $pdfStudioInitialPdfUrl = route('company.invoice-template-preview', ['company' => $company->id, 'template' => $pdfStudioInvoiceKey, 'format' => 'pdf']);
            @endphp
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Company Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $company->name) }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
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
                <div class="col-md-6">
                    <label for="gst_number" class="form-label">GST Number</label>
                    <input type="text" class="form-control" id="gst_number" name="gst_number"  value="{{ old('gst_number', $company->gst_number) }}">
                    @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input type="text" class="form-control" id="pan_number" name="pan_number" required value="{{ old('pan_number', $company->pan_number) }}">
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
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
                 <div class="col-md-6">
                    <label for="gst_type" class="form-label">GST Type *</label>
                    <input type="text" class="form-control" id="gst_type" name="gst_type" required value="Regular" disabled>
                    @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" required>{{ old('address', $company->address) }}</textarea>
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
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
                <div class="col-md-6">
                    <label for="state_id" class="form-label">State</label>
                    <select class="form-select" id="state_id" name="state_id" required onchange="fetchCities(this.value)">
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option {{ (string) old('state_id', $company->state_id) === (string) $state->id ? 'selected' : '' }} value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="city_id" class="form-label">City</label>
                    <select class="form-select" id="city_id" name="city_id" required>
                        <option value="">Select City</option>
                        @foreach($cities as $city)
                            <option {{ (string) old('city_id', $company->city_id) === (string) $city->id ? 'selected' : '' }} value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="pin_code" class="form-label">Pin Code</label>
                    <input type="text" class="form-control" id="pin_code" name="pin_code" required value="{{ old('pin_code', $company->pin_code) }}">
                    @error('pin_code') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" required value="{{ old('contact_person', $company->contact_person) }}">
                    @error('contact_person') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" required value="{{ old('contact_number', $company->contact_number) }}">
                    @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
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
            </div>
            <div class="row g-3">
                <input type="hidden" id="invoice_template" name="invoice_template" value="{{ old('invoice_template', $company->invoice_template ?? 'classic') }}">
                <input type="hidden" id="lr_copy_template" name="lr_copy_template" value="{{ old('lr_copy_template', $company->lr_copy_template ?? 'classic') }}">
                <input type="hidden" id="freight_challan_template" name="freight_challan_template" value="{{ old('freight_challan_template', $company->freight_challan_template ?? 'classic') }}">

                <div class="col-12">
                    <div class="invoice-layout-shell pdf-template-studio">
                        <div class="invoice-layout-topbar flex-wrap gap-2">
                            <span class="fw-semibold text-dark">
                                <i class="bi bi-layout-three-columns text-primary me-1"></i>
                                PDF &amp; print templates
                            </span>
                            <span class="invoice-layout-selected ms-auto">
                                <i class="bi bi-stars"></i>
                                <span id="pdfStudioSelectedSummary">Party bill · @if ($pdfStudioInvoiceKey === 'modern') B (Modern) @elseif ($pdfStudioInvoiceKey === 'compact') C (Compact) @else A (Classic) @endif</span>
                            </span>
                        </div>
                        <p class="small text-muted mb-2">
                            Choose the <strong>document</strong> first, then the <strong>variant</strong> (A / B / C). The preview below always matches that pair — one studio instead of three stacked blocks.
                        </p>
                        <div class="pdf-studio-toolbar row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="pdfStudioDocumentType">Document</label>
                                <select class="form-select" id="pdfStudioDocumentType" autocomplete="off">
                                    <option value="invoice">Party bill (invoice PDF)</option>
                                    <option value="lr">Good Receipt (Bilty) LR copy</option>
                                    <option value="freight">Freight challan (Good Hires / भाड़ा-चालान)</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="pdfStudioVariant">Template variant</label>
                                <select class="form-select" id="pdfStudioVariant" autocomplete="off"></select>
                            </div>
                        </div>
                        <div class="pdf-studio-help mb-3" id="pdfStudioHelpText">Party bill / invoice: used for invoice PDFs and party-bill downloads. A = classic, B = modern, C = compact.</div>
                        <div class="invoice-preview-frame rounded overflow-hidden bg-light mb-3 pdf-studio-preview-wrap" id="pdfStudioPreviewContainer">
                            <div class="pdf-studio-loading" id="pdfStudioLoading">Loading preview…</div>
                            <iframe id="pdfStudioPreviewIframe" title="Template preview" src="{{ $pdfStudioInitialPreviewUrl }}"></iframe>
                        </div>
                        <a href="{{ $pdfStudioInitialPdfUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-danger w-100 mb-3" id="pdfStudioPdfButton">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Open current template as PDF
                        </a>

                        <div id="pdfStudioCardsInvoice" class="pdf-studio-card-panel row g-3" data-studio-doc="invoice">
                            <div class="col-12 col-lg-4 invoice-preview-col pdf-studio-card"
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
                            <div class="col-12 col-lg-4 invoice-preview-col pdf-studio-card"
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
                            <div class="col-12 col-lg-4 invoice-preview-col pdf-studio-card"
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

                        <div id="pdfStudioCardsLr" class="pdf-studio-card-panel row g-3 d-none" data-studio-doc="lr">
                            <div class="col-12 col-lg-4 lr-preview-col invoice-preview-col pdf-studio-card"
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
                            <div class="col-12 col-lg-4 lr-preview-col invoice-preview-col pdf-studio-card"
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
                            <div class="col-12 col-lg-4 lr-preview-col invoice-preview-col pdf-studio-card"
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

                        <div id="pdfStudioCardsFreight" class="pdf-studio-card-panel row g-3 d-none" data-studio-doc="freight">
                            <div class="col-12 col-lg-6 fr-challan-preview-col invoice-preview-col pdf-studio-card"
                                data-template-key="classic"
                                data-template-label="A (Classic voucher)"
                                data-preview-url="{{ route('company.freight-challan-template-preview', ['company' => $company->id, 'template' => 'classic']) }}"
                                data-pdf-url="{{ route('company.freight-challan-template-preview', ['company' => $company->id, 'template' => 'classic', 'format' => 'pdf']) }}">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">A</span> Classic</div>
                                        <div class="invoice-template-use">Current भाड़ा-चालान (Hindi / voucher)</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6 fr-challan-preview-col invoice-preview-col pdf-studio-card"
                                data-template-key="modern"
                                data-template-label="B (Detailed challan)"
                                data-preview-url="{{ route('company.freight-challan-template-preview', ['company' => $company->id, 'template' => 'modern']) }}"
                                data-pdf-url="{{ route('company.freight-challan-template-preview', ['company' => $company->id, 'template' => 'modern', 'format' => 'pdf']) }}">
                                <div class="invoice-template-meta">
                                    <div>
                                        <div class="invoice-template-name"><span class="invoice-option-letter me-1">B</span> Detailed</div>
                                        <div class="invoice-template-use">English challan — system data only</div>
                                    </div>
                                    <span class="badge text-bg-primary invoice-selected-badge">Selected</span>
                                </div>
                            </div>
                        </div>

                        <small class="text-muted d-block mt-2">
                            Cards and the variant dropdown stay in sync. Freight challan preview uses voucher / party-bill name &amp; address; if they are empty, it falls back to company name/address.
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="company_logo" class="form-label">Company Logo</label>
                    <input type="file" class="form-control" id="company_logo" name="company_logo">
                    <img src="{{ asset('storage/' . $company->company_logo) }}" alt="Company Logo" style="width: 100px; height: 100px;">
                    @error('company_logo') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
            </div>
            <div class="row g-3">
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
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-success" type="submit">Update Company</button>
            <a href="{{ route('company.index') }}" class="btn btn-brand-primary">Back to list</a>
        </div>
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
            const docSelect = document.getElementById('pdfStudioDocumentType');
            const variantSelect = document.getElementById('pdfStudioVariant');
            const helpEl = document.getElementById('pdfStudioHelpText');
            const summaryEl = document.getElementById('pdfStudioSelectedSummary');
            const iframeEl = document.getElementById('pdfStudioPreviewIframe');
            const pdfBtn = document.getElementById('pdfStudioPdfButton');
            const hiddenInvoice = document.getElementById('invoice_template');
            const hiddenLr = document.getElementById('lr_copy_template');
            const hiddenFreight = document.getElementById('freight_challan_template');
            const panelInvoice = document.getElementById('pdfStudioCardsInvoice');
            const panelLr = document.getElementById('pdfStudioCardsLr');
            const panelFreight = document.getElementById('pdfStudioCardsFreight');
            const loadingEl = document.getElementById('pdfStudioLoading');

            if (!docSelect || !variantSelect || !iframeEl || !hiddenInvoice || !hiddenLr || !hiddenFreight || !pdfBtn) {
                return;
            }

            const cacheBust = (url) => {
                if (!url || url === '#') {
                    return url;
                }
                const sep = url.includes('?') ? '&' : '?';

                return `${url}${sep}_=${Date.now()}`;
            };

            if (iframeEl && loadingEl) {
                iframeEl.addEventListener('load', () => {
                    loadingEl.classList.add('d-none');
                });
            }

            const DOC_HELP = {
                invoice: 'Party bill / invoice: used for invoice PDFs and party-bill downloads. A = classic, B = modern, C = compact.',
                lr: 'Good Receipt (Bilty) LR copy: layout for LR copy PDFs from the Good Receipt list and LR copy report. The report screen can still override the template.',
                freight: 'Freight challan: downloads from Good Hires (Lorry Hire). A = Hindi voucher style, B = detailed English. Uses your company / voucher data.',
            };

            const DOC_LABEL = {
                invoice: 'Party bill',
                lr: 'LR copy',
                freight: 'Freight challan',
            };

            const VARIANT_OPTIONS = {
                invoice: [
                    { value: 'classic', text: 'A — Classic' },
                    { value: 'modern', text: 'B — Modern' },
                    { value: 'compact', text: 'C — Compact' },
                ],
                lr: [
                    { value: 'classic', text: 'A — Classic' },
                    { value: 'modern', text: 'B — Modern' },
                    { value: 'compact', text: 'C — Compact' },
                ],
                freight: [
                    { value: 'classic', text: 'A — Classic (Hindi voucher)' },
                    { value: 'modern', text: 'B — Detailed (English)' },
                ],
            };

            const getHiddenForDoc = (doc) => {
                if (doc === 'lr') {
                    return hiddenLr;
                }
                if (doc === 'freight') {
                    return hiddenFreight;
                }

                return hiddenInvoice;
            };

            const getPanelForDoc = (doc) => {
                if (doc === 'lr') {
                    return panelLr;
                }
                if (doc === 'freight') {
                    return panelFreight;
                }

                return panelInvoice;
            };

            const findCard = (doc, key) => {
                const panel = getPanelForDoc(doc);
                if (!panel) {
                    return null;
                }

                return panel.querySelector(`.pdf-studio-card[data-template-key="${key}"]`);
            };

            const coerceKeyForDoc = (doc, key) => {
                const allowed = VARIANT_OPTIONS[doc].map((o) => o.value);
                if (allowed.includes(key)) {
                    return key;
                }

                return allowed[0] || 'classic';
            };

            const applySelection = (doc, key, loadPreview) => {
                const k = coerceKeyForDoc(doc, key);
                const hidden = getHiddenForDoc(doc);
                hidden.value = k;

                const panel = getPanelForDoc(doc);
                if (panel) {
                    panel.querySelectorAll('.pdf-studio-card').forEach((el) => {
                        el.classList.toggle('is-selected', el.dataset.templateKey === k);
                    });
                }

                const card = findCard(doc, k);
                const label = card?.dataset?.templateLabel || k;

                if (helpEl) {
                    helpEl.textContent = DOC_HELP[doc] || '';
                }
                if (summaryEl) {
                    summaryEl.textContent = `${DOC_LABEL[doc] || doc} · ${label}`;
                }

                if (loadPreview && iframeEl && pdfBtn && card?.dataset?.previewUrl) {
                    if (loadingEl) {
                        loadingEl.classList.remove('d-none');
                    }
                    iframeEl.src = cacheBust(card.dataset.previewUrl);
                    pdfBtn.href = cacheBust(card.dataset.pdfUrl || '#');
                }
            };

            const rebuildVariantDropdown = (doc) => {
                const current = getHiddenForDoc(doc).value;
                const k = coerceKeyForDoc(doc, current);
                variantSelect.innerHTML = '';
                VARIANT_OPTIONS[doc].forEach((opt) => {
                    const o = document.createElement('option');
                    o.value = opt.value;
                    o.textContent = opt.text;
                    variantSelect.appendChild(o);
                });
                variantSelect.value = k;
            };

            const showPanelForDoc = (doc) => {
                [panelInvoice, panelLr, panelFreight].forEach((p) => {
                    if (p) {
                        p.classList.toggle('d-none', p !== getPanelForDoc(doc));
                    }
                });
            };

            docSelect.addEventListener('change', () => {
                const doc = docSelect.value;
                showPanelForDoc(doc);
                rebuildVariantDropdown(doc);
                applySelection(doc, getHiddenForDoc(doc).value, true);
            });

            variantSelect.addEventListener('change', () => {
                const doc = docSelect.value;
                applySelection(doc, variantSelect.value, true);
            });

            document.querySelectorAll('.pdf-studio-card').forEach((card) => {
                card.addEventListener('click', () => {
                    const panel = card.closest('.pdf-studio-card-panel');
                    const doc = panel?.dataset?.studioDoc || docSelect.value;
                    if (!doc) {
                        return;
                    }
                    docSelect.value = doc;
                    showPanelForDoc(doc);
                    rebuildVariantDropdown(doc);
                    applySelection(doc, card.dataset.templateKey, true);
                    variantSelect.value = coerceKeyForDoc(doc, card.dataset.templateKey);
                });
            });

            const initialDoc = 'invoice';
            docSelect.value = initialDoc;
            showPanelForDoc(initialDoc);
            rebuildVariantDropdown(initialDoc);
            applySelection(initialDoc, hiddenInvoice.value, false);
        });
    </script>
    <!--end::JavaScript-->
        </div>
    </div>
</div>
@endsection

