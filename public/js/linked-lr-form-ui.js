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

        const form = document.getElementById('lorryReceiptForm');
        const isEdit = form?.classList.contains('linked-lr-form--edit');
        const submitBtn = document.getElementById('linked-lr-submit');
        const minHint = document.getElementById('linked-lr-min-hint');
        const canSubmit = items.length >= 2;

        if (submitBtn && !isEdit) {
            submitBtn.disabled = !canSubmit;
            submitBtn.title = canSubmit
                ? ''
                : 'Add at least 2 lorry receipts to create a linked group';
        }

        if (minHint) {
            minHint.hidden = canSubmit;
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

    function displayOrDash(value) {
        const text = (value || '').toString().trim();
        if (!text || text === 'No Truck Owner' || text === '--' || text === 'Loading...' || text === 'Error') {
            return '—';
        }
        return text;
    }

    function initPayeeUi() {
        const form = document.getElementById('lorryReceiptForm');
        if (form?.dataset.linkedLrPayeeUi === '1') {
            if (typeof window.syncLrPayeeVisibility === 'function') {
                window.syncLrPayeeVisibility();
            }
            return;
        }

        const truckSelect = document.getElementById('truck_id');
        const lrTruckSummaryTds = document.getElementById('lrTruckSummaryTds');
        const lrTruckSummaryDriver = document.getElementById('lrTruckSummaryDriver');
        const lrTruckSummaryDriverItem = document.getElementById('lrTruckSummaryDriverItem');
        const lrTruckSummaryDriverSep = document.getElementById('lrTruckSummaryDriverSep');
        const tdsPercentageValue = document.getElementById('tdsPercentageValue');
        const lrPayToField = document.getElementById('lrPayToField');
        const lrPayeeRow = document.getElementById('lrPayeeRow');

        if (!truckSelect && !lrPayToField) {
            return;
        }

        function syncTruckSummaryTds() {
            if (!lrTruckSummaryTds) {
                return;
            }
            lrTruckSummaryTds.textContent = displayOrDash(tdsPercentageValue ? tdsPercentageValue.textContent : '');
        }

        function syncPayeeFieldLockStyle() {
            const transporterSelect = document.getElementById('transporter_id');
            if (!lrPayeeRow || !transporterSelect) {
                return;
            }
            lrPayeeRow.classList.toggle('is-payee-locked', !!transporterSelect.disabled);
        }

        function syncPayeeVisibility() {
            const hasTruck = !!(truckSelect && truckSelect.value);
            const transporterTypeSelect = document.getElementById('transporter_type');
            const transporterSelect = document.getElementById('transporter_id');
            const payType = transporterTypeSelect ? transporterTypeSelect.value : '';
            const hasPayType = payType === 'owner' || payType === 'agency';

            if (lrPayToField) {
                lrPayToField.hidden = !hasTruck;
            }
            if (lrPayeeRow) {
                lrPayeeRow.hidden = !hasTruck || !hasPayType;
            }
            if (transporterTypeSelect) {
                transporterTypeSelect.required = hasTruck;
            }
            if (transporterSelect) {
                transporterSelect.required = hasTruck && hasPayType;
            }
            syncPayeeFieldLockStyle();
        }

        function updateTruckMetaSummary(ownerName, driverName) {
            const driver = displayOrDash(driverName);
            const hasTruck = !!(truckSelect && truckSelect.value);
            const hasDriver = driver !== '—';
            const transporterTypeSelect = document.getElementById('transporter_type');
            const transporterSelect = document.getElementById('transporter_id');
            const wasTruckSelected = !!(lrPayToField && !lrPayToField.hidden);

            if (lrTruckSummaryDriver) {
                lrTruckSummaryDriver.textContent = driver;
            }
            if (lrTruckSummaryDriverItem) {
                lrTruckSummaryDriverItem.hidden = !(hasTruck && hasDriver);
            }
            if (lrTruckSummaryDriverSep) {
                lrTruckSummaryDriverSep.hidden = !(hasTruck && hasDriver);
            }
            syncTruckSummaryTds();
            syncPayeeVisibility();

            if (!hasTruck && wasTruckSelected) {
                if (transporterTypeSelect) {
                    transporterTypeSelect.value = '';
                    transporterTypeSelect.dispatchEvent(new Event('change'));
                }
                document.querySelectorAll('.lrf-pay-pill').forEach(function (pill) {
                    pill.classList.remove('is-active');
                    if (pill.getAttribute('data-pay-type') === 'owner') {
                        pill.disabled = false;
                    }
                });
                if (transporterSelect && window.jQuery && window.jQuery(transporterSelect).hasClass('select2-hidden-accessible')) {
                    window.jQuery(transporterSelect).val(null).trigger('change');
                } else if (transporterSelect) {
                    transporterSelect.value = '';
                }
                const tdsDisplay = document.getElementById('tdsRateDisplay');
                const tdsValue = document.getElementById('tdsPercentageValue');
                if (tdsDisplay) {
                    tdsDisplay.style.display = 'none';
                }
                if (tdsValue) {
                    tdsValue.textContent = '--';
                }
                if (typeof window.LREntity !== 'undefined' && typeof window.LREntity.showTdsPercentage === 'function') {
                    window.LREntity.showTdsPercentage(null);
                }
                if (lrTruckSummaryDriverItem) {
                    lrTruckSummaryDriverItem.hidden = true;
                }
                if (lrTruckSummaryDriverSep) {
                    lrTruckSummaryDriverSep.hidden = true;
                }
                syncPayeeVisibility();
            }
        }

        function readTruckMetaFromSelection() {
            if (!truckSelect || !truckSelect.value) {
                updateTruckMetaSummary('', '');
                return;
            }

            let ownerName = '';
            let driverName = '';

            if (window.jQuery) {
                const selected = window.jQuery('#truck_id').select2('data');
                const data = selected && selected[0] ? selected[0] : null;
                if (data) {
                    ownerName = data.transporter_name || '';
                    driverName = data.driver_name || '';
                    if (!ownerName && data.text) {
                        const match = String(data.text).match(/\(([^)]+)\)$/);
                        ownerName = match ? match[1].trim() : '';
                    }
                    if (!driverName && data.text) {
                        const driverMatch = String(data.text).match(/Driver:\s*([^-—(]+)/i);
                        driverName = driverMatch ? driverMatch[1].trim() : '';
                    }
                }
            }

            const option = truckSelect.options[truckSelect.selectedIndex];
            if (option) {
                if (!ownerName) {
                    ownerName = option.getAttribute('data-transporter-name') || '';
                }
                if (!driverName) {
                    driverName = option.getAttribute('data-driver-name') || '';
                }
            }

            updateTruckMetaSummary(ownerName, driverName);

            if (truckSelect.value && (!ownerName || !driverName || ownerName === '—')) {
                fetch(`/api/trucks/${encodeURIComponent(truckSelect.value)}`)
                    .then((response) => (response.ok ? response.json() : null))
                    .then((data) => {
                        if (!data || String(data.id) !== String(truckSelect.value)) {
                            return;
                        }
                        const fetchedOwner = data.transporter
                            ? (data.transporter.name || data.transporter.short_name || '')
                            : '';
                        const fetchedDriver = data.driver ? (data.driver.name || '') : '';
                        updateTruckMetaSummary(
                            ownerName && ownerName !== '—' ? ownerName : fetchedOwner,
                            driverName && driverName !== '—' ? driverName : fetchedDriver
                        );
                    })
                    .catch(() => {});
            }
        }

        window.syncLrPayeeVisibility = syncPayeeVisibility;
        window.syncLrPayeeFieldLockStyle = syncPayeeFieldLockStyle;
        window.syncLrTruckMetaAfterClear = function () {
            updateTruckMetaSummary('', '');
            syncPayeeVisibility();
        };

        if (form) {
            form.dataset.linkedLrPayeeUi = '1';
        }

        if (truckSelect) {
            truckSelect.addEventListener('change', readTruckMetaFromSelection);
        }
        if (window.jQuery) {
            window.jQuery(document)
                .off('select2:select.lrTruckMeta select2:clear.lrTruckMeta change.lrTruckMeta', '#truck_id')
                .on(
                    'select2:select.lrTruckMeta select2:clear.lrTruckMeta change.lrTruckMeta',
                    '#truck_id',
                    function () {
                        setTimeout(readTruckMetaFromSelection, 0);
                    }
                );
        }

        if (tdsPercentageValue && typeof MutationObserver !== 'undefined') {
            const tdsObserver = new MutationObserver(syncTruckSummaryTds);
            tdsObserver.observe(tdsPercentageValue, {
                childList: true,
                characterData: true,
                subtree: true,
            });
        }

        const transporterSelectForLock = document.getElementById('transporter_id');
        if (transporterSelectForLock && typeof MutationObserver !== 'undefined') {
            const payeeLockObserver = new MutationObserver(syncPayeeFieldLockStyle);
            payeeLockObserver.observe(transporterSelectForLock, {
                attributes: true,
                attributeFilter: ['disabled'],
            });
        }
        if (window.jQuery) {
            window.jQuery(document).on(
                'select2:select.lrPayeeLock select2:clear.lrPayeeLock change.lrPayeeLock',
                '#transporter_id',
                function () {
                    setTimeout(syncPayeeFieldLockStyle, 0);
                }
            );
        }

        readTruckMetaFromSelection();
        syncTruckSummaryTds();
        syncPayeeVisibility();
        syncPayeeFieldLockStyle();
    }

    function parseTruckOptionDisplay(rawText, extra = {}) {
        const text = String(rawText || '').replace(/\s+/g, ' ').trim();
        let truckNumber = String(extra.truckNumber || '').trim();
        let driverName = String(extra.driverName || '').trim();

        if (text) {
            const driverMatch = text.match(/\s*-\s*Driver:\s*(.+?)(?:\s*\(([^)]*)\)\s*)?$/i);
            if (driverMatch) {
                if (!driverName) {
                    driverName = driverMatch[1].trim();
                }
                if (!truckNumber) {
                    truckNumber = text.slice(0, driverMatch.index).trim();
                }
            } else if (!truckNumber) {
                truckNumber = text.replace(/\s*\([^)]*\)\s*$/, '').trim();
            }
        }

        return {
            truckNumber,
            driverName,
        };
    }

    function getSharedTransportDisplay() {
        const extra = {};
        const select = document.getElementById('truck_id');
        const option = select?.options[select.selectedIndex];

        if (option && option.value) {
            extra.truckNumber = option.getAttribute('data-truck-number') || '';
            extra.driverName = option.getAttribute('data-driver-name') || '';
        }

        if (window.jQuery) {
            const $truck = window.jQuery('#truck_id');
            if ($truck.length && $truck.hasClass('select2-hidden-accessible')) {
                const data = $truck.select2('data')?.[0];
                if (data) {
                    extra.driverName = extra.driverName || data.driver_name || '';
                    extra.truckNumber = extra.truckNumber || data.truck_number || '';
                    const parsed = parseTruckOptionDisplay(data.text, extra);
                    extra.truckNumber = parsed.truckNumber;
                    extra.driverName = parsed.driverName;
                }
            }
        }

        if ((!extra.truckNumber || !extra.driverName) && option && option.value) {
            const parsed = parseTruckOptionDisplay(option.textContent, extra);
            extra.truckNumber = extra.truckNumber || parsed.truckNumber;
            extra.driverName = extra.driverName || parsed.driverName;
        }

        if (!extra.driverName) {
            const summary = document.getElementById('lrTruckSummaryDriver')?.textContent?.trim();
            if (summary && summary !== '—') {
                extra.driverName = summary;
            }
        }

        return {
            truckNumber: extra.truckNumber || 'Not selected',
            driverName: extra.driverName || '—',
        };
    }

    window.LinkedLrFormUI = {
        formatCurrency,
        updateRoutePreview,
        updateItemMeta,
        updateSummary,
        refreshIndices,
        bind,
        initPayeeUi,
        getSharedTransportDisplay,
        init() {
            const container = document.getElementById('lr-items-container');
            bind(container);
            updateSummary();
            const startPayeeUi = () => initPayeeUi();
            if (window.jQuery) {
                startPayeeUi();
            } else {
                const waitForJQuery = () => {
                    if (window.jQuery) {
                        startPayeeUi();
                        return;
                    }
                    setTimeout(waitForJQuery, 50);
                };
                waitForJQuery();
            }
        },
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('lorryReceiptForm')?.classList.contains('linked-lr-form')) {
            window.LinkedLrFormUI.init();
        }
    });
})();
