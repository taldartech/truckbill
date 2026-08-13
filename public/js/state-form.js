/**
 * Themed create confirmation for master states.
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

        if (el.tagName === 'SELECT') {
            const option = el.options[el.selectedIndex];
            const text = (option?.text || '').trim();
            if (!text || option.value === '') {
                return '';
            }
            return text;
        }

        return String(el.value || '').trim();
    }

    function display(value) {
        const text = String(value || '').trim();
        return text !== '' ? escapeHtml(text) : '—';
    }

    function fact(label, value) {
        return `
            <div class="fb-pay-fact">
                <span class="fb-pay-fact__label">${escapeHtml(label)}</span>
                <span class="fb-pay-fact__value">${value}</span>
            </div>
        `;
    }

    function buildSummaryHtml() {
        return `
            <div class="tb-company-confirm text-start">
                <div class="tb-swal-note tb-swal-note--warn tb-swal-note--compact">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Please check the spelling again before saving. Once saved, a state cannot be updated.</span>
                </div>
                <div class="tb-company-confirm__grid">
                    ${fact('State name', display(fieldText('name')))}
                    ${fact('Country', display(fieldText('country_id')))}
                    ${fact('Code', display(fieldText('code')))}
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
            if (window.confirm('Please check the spelling again before saving. Once saved, a state cannot be updated.\n\nProceed?')) {
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
            title: 'Confirm state creation',
            html: buildSummaryHtml(),
            showCancelButton: true,
            confirmButtonText: 'Save State',
            cancelButtonText: 'Cancel',
            width: '560px',
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
        const form = document.getElementById('stateForm');
        if (!form || form.dataset.confirmMode !== 'create') {
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
