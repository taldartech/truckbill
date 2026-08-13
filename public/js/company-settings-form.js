/**
 * Themed confirmation for company settings.
 */
(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fieldText(id) {
        const el = document.getElementById(id);
        if (!el) {
            return '';
        }

        return String(el.value || '').trim();
    }

    function radioLabel(name) {
        const checked = document.querySelector(`input[name="${name}"]:checked`);
        if (!checked) {
            return '';
        }

        const label = checked.closest('.cs-variant')?.querySelector('.cs-variant-name');
        return (label?.textContent || checked.value || '').trim();
    }

    function editorPlainText(id) {
        const el = document.getElementById(id);
        if (!el) {
            return '';
        }

        const root = el.closest('.app-form-section__body') || el.parentElement;
        const editable = root?.querySelector('.ck-editor__editable');
        if (editable) {
            return (editable.innerText || '').replace(/\s+/g, ' ').trim();
        }

        return String(el.value || '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function display(value) {
        const text = String(value || '').trim();
        return text !== '' ? escapeHtml(text) : '—';
    }

    function fact(label, value, extraClass = '') {
        return `
            <div class="fb-pay-fact${extraClass ? ` ${extraClass}` : ''}">
                <span class="fb-pay-fact__label">${escapeHtml(label)}</span>
                <span class="fb-pay-fact__value">${value}</span>
            </div>
        `;
    }

    function buildSummaryHtml() {
        return `
            <div class="tb-company-confirm text-start">
                <div class="tb-swal-note tb-swal-note--compact">
                    <i class="bi bi-info-circle"></i>
                    <span>Please review the company settings before saving.</span>
                </div>
                <div class="tb-company-confirm__grid">
                    ${fact('Business name', display(fieldText('template_name')))}
                    ${fact('Tagline', display(fieldText('template_header')))}
                    ${fact('Address', display(fieldText('template_address')), 'tb-company-confirm__fact--wide')}
                    ${fact('Jurisdiction', display(fieldText('template_jurisdictional_boundary')))}
                    ${fact('Footer note', display(editorPlainText('template_note')), 'tb-company-confirm__fact--wide')}
                    ${fact('Party bill', display(radioLabel('invoice_template')))}
                    ${fact('LR copy', display(radioLabel('lr_copy_template')))}
                    ${fact('Freight challan', display(radioLabel('freight_challan_template')))}
                </div>
            </div>
        `;
    }

    function submitForm(form) {
        form.dataset.tbConfirmOk = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }
        form.submit();
    }

    function showConfirmation(form) {
        if (typeof window.Swal === 'undefined') {
            if (window.confirm('Confirm company settings\n\nProceed?')) {
                submitForm(form);
            }
            return;
        }

        window.TruckbillUI?.ensureSwalDefaults?.();

        const classes = window.TruckbillUI?.swalClasses || {
            popup: 'tb-swal-popup',
            title: 'tb-swal-title',
            htmlContainer: 'tb-swal-html',
            actions: 'tb-swal-actions',
            confirmButton: 'tb-swal-confirm',
            cancelButton: 'tb-swal-cancel',
        };

        window.Swal.fire({
            title: 'Confirm settings update',
            html: buildSummaryHtml(),
            showCancelButton: true,
            confirmButtonText: 'Save settings',
            cancelButtonText: 'Cancel',
            width: '720px',
            heightAuto: true,
            scrollbarPadding: false,
            customClass: {
                ...classes,
                popup: `${classes.popup || 'tb-swal-popup'} tb-swal-popup--company`,
                htmlContainer: `${classes.htmlContainer || 'tb-swal-html'} tb-swal-html--company`,
            },
        }).then((result) => {
            if (result.isConfirmed) {
                submitForm(form);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.cs-variant-eye').forEach((eye) => {
            eye.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        const form = document.getElementById('companySettingsForm');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (form.dataset.tbConfirmOk === '1') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            showConfirmation(form);
        });
    });
})();
