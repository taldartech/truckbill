<style>
    .sub-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1rem 1rem 1.5rem;
    }

    .sub-page--manage {
        max-width: 1080px;
        padding-bottom: 1rem;
    }

    .sub-back {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--brand-text-muted);
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 1rem;
        transition: color 0.2s ease;
    }

    .sub-back:hover {
        color: var(--brand-primary);
    }

    .sub-hero {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        margin-bottom: 1.25rem;
    }

    .sub-hero-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
    }

    .sub-hero-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
        letter-spacing: -0.02em;
    }

    .sub-hero-subtitle {
        color: var(--brand-text-muted);
        margin: 0;
        font-size: 0.95rem;
    }

    .sub-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        background: var(--brand-soft-primary);
        color: var(--brand-primary);
    }

    .sub-alert {
        border-radius: 14px;
        border: 1px solid transparent;
        padding: 0.85rem 1rem;
        margin-bottom: 1.25rem;
        font-size: 0.9rem;
    }

    .sub-alert-success {
        background: var(--brand-soft-success);
        border-color: rgba(34, 197, 94, 0.2);
        color: #166534;
    }

    .sub-alert-danger {
        background: var(--brand-soft-danger);
        border-color: rgba(239, 68, 68, 0.2);
        color: #991b1b;
    }

    .sub-alert-warning {
        background: #fffbeb;
        border-color: rgba(245, 158, 11, 0.25);
        color: #92400e;
    }

    .sub-alert-info {
        background: #eff6ff;
        border-color: rgba(59, 130, 246, 0.2);
        color: #1e40af;
    }

    .sub-card {
        background: var(--brand-card-bg);
        border: 1px solid var(--brand-border);
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    .sub-card-fill {
        height: 100%;
    }

    .sub-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--brand-border);
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .sub-card-header-gradient {
        background: var(--brand-gradient);
        color: #fff;
        border-bottom: none;
    }

    .sub-card-header-gradient .sub-card-label,
    .sub-card-header-gradient .sub-card-title {
        color: #fff;
    }

    .sub-card-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        color: var(--brand-text-muted);
        margin-bottom: 0.25rem;
    }

    .sub-card-title {
        font-size: 1.125rem;
        font-weight: 700;
        margin: 0;
    }

    .sub-card-body {
        padding: 1.1rem 1.25rem;
    }

    .sub-stat-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .sub-stat {
        background: var(--brand-bg);
        border: 1px solid var(--brand-border);
        border-radius: 14px;
        padding: 0.9rem 1rem;
    }

    .sub-stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--brand-text-muted);
        font-weight: 600;
        margin-bottom: 0.35rem;
    }

    .sub-stat-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--brand-text-primary);
    }

    .sub-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: capitalize;
    }

    .sub-badge-active,
    .sub-badge-trial {
        background: var(--brand-soft-success);
        color: #15803d;
    }

    .sub-badge-expired {
        background: var(--brand-soft-danger);
        color: #b91c1c;
    }

    .sub-badge-cancelled,
    .sub-badge-none {
        background: var(--brand-soft-secondary);
        color: var(--brand-text-muted);
    }

    .sub-table-wrap {
        overflow-x: auto;
    }

    .sub-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .sub-table thead th {
        background: var(--brand-table-header-bg);
        color: var(--brand-text-muted);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 0.9rem 1.25rem;
        text-align: left;
        border-bottom: 1px solid var(--brand-border);
        white-space: nowrap;
    }

    .sub-table tbody td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--brand-border);
        vertical-align: middle;
    }

    .sub-table tbody tr:hover {
        background: var(--brand-row-hover);
    }

    .sub-table tbody tr:last-child td {
        border-bottom: none;
    }

    .sub-company-name {
        font-weight: 700;
        color: var(--brand-text-primary);
    }

    .sub-empty {
        text-align: center;
        padding: 1.5rem 1rem;
        color: var(--brand-text-muted);
    }

    .sub-empty i {
        font-size: 2rem;
        margin-bottom: 0.75rem;
        opacity: 0.45;
    }

    .sub-segment {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
        margin-bottom: 1.25rem;
    }

    .sub-segment-btn {
        border: 1px solid var(--brand-border);
        background: #fff;
        border-radius: 14px;
        padding: 0.85rem 1rem;
        text-align: left;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .sub-segment-btn:hover:not(:disabled) {
        border-color: var(--brand-primary);
        background: var(--brand-active-bg);
    }

    .sub-segment-btn.active {
        border-color: var(--brand-primary);
        background: var(--brand-active-bg);
        box-shadow: 0 0 0 3px var(--brand-input-focus-shadow);
    }

    .sub-segment-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .sub-segment-title {
        display: block;
        font-weight: 700;
        font-size: 0.9rem;
        margin-bottom: 0.15rem;
    }

    .sub-segment-desc {
        display: block;
        font-size: 0.78rem;
        color: var(--brand-text-muted);
    }

    .sub-field {
        margin-bottom: 1rem;
    }

    .sub-field label {
        display: block;
        font-size: 0.8125rem;
        font-weight: 700 !important;
        color: var(--brand-text-primary);
        margin-bottom: 0.45rem;
    }

    .sub-input,
    .sub-select {
        width: 100%;
        height: 44px;
        border: 1px solid var(--brand-border);
        border-radius: 12px;
        padding: 0 0.9rem;
        background: #fff;
        font-size: 0.9rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .sub-input:focus,
    .sub-select:focus {
        outline: none;
        border-color: var(--brand-primary);
        box-shadow: 0 0 0 3px var(--brand-input-focus-shadow);
    }

    .sub-addon-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
        margin-bottom: 1.25rem;
    }

    .sub-promo-row {
        display: flex;
        gap: 0.65rem;
        margin-bottom: 1rem;
    }

    .sub-promo-row .sub-input {
        flex: 1;
    }

    .sub-btn-secondary {
        height: 44px;
        padding: 0 1rem;
        border-radius: 12px;
        border: 1px solid var(--brand-border);
        background: #fff;
        color: var(--brand-text-primary);
        font-weight: 600;
        font-size: 0.875rem;
        white-space: nowrap;
        transition: all 0.2s ease;
    }

    .sub-btn-secondary:hover {
        border-color: var(--brand-primary);
        color: var(--brand-primary);
        background: var(--brand-active-bg);
    }

    .sub-btn-primary {
        width: 100%;
        height: 50px;
        border: none;
        border-radius: 14px;
        background: var(--brand-gradient);
        color: #fff;
        font-weight: 700;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        box-shadow: 0 10px 24px rgba(47, 179, 111, 0.25);
    }

    .sub-btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(47, 179, 111, 0.32);
        color: #fff;
    }

    .sub-btn-primary:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        box-shadow: none;
    }

    .sub-btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        height: 42px;
        padding: 0 1rem;
        border-radius: 12px;
        border: 1px solid var(--brand-border);
        background: #fff;
        color: var(--brand-text-primary);
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    .sub-btn-outline:hover {
        border-color: var(--brand-primary);
        color: var(--brand-primary);
        background: var(--brand-active-bg);
    }

    .sub-quote-box {
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid var(--brand-border);
        border-radius: 16px;
        padding: 1.1rem 1.15rem;
        margin-bottom: 1rem;
    }

    .sub-quote-lines {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        min-height: 2rem;
    }

    .sub-quote-line {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        font-size: 0.875rem;
        color: var(--brand-text-muted);
    }

    .sub-quote-line span:last-child {
        font-weight: 600;
        color: var(--brand-text-primary);
        white-space: nowrap;
    }

    .sub-quote-line.discount span:last-child {
        color: var(--brand-success);
    }

    .sub-quote-total {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 0.85rem;
        margin-top: 0.85rem;
        border-top: 1px dashed var(--brand-border);
    }

    .sub-quote-total-label {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--brand-text-primary);
    }

    .sub-quote-total-value {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--brand-primary);
    }

    .sub-error {
        color: var(--brand-danger);
        font-size: 0.875rem;
        font-weight: 600;
        min-height: 1.25rem;
        margin-bottom: 0.75rem;
    }

    .sub-success-note {
        color: #15803d;
        font-size: 0.875rem;
        font-weight: 600;
        min-height: 1.25rem;
        margin-bottom: 0.75rem;
    }

    .sub-plan-price {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1;
        margin: 0.35rem 0 0;
    }

    .sub-plan-meta {
        font-size: 0.8125rem;
        opacity: 0.9;
    }

    .sub-summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .sub-summary-card {
        background: var(--brand-card-bg);
        border: 1px solid var(--brand-border);
        border-radius: 16px;
        padding: 1rem 1.1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }

    .sub-summary-card .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--brand-text-muted);
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .sub-summary-card .value {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--brand-text-primary);
    }

    .sub-action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-bottom: 0;
    }

    .sub-manage-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        align-items: start;
    }

    .sub-manage-span-2 {
        grid-column: span 2;
    }

    .sub-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        height: 46px;
        padding: 0 1.25rem;
        border-radius: 14px;
        font-weight: 700;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        border: none;
        text-decoration: none;
    }

    .sub-action-btn-primary {
        background: var(--brand-gradient);
        color: #fff;
        box-shadow: 0 10px 24px rgba(47, 179, 111, 0.22);
    }

    .sub-action-btn-primary:hover {
        transform: translateY(-1px);
        color: #fff;
        box-shadow: 0 14px 28px rgba(47, 179, 111, 0.3);
    }

    .sub-action-btn-secondary {
        background: #fff;
        color: var(--brand-text-primary);
        border: 1px solid var(--brand-border);
    }

    .sub-action-btn-secondary:hover:not(.disabled) {
        border-color: var(--brand-primary);
        color: var(--brand-primary);
        background: var(--brand-active-bg);
    }

    .sub-action-btn.disabled {
        opacity: 0.5;
        pointer-events: none;
        cursor: not-allowed;
    }

    .sub-usage-list {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .sub-usage-item-head {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.45rem;
        font-size: 0.875rem;
    }

    .sub-usage-item-head strong {
        font-weight: 700;
        color: var(--brand-text-primary);
    }

    .sub-usage-item-head span {
        color: var(--brand-text-muted);
        font-weight: 600;
    }

    .sub-usage-bar {
        height: 8px;
        border-radius: 999px;
        background: var(--brand-border);
        overflow: hidden;
    }

    .sub-usage-bar-fill {
        height: 100%;
        border-radius: 999px;
        background: var(--brand-gradient);
        transition: width 0.3s ease;
    }

    .sub-usage-bar-fill.warning {
        background: var(--brand-gradient-warning);
    }

    .sub-usage-bar-fill.danger {
        background: var(--brand-gradient-danger);
    }

    .sub-plan-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .sub-plan-item {
        background: var(--brand-bg);
        border: 1px solid var(--brand-border);
        border-radius: 14px;
        padding: 0.85rem 1rem;
    }

    .sub-plan-item .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--brand-text-muted);
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .sub-plan-item .value {
        font-size: 1rem;
        font-weight: 700;
    }

    @media (max-width: 767.98px) {
        .sub-hero-title {
            font-size: 1.45rem;
        }

        .sub-segment,
        .sub-addon-grid,
        .sub-stat-grid,
        .sub-plan-grid,
        .sub-manage-grid {
            grid-template-columns: 1fr;
        }

        .sub-manage-span-2 {
            grid-column: span 1;
        }

        .sub-promo-row {
            flex-direction: column;
        }
    }
</style>
