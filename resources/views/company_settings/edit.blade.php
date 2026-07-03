@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 cs-settings">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h1 class="h5 mb-0 fw-semibold">
            <i class="bi bi-sliders text-primary me-1"></i>{{ $company->name }}
            <span class="text-muted fw-normal small">· settings</span>
        </h1>
        <a href="{{ route('company_settings.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>All companies
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('company_settings.update', $company) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <section class="cs-block mb-3">
            <div class="cs-block-title">
                <span class="cs-step">1</span>
                <span>Print header</span>
                <span class="cs-block-hint">Appears on top of vouchers, challans &amp; bills</span>
            </div>
            @include('company_settings.partials.voucher-header', ['setting' => $setting])
        </section>

        <section class="cs-block mb-3">
            <div class="cs-block-title">
                <span class="cs-step">2</span>
                <span>Document designs</span>
                <span class="cs-block-hint">Pick a design — each has its own Preview PDF button</span>
            </div>
            @include('company_settings.partials.pdf-studio', ['company' => $company, 'setting' => $setting])
        </section>

        <div class="cs-save-bar">
            <span class="text-muted small d-none d-sm-inline"><i class="bi bi-shield-check me-1"></i>Applies to {{ $company->name }} only</span>
            <div class="d-flex gap-2 ms-auto">
                <a href="{{ route('company_settings.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-success px-3">
                    <i class="bi bi-check-lg me-1"></i>Save settings
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    .cs-settings { max-width: 1100px; }
    .cs-settings .cs-block {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        padding: 14px 16px;
    }
    .cs-settings .cs-block-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 14px;
    }
    .cs-settings .cs-step {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--bs-primary);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        flex: 0 0 auto;
    }
    .cs-settings .cs-block-hint {
        font-weight: 400;
        font-size: 12px;
        color: #94a3b8;
        margin-left: auto;
    }
    .cs-settings .cs-label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 3px;
    }

    /* Document design picker */
    .cs-docs { display: flex; flex-direction: column; gap: 12px; }
    .cs-doc-row {
        border: 1px solid #eef2f7;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fcfdff;
    }
    .cs-doc-row-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .cs-doc-ico {
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; border-radius: 8px;
        background: #eff6ff; color: var(--bs-primary); font-size: 16px; flex: 0 0 auto;
    }
    .cs-doc-title { font-size: 13px; font-weight: 700; color: #1f2937; }
    .cs-doc-sub { font-size: 11px; color: #94a3b8; }

    .cs-variants { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; }
    .cs-variant { margin: 0; cursor: pointer; }
    .cs-variant input { position: absolute; opacity: 0; pointer-events: none; }
    .cs-variant-card {
        position: relative;
        display: block;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 9px 34px 9px 42px;
        background: #fff;
        transition: border-color .12s ease, box-shadow .12s ease, background .12s ease;
        height: 100%;
        min-height: 58px;
    }
    .cs-variant-card:hover { border-color: #bfdbfe; }
    .cs-variant-name { display: block; font-size: 13px; font-weight: 700; color: #334155; }
    .cs-variant-desc { display: block; font-size: 11px; color: #94a3b8; margin-top: 1px; }

    /* Eye preview button inside the card (top-left, before the tick) */
    .cs-variant-eye {
        position: absolute;
        top: 50%;
        left: 8px;
        transform: translateY(-50%);
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        text-decoration: none;
        transition: color .12s ease, background .12s ease, border-color .12s ease;
        z-index: 2;
    }
    .cs-variant-eye:hover {
        color: #fff;
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    .cs-variant-check {
        position: absolute; top: 7px; right: 7px;
        width: 18px; height: 18px; border-radius: 50%;
        background: var(--bs-primary); color: #fff;
        display: none; align-items: center; justify-content: center; font-size: 11px;
        z-index: 2;
    }
    .cs-variant input:checked + .cs-variant-card {
        border-color: var(--bs-primary);
        background: #f5f9ff;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
    }
    .cs-variant input:checked + .cs-variant-card .cs-variant-check { display: inline-flex; }
    .cs-variant input:checked + .cs-variant-card .cs-variant-name { color: var(--bs-primary); }
    .cs-variant input:focus-visible + .cs-variant-card { border-color: var(--bs-primary); }

    /* Sticky save bar */
    .cs-settings .cs-save-bar {
        position: sticky;
        bottom: 0;
        z-index: 5;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        background: rgba(255, 255, 255, .96);
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 -4px 14px rgba(15, 23, 42, .06);
        backdrop-filter: blur(4px);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.cs-variant-eye').forEach(function (eye) {
            eye.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });
    });
</script>
@endsection
