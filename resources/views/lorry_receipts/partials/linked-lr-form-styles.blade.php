<style>
    .linked-lr-form .text-danger {
        font-size: 0.875rem;
        margin-top: 0.25rem;
        word-wrap: break-word;
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

    .linked-lr-card {
        border: 1px solid var(--brand-border, #e2e8f0);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        margin-bottom: 10px;
    }

    .linked-lr-card .lrf-grid {
        gap: 8px 10px;
    }

    .linked-lr-card .lrf-grid--6 {
        grid-template-columns: minmax(7.5rem, 1.4fr) repeat(5, minmax(0, 1fr));
    }

    @media (max-width: 991px) {
        .linked-lr-card .lrf-grid--6 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .linked-lr-card .lrf-pf {
        gap: 3px;
    }

    .linked-lr-card .lrf-pf__lbl {
        min-height: 0;
        font-size: 0.72rem;
    }

    .linked-lr-card .lrf-pf__hint:empty,
    .linked-lr-card .lr-number-feedback:empty,
    .linked-lr-card .invoice-number-feedback:empty,
    .linked-lr-card .gr-number-feedback:empty,
    .linked-lr-card .lrf-field-feedback:empty {
        display: none;
        margin: 0;
    }

    .linked-lr-card .lrf-pf__hint {
        margin-top: 0;
        font-size: 0.65rem;
    }

    .linked-lr-card + .linked-lr-card {
        margin-top: 0;
    }

    .linked-lr-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 7px 12px;
        background: linear-gradient(135deg, #e7f1ff 0%, #f8f9fa 100%);
        border-bottom: 1px solid var(--brand-border, #e2e8f0);
        min-height: 0;
    }

    .linked-lr-card__title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }

    .linked-lr-card__title h6 {
        margin: 0;
        font-size: 0.8rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }

    .linked-lr-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: var(--primary, #0d6efd);
        color: #fff;
        font-size: 0.68rem;
        font-weight: 800;
        flex-shrink: 0;
    }

    .linked-lr-card__meta {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-shrink: 0;
    }

    .linked-lr-amount-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        white-space: nowrap;
    }

    .linked-lr-route-preview {
        display: block;
        font-size: 0.68rem;
        color: var(--brand-text-muted, #64748b);
        margin-top: 0;
        line-height: 1.2;
    }

    .linked-lr-card__body {
        padding: 8px 12px 10px;
    }

    .linked-lr-subsection + .linked-lr-subsection {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed var(--brand-border, #e2e8f0);
    }

    .linked-lr-subsection__label {
        font-size: 0.64rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--brand-text-muted, #64748b);
        margin-bottom: 6px;
    }

    .linked-lr-route-banner {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
        padding: 0.3rem 0.55rem;
        margin-top: 6px;
        border-radius: 6px;
        background: #f8fafc;
        border: 1px solid var(--brand-border, #e2e8f0);
        font-size: 0.72rem;
    }

    .linked-lr-route-banner .route-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.1rem 0.45rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--brand-border, #e2e8f0);
        max-width: 100%;
    }

    .linked-lr-party-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 12px;
    }

    .linked-lr-party-group {
        min-width: 0;
    }

    .linked-lr-party-hints {
        display: none;
        flex-wrap: wrap;
        gap: 0.15rem 0.85rem;
        margin-top: 4px;
        padding: 4px 8px;
        border-radius: 6px;
        background: #f8fafc;
        border: 1px solid var(--brand-border, #e2e8f0);
        line-height: 1.3;
    }

    .linked-lr-party-hints:has(.linked-lr-party-hint:not(:empty)) {
        display: flex;
    }

    .linked-lr-party-hint {
        display: none;
        font-size: 0.68rem;
        color: #475569;
        margin: 0;
        min-height: 0;
    }

    .linked-lr-party-hint:not(:empty) {
        display: inline;
    }

    @media (max-width: 991px) {
        .linked-lr-party-grid {
            grid-template-columns: 1fr;
        }
    }

    .linked-lr-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        padding: 12px 14px;
        margin: 0 0 12px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid var(--brand-border, #e2e8f0);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .linked-lr-summary__item {
        text-align: center;
    }

    .linked-lr-summary__label {
        display: block;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #94a3b8;
        margin-bottom: 0.15rem;
    }

    .linked-lr-summary__value {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
    }

    .linked-lr-summary__hint {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.68rem;
        font-weight: 600;
        color: #b45309;
    }

    .linked-lr-summary__hint[hidden] {
        display: none;
    }

    #linked-lr-submit:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        box-shadow: none;
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
        color: var(--brand-text-muted, #64748b);
        border: 1px dashed var(--brand-border, #e2e8f0);
        border-radius: 12px;
        background: #fafbfc;
        font-size: 0.84rem;
    }

    @media (max-width: 767.98px) {
        .linked-lr-summary {
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

    @media (max-width: 575.98px) {
        .linked-lr-card .lrf-grid--6 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
