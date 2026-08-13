/**
 * Themed create/update confirmation for company forms.
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

        if (el.type === 'file') {
            return el.files?.[0]?.name || '';
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

    function logoLabel(isUpdate) {
        const selected = fieldText('company_logo');
        if (selected) {
            return selected;
        }
        if (isUpdate && document.querySelector('img[alt="Company logo"]')) {
            return 'Keep existing logo';
        }
        return 'Not provided';
    }

    function buildSummaryHtml(isUpdate) {
        const note = isUpdate
            ? 'Please review the company details before updating.'
            : 'Please review the company details before registering.';

        return `
            <div class="tb-company-confirm text-start">
                <div class="tb-swal-note tb-swal-note--compact">
                    <i class="bi bi-info-circle"></i>
                    <span>${escapeHtml(note)}</span>
                </div>
                <div class="tb-company-confirm__grid">
                    ${fact('Name', display(fieldText('name')))}
                    ${fact('Short name', display(fieldText('short_name')))}
                    ${fact('Logo', display(logoLabel(isUpdate)))}
                    ${fact('GST number', display(fieldText('gst_number')))}
                    ${fact('PAN number', display(fieldText('pan_number')))}
                    ${fact('GST scheme', display(fieldText('gst_scheme')))}
                    ${fact('GST type', display(fieldText('gst_type')))}
                    ${fact('GST slab', display(fieldText('gst_slab')))}
                    ${fact('Contact person', display(fieldText('contact_person')))}
                    ${fact('Contact number', display(fieldText('contact_number')))}
                    ${fact('Country', display(fieldText('country_id')))}
                    ${fact('State', display(fieldText('state_id')))}
                    ${fact('City', display(fieldText('city_id')))}
                    ${fact('Pin code', display(fieldText('pin_code')))}
                    ${fact('Bank', display(fieldText('bank')))}
                    ${fact('Account number', display(fieldText('account_number')))}
                    ${fact('IFSC', display(fieldText('ifsc')))}
                    ${fact('Address', display(fieldText('address')), 'tb-company-confirm__fact--wide')}
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
        const title = isUpdate ? 'Confirm company update' : 'Confirm company registration';
        const confirmText = isUpdate ? 'Update Company' : 'Register Company';

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
            width: '760px',
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
        const form = document.getElementById('companyForm');
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
