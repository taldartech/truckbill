{{-- Shared Quick Add Pump for freight booking voucher payments (normal + linked) --}}
<style>
    #voucherPaymentModal.pump-modal-open,
    #combinedPaymentModal.pump-modal-open {
        pointer-events: none;
    }

    #voucherPaymentModal.pump-modal-open .modal-content,
    #combinedPaymentModal.pump-modal-open .modal-content {
        opacity: 0.7;
    }

    #quickAddPumpModal {
        pointer-events: auto;
        z-index: 1065;
    }

    #quickAddPumpModal .modal-header {
        background: var(--brand-primary, #20b486);
        color: #fff;
    }
</style>

<div class="modal fade" id="quickAddPumpModal" tabindex="-1"
    aria-labelledby="quickAddPumpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddPumpModalLabel">
                    <i class="bi bi-fuel-pump"></i> Add New Pump
                </h5>
                <button type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="quickAddPumpForm"
                method="POST"
                action="{{ route('master.pump.store') }}">
                @csrf

                <div class="modal-body">
                    <div id="quickPumpError" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="quick_pump_name" class="form-label">Pump Name *</label>
                            <input type="text"
                                class="form-control"
                                id="quick_pump_name"
                                name="pump_name"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label for="quick_contact_name" class="form-label">Contact Person *</label>
                            <input type="text"
                                class="form-control"
                                id="quick_contact_name"
                                name="contact_name"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label for="quick_contact_number" class="form-label">Contact Number *</label>
                            <input type="text"
                                class="form-control"
                                id="quick_contact_number"
                                name="contact_number"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label for="quick_remark" class="form-label">Remark</label>
                            <textarea
                                class="form-control"
                                id="quick_remark"
                                name="remark"
                                rows="1"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit"
                        class="btn btn-brand-primary"
                        id="quickAddPumpSaveBtn">
                        <i class="bi bi-floppy-fill me-1"></i>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    let targetPumpSelectId = 'pumpName';
    let lockedParentModal = null;

    function formatValidationMessage(data) {
        if (data?.errors && typeof data.errors === 'object') {
            return Object.values(data.errors).flat().join(' ');
        }

        return data?.message || 'Failed to add pump';
    }

    function pumpLabel(pump) {
        return pump.pump_name + (pump.contact_name ? ' (' + pump.contact_name + ')' : '');
    }

    function injectPumpOption(selectId, pump) {
        const select = document.getElementById(selectId);
        if (!select || !pump) {
            return;
        }

        const existing = Array.from(select.options).find(
            (option) => String(option.value) === String(pump.id)
        );
        if (existing) {
            select.value = String(pump.id);
        } else {
            const option = new Option(pumpLabel(pump), pump.id, true, true);
            select.appendChild(option);
            select.value = String(pump.id);
        }

        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function resolveParentModal(trigger) {
        const knownParents = ['voucherPaymentModal', 'combinedPaymentModal'];
        for (const id of knownParents) {
            const modal = document.getElementById(id);
            if (modal && modal.classList.contains('show')) {
                return modal;
            }
        }

        return trigger?.closest('.modal') || null;
    }

    document.addEventListener('click', function (e) {
        const addPumpBtn = e.target.closest('.js-open-quick-add-pump');
        if (!addPumpBtn) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const quickAddPumpModal = document.getElementById('quickAddPumpModal');
        if (!quickAddPumpModal) {
            return;
        }

        targetPumpSelectId = addPumpBtn.getAttribute('data-pump-select') || 'pumpName';
        lockedParentModal = resolveParentModal(addPumpBtn);
        if (lockedParentModal) {
            lockedParentModal.classList.add('pump-modal-open');
        }

        bootstrap.Modal.getOrCreateInstance(quickAddPumpModal).show();

        quickAddPumpModal.addEventListener('hidden.bs.modal', function () {
            if (lockedParentModal) {
                lockedParentModal.classList.remove('pump-modal-open');
                lockedParentModal = null;
            }
        }, { once: true });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const quickAddPumpForm = document.getElementById('quickAddPumpForm');
        const quickPumpError = document.getElementById('quickPumpError');
        const quickAddPumpSaveBtn = document.getElementById('quickAddPumpSaveBtn');

        if (!quickAddPumpForm) {
            return;
        }

        quickAddPumpForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            quickPumpError.classList.add('d-none');
            quickPumpError.textContent = '';
            quickAddPumpSaveBtn.disabled = true;
            quickAddPumpSaveBtn.innerHTML = 'Saving...';

            try {
                const response = await fetch("{{ route('master.pump.store') }}", {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(quickAddPumpForm),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(formatValidationMessage(data));
                }

                injectPumpOption(targetPumpSelectId, data.pump);

                // Keep both payment dropdowns in sync when present.
                ['pumpName', 'combinedPumpName'].forEach((selectId) => {
                    if (selectId !== targetPumpSelectId) {
                        const other = document.getElementById(selectId);
                        if (!other || !data.pump) {
                            return;
                        }
                        const exists = Array.from(other.options).some(
                            (option) => String(option.value) === String(data.pump.id)
                        );
                        if (!exists) {
                            other.appendChild(new Option(pumpLabel(data.pump), data.pump.id));
                        }
                    }
                });

                bootstrap.Modal.getInstance(
                    document.getElementById('quickAddPumpModal')
                )?.hide();

                quickAddPumpForm.reset();
            } catch (error) {
                quickPumpError.textContent = error.message || 'Failed to add pump';
                quickPumpError.classList.remove('d-none');
            } finally {
                quickAddPumpSaveBtn.disabled = false;
                quickAddPumpSaveBtn.innerHTML =
                    '<i class="bi bi-floppy-fill me-1"></i> Save';
            }
        });
    });
})();
</script>
