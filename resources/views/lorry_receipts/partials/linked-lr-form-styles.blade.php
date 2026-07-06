<style>
    .linked-lr-page {
        max-width: 1180px;
        margin: 0 auto;
    }

    .linked-lr-form .form-control,
    .linked-lr-form .form-select {
        min-height: 38px;
        box-sizing: border-box;
    }

    .linked-lr-form .text-danger {
        font-size: 0.875rem;
        margin-top: 0.25rem;
        word-wrap: break-word;
    }

    .linked-lr-form .card-body .select2-container {
        width: 100% !important;
        max-width: 100%;
    }

    .linked-lr-form .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }

    .linked-lr-form .select2-container--bootstrap-5 .select2-selection--single {
        min-height: 38px;
    }

    .linked-lr-form .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        display: block;
        padding: 0.375rem 2rem 0.375rem 0.75rem;
        line-height: 1.35;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .linked-lr-form .select2-container--bootstrap-5.select2-container--focus .select2-selection--single,
    .linked-lr-form .select2-container--bootstrap-5.select2-container--open .select2-selection--single {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    }

    .linked-lr-form .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        height: auto;
        min-height: 36px;
        top: 0;
        right: 0.5rem;
    }

    .linked-lr-form .select2-container.declaration-warning,
    .linked-lr-form #transporter_id.declaration-warning {
        border-color: #ffc107 !important;
        border-width: 2px !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
    }

    .linked-lr-form .select2-container.declaration-warning .select2-selection__rendered {
        background-color: #fff3cd !important;
    }

    .linked-lr-form .lr-payee-select-wrap .select2-selection--single .select2-selection__rendered {
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
        text-overflow: clip;
    }

    .linked-lr-hero {
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08), rgba(var(--bs-info-rgb), 0.06));
        border-bottom: 1px solid rgba(var(--bs-primary-rgb), 0.12);
    }

    .linked-lr-hero__title {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
    }

    .linked-lr-hero__subtitle {
        color: #6c757d;
        font-size: 0.9rem;
        margin-top: 0.25rem;
    }

    .linked-lr-section {
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        margin-bottom: 1.25rem;
        overflow: hidden;
    }

    .linked-lr-section__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.9rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .linked-lr-section__title {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
    }

    .linked-lr-section__title i {
        color: var(--bs-primary);
    }

    .linked-lr-section__body {
        padding: 1rem;
    }

    .linked-lr-items-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .linked-lr-items-count {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .linked-lr-card {
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
        overflow: hidden;
    }

    .linked-lr-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        background: linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.08), rgba(var(--bs-primary-rgb), 0.02));
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .linked-lr-card__title {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        min-width: 0;
    }

    .linked-lr-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        background: var(--bs-primary);
        color: #fff;
        font-size: 0.85rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .linked-lr-card__meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .linked-lr-amount-badge {
        font-size: 0.8rem;
        padding: 0.45rem 0.65rem;
        border-radius: 999px;
        white-space: nowrap;
    }

    .linked-lr-route-preview {
        display: block;
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 0.15rem;
    }

    .linked-lr-card__body {
        padding: 1rem;
    }

    .linked-lr-subsection + .linked-lr-subsection {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px dashed rgba(0, 0, 0, 0.08);
    }

    .linked-lr-subsection__label {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 0.75rem;
    }

    .linked-lr-route-banner {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        padding: 0.55rem 0.75rem;
        border-radius: 0.5rem;
        background: #f8fafc;
        border: 1px solid rgba(0, 0, 0, 0.06);
        font-size: 0.85rem;
    }

    .linked-lr-route-banner .route-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        max-width: 100%;
    }

    .linked-lr-party-hint {
        display: block;
        font-size: 0.78rem;
        color: #6c757d;
        margin-top: 0.25rem;
        min-height: 1rem;
    }

    .linked-lr-summary {
        position: sticky;
        bottom: 0.75rem;
        z-index: 20;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        margin-bottom: 1rem;
        border-radius: 0.75rem;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(var(--bs-primary-rgb), 0.18);
        box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08);
        backdrop-filter: blur(8px);
    }

    .linked-lr-summary__item {
        text-align: center;
    }

    .linked-lr-summary__label {
        display: block;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
        margin-bottom: 0.15rem;
    }

    .linked-lr-summary__value {
        font-size: 1rem;
        font-weight: 700;
        color: #212529;
    }

    .linked-lr-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    .linked-lr-form--edit .remove-lr-item,
    .linked-lr-form--edit #add-lr-item {
        display: none !important;
    }

    #lr-items-container:empty::before {
        content: 'No lorry receipt rows yet. Use "Add LR" to start building the linked group.';
        display: block;
        padding: 1.25rem;
        text-align: center;
        color: #6c757d;
        border: 1px dashed rgba(0, 0, 0, 0.12);
        border-radius: 0.75rem;
        background: #fafbfc;
    }

    @media (max-width: 767.98px) {
        .linked-lr-summary {
            grid-template-columns: 1fr;
        }

        .linked-lr-actions {
            grid-template-columns: 1fr;
        }

        .linked-lr-card__header {
            flex-direction: column;
        }

        .linked-lr-card__meta {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>
