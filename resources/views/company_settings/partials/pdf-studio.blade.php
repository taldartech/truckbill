@php
    $documents = [
        [
            'field' => 'invoice_template',
            'icon' => 'bi-file-earmark-text',
            'title' => 'Party bill',
            'desc' => 'Invoice PDFs & party-bill downloads',
            'current' => old('invoice_template', $setting->invoice_template ?? 'classic'),
            'route' => 'company.invoice-template-preview',
            'variants' => [
                ['value' => 'classic', 'label' => 'Classic', 'desc' => 'Balanced, clear sections'],
                ['value' => 'modern', 'label' => 'Modern', 'desc' => 'Bold heading & emphasis'],
                ['value' => 'compact', 'label' => 'Compact', 'desc' => 'Dense, fits more detail'],
            ],
        ],
        [
            'field' => 'lr_copy_template',
            'icon' => 'bi-truck',
            'title' => 'Good Receipt (LR copy)',
            'desc' => 'Bilty / LR copy PDFs',
            'current' => old('lr_copy_template', $setting->lr_copy_template ?? 'classic'),
            'route' => 'company.lr-copy-template-preview',
            'variants' => [
                ['value' => 'classic', 'label' => 'Classic', 'desc' => 'Original table bilty'],
                ['value' => 'modern', 'label' => 'Modern', 'desc' => 'Form A · green card'],
                ['value' => 'compact', 'label' => 'Compact', 'desc' => 'Form C · compact amber'],
            ],
        ],
        [
            'field' => 'freight_challan_template',
            'icon' => 'bi-receipt-cutoff',
            'title' => 'Freight challan',
            'desc' => 'Good Hires भाड़ा-चालान',
            'current' => old('freight_challan_template', $setting->freight_challan_template ?? 'classic'),
            'route' => 'company.freight-challan-template-preview',
            'variants' => [
                ['value' => 'classic', 'label' => 'Hindi voucher', 'desc' => 'भाड़ा-चालान style'],
                ['value' => 'modern', 'label' => 'Detailed', 'desc' => 'English challan'],
            ],
        ],
    ];
@endphp

<div class="cs-docs">
    @foreach ($documents as $doc)
        @php
            $current = in_array($doc['current'], array_column($doc['variants'], 'value'), true) ? $doc['current'] : $doc['variants'][0]['value'];
        @endphp
        <div class="cs-doc-row">
            <div class="cs-doc-row-head">
                <span class="cs-doc-ico"><i class="bi {{ $doc['icon'] }}"></i></span>
                <div>
                    <div class="cs-doc-title">{{ $doc['title'] }}</div>
                    <div class="cs-doc-sub">{{ $doc['desc'] }}</div>
                </div>
            </div>
            <div class="cs-variants" role="radiogroup" aria-label="{{ $doc['title'] }} template">
                @foreach ($doc['variants'] as $variant)
                    @php
                        $id = $doc['field'].'_'.$variant['value'];
                        $pdfUrl = route($doc['route'], [
                            'company' => $company->id,
                            'template' => $variant['value'],
                            'format' => 'pdf',
                        ]);
                    @endphp
                    <label class="cs-variant" for="{{ $id }}">
                        <input type="radio" id="{{ $id }}" name="{{ $doc['field'] }}"
                            value="{{ $variant['value'] }}" {{ $current === $variant['value'] ? 'checked' : '' }}>
                        <span class="cs-variant-card">
                            <span class="cs-variant-check"><i class="bi bi-check-lg"></i></span>
                            <a href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer"
                                class="cs-variant-eye" title="Preview PDF" aria-label="Preview {{ $doc['title'] }} — {{ $variant['label'] }}">
                                <i class="bi bi-eye"></i>
                            </a>
                            <span class="cs-variant-name">{{ $variant['label'] }}</span>
                            <span class="cs-variant-desc">{{ $variant['desc'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@error('invoice_template')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
@error('lr_copy_template')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
@error('freight_challan_template')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
