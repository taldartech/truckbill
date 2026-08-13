/**
 * Themed create/update confirmation for branch forms.
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

    function fact(label, value, extraClass = '') {
        return `
            <div class="fb-pay-fact${extraClass ? ` ${extraClass}` : ''}">
                <span class="fb-pay-fact__label">${escapeHtml(label)}</span>
                <span class="fb-pay-fact__value">${value}</span>
            </div>
        `;
    }

    function buildSummaryHtml(isUpdate) {
        const note = isUpdate
            ? 'Please review the branch details before updating.'
            : 'Please review the branch details before registering.';

        return `
            <div class="tb-company-confirm text-start">
                <div class="tb-swal-note tb-swal-note--compact">
                    <i class="bi bi-info-circle"></i>
                    <span>${escapeHtml(note)}</span>
                </div>
                <div class="tb-company-confirm__grid">
                    ${fact('Company', display(fieldText('company_id')))}
                    ${fact('Branch name', display(fieldText('branch_name')))}
                    ${fact('Contact number', display(fieldText('branch_contact_number')))}
                    ${fact('Head office', display(fieldText('head_office')))}
                    ${fact('Address', display(fieldText('branch_address')), 'tb-company-confirm__fact--wide')}
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
        const isUpdate = form.dataset.confirmMode === 'update';
        const title = isUpdate ? 'Confirm branch update' : 'Confirm branch registration';
        const confirmText = isUpdate ? 'Update Branch' : 'Register Branch';

        if (typeof window.Swal === 'undefined') {
            if (window.confirm(`${title}\n\nProceed?`)) {
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
            title,
            html: buildSummaryHtml(isUpdate),
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancel',
            width: '640px',
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
        const contactInput = document.getElementById('branch_contact_number');
        contactInput?.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });

        const form = document.getElementById('branchForm');
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
