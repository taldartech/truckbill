/**
 * Branch filtering + themed create/update confirmation for user forms.
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

        if (el.type === 'password') {
            return el.value ? 'Set' : '';
        }

        if (el.type === 'hidden' && el.id === 'role') {
            return String(el.value || '')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
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
            ? 'Please review the user details before updating.'
            : 'Please review the user details before creating.';

        let facts = fact('Name', display(fieldText('name')))
            + fact('Email', display(fieldText('email')))
            + fact('Role', display(fieldText('role')));

        if (document.getElementById('company_id')) {
            facts += fact('Company', display(fieldText('company_id')));
        }

        if (document.getElementById('branch_id')) {
            facts += fact('Branch', display(fieldText('branch_id')));
        }

        const passwordLabel = fieldText('password') || (isUpdate ? 'Unchanged' : '');
        facts += fact('Password', display(passwordLabel));

        return `
            <div class="tb-company-confirm text-start">
                <div class="tb-swal-note tb-swal-note--compact">
                    <i class="bi bi-info-circle"></i>
                    <span>${escapeHtml(note)}</span>
                </div>
                <div class="tb-company-confirm__grid">
                    ${facts}
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

    function passwordsMatch(form) {
        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');
        if (!password || !confirmation) {
            return true;
        }

        const isUpdate = form.dataset.confirmMode === 'update';
        const passwordValue = password.value || '';
        const confirmationValue = confirmation.value || '';

        if (isUpdate && passwordValue === '' && confirmationValue === '') {
            confirmation.setCustomValidity('');
            return true;
        }

        if (!confirmationValue) {
            confirmation.setCustomValidity('Please confirm the password.');
            return false;
        }

        if (passwordValue !== confirmationValue) {
            confirmation.setCustomValidity('Password confirmation does not match.');
            return false;
        }

        confirmation.setCustomValidity('');
        return true;
    }

    function showConfirmation(form) {
        const isUpdate = form.dataset.confirmMode === 'update';
        const title = isUpdate ? 'Confirm user update' : 'Confirm user creation';
        const confirmText = isUpdate ? 'Update User' : 'Create User';

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

    function filterBranches(branchSelect, companyId, selectedBranchId = '') {
        const options = Array.from(branchSelect.querySelectorAll('option'));
        let hasVisibleOptions = false;

        options.forEach((option) => {
            if (option.value === '' && option.id !== 'no-branch-option') {
                return;
            }

            if (option.id === 'no-branch-option') {
                option.remove();
                return;
            }

            const matches = Boolean(companyId) && option.getAttribute('data-company') === String(companyId);
            option.hidden = !matches;
            option.disabled = !matches;
            if (matches) {
                hasVisibleOptions = true;
            }
        });

        if (!hasVisibleOptions && companyId) {
            const emptyOption = document.createElement('option');
            emptyOption.id = 'no-branch-option';
            emptyOption.value = '';
            emptyOption.textContent = 'No branches available';
            branchSelect.appendChild(emptyOption);
        }

        if (selectedBranchId) {
            const selected = branchSelect.querySelector(`option[value="${CSS.escape(String(selectedBranchId))}"]`);
            if (selected && !selected.hidden) {
                branchSelect.value = selectedBranchId;
                return;
            }
        }

        branchSelect.value = '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('userForm');
        const companySelect = document.getElementById('company_id');
        const branchSelect = document.getElementById('branch_id');
        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');

        if (companySelect && branchSelect) {
            companySelect.addEventListener('change', function () {
                filterBranches(branchSelect, this.value);
            });

            if (companySelect.value) {
                filterBranches(branchSelect, companySelect.value, form?.dataset.oldBranchId || '');
            }
        }

        [password, confirmation].forEach((input) => {
            input?.addEventListener('input', function () {
                confirmation?.setCustomValidity('');
            });
        });

        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (form.dataset.tbConfirmOk === '1') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const match = passwordsMatch(form);
            if (!form.checkValidity() || !match) {
                form.classList.add('was-validated');
                return;
            }

            showConfirmation(form);
        });
    });
})();
