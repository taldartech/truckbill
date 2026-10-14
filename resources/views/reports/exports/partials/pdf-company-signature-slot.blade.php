{{--
    Digital signature area for DomPDF / mPDF.
    No nested table (parent invoice tables use border="1" and DomPDF draws borders on nested tables).
    mPDF ignores CSS max-width/max-height on images; set HTML width like the logo slot.
    @var string|null $signatureSrc  Filesystem path or public URL from Company::signatureFilesystemPathForPdf() / signaturePublicUrl()
    @var string $companyName
    @var int $slotWidthPx   Max image width (default 100)
    @var int $slotHeightPx  Max image height (default 36)
    @var string $align      left|center|right (default right)
--}}
@php
    $companyName = $companyName ?? '';
    $slotWidthPx = (int) ($slotWidthPx ?? 100);
    $slotHeightPx = (int) ($slotHeightPx ?? 36);
    $align = strtolower((string) ($align ?? 'right'));
    if (! in_array($align, ['left', 'center', 'right'], true)) {
        $align = 'right';
    }
    $imgMaxW = max(12, $slotWidthPx);
    $imgMaxH = max(10, $slotHeightPx);
@endphp
@if (! empty($signatureSrc))
    <div
        class="pdf-signature-slot"
        style="width: 100%; margin: 2px 0 0; padding: 0; border: none; outline: none; background: transparent; text-align: {{ $align }}; line-height: 0;"
    >
        <img
            src="{{ $signatureSrc }}"
            alt="{{ $companyName }} signature"
            width="{{ $imgMaxW }}"
            style="width: {{ $imgMaxW }}px; max-width: {{ $imgMaxW }}px !important; height: auto !important; max-height: {{ $imgMaxH }}px !important; border: none; outline: none; display: inline-block; vertical-align: middle; image-rendering: auto;"
        >
    </div>
@endif
