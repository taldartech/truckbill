(function (window) {
    'use strict';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatAmount(value) {
        return '₹' + Number(value || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function formatDate(value) {
        if (!value) {
            return '—';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return escapeHtml(value);
        }

        return date.toLocaleDateString('en-GB');
    }

    function buildStatRow(label, value, options) {
        const valueClass = options?.valueClass ? ` ${options.valueClass}` : '';

        return `
            <div class="d-flex justify-content-between gap-3 py-1 border-bottom border-light-subtle">
                <span class="text-muted">${escapeHtml(label)}</span>
                <span class="fw-semibold text-end${valueClass}">${value}</span>
            </div>
        `;
    }

    function buildSummaryShell(title, bodyHtml) {
        return `
            <div class="text-start">
                <div class="alert alert-soft-primary bg-primary bg-opacity-10 border-0 mb-3 py-2 px-3 rounded-3">
                    <div class="fw-semibold text-primary mb-0">${escapeHtml(title)}</div>
                </div>
                ${bodyHtml}
            </div>
        `;
    }

    function confirmDialog(options) {
        if (typeof window.Swal === 'undefined') {
            return Promise.resolve(window.confirm(options.fallbackText || 'Are you sure?'));
        }

        return window.Swal.fire({
            title: options.title,
            html: options.html,
            icon: options.icon || 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: options.confirmText || 'Confirm',
            cancelButtonText: options.cancelText || 'Cancel',
            width: options.width || '560px',
            customClass: options.customClass || {},
        }).then(function (result) {
            return Boolean(result.isConfirmed);
        });
    }

    function buildInvoiceSummaryHtml(data) {
        const taxLines = (data.taxLines || []).map(function (line) {
            return buildStatRow(line.label, formatAmount(line.amount), {
                valueClass: line.muted ? 'text-muted fw-normal' : '',
            });
        }).join('');

        const bodyHtml = `
            ${buildStatRow('Customer', escapeHtml(data.customer || '—'))}
            ${buildStatRow('Bill date', formatDate(data.generatedDate))}
            ${data.reference ? buildStatRow('Reference #', escapeHtml(data.reference)) : ''}
            ${data.memos ? buildStatRow('Memos', escapeHtml(data.memos)) : ''}
            ${data.lrMeta ? buildStatRow('LRs', escapeHtml(data.lrMeta)) : ''}
            ${data.invoiceType ? buildStatRow('Invoice type', escapeHtml(data.invoiceType)) : ''}
            ${data.subtotal ? buildStatRow('LR subtotal', formatAmount(data.subtotal)) : ''}
            ${taxLines}
            <div class="d-flex justify-content-between align-items-center mt-3 p-3 rounded-3 bg-light border">
                <span class="fw-semibold">${escapeHtml(data.totalLabel || 'Invoice total')}</span>
                <span class="fs-5 fw-bold text-primary">${formatAmount(data.totalAmount)}</span>
            </div>
            ${data.netAmount !== undefined && data.netAmount !== null && Math.abs(Number(data.netAmount) - Number(data.totalAmount)) > 0.009
                ? buildStatRow('Net amount to collect', formatAmount(data.netAmount))
                : ''}
        `;

        return buildSummaryShell(data.title || 'Review invoice details', bodyHtml);
    }

    function buildPaymentSummaryHtml(data) {
        const bodyHtml = `
            ${buildStatRow('Invoice', escapeHtml(data.invoiceNumber || '—'))}
            ${buildStatRow('Payment date', formatDate(data.paymentDate))}
            ${buildStatRow('Mode', escapeHtml(data.mode || '—'))}
            ${buildStatRow('Amount', formatAmount(data.amount), { valueClass: 'text-success' })}
            ${data.remark ? buildStatRow('Remark', escapeHtml(data.remark)) : ''}
            <div class="mt-3 p-3 rounded-3 bg-light border">
                ${buildStatRow('Invoice total', formatAmount(data.invoiceTotal))}
                ${buildStatRow('Already paid', formatAmount(data.paidAmount), { valueClass: 'text-success' })}
                ${buildStatRow('Balance before payment', formatAmount(data.balanceBefore), { valueClass: 'text-danger' })}
                ${buildStatRow('Balance after payment', formatAmount(data.balanceAfter), { valueClass: 'text-danger fw-bold' })}
            </div>
        `;

        return buildSummaryShell('Review payment details', bodyHtml);
    }

    function confirmInvoiceSummary(data) {
        return confirmDialog({
            title: data.dialogTitle || 'Confirm invoice',
            html: buildInvoiceSummaryHtml(data),
            confirmText: data.confirmText || 'Generate invoice',
            fallbackText: 'Generate this invoice?',
            width: data.width || '620px',
        });
    }

    function confirmPaymentSummary(data) {
        return confirmDialog({
            title: 'Confirm payment',
            html: buildPaymentSummaryHtml(data),
            confirmText: 'Record payment',
            fallbackText: 'Record this payment?',
            width: '560px',
        });
    }

    window.InvoiceConfirmation = {
        escapeHtml: escapeHtml,
        formatAmount: formatAmount,
        formatDate: formatDate,
        buildInvoiceSummaryHtml: buildInvoiceSummaryHtml,
        buildPaymentSummaryHtml: buildPaymentSummaryHtml,
        confirmInvoiceSummary: confirmInvoiceSummary,
        confirmPaymentSummary: confirmPaymentSummary,
    };
})(window);
