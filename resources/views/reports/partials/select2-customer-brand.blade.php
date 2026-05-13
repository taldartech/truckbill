{{-- TruckBill brand styling for Select2 customer/party search on reports.
     Pass containerCssClass: 'tb-select2-container-brand' and dropdownCssClass: 'tb-select2-dropdown-brand' from Select2 init. --}}
<style>
    .tb-report-customer-select2-form .form-control,
    .tb-report-customer-select2-form .form-select {
        min-height: 38px;
        box-sizing: border-box;
        border-radius: 0.5rem;
        border-color: var(--brand-border, #e5e7eb);
        background-color: var(--brand-card-bg, #fff);
    }

    .tb-report-customer-select2-form .form-control:focus {
        border-color: var(--brand-primary) !important;
        box-shadow: 0 0 0 0.2rem var(--brand-soft-primary, rgba(47, 179, 111, 0.12)) !important;
    }

    span.select2-container.tb-select2-container-brand {
        width: 100% !important;
        max-width: 100%;
    }

    span.tb-select2-container-brand .select2-selection--single {
        min-height: 38px;
        height: auto !important;
        border: 1px solid var(--brand-border, #e5e7eb) !important;
        border-radius: 0.5rem !important;
        background-color: var(--brand-card-bg, #fff) !important;
    }

    span.tb-select2-container-brand .select2-selection--single .select2-selection__rendered {
        display: block;
        padding: 0.5rem 2rem 0.5rem 0.75rem;
        line-height: 1.5;
        color: var(--brand-text-primary, #111827);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    span.tb-select2-container-brand.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        height: auto;
        min-height: 34px;
        top: 1px;
        right: 0.5rem;
    }

    span.tb-select2-container-brand.select2-container--focus .select2-selection--single,
    span.tb-select2-container-brand.select2-container--open .select2-selection--single {
        border-color: var(--brand-primary) !important;
        box-shadow: 0 0 0 0.2rem var(--brand-soft-primary, rgba(47, 179, 111, 0.15)) !important;
    }

    /* Other Select2 fields in the same report row (e.g. LR / invoice) — align with brand borders */
    .tb-report-customer-select2-form span.select2-container--bootstrap-5:not(.tb-select2-container-brand) .select2-selection--single {
        border-radius: 0.5rem !important;
        border-color: var(--brand-border, #e5e7eb) !important;
        background-color: var(--brand-card-bg, #fff) !important;
    }

    .tb-report-customer-select2-form span.select2-container--bootstrap-5:not(.tb-select2-container-brand).select2-container--focus .select2-selection--single,
    .tb-report-customer-select2-form span.select2-container--bootstrap-5:not(.tb-select2-container-brand).select2-container--open .select2-selection--single {
        border-color: var(--brand-primary) !important;
        box-shadow: 0 0 0 0.2rem var(--brand-soft-primary, rgba(47, 179, 111, 0.12)) !important;
    }

    .select2-dropdown.tb-select2-dropdown-brand {
        border: 1px solid var(--brand-border, #e5e7eb) !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 8px 24px rgba(17, 24, 39, 0.08);
        overflow: hidden;
    }

    .tb-select2-dropdown-brand .select2-search--dropdown {
        padding: 0.5rem;
    }

    .tb-select2-dropdown-brand .select2-search--dropdown .select2-search__field {
        border: 1px solid var(--brand-border, #e5e7eb) !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem 0.65rem;
    }

    .tb-select2-dropdown-brand .select2-search--dropdown .select2-search__field:focus {
        border-color: var(--brand-primary) !important;
        box-shadow: 0 0 0 0.15rem var(--brand-soft-primary, rgba(47, 179, 111, 0.12)) !important;
        outline: 0;
    }

    .tb-select2-dropdown-brand .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: var(--brand-primary) !important;
        color: #fff !important;
    }

    .tb-select2-dropdown-brand .select2-results__option--selected {
        background-color: var(--brand-active-bg, #e9f7f0) !important;
        color: var(--brand-primary-dark, #23965c) !important;
    }
</style>
