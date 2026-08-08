(function () {
  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function formatTransporterDisplayName(data) {
    if (!data || typeof data !== 'object') {
      return '';
    }

    const name = String(
      data.name ??
        data.transporterMaster?.name ??
        data.transporter_master?.name ??
        ''
    ).trim();

    const shortName = String(data.short_name ?? '').trim();

    if (name === '') {
      return shortName !== '' ? `Not Set (${shortName})` : 'Not Set';
    }

    if (shortName !== '') {
      return `${name} (${shortName})`;
    }

    return name;
  }

  function renderDetailsHtmlFromObject(data, options = {}) {
    const { title = 'Details', hideDeclarationGiven = false } = options;

    if (data === null || data === undefined) {
      return `<div class="lrf-confirm"><p class="lrf-confirm__empty">No details available.</p></div>`;
    }

    const getSafe = (value) => {
      if (value === null || value === undefined || value === '') {
        return 'Not Set';
      }

      if (Array.isArray(value)) {
        return value.length ? value.join(', ') : 'Not Set';
      }

      return String(value);
    };

    const yesNo = (value) => {
      if (value === null || value === undefined) {
        return 'Not Set';
      }

      return value ? 'Yes' : 'No';
    };

    const tdsValue = data.tds_percentage ?? data.tdsPercentage ?? null;
    const tdsDisplay =
      tdsValue === null || tdsValue === undefined || tdsValue === ''
        ? 'Not Set'
        : `${parseFloat(tdsValue).toFixed(2)}%`;

    const name = getSafe(
      data.name ?? data.transporterMaster?.name ?? data.transporter_master?.name
    );
    const email = getSafe(
      data.email ?? data.transporterMaster?.email ?? data.transporter_master?.email
    );
    const pan = getSafe(
      data.pan_number ?? data.transporterMaster?.pan_number ?? data.transporter_master?.pan_number
    );
    const gst = getSafe(
      data.gst_number ?? data.transporterMaster?.gst_number ?? data.transporter_master?.gst_number
    );
    const mobile = getSafe(data.mobile_number);
    const contactPerson = getSafe(data.contact_person_name);
    const beneficiaryId = getSafe(data.beneficiary_id);
    const declarationGiven = yesNo(data.declaration_given);
    const shortName = getSafe(data.short_name);
    const gstType = getSafe(
      data.gst_type ?? data.transporterMaster?.gst_type ?? data.transporter_master?.gst_type
    );
    const address = getSafe(
      data.address ?? data.transporterMaster?.address ?? data.transporter_master?.address
    );

    const tm = data.transporterMaster ?? data.transporter_master ?? null;
    const cityDisplay = getSafe(
      (typeof data.city === 'string' ? data.city : null) ?? tm?.city?.name
    );
    const stateDisplay = getSafe(
      (typeof data.state === 'string' ? data.state : null) ?? tm?.state?.name
    );

    const isUnset = (value) => value === 'Not Set';
    const fact = (label, value, { emphasize = false } = {}) => {
      const unset = isUnset(value);
      const valueClass = [
        'lrf-confirm__fact-value',
        unset ? 'is-unset' : '',
        emphasize && !unset ? 'is-emphasis' : '',
      ]
        .filter(Boolean)
        .join(' ');

      return `
        <div class="lrf-confirm__fact">
          <span class="lrf-confirm__fact-label">${escapeHtml(label)}</span>
          <span class="${valueClass}">${escapeHtml(value)}</span>
        </div>
      `;
    };

    const declarationChip =
      declarationGiven === 'Yes'
        ? '<span class="lrf-confirm__chip lrf-confirm__chip--ok">Declaration Yes</span>'
        : declarationGiven === 'No'
          ? '<span class="lrf-confirm__chip lrf-confirm__chip--warn">Declaration No</span>'
          : '';

    return `
      <div class="lrf-confirm">
        <div class="lrf-confirm__hero">
          <div class="lrf-confirm__avatar" aria-hidden="true">${escapeHtml((name || '?').charAt(0).toUpperCase())}</div>
          <div class="lrf-confirm__hero-text">
            <div class="lrf-confirm__name">${escapeHtml(name)}</div>
            <div class="lrf-confirm__meta">
              ${!isUnset(shortName) ? `<span class="lrf-confirm__chip">${escapeHtml(shortName)}</span>` : ''}
              <span class="lrf-confirm__chip lrf-confirm__chip--soft">TDS ${escapeHtml(tdsDisplay)}</span>
              ${hideDeclarationGiven ? '' : declarationChip}
            </div>
          </div>
        </div>

        <div class="lrf-confirm__section-label">${escapeHtml(title)}</div>

        <div class="lrf-confirm__grid">
          ${fact('Mobile', mobile, { emphasize: true })}
          ${fact('Contact Person', contactPerson)}
          ${fact('Email', email)}
          ${fact('PAN', pan, { emphasize: true })}
          ${fact('GST', gst)}
          ${fact('GST Type', gstType)}
          ${hideDeclarationGiven ? '' : fact('Declaration', declarationGiven)}
          ${fact('Beneficiary ID', beneficiaryId)}
          ${fact('City', cityDisplay)}
          ${fact('State', stateDisplay)}
        </div>

        <div class="lrf-confirm__address">
          <span class="lrf-confirm__fact-label">Address</span>
          <span class="lrf-confirm__fact-value ${isUnset(address) ? 'is-unset' : ''}">${escapeHtml(address)}</span>
        </div>
      </div>
    `;
  }

  function ensureConfirmModalStyles() {
    if (document.getElementById('lrf-confirm-modal-styles')) {
      return;
    }

    const style = document.createElement('style');
    style.id = 'lrf-confirm-modal-styles';
    style.textContent = `
      .lrf-swal-popup {
        border-radius: 16px !important;
        padding: 0 !important;
        width: min(560px, 94vw) !important;
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.18) !important;
        overflow: hidden;
      }
      .lrf-swal-title {
        margin: 0 !important;
        padding: 1rem 1.15rem 0.35rem !important;
        font-size: 1.05rem !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        text-align: left !important;
      }
      .lrf-swal-html {
        margin: 0 !important;
        padding: 0 1.15rem 0.35rem !important;
        text-align: left !important;
      }
      .lrf-swal-actions {
        margin: 0 !important;
        padding: 0.85rem 1.15rem 1.1rem !important;
        gap: 0.55rem !important;
        width: 100% !important;
        justify-content: stretch !important;
      }
      .lrf-swal-confirm,
      .lrf-swal-cancel {
        flex: 1 1 0 !important;
        margin: 0 !important;
        border-radius: 10px !important;
        font-weight: 700 !important;
        font-size: 0.875rem !important;
        padding: 0.65rem 0.9rem !important;
        box-shadow: none !important;
      }
      .lrf-swal-confirm {
        background: #2fb36f !important;
      }
      .lrf-swal-confirm:hover {
        background: #23965c !important;
      }
      .lrf-swal-cancel {
        background: #fff !important;
        color: #475569 !important;
        border: 1px solid #e2e8f0 !important;
      }
      .lrf-swal-cancel:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
      }
      .lrf-swal-popup .swal2-icon {
        display: none !important;
      }
      .lrf-confirm {
        font-family: inherit;
        color: #334155;
      }
      .lrf-confirm__empty {
        margin: 0.5rem 0;
        color: #94a3b8;
      }
      .lrf-confirm__hero {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        padding: 0.85rem 0.9rem;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(47,179,111,0.12) 0%, rgba(45,140,255,0.06) 55%, #f8fafc 100%);
        border: 1px solid rgba(47,179,111,0.18);
        margin-bottom: 0.85rem;
      }
      .lrf-confirm__avatar {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #2fb36f;
        color: #fff;
        font-weight: 800;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(47,179,111,0.28);
      }
      .lrf-confirm__name {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
        word-break: break-word;
      }
      .lrf-confirm__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.35rem;
      }
      .lrf-confirm__chip {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        background: #e2e8f0;
        color: #334155;
      }
      .lrf-confirm__chip--soft {
        background: #eff6ff;
        color: #1d4ed8;
      }
      .lrf-confirm__chip--ok {
        background: #dcfce7;
        color: #166534;
      }
      .lrf-confirm__chip--warn {
        background: #fef3c7;
        color: #92400e;
      }
      .lrf-confirm__section-label {
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 0.5rem;
      }
      .lrf-confirm__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem 0.75rem;
      }
      .lrf-confirm__fact,
      .lrf-confirm__address {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.12rem;
      }
      .lrf-confirm__address {
        margin-top: 0.75rem;
        padding-top: 0.7rem;
        border-top: 1px dashed #e2e8f0;
      }
      .lrf-confirm__fact-label {
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #94a3b8;
      }
      .lrf-confirm__fact-value {
        font-size: 0.84rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.35;
        word-break: break-word;
      }
      .lrf-confirm__fact-value.is-unset {
        color: #94a3b8;
        font-weight: 600;
      }
      .lrf-confirm__fact-value.is-emphasis {
        color: #0f172a;
        font-weight: 800;
      }
      @media (max-width: 520px) {
        .lrf-confirm__grid {
          grid-template-columns: 1fr;
        }
      }
    `;
    document.head.appendChild(style);
  }

  function ConfirmEntitySelectionModal({ title, detailsHtml, onConfirm, onCancel }) {
    ensureConfirmModalStyles();

    if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
      Swal.fire({
        title,
        html: detailsHtml,
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        focusCancel: true,
        allowOutsideClick: false,
        reverseButtons: true,
        buttonsStyling: true,
        didOpen: () => {
          setTimeout(() => {
            const cancelButton = Swal.getCancelButton();
            cancelButton?.focus();
          }, 50);
        },
        customClass: {
          popup: 'lrf-swal-popup',
          title: 'lrf-swal-title',
          htmlContainer: 'lrf-swal-html',
          actions: 'lrf-swal-actions',
          confirmButton: 'lrf-swal-confirm',
          cancelButton: 'lrf-swal-cancel',
        },
      }).then((result) => {
        if (result.isConfirmed) {
          onConfirm?.();
        } else {
          onCancel?.();
        }
      });

      return;
    }

    const ok = confirm(title);
    if (ok) {
      onConfirm?.();
    } else {
      onCancel?.();
    }
  }

  function removeHiddenTransporterIdInputs() {
    document.querySelectorAll('input[type="hidden"][name="transporter_id"]').forEach((el) => el.remove());
  }

  function resetEditTruckOwnerLinkHref() {
    const editTruckOwnerLink = document.getElementById('editTruckOwnerLink');
    if (editTruckOwnerLink) {
      editTruckOwnerLink.href = '#';
    }
  }

  /**
   * Mirrors the LR create/edit pages: updates TDS display + declaration warning styling.
   */
  function showTdsPercentage(transporterId) {
    const tdsDisplay = document.getElementById('tdsRateDisplay');
    const tdsValue = document.getElementById('tdsPercentageValue');
    const declarationWarning = document.getElementById('declarationWarning');
    const editTruckOwnerLink = document.getElementById('editTruckOwnerLink');
    const transporterSelect = document.getElementById('transporter_id');
    const transporterSelect2 = window.jQuery ? window.jQuery('#transporter_id') : null;

    if (!tdsDisplay || !tdsValue) {
      return;
    }

    function removeHighlight() {
      if (transporterSelect) {
        transporterSelect.classList.remove('declaration-warning');
      }

      if (!transporterSelect2 || typeof transporterSelect2.next !== 'function') {
        return;
      }

      let select2Container = transporterSelect2.next('.select2-container');
      if (!select2Container.length) {
        select2Container = window.jQuery('.select2-container').has('#transporter_id');
      }
      if (!select2Container.length) {
        select2Container = transporterSelect2.closest('.select2-container');
      }
      if (select2Container.length) {
        select2Container.removeClass('declaration-warning');
      } else if (transporterSelect && transporterSelect.parentElement) {
        const container = transporterSelect.parentElement.querySelector('.select2-container');
        if (container) {
          container.classList.remove('declaration-warning');
        }
      }
    }

    function addHighlight() {
      if (transporterSelect) {
        transporterSelect.classList.add('declaration-warning');
      }

      if (!transporterSelect2 || typeof transporterSelect2.next !== 'function') {
        return;
      }

      let select2Container = transporterSelect2.next('.select2-container');
      if (!select2Container.length) {
        select2Container = window.jQuery('.select2-container').has('#transporter_id');
      }
      if (!select2Container.length) {
        select2Container = transporterSelect2.closest('.select2-container');
      }
      if (select2Container.length) {
        select2Container.addClass('declaration-warning');
      } else if (transporterSelect && transporterSelect.parentElement) {
        const container = transporterSelect.parentElement.querySelector('.select2-container');
        if (container) {
          container.classList.add('declaration-warning');
        }
      }
    }

    const id = transporterId ? String(transporterId) : '';

    if (id) {
      tdsDisplay.style.display = 'block';
      tdsValue.textContent = 'Loading...';
      if (declarationWarning) {
        declarationWarning.style.display = 'none';
      }
      removeHighlight();

      fetch(`/api/transporters/${encodeURIComponent(id)}`)
        .then((response) => response.json())
        .then((data) => {
          if (data.tds_percentage !== null && data.tds_percentage !== undefined) {
            tdsValue.textContent = `${parseFloat(data.tds_percentage).toFixed(2)}%`;
          } else {
            tdsValue.textContent = 'Not Set';
          }

          if (data.is_owner && !data.declaration_given) {
            if (declarationWarning) {
              declarationWarning.style.display = 'block';
            }
            if (editTruckOwnerLink) {
              editTruckOwnerLink.href = `/truck_owners/${encodeURIComponent(id)}/edit`;
            }
            addHighlight();
          } else {
            if (declarationWarning) {
              declarationWarning.style.display = 'none';
            }
            removeHighlight();
          }
        })
        .catch((error) => {
          console.error('Error fetching transporter TDS percentage:', error);
          tdsValue.textContent = 'Error';
          if (declarationWarning) {
            declarationWarning.style.display = 'none';
          }
          removeHighlight();
        });

      return;
    }

    tdsDisplay.style.display = 'none';
    if (declarationWarning) {
      declarationWarning.style.display = 'none';
    }
    removeHighlight();
  }

  function applyTransporterSelection({
    transporterSelect,
    transporterSelect2,
    transporterId,
    transporterName,
    disableSelect,
    disableTitle,
  }) {
    if (!transporterSelect || !transporterId) {
      return;
    }

    transporterSelect.innerHTML = '<option value="">Select Transporter</option>';

    const option = document.createElement('option');
    option.value = String(transporterId);
    option.textContent = transporterName || '';
    option.selected = true;
    transporterSelect.appendChild(option);

    transporterSelect.value = String(transporterId);
    transporterSelect.disabled = !!disableSelect;
    transporterSelect.style.backgroundColor = disableSelect ? '#e9ecef' : '';
    transporterSelect.title = disableTitle || '';

    removeHiddenTransporterIdInputs();

    if (transporterSelect.form) {
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'transporter_id';
      hiddenInput.value = String(transporterId);
      transporterSelect.form.appendChild(hiddenInput);
    }

    if (transporterSelect2 && typeof transporterSelect2.trigger === 'function') {
      transporterSelect2.trigger('change.select2');
    }
  }

  function applyOwnerSelection({
    transporterSelect,
    transporterSelect2,
    ownerId,
    ownerName,
  }) {
    if (!transporterSelect || !ownerId) {
      return;
    }

    transporterSelect.innerHTML = '<option value="">Select Transporter</option>';

    const option = document.createElement('option');
    option.value = String(ownerId);
    option.textContent = ownerName || '';
    option.selected = true;
    transporterSelect.appendChild(option);

    transporterSelect.value = String(ownerId);
    transporterSelect.disabled = true;
    transporterSelect.style.backgroundColor = '#e9ecef';
    transporterSelect.title = 'Automatically selected based on truck owner';

    removeHiddenTransporterIdInputs();
    if (transporterSelect.form) {
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'transporter_id';
      hiddenInput.value = String(ownerId);
      transporterSelect.form.appendChild(hiddenInput);
    }

    if (transporterSelect2 && typeof transporterSelect2.trigger === 'function') {
      transporterSelect2.trigger('change.select2');
    }
  }

  function clearTruckAndTransporterForOwnerCancel({ transporterSelect, transporterSelect2, truckSelect2 }) {
    removeHiddenTransporterIdInputs();

    if (transporterSelect) {
      transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
      transporterSelect.value = '';
      transporterSelect.disabled = false;
      transporterSelect.style.backgroundColor = '';
      transporterSelect.title = '';
    }

    if (transporterSelect2 && typeof transporterSelect2.trigger === 'function') {
      transporterSelect2.trigger('change');
    }

    if (truckSelect2 && typeof truckSelect2.val === 'function') {
      // Use plain `change` so page listeners (pay-to visibility, summary) also reset.
      truckSelect2.val(null).trigger('change');
    }
  }

  function clearAgencySelection({
    transporterSelect,
    transporterSelect2,
    transporterTypeSelect,
  }) {
    if (!transporterSelect) {
      return;
    }

    removeHiddenTransporterIdInputs();
    transporterSelect.value = '';
    transporterSelect.disabled = false;
    transporterSelect.style.backgroundColor = '';
    transporterSelect.title = '';

    if (transporterTypeSelect) {
      transporterTypeSelect.value = '';
    }

    if (transporterSelect2 && typeof transporterSelect2.trigger === 'function') {
      transporterSelect2.trigger('change.select2');
    }
  }

  function initLorryReceiptTransporterSelectionFlow(options) {
    const {
      transporterTypeSelect,
      truckSelect,
      transporterSelect,
      transporterLabel,
      trucksData: initialTrucksData = {},
      mode = 'create', // create | edit
      allowTransporterChanges = true,
      afterTransporterTypeChange = null,
    } = options;

    if (!transporterTypeSelect || !truckSelect || !transporterSelect || !transporterLabel) {
      return;
    }

    if (!allowTransporterChanges) {
      return;
    }

    if (!window.jQuery) {
      console.error('LR transporter flow requires jQuery for Select2 integration.');
      return;
    }

    const $ = window.jQuery;
    const truckSelect2 = $('#truck_id');
    const transporterSelect2 = $(transporterSelect);

    const trucksData = initialTrucksData && typeof initialTrucksData === 'object' ? initialTrucksData : {};

    let clearingDueToCancel = false;
    let confirmSeq = 0;

    const runAfterClearingCancel = (fn) => {
      clearingDueToCancel = true;
      try {
        fn();
      } finally {
        setTimeout(() => {
          clearingDueToCancel = false;
        }, 0);
      }
    };

    const setOwnerOptionAvailability = (isOwnerAvailable) => {
      const ownerOption = transporterTypeSelect.querySelector('option[value="owner"]');
      if (!ownerOption) {
        return;
      }

      ownerOption.disabled = !isOwnerAvailable;
      if (!isOwnerAvailable && transporterTypeSelect.value === 'owner') {
        transporterTypeSelect.value = '';
      }

      const ownerPill = document.getElementById('lrPayPillOwner');
      if (ownerPill) {
        ownerPill.disabled = !isOwnerAvailable;
      }
    };

    const enforceAgencyForOwnerlessTruck = (truckId) => {
      const transporterId = trucksData[truckId]?.transporterId ?? trucksData[truckId]?.transporter_id ?? null;
      const ownerAvailable = transporterId !== null && transporterId !== undefined && transporterId !== '';
      setOwnerOptionAvailability(ownerAvailable);

      if (!ownerAvailable && transporterTypeSelect.value === 'owner') {
        transporterTypeSelect.value = '';
        transporterLabel.textContent = 'Payee *';
        document.querySelectorAll('.lrf-pay-pill').forEach((pill) => {
          pill.classList.remove('is-active');
        });
      }

      return ownerAvailable;
    };

    $('#truck_id').on('select2:select', function (e) {
      if (clearingDueToCancel) {
        return;
      }

      const selectedTruckId = e.params.data.id;
      const data = e.params.data || {};

      const truckText = (data.text || '').toString().trim() || $(this).find('option:selected').text().trim();
      const transporterId = data.transporter_id !== undefined && data.transporter_id !== null && data.transporter_id !== ''
        ? parseInt(data.transporter_id, 10)
        : null;

      const transporterNameFromData = (data.transporter_name || '').toString().trim();
      const transporterMatch = truckText.match(/\(([^)]+)\)$/);
      const transporterName = transporterNameFromData || (transporterMatch ? transporterMatch[1].trim() : '');

      trucksData[selectedTruckId] = {
        transporterId,
        transporterName,
        truckText,
      };

      enforceAgencyForOwnerlessTruck(selectedTruckId);

      if (transporterTypeSelect.value === 'owner') {
        autoSelectTruckOwner(selectedTruckId);
      }
    });

    $('#truck_id').on('change', function () {
      if (clearingDueToCancel) {
        return;
      }

      const selectedTruckId = $(this).val();
      if (!selectedTruckId || trucksData[selectedTruckId]) {
        return;
      }

      const select2Data = truckSelect2 && typeof truckSelect2.select2 === 'function'
        ? (truckSelect2.select2('data') || [])[0] || {}
        : {};

      const truckText = (select2Data.text || '').toString().trim() || $(this).find('option:selected').text().trim();
      const transporterId = select2Data.transporter_id !== undefined && select2Data.transporter_id !== null && select2Data.transporter_id !== ''
        ? parseInt(select2Data.transporter_id, 10)
        : null;

      const transporterNameFromData = (select2Data.transporter_name || '').toString().trim();
      const transporterMatch = truckText.match(/\(([^)]+)\)$/);
      const transporterName = transporterNameFromData || (transporterMatch ? transporterMatch[1].trim() : '');

      trucksData[selectedTruckId] = {
        transporterId,
        transporterName,
        truckText,
      };

      enforceAgencyForOwnerlessTruck(selectedTruckId);

      if (transporterTypeSelect.value === 'owner') {
        autoSelectTruckOwner(selectedTruckId);
      }
    });

    transporterTypeSelect.addEventListener('change', function () {
      const selectedType = this.value;
      const selectedTruckId = truckSelect.value;

      document.querySelectorAll('input[type="hidden"][name="transporter_id"]').forEach((el) => el.remove());

      transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
      transporterSelect.disabled = false;
      transporterSelect2.trigger('change.select2');

      transporterLabel.innerHTML = selectedType === 'owner'
        ? 'Truck Owner<span class="lrf-pf__req">*</span>'
        : selectedType === 'agency'
          ? 'Agency<span class="lrf-pf__req">*</span>'
          : 'Payee<span class="lrf-pf__req">*</span>';
      transporterLabel.classList.add('lrf-pf__lbl');

      document.querySelectorAll('.lrf-pay-pill').forEach((pill) => {
        const isActive = selectedType !== '' && pill.getAttribute('data-pay-type') === selectedType;
        pill.classList.toggle('is-active', isActive);
      });

      if (selectedType === 'owner') {
        const ownerAvailable = enforceAgencyForOwnerlessTruck(selectedTruckId);
        if (!ownerAvailable) {
          transporterSelect2.trigger('change.select2');
          if (typeof afterTransporterTypeChange === 'function') {
            afterTransporterTypeChange(selectedType);
          }
          return;
        }

        if (selectedTruckId) {
          autoSelectTruckOwner(selectedTruckId);
        }
      } else if (selectedType === 'agency') {
        // Agencies are loaded via Select2 AJAX (no full list fetch).
        transporterSelect2.trigger('change.select2');
      }

      if (typeof afterTransporterTypeChange === 'function') {
        afterTransporterTypeChange(selectedType);
      }
    });

    function autoSelectTruckOwner(truckId) {
      if (!truckId) {
        return;
      }

      const seq = ++confirmSeq;

      fetch(`/api/trucks/${encodeURIComponent(String(truckId))}/owner-transporters`)
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          const owner = Array.isArray(data) && data.length > 0 ? data[0] : null;

          if (!owner || !owner.id) {
            if (seq !== confirmSeq) {
              return;
            }

            transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
            transporterSelect.disabled = false;
            transporterSelect.style.backgroundColor = '';
            transporterSelect.title = '';
            transporterSelect2.trigger('change.select2');
            removeHiddenTransporterIdInputs();
            showTdsPercentage(null);
            return;
          }

          const ownerId = owner.id;

          return Promise.all([
            fetch(`/api/transporters/${encodeURIComponent(String(ownerId))}`).then((r) => (r.ok ? r.json() : null)),
            fetch(`/api/trucks/${encodeURIComponent(String(truckId))}`).then((r) => (r.ok ? r.json() : null)),
          ]).then(([transporterDetails]) => {
            if (seq !== confirmSeq) {
              return;
            }

            if (!transporterDetails) {
              if (mode === 'edit') {
                applyOwnerSelection({
                  transporterSelect,
                  transporterSelect2,
                  ownerId,
                  ownerName: owner.name || '',
                });
              } else {
                applyTransporterSelection({
                  transporterSelect,
                  transporterSelect2,
                  transporterId: ownerId,
                  transporterName: owner.name || '',
                  disableSelect: true,
                  disableTitle: 'Automatically selected based on truck owner',
                });
              }

              showTdsPercentage(ownerId);
              return;
            }

            const entityName = formatTransporterDisplayName(transporterDetails) || owner.name || 'N/A';

            const completeDetailsHtml = renderDetailsHtmlFromObject(transporterDetails, {
              title: 'Owner details',
            });

            const detailsHtml = `
              <div class="text-start">
                ${completeDetailsHtml}
              </div>
            `;

            ConfirmEntitySelectionModal({
              title: 'Confirm truck owner',
              detailsHtml,
              onConfirm: () => {
                if (seq !== confirmSeq) {
                  return;
                }

                if (mode === 'edit') {
                  applyOwnerSelection({
                    transporterSelect,
                    transporterSelect2,
                    ownerId,
                    ownerName: entityName,
                  });
                } else {
                  applyTransporterSelection({
                    transporterSelect,
                    transporterSelect2,
                    transporterId: ownerId,
                    transporterName: entityName,
                    disableSelect: true,
                    disableTitle: 'Automatically selected based on truck owner',
                  });
                }

                showTdsPercentage(ownerId);
              },
              onCancel: () => {
                if (seq !== confirmSeq) {
                  return;
                }

                runAfterClearingCancel(() => {
                  clearTruckAndTransporterForOwnerCancel({
                    transporterSelect,
                    transporterSelect2,
                    truckSelect2,
                  });
                  if (transporterTypeSelect) {
                    transporterTypeSelect.value = '';
                    transporterTypeSelect.dispatchEvent(new Event('change'));
                  }
                  document.querySelectorAll('.lrf-pay-pill').forEach((pill) => {
                    pill.classList.remove('is-active');
                    if (pill.getAttribute('data-pay-type') === 'owner') {
                      pill.disabled = false;
                    }
                  });
                  showTdsPercentage(null);
                  resetEditTruckOwnerLinkHref();
                  if (typeof window.syncLrPayeeVisibility === 'function') {
                    window.syncLrPayeeVisibility();
                  }
                  if (typeof window.syncLrTruckMetaAfterClear === 'function') {
                    window.syncLrTruckMetaAfterClear();
                  }
                });
              },
            });
          });
        })
        .catch((error) => {
          console.error('❌ Error fetching truck owner:', error);

          if (seq !== confirmSeq) {
            return;
          }

          transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
          transporterSelect.disabled = false;
          transporterSelect.style.backgroundColor = '';
          transporterSelect.title = '';
          transporterSelect2.trigger('change.select2');
          removeHiddenTransporterIdInputs();
          showTdsPercentage(null);
        });
    }

    function handleAgencySelection(transporterId) {
      if (!transporterId) {
        showTdsPercentage(null);
        return;
      }

      const truckId = truckSelect.value;
      const seq = ++confirmSeq;

      const transporterFetch = fetch(`/api/transporters/${encodeURIComponent(String(transporterId))}`).then((r) =>
        r.ok ? r.json() : null
      );
      const truckFetch = truckId
        ? fetch(`/api/trucks/${encodeURIComponent(String(truckId))}`).then((r) => (r.ok ? r.json() : null))
        : Promise.resolve(null);

      Promise.all([transporterFetch, truckFetch])
        .then(([transporterDetails]) => {
          if (seq !== confirmSeq) {
            return;
          }

          if (!transporterDetails) {
            showTdsPercentage(transporterId);
            return;
          }

          const completeDetailsHtml = renderDetailsHtmlFromObject(transporterDetails, {
            title: 'Agency details',
            hideDeclarationGiven: true,
          });

          const detailsHtml = `
            <div class="text-start">
              ${completeDetailsHtml}
            </div>
          `;

          ConfirmEntitySelectionModal({
            title: 'Confirm agency',
            detailsHtml,
            onConfirm: () => {
              if (seq !== confirmSeq) {
                return;
              }
              showTdsPercentage(transporterId);
            },
            onCancel: () => {
              if (seq !== confirmSeq) {
                return;
              }

              runAfterClearingCancel(() => {
                clearAgencySelection({
                  transporterSelect,
                  transporterSelect2,
                  transporterTypeSelect,
                });
                showTdsPercentage(null);
                resetEditTruckOwnerLinkHref();
              });
            },
          });
        })
        .catch(() => {
          showTdsPercentage(transporterId);
        });
    }

    $(document)
      .off('select2:select.lrTransporterAgencyConfirm', '#transporter_id')
      .on('select2:select.lrTransporterAgencyConfirm', '#transporter_id', function (e) {
        if (!transporterSelect || this !== transporterSelect) {
          return;
        }

        if (clearingDueToCancel) {
          return;
        }

        if (transporterTypeSelect.value !== 'agency') {
          return;
        }

        const selectedId = e?.params?.data?.id;
        handleAgencySelection(selectedId);
      });

    transporterSelect.addEventListener('change', function () {
      if (clearingDueToCancel) {
        return;
      }

      const id = this.value;

      if (transporterTypeSelect.value === 'owner') {
        return;
      }

      if (transporterTypeSelect.value === 'agency') {
        if (typeof $.fn.select2 !== 'undefined') {
          return;
        }

        handleAgencySelection(id);
        return;
      }

      showTdsPercentage(id);
    });

    // Edit page previously also confirmed agency on native change even with Select2; behavior is unified to Select2 primary + native fallback when Select2 is absent.
    if (truckSelect.value) {
      enforceAgencyForOwnerlessTruck(truckSelect.value);
    } else {
      setOwnerOptionAvailability(true);
    }
  }

  function showValidationError(element, message) {
    if (!element) {
      return;
    }

    clearValidationError(element);
    element.classList.add('is-invalid');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;
    errorDiv.id = `${element.id}_validation_error`;

    element.parentNode.insertBefore(errorDiv, element.nextSibling);
  }

  function clearValidationError(element) {
    if (!element) {
      return;
    }

    element.classList.remove('is-invalid');
    const existingError = document.getElementById(`${element.id}_validation_error`);
    if (existingError) {
      existingError.remove();
    }
  }

  window.LREntity = {
    escapeHtml,
    renderDetailsHtmlFromObject,
    ConfirmEntitySelectionModal,
    showTdsPercentage,
    initLorryReceiptTransporterSelectionFlow,
    showValidationError,
    clearValidationError,
  };
})();

