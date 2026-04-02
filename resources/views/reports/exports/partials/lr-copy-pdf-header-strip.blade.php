{{--
    Optional company header with logo (PDF-friendly path or URL) — same idea as party-bill-pdf-modern.
    @var \App\Models\Company $company
    @var string|null $logoSrc
    @var string $theme  b | c
--}}
@php
    $theme = $theme ?? 'b';
@endphp
<div class="lr-pdf-header lr-pdf-header--{{ $theme }}">
    <table class="lr-pdf-header__table">
        <tr>
            @if (!empty($logoSrc))
                <td class="lr-pdf-header__logo-cell">
                    <div class="lr-pdf-header__logo-box">
                        <img src="{{ $logoSrc }}" alt="{{ $company->name }}">
                    </div>
                </td>
            @endif
            <td class="lr-pdf-header__text-cell">
                <p class="lr-pdf-header__doc-title">LR COPY</p>
                <div class="lr-pdf-header__company-name">{{ $company->name }}</div>
                <div class="lr-pdf-header__company-meta">
                    {{ $company->address }},
                    {{ optional($company->city)->name }},
                    {{ optional($company->state)->name }} - {{ $company->pin_code }}
                </div>
                <div class="lr-pdf-header__company-meta">
                    GSTIN: {{ $company->gst_number }} &nbsp;|&nbsp; PAN: {{ $company->pan_number }}
                </div>
            </td>
        </tr>
    </table>
</div>
