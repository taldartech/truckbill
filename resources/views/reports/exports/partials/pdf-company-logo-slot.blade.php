{{--
    Fixed-size logo area for DomPDF / mPDF — colours preserved (use PNG/JPEG with reasonable resolution).
    @var string|null $logoSrc  Filesystem path or public URL from Company::logoFilesystemPathForPdf() / logoPublicUrl()
    @var string $companyName
    @var int $slotWidthPx   Outer box width (default 88)
    @var int $slotHeightPx  Outer box height (default 40)
--}}
@php
    $companyName = $companyName ?? '';
    $slotWidthPx = (int) ($slotWidthPx ?? 88);
    $slotHeightPx = (int) ($slotHeightPx ?? 40);
    $padX = 4;
    $padY = 4;
    $imgMaxW = max(12, $slotWidthPx - $padX);
    $imgMaxH = max(10, $slotHeightPx - $padY);
@endphp
@if (! empty($logoSrc))
    <div
        class="pdf-logo-slot"
        style="width: {{ $slotWidthPx }}px; max-width: {{ $slotWidthPx }}px; height: {{ $slotHeightPx }}px; max-height: {{ $slotHeightPx }}px; box-sizing: border-box; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 2px; text-align: center; display: inline-block; vertical-align: middle; overflow: hidden; line-height: 0;"
    >
        <img
            src="{{ $logoSrc }}"
            alt="{{ $companyName }}"
            width="{{ $imgMaxW }}"
            style="width: {{ $imgMaxW }}px; max-width: {{ $imgMaxW }}px !important; height: auto !important; max-height: {{ $imgMaxH }}px !important; object-fit: contain; object-position: center; display: block; margin: 0 auto; image-rendering: auto;"
        >
    </div>
@endif
