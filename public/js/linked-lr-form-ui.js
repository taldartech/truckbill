(function () {
    const currencyFormatter = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    function formatCurrency(value) {
        const amount = Number(value) || 0;
        return currencyFormatter.format(amount);
    }

    function updateRoutePreview(scope) {
        if (!scope) {
            return;
        }

        const sourceCity = scope.querySelector('.source-city')?.value?.trim() || '';
        const sourceState = scope.querySelector('.source-state')?.value?.trim() || '';
        const destinationCity = scope.querySelector('.destination-city')?.value?.trim() || '';
        const destinationState = scope.querySelector('.destination-state')?.value?.trim() || '';

        const source = [sourceCity, sourceState].filter(Boolean).join(', ');
        const destination = [destinationCity, destinationState].filter(Boolean).join(', ');
        const preview = scope.querySelector('.lr-route-preview');
        const banner = scope.querySelector('.lr-route-banner');
        const sourceText = scope.querySelector('.route-source-text');
        const destinationText = scope.querySelector('.route-destination-text');

        if (preview) {
            preview.textContent = source && destination
                ? `${source} → ${destination}`
                : 'Select consignor and consignee to show route';
        }

        if (banner && sourceText && destinationText) {
            if (source || destination) {
                sourceText.textContent = source || '—';
                destinationText.textContent = destination || '—';
                banner.classList.remove('d-none');
            } else {
                banner.classList.add('d-none');
            }
        }
    }

    function updateItemMeta(item) {
        if (!item) {
            return;
        }

        const amount = Number(item.querySelector('.amount')?.value) || 0;
        const lrNumber = item.querySelector('.lr-number-input')?.value?.trim();
        const amountBadge = item.querySelector('.lr-amount-badge');

        if (amountBadge) {
            amountBadge.textContent = formatCurrency(amount);
        }

        const title = item.querySelector('.linked-lr-card__title h6');
        if (title && lrNumber) {
            const index = item.querySelector('.lr-index')?.textContent || '';
            title.innerHTML = `Lorry Receipt <span class="lr-index">${index}</span> <span class="text-muted fw-normal">· ${lrNumber}</span>`;
        }

        updateRoutePreview(item);
    }

    function updateSummary() {
        const items = document.querySelectorAll('#lr-items-container .lr-item');
        const countEl = document.getElementById('linked-lr-count');
        const weightEl = document.getElementById('linked-lr-total-weight');
        const amountEl = document.getElementById('linked-lr-total-amount');
        const toolbarCount = document.getElementById('linked-lr-toolbar-count');

        let totalWeight = 0;
        let totalAmount = 0;

        items.forEach((item) => {
            totalWeight += Number(item.querySelector('.weight')?.value) || 0;
            totalAmount += Number(item.querySelector('.amount')?.value) || 0;
            updateItemMeta(item);
        });

        if (countEl) {
            countEl.textContent = String(items.length);
        }

        if (toolbarCount) {
            toolbarCount.textContent = `${items.length} item${items.length === 1 ? '' : 's'}`;
        }

        if (weightEl) {
            weightEl.textContent = `${totalWeight.toFixed(3)} t`;
        }

        if (amountEl) {
            amountEl.textContent = formatCurrency(totalAmount);
        }
    }

    function refreshIndices() {
        const items = document.querySelectorAll('#lr-items-container .lr-item');
        items.forEach((item, idx) => {
            const label = `#${idx + 1}`;
            const indexEl = item.querySelector('.lr-index');
            const badgeEl = item.querySelector('.lr-index-badge');

            if (indexEl) {
                indexEl.textContent = label;
            }

            if (badgeEl) {
                badgeEl.textContent = String(idx + 1);
            }
        });

        updateSummary();
    }

    function bind(container) {
        if (!container || container.dataset.linkedLrUiBound === '1') {
            return;
        }

        container.dataset.linkedLrUiBound = '1';

        container.addEventListener('input', (event) => {
            const item = event.target.closest('.lr-item');
            if (!item) {
                return;
            }

            updateItemMeta(item);
            updateSummary();
        });

        container.addEventListener('change', (event) => {
            const item = event.target.closest('.lr-item');
            if (!item) {
                return;
            }

            updateItemMeta(item);
            updateSummary();
        });
    }

    window.LinkedLrFormUI = {
        formatCurrency,
        updateRoutePreview,
        updateItemMeta,
        updateSummary,
        refreshIndices,
        bind,
        init() {
            const container = document.getElementById('lr-items-container');
            bind(container);
            updateSummary();
        },
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('lorryReceiptForm')?.classList.contains('linked-lr-form')) {
            window.LinkedLrFormUI.init();
        }
    });
})();
