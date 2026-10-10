/**
 * Global themed confirmations + SweetAlert defaults for Truckbill.
 */
(function (window, document) {
  'use strict';

  const SWAL_CLASSES = {
    popup: 'tb-swal-popup',
    title: 'tb-swal-title',
    htmlContainer: 'tb-swal-html',
    actions: 'tb-swal-actions',
    confirmButton: 'tb-swal-confirm',
    cancelButton: 'tb-swal-cancel',
    denyButton: 'tb-swal-deny',
  };

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function extractConfirmMessage(source) {
    if (!source) {
      return 'Are you sure?';
    }

    const match = String(source).match(/confirm\s*\(\s*(['"`])([\s\S]*?)\1\s*\)/i);
    if (!match) {
      return 'Are you sure?';
    }

    return match[2].replace(/\\n/g, '\n').replace(/\\'/g, "'").replace(/\\"/g, '"');
  }

  function noteHtml(message, tone) {
    const toneClass = tone === 'danger'
      ? ' tb-swal-note--danger'
      : (tone === 'warn' ? ' tb-swal-note--warn' : '');
    const icon = tone === 'danger'
      ? 'bi-exclamation-triangle'
      : (tone === 'warn' ? 'bi-exclamation-circle' : 'bi-info-circle');

    return `<div class="tb-swal-note${toneClass}"><i class="bi ${icon}"></i><span>${escapeHtml(message)}</span></div>`;
  }

  function ensureSwalDefaults() {
    if (typeof window.Swal === 'undefined' || window.Swal.__truckbillThemed) {
      return;
    }

    window.Swal = window.Swal.mixin({
      buttonsStyling: true,
      reverseButtons: true,
      focusCancel: false,
      allowOutsideClick: false,
      customClass: SWAL_CLASSES,
      confirmButtonColor: '#2fb36f',
      cancelButtonColor: '#94a3b8',
    });

    window.Swal.__truckbillThemed = true;
  }

  function confirm(options) {
    ensureSwalDefaults();

    const opts = typeof options === 'string'
      ? { text: options }
      : (options || {});

    const title = opts.title || 'Please confirm';
    const text = opts.text || opts.message || 'Are you sure?';
    const tone = opts.tone || 'warn';
    const confirmText = opts.confirmText || 'Confirm';
    const cancelText = opts.cancelText || 'Cancel';

    if (typeof window.Swal === 'undefined') {
      return Promise.resolve(window.confirm(text));
    }

    return window.Swal.fire({
      title,
      html: noteHtml(text, tone),
      icon: opts.icon || (tone === 'danger' ? 'warning' : 'question'),
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      customClass: SWAL_CLASSES,
    }).then((result) => Boolean(result.isConfirmed));
  }

  function alert(options) {
    ensureSwalDefaults();

    const opts = typeof options === 'string'
      ? { text: options }
      : (options || {});

    if (typeof window.Swal === 'undefined') {
      window.alert(opts.text || opts.message || '');
      return Promise.resolve();
    }

    return window.Swal.fire({
      title: opts.title || 'Notice',
      html: noteHtml(opts.text || opts.message || '', opts.tone || null),
      icon: opts.icon || 'info',
      confirmButtonText: opts.confirmText || 'OK',
      customClass: SWAL_CLASSES,
    });
  }

  function tagModals() {
    document.querySelectorAll('.modal.fade:not(.fb-pay-modal)').forEach((modal) => {
      modal.classList.add('fb-pay-modal');
    });
  }

  function interceptNativeConfirms() {
    document.addEventListener('click', function (event) {
      const el = event.target.closest('[onclick]');
      if (!el) {
        return;
      }

      const onclick = el.getAttribute('onclick') || '';
      if (!/confirm\s*\(/i.test(onclick)) {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();

      const message = extractConfirmMessage(onclick);
      const tone = /delete|remove|cancel/i.test(message) ? 'danger' : 'warn';

      confirm({
        title: tone === 'danger' ? 'Confirm deletion' : 'Please confirm',
        text: message,
        tone,
        confirmText: tone === 'danger' ? 'Yes, continue' : 'Confirm',
      }).then((ok) => {
        if (!ok) {
          return;
        }

        el.removeAttribute('onclick');

        if (el.tagName === 'A' && el.getAttribute('href')) {
          window.location.href = el.getAttribute('href');
          return;
        }

        if (el.tagName === 'BUTTON' && el.type === 'submit') {
          const form = el.closest('form');
          if (form) {
            if (form.requestSubmit) {
              form.requestSubmit(el);
            } else {
              form.submit();
            }
            return;
          }
        }

        el.click();
      });
    }, true);

    document.addEventListener('submit', function (event) {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const onsubmit = form.getAttribute('onsubmit') || '';
      if (!/confirm\s*\(/i.test(onsubmit)) {
        return;
      }

      if (form.dataset.tbConfirmOk === '1') {
        form.dataset.tbConfirmOk = '0';
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();

      const message = extractConfirmMessage(onsubmit);
      const tone = /delete|remove|cancel/i.test(message) ? 'danger' : 'warn';

      confirm({
        title: tone === 'danger' ? 'Confirm deletion' : 'Please confirm',
        text: message,
        tone,
        confirmText: tone === 'danger' ? 'Yes, continue' : 'Confirm',
      }).then((ok) => {
        if (!ok) {
          return;
        }

        form.dataset.tbConfirmOk = '1';
        form.removeAttribute('onsubmit');
        if (form.requestSubmit) {
          form.requestSubmit();
        } else {
          form.submit();
        }
      });
    }, true);
  }

  function initIndexFiltersToggle() {
    document.addEventListener('click', function (event) {
      const toggle = event.target.closest('[data-app-index-filters-toggle]');
      if (!toggle) {
        return;
      }

      const root = toggle.closest('[data-app-index-filters]');
      if (!root) {
        return;
      }

      const isOpen = root.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  /* ==========================================================================
     TruckBill Form Draft Autosave & Restore (Pure Bootstrap UI)
     ========================================================================== */
  function getFormSnapshot(form) {
    const snap = {};
    if (!form) return snap;
    form.querySelectorAll('input, select, textarea').forEach(function (el) {
      if (!el.name || el.name === '_token' || el.name === '_method') return;
      if (el.type === 'submit' || el.type === 'button' || el.type === 'file' || el.type === 'password') return;
      if (el.type === 'checkbox' || el.type === 'radio') {
        snap[el.name + '::' + el.value] = el.checked ? '1' : '0';
        if (el.type === 'radio' && el.checked) {
          snap[el.name] = el.value;
        }
      } else {
        snap[el.name] = (el.value || '').trim();
      }
    });
    return snap;
  }

  function isBoilerplateField(name) {
    if (!name) return true;
    const lower = String(name).toLowerCase();
    if ([
      '_token',
      '_method',
      'company_id',
      'branch_id',
      'user_id',
      'country',
      'country_id',
      'status',
      'is_active',
      'active',
      'currency',
      'voucher_type',
      'challan_head',
      'lane',
      'is_dummy_pan',
      'pan_verified',
      'advance_amount',
      'transporter_master_id',
      'good_type_field',
      'registered_truck_id',
      'consignor_contact_number',
      'source_city',
      'source_state',
      'consignee_contact_number',
      'destination_city',
      'destination_state'
    ].includes(lower)) {
      return true;
    }
    // Date fields (e.g. gr_date, lr_date, voucher_date, declaration_194c6_date, challan_tender_date default to today in Blade)
    if (/date$/i.test(lower) || lower === 'date' || lower === 'dated') {
      return true;
    }
    // Accounting voucher Dr / Cr row type indicators e.g. items[0][type], items[1][type]
    if (/items\[\d+\]\[type\]/i.test(lower) || /\[type\]$/i.test(lower)) {
      return true;
    }
    return false;
  }

  function isRealLinkedLrItem(item) {
    if (!item || typeof item !== 'object') return false;
    const meaningfulFields = [
      'lr_number',
      'invoice_number',
      'customer_id',
      'gr_number',
      'consignor_id',
      'consignee_id',
      'loading_station',
      'unloading_station',
      'goods_type',
      'units',
      'goods_value',
      'weight',
      'fr_rate',
      'amount'
    ];
    return meaningfulFields.some(function (k) {
      const v = item[k];
      if (v === undefined || v === null) return false;
      const str = String(v).trim();
      return str.length > 0 && str !== '0' && str !== '0.00' && str !== '0.000' && !str.toLowerCase().startsWith('select ');
    });
  }

  function hasMeaningfulUserData(data, initialSnapshot, isLinkedLr) {
    if (!data || typeof data !== 'object') return false;

    // Check dynamic linked LRs
    const hasLinkedItems = Array.isArray(data._linked_lrs) && data._linked_lrs.length > 0;
    const hasRealLinkedLrData = hasLinkedItems && data._linked_lrs.some(isRealLinkedLrItem);

    if (isLinkedLr) {
      // For Linked LR: must EITHER have real LR row data OR a selected truck/reference
      if (hasRealLinkedLrData) return true;
      const truckId = data.truck_id && typeof data.truck_id === 'object' ? data.truck_id.value : data.truck_id;
      if (truckId && String(truckId).trim() !== '' && String(truckId).trim() !== '0') return true;
      const refBy = data.reference_by && typeof data.reference_by === 'object' ? data.reference_by.value : data.reference_by;
      if (refBy && String(refBy).trim() !== '' && String(refBy).trim() !== '0') return true;
      return false;
    }

    if (hasRealLinkedLrData) return true;

    let meaningfulCount = 0;
    const keys = Object.keys(data);
    for (let i = 0; i < keys.length; i++) {
      const key = keys[i];
      if (key === '_linked_lrs') continue;
      if (isBoilerplateField(key)) continue;

      const item = data[key];
      if (item === undefined || item === null) continue;

      if (Array.isArray(item)) {
        if (item.length > 0) {
          if (initialSnapshot) {
            let changed = false;
            for (let j = 0; j < item.length; j++) {
              const val = typeof item[j] === 'object' && item[j] !== null ? item[j].value : item[j];
              if (initialSnapshot[key + '::' + val] !== '1') {
                changed = true;
                break;
              }
            }
            if (changed) meaningfulCount++;
          } else {
            meaningfulCount++;
          }
        }
        continue;
      }

      let val = typeof item === 'object' && item !== null ? item.value : item;
      if (val === undefined || val === null) continue;

      const str = String(val).trim();
      if (str.length === 0 || str === '0' || str === '0.00') {
        continue;
      }

      if (initialSnapshot) {
        const initVal = (initialSnapshot[key] !== undefined ? initialSnapshot[key] : '').trim();
        if (initialSnapshot[key + '::' + str] !== undefined) {
          if (initialSnapshot[key + '::' + str] !== '1') {
            meaningfulCount++;
          }
        } else if (str !== initVal) {
          meaningfulCount++;
        }
      } else {
        meaningfulCount++;
      }
    }

    return meaningfulCount > 0;
  }

  function initFormDrafts() {
    if (typeof window === 'undefined' || !window.localStorage) {
      return;
    }

    // One-time purge of any residual drafts from excluded modules
    try {
      ['transporter', 'truck_owner', 'truck-owner', 'voucher', 'invoice'].forEach(function (term) {
        for (let i = localStorage.length - 1; i >= 0; i--) {
          const key = localStorage.key(i);
          if (key && key.startsWith('tb_draft_') && key.includes(term)) {
            localStorage.removeItem(key);
          }
        }
      });
    } catch (e) {}

    const form = findCreateForm();
    if (!form) {
      return;
    }

    const moduleKey = getDraftModuleKey(form);
    if (!moduleKey) {
      return;
    }

    const isLinkedLr = moduleKey.includes('lorry_receipts_create_linked') || form.classList.contains('linked-lr-form');

    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') || '0';
    const companyId = document.querySelector('meta[name="company-id"]')?.getAttribute('content') || '0';
    const storageKey = 'tb_draft_c' + companyId + '_u' + userId + '_' + moduleKey;

    // Check if user just clicked "Clear" in this session/tab
    try {
      if (
        window.sessionStorage &&
        (window.sessionStorage.getItem('tb_draft_just_cleared_' + moduleKey) === '1' ||
          (isLinkedLr && window.sessionStorage.getItem('tb_draft_just_cleared_lorry_receipts_create_linked') === '1'))
      ) {
        window.sessionStorage.removeItem('tb_draft_just_cleared_' + moduleKey);
        window.sessionStorage.removeItem('tb_draft_just_cleared_lorry_receipts_create_linked');
        window.sessionStorage.removeItem('tb_draft_just_cleared_lorry_receipts_create');
        localStorage.removeItem(storageKey);
        for (let i = localStorage.length - 1; i >= 0; i--) {
          const k = localStorage.key(i);
          if (k && k.startsWith('tb_draft_') && (k.includes(moduleKey) || (isLinkedLr && k.includes('lorry_receipts_create')))) {
            localStorage.removeItem(k);
          }
        }
      }
    } catch (e) {}

    window.__tbDraftDiscardActive = false;
    delete form.dataset.tbDraftDiscarding;

    let isRestoring = false;
    let isDiscarding = false;
    let isDirty = false;
    let isPageInitializing = true;
    setTimeout(function () {
      isPageInitializing = false;
    }, 800);

    // Capture initial pristine state of the form
    const initialSnapshot = getFormSnapshot(form);

    const existingDraft = localStorage.getItem(storageKey);
    let hasRealDraft = false;
    if (existingDraft) {
      try {
        const parsed = JSON.parse(existingDraft);
        if (parsed && parsed.data && hasMeaningfulUserData(parsed.data, initialSnapshot, isLinkedLr)) {
          hasRealDraft = true;
        } else {
          // Stale draft containing only defaults or empty data: purge it!
          localStorage.removeItem(storageKey);
          for (let i = localStorage.length - 1; i >= 0; i--) {
            const k = localStorage.key(i);
            if (k && k.startsWith('tb_draft_') && (k.includes(moduleKey) || (isLinkedLr && k.includes('lorry_receipts_create')))) {
              localStorage.removeItem(k);
            }
          }
        }
      } catch (e) {
        try { localStorage.removeItem(storageKey); } catch (err) {}
      }
    }

    // Clean up draft on form submission
    form.addEventListener('submit', function () {
      isDiscarding = true;
      form.dataset.tbDraftDiscarding = '1';
      try { localStorage.removeItem(storageKey); } catch (e) {}
    });

    try {
      const origSubmit = form.submit;
      form.submit = function () {
        isDiscarding = true;
        form.dataset.tbDraftDiscarding = '1';
        try { localStorage.removeItem(storageKey); } catch (e) {}
        return origSubmit.apply(form, arguments);
      };
    } catch (e) {}

    // Skip restore ONLY if there are active server-side validation error classes
    const hasServerErrors = form.querySelector('.is-invalid') !== null;

    if (!hasServerErrors && hasRealDraft) {
      isRestoring = true;
      restoreDraft(form, storageKey, function () {
        isDiscarding = true;
        isDirty = false;
        form.dataset.tbDraftDiscarding = '1';
      }, initialSnapshot);
      setTimeout(function () {
        isRestoring = false;
      }, 500);
    }

    // Attach or inject the Clear button next to Cancel / Submit
    ensureClearDraftButton(form, storageKey);

    // Auto-save on input or change (debounced)
    let saveTimeout = null;
    const scheduleSave = function () {
      if (isPageInitializing || isRestoring || isDiscarding || window.__tbDraftDiscardActive || form.dataset.tbDraftDiscarding === '1') return;
      clearTimeout(saveTimeout);
      saveTimeout = setTimeout(function () {
        if (!isPageInitializing && !isRestoring && !isDiscarding && !window.__tbDraftDiscardActive && form.dataset.tbDraftDiscarding !== '1') {
          saveDraft(form, storageKey, initialSnapshot);
        }
      }, 250);
    };

    const onUserInteraction = function () {
      if (isPageInitializing || isRestoring || isDiscarding || window.__tbDraftDiscardActive || form.dataset.tbDraftDiscarding === '1') return;
      isDirty = true;
      scheduleSave();
    };

    form.addEventListener('input', onUserInteraction);
    form.addEventListener('change', onUserInteraction);
    form.addEventListener('click', function (e) {
      if (e.target.closest('#lrClearFormBtn, #fbClearFormBtn, [data-draft-clear="true"]')) {
        isDiscarding = true;
        isDirty = false;
        window.__tbDraftDiscardActive = true;
        form.dataset.tbDraftDiscarding = '1';
        return;
      }
      if (e.target.closest('.remove-lr-item, #add-lr-item')) {
        setTimeout(onUserInteraction, 60);
      }
    });

    if (window.jQuery) {
      window.jQuery(form).on('change.tbdraft select2:select.tbdraft select2:clear.tbdraft', 'select', onUserInteraction);
    }

    // Immediate save before leaving / switching pages ONLY if user modified form
    window.addEventListener('beforeunload', function () {
      if (window.__tbDraftDiscardActive || form.dataset.tbDraftDiscarding === '1') return;
      if (!isDiscarding && isDirty) {
        saveDraft(form, storageKey, initialSnapshot);
      }
    });
    window.addEventListener('pagehide', function () {
      if (window.__tbDraftDiscardActive || form.dataset.tbDraftDiscarding === '1') return;
      if (!isDiscarding && isDirty) {
        saveDraft(form, storageKey, initialSnapshot);
      }
    });
    document.addEventListener('click', function (e) {
      // If clicking Clear button, let Clear button handler do its job (discard)
      if (e.target.closest('#lrClearFormBtn, #fbClearFormBtn, [data-draft-clear="true"]')) {
        isDiscarding = true;
        isDirty = false;
        window.__tbDraftDiscardActive = true;
        form.dataset.tbDraftDiscarding = '1';
        return;
      }

      // Any navigation click (Cancel, Back, sidebar, nav links) preserves and saves the draft ONLY if user made changes
      if (!isDiscarding && isDirty && !window.__tbDraftDiscardActive && form.dataset.tbDraftDiscarding !== '1' && e.target.closest('a[href], .sidebar-menu a, .nav-link, .btn-secondary, .btn-back, .btn-outline-secondary')) {
        saveDraft(form, storageKey, initialSnapshot);
      }
    }, true);
  }

  function isIgnoredForm(form) {
    if (!form) return true;
    if (form.closest('.modal, header, .app-header, nav, .user-footer, .user-menu')) return true;
    if (form.classList.contains('no-draft') || form.getAttribute('data-draft-ignore') === 'true') return true;

    // Ignore edit or delete forms
    if (form.querySelector('input[name="_method"][value="PUT" i], input[name="_method"][value="PATCH" i], input[name="_method"][value="DELETE" i]')) {
      return true;
    }
    const path = window.location.pathname.toLowerCase();
    if (path.includes('/edit')) {
      return true;
    }

    const action = (form.getAttribute('action') || '').toLowerCase();
    if (action.includes('/logout')) return true;
    if (form.id === 'searchForm' || form.id === 'filterForm' || form.id === 'bulkUploadForm' || form.id === 'quickAddLedgerForm') return true;
    if (form.id && form.id.startsWith('add') && form.id.endsWith('Form')) return true;

    // Strictly exclude Transporters, Truck Owners, Invoices, Vouchers
    const excludedPatterns = [
      'transporters',
      'transporter-master',
      'transporter_master',
      'truck_owner',
      'truck-owner',
      'invoice',
      'voucher'
    ];
    for (const pat of excludedPatterns) {
      if (path.includes(pat) || action.includes(pat)) {
        return true;
      }
    }

    return false;
  }

  function findCreateForm() {
    // 1. Explicit data-draft-key or data-draft-enabled
    let f = document.querySelector('form[data-draft-key], form[data-draft-enabled="true"]');
    if (f && !isIgnoredForm(f)) return f;

    // 2. Supported primary create forms for draft autosave & restore
    const targetSelectors = [
      '#lorryReceiptForm',
      '#freightBookingForm',
      '#truckForm',
      '.app-create-page form[action*="/trucks"]',
      '.app-create-page form[action*="/consignees"]',
      '.app-create-page form[action*="/goods-types"]',
      '.app-create-page form[action*="/pump"]',
      '.app-create-page form[action*="/truck-types"]',
      'form[action*="/trucks"]',
      'form[action*="/consignees"]',
      'form[action*="/goods-types"]',
      'form[action*="/pumps"]',
      'form[action*="/pump"]',
      'form[action*="/truck-types"]'
    ];
    for (const selector of targetSelectors) {
      const el = document.querySelector(selector);
      if (el && !isIgnoredForm(el)) return el;
    }

    return null;
  }

  function getDraftModuleKey(form) {
    if (form.getAttribute('data-draft-key')) {
      const baseKey = form.getAttribute('data-draft-key');
      const search = window.location.search;
      const params = new URLSearchParams(search);
      const suffix = [];
      if (params.get('lr_id')) suffix.push('lr_' + params.get('lr_id'));
      if (params.get('freight_booking_id')) suffix.push('fb_' + params.get('freight_booking_id'));
      return baseKey + (suffix.length ? '_' + suffix.join('_') : '');
    }
    const path = window.location.pathname.replace(/\/+$/, '').toLowerCase();
    if (path.startsWith('/trucks/create')) {
      return 'trucks_create';
    }
    if (path.startsWith('/consignees/create')) {
      return 'consignees_create';
    }
    if (path.startsWith('/master/goods-types/create')) {
      return 'master_goods_types_create';
    }
    if (path.startsWith('/master/pumps/create')) {
      return 'master_pumps_create';
    }
    if (path.startsWith('/master/truck-types/create')) {
      return 'master_truck_types_create';
    }
    if (path.startsWith('/lorry_receipts/create-linked')) {
      return 'lorry_receipts_create_linked';
    }
    if (path.startsWith('/lorry_receipts/create')) {
      const params = new URLSearchParams(window.location.search);
      const suffix = [];
      if (params.get('freight_booking_id')) suffix.push('fb_' + params.get('freight_booking_id'));
      return 'lorry_receipts_create' + (suffix.length ? '_' + suffix.join('_') : '');
    }
    if (path.startsWith('/freight_bookings/create')) {
      const params = new URLSearchParams(window.location.search);
      const suffix = [];
      if (params.get('lr_id')) suffix.push('lr_' + params.get('lr_id'));
      return 'freight_bookings_create' + (suffix.length ? '_' + suffix.join('_') : '');
    }
    const cleanPath = path.replace(/^\//, '').replace(/[^a-z0-9_-]/g, '_');
    return cleanPath;
  }

  function saveDraft(form, storageKey, initialSnapshot) {
    if (!form || window.__tbDraftDiscardActive || form.dataset.tbDraftDiscarding === '1') {
      return;
    }
    try {
      const elements = Array.from(form.querySelectorAll('input, select, textarea'));
      const data = {};

      elements.forEach(function (el) {
        if (!el.name) return;
        if (el.name === '_token' || el.name === '_method') return;
        if (el.type === 'file' || el.type === 'password' || el.type === 'submit' || el.type === 'button') return;
        if (el.readOnly || el.disabled) return;

        // Skip fixed hidden inputs and server boilerplate that are never manually filled by the user
        if (el.type === 'hidden') {
          const hName = el.name.toLowerCase();
          if (
            form.id === 'freightBookingForm' ||
            [
              'company_id',
              'branch_id',
              'user_id',
              'lane',
              'advance_amount',
              'is_delivered',
              'is_cancelled',
              'redirect_to_payment',
              'transporter_tds_percentage',
              'pan_verified',
              'is_dummy_pan',
              'consignor_contact_number',
              'source_city',
              'source_state',
              'consignee_contact_number',
              'destination_city',
              'destination_state'
            ].includes(hName)
          ) {
            return;
          }
        }

        if (el.type === 'checkbox') {
          if (el.checked) {
            if (!data[el.name]) data[el.name] = [];
            data[el.name].push(el.value || '1');
          } else if (initialSnapshot && initialSnapshot[el.name + '::' + el.value] === '1') {
            if (!data[el.name]) data[el.name] = [];
          }
        } else if (el.type === 'radio') {
          if (el.checked) {
            data[el.name] = el.value;
          }
        } else if (el.tagName === 'SELECT') {
          if (el.multiple) {
            const selected = Array.from(el.selectedOptions).map(function (o) {
              return { value: o.value, text: (o.text || o.innerText || '').trim() };
            });
            if (selected.length) {
              data[el.name] = selected;
            }
          } else {
            const opt = el.selectedOptions && el.selectedOptions[0];
            const val = el.value;
            if (val === undefined || val === null || String(val).trim().length === 0) {
              return;
            }

            let text = opt ? (opt.text || opt.innerText || '').trim() : '';
            const attrs = {};

            if (window.jQuery && window.jQuery(el).hasClass('select2-hidden-accessible')) {
              try {
                const s2Data = window.jQuery(el).select2('data');
                if (s2Data && s2Data[0] && s2Data[0].id) {
                  val = String(s2Data[0].id);
                  if (s2Data[0].text) {
                    text = String(s2Data[0].text).trim();
                  }
                  if (s2Data[0].transporter_id) attrs.transporterId = s2Data[0].transporter_id;
                  if (s2Data[0].transporter_name) attrs.transporterName = s2Data[0].transporter_name;
                  if (s2Data[0].driver_name) attrs.driverName = s2Data[0].driver_name;
                }
              } catch (e) {}
            }

            if (opt) {
              const dtId = opt.getAttribute('data-transporter-id');
              const dtName = opt.getAttribute('data-transporter-name');
              const dName = opt.getAttribute('data-driver-name');
              if (dtId && !attrs.transporterId) attrs.transporterId = dtId;
              if (dtName && !attrs.transporterName) attrs.transporterName = dtName;
              if (dName && !attrs.driverName) attrs.driverName = dName;
            }

            const hasAttrs = Object.keys(attrs).length > 0;
            const isSelect2 = window.jQuery && window.jQuery(el).hasClass('select2-hidden-accessible');
            const isAjaxSelect2 = Boolean(
              isSelect2 && (
                window.jQuery(el).data('select2')?.options?.options?.ajax ||
                el.getAttribute('data-ajax-url') ||
                el.classList.contains('select2-ajax') ||
                el.classList.contains('ajax-select')
              )
            );
            const needsTextObject = isAjaxSelect2 || (isSelect2 && text && text !== val && !el.querySelector('option[value="' + CSS.escape(val) + '"]'));

            if (hasAttrs) {
              data[el.name] = {
                value: val,
                text: text || val,
                attrs: attrs
              };
            } else if (needsTextObject) {
              data[el.name] = {
                value: val,
                text: text
              };
            } else {
              data[el.name] = val;
            }
          }
        } else {
          const val = el.value;
          if (val && val.trim().length > 0) {
            data[el.name] = val.trim();
          }
        }
      });

      // Special capture for Linked LR items inside #lr-items-container (which don't have name attributes)
      if (form.classList.contains('linked-lr-form') || form.querySelector('#lr-items-container')) {
        const lrCards = Array.from(form.querySelectorAll('#lr-items-container .lr-item'));
        const savedLrs = [];
        lrCards.forEach(function (card) {
          const getVal = function (cls) {
            return (card.querySelector('.' + cls)?.value || '').trim();
          };
          const getText = function (cls) {
            const sel = card.querySelector('.' + cls);
            if (!sel) return '';
            if (window.jQuery && window.jQuery(sel).hasClass('select2-hidden-accessible')) {
              try {
                const s2 = window.jQuery(sel).select2('data');
                if (s2 && s2[0] && s2[0].id && s2[0].text) return (s2[0].text || '').trim();
              } catch (e) {}
            }
            const opt = sel.selectedOptions && sel.selectedOptions[0];
            if (opt && opt.value && String(opt.value).trim().length > 0) {
              return (opt.text || opt.innerText || '').trim();
            }
            return '';
          };
          const item = {
            lr_number: getVal('lr-number-input'),
            invoice_number: getVal('invoice-number-input'),
            customer_id: getVal('customer-select'),
            customer_text: getText('customer-select'),
            gr_number: getVal('gr-number-input'),
            consignor_id: getVal('consignor-select'),
            consignor_text: getText('consignor-select'),
            consignee_id: getVal('consignee-select'),
            consignee_text: getText('consignee-select'),
            loading_station: getVal('loading-station-input'),
            unloading_station: getVal('unloading-station-input'),
            lane: getVal('lane'),
            goods_type: getVal('goods-type'),
            goods_type_text: getText('goods-type'),
            units: getVal('units'),
            goods_value: getVal('goods-value'),
            weight: getVal('weight'),
            fr_rate: getVal('fr-rate'),
            is_fixed_amount: card.querySelector('.is-fixed-amount')?.checked ? '1' : '0',
            amount: getVal('amount'),
            source_city: getVal('source-city'),
            source_state: getVal('source-state'),
            destination_city: getVal('destination-city'),
            destination_state: getVal('destination-state'),
            consignor_contact: getVal('consignor-contact'),
            consignee_contact: getVal('consignee-contact'),
          };

          if (isRealLinkedLrItem(item)) {
            savedLrs.push(item);
          }
        });
        if (savedLrs.length > 0) {
          data._linked_lrs = savedLrs;
        }
      }

      // ONLY save if there is actual, meaningful user-entered data differing from pristine state
      const moduleKey = getDraftModuleKey(form);
      const isLinkedLr = moduleKey && (moduleKey.includes('lorry_receipts_create_linked') || form.classList.contains('linked-lr-form'));
      if (hasMeaningfulUserData(data, initialSnapshot, isLinkedLr)) {
        localStorage.setItem(storageKey, JSON.stringify({
          savedAt: new Date().toISOString(),
          data: data
        }));
      } else {
        localStorage.removeItem(storageKey);
      }
    } catch (e) {}
  }

  function restoreDraft(form, storageKey, onDiscard, initialSnapshot) {
    try {
      const raw = localStorage.getItem(storageKey);
      if (!raw) return;

      const parsed = JSON.parse(raw);
      if (!parsed || !parsed.data) {
        try { localStorage.removeItem(storageKey); } catch (e) {}
        return;
      }

      const data = parsed.data;
      const moduleKey = getDraftModuleKey(form);
      const isLinkedLr = moduleKey && (moduleKey.includes('lorry_receipts_create_linked') || form.classList.contains('linked-lr-form'));

      // FIRST: Check if the saved draft actually contains real user input differing from pristine render
      if (!hasMeaningfulUserData(data, initialSnapshot, isLinkedLr)) {
        try { localStorage.removeItem(storageKey); } catch (e) {}
        return;
      }

      let appliedCount = 0;
      let actuallyChangedCount = 0;

      Object.keys(data).forEach(function (name) {
        if (name === '_linked_lrs') return;
        const fieldData = data[name];
        const val = typeof fieldData === 'object' && fieldData !== null && !Array.isArray(fieldData)
          ? fieldData.value
          : fieldData;

        if (Array.isArray(fieldData)) {
          // Checkboxes
          const checkboxes = form.querySelectorAll(`input[type="checkbox"][name="${name}"]`);
          if (checkboxes.length) {
            checkboxes.forEach(function (cb) {
              if (fieldData.includes(cb.value)) {
                if (!cb.checked) {
                  cb.checked = true;
                  appliedCount++;
                  if (!isBoilerplateField(name)) actuallyChangedCount++;
                  cb.dispatchEvent(new Event('change', { bubbles: true }));
                }
              }
            });
          }
          // Multi-select
          const select = form.querySelector(`select[name="${name}"]`);
          if (select && select.multiple) {
            let changedSelect = false;
            fieldData.forEach(function (item) {
              const itemVal = typeof item === 'object' ? item.value : item;
              const itemText = typeof item === 'object' ? item.text : item;
              let opt = select.querySelector('option[value="' + CSS.escape(itemVal) + '"]');
              if (!opt) {
                opt = new Option(itemText || itemVal, itemVal, true, true);
                select.add(opt);
                changedSelect = true;
              } else if (!opt.selected) {
                opt.selected = true;
                changedSelect = true;
              }
            });
            if (changedSelect) {
              appliedCount++;
              if (!isBoilerplateField(name)) actuallyChangedCount++;
              if (window.jQuery && window.jQuery(select).data('select2')) {
                window.jQuery(select).trigger('change.select2');
              }
            }
          }
        } else {
          const field = form.querySelector(`[name="${name}"]`);
          if (field) {
            if (field.type === 'radio') {
              const radio = form.querySelector(`input[type="radio"][name="${name}"][value="${val}"]`);
              if (radio) {
                if (!radio.checked) {
                  radio.checked = true;
                  appliedCount++;
                  if (!isBoilerplateField(name)) actuallyChangedCount++;
                  radio.dispatchEvent(new Event('change', { bubbles: true }));
                }
              }
            } else if (field.tagName === 'SELECT') {
              if (val !== undefined && val !== null && val !== '') {
                const text = typeof fieldData === 'object' && fieldData !== null ? fieldData.text : '';
                const attrs = typeof fieldData === 'object' && fieldData !== null && fieldData.attrs ? fieldData.attrs : {};

                let opt = field.querySelector('option[value="' + CSS.escape(val) + '"]');
                if (!opt) {
                  opt = new Option(text || val, val, true, true);
                  if (attrs.transporterId) opt.setAttribute('data-transporter-id', attrs.transporterId);
                  if (attrs.transporterName) opt.setAttribute('data-transporter-name', attrs.transporterName);
                  if (attrs.driverName) opt.setAttribute('data-driver-name', attrs.driverName);
                  field.add(opt);
                } else if (attrs) {
                  if (attrs.transporterId && !opt.getAttribute('data-transporter-id')) opt.setAttribute('data-transporter-id', attrs.transporterId);
                  if (attrs.transporterName && !opt.getAttribute('data-transporter-name')) opt.setAttribute('data-transporter-name', attrs.transporterName);
                  if (attrs.driverName && !opt.getAttribute('data-driver-name')) opt.setAttribute('data-driver-name', attrs.driverName);
                }
                const oldVal = (initialSnapshot && initialSnapshot[name] !== undefined) ? initialSnapshot[name] : (field.value || '');
                const newVal = String(val).trim();
                opt.selected = true;
                field.value = val;
                appliedCount++;
                if (!isBoilerplateField(name) && newVal !== oldVal && newVal.length > 0 && newVal !== '0' && newVal !== '0.00') {
                  actuallyChangedCount++;
                }

                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));

                if (window.jQuery) {
                  window.jQuery(field).trigger('change');
                }
              }
            } else {
              if (val !== undefined && val !== null && val !== '') {
                const oldVal = (initialSnapshot && initialSnapshot[name] !== undefined) ? initialSnapshot[name] : (field.value || '').trim();
                const newVal = String(val).trim();
                field.value = val;
                appliedCount++;
                if (!isBoilerplateField(name) && field.type !== 'hidden' && newVal.length > 0 && newVal !== '0' && newVal !== '0.00' && newVal !== oldVal) {
                  actuallyChangedCount++;
                }
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
              }
            }
          }
        }
      });

      // Special handling for Pay To pills (transporter_type)
      if (data.transporter_type) {
        const pType = typeof data.transporter_type === 'object' ? data.transporter_type.value : data.transporter_type;
        if (pType) {
          const tTypeSelect = form.querySelector('#transporter_type');
          if (tTypeSelect && tTypeSelect.value !== pType) {
            tTypeSelect.value = pType;
            tTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            const truckVal = form.querySelector('#truck_id')?.value;
            if (truckVal && truckVal.trim() !== '') {
              actuallyChangedCount++;
            }
          }
          document.querySelectorAll('.lrf-pay-pill').forEach(function (pill) {
            pill.classList.toggle('is-active', pill.getAttribute('data-pay-type') === pType);
          });
          if (typeof window.syncLrPayeeVisibility === 'function') {
            window.syncLrPayeeVisibility();
          }
        }
      }

      // Special restore for Linked LR items inside #lr-items-container
      if ((form.classList.contains('linked-lr-form') || form.querySelector('#lr-items-container')) && Array.isArray(data._linked_lrs)) {
        const validLrs = data._linked_lrs.filter(isRealLinkedLrItem);
        if (validLrs.length > 0) {
          actuallyChangedCount += validLrs.length;
          appliedCount += validLrs.length;

          const populateLinkedLrs = function () {
            const container = form.querySelector('#lr-items-container');
            if (!container) return;

            container.innerHTML = '';

            validLrs.forEach(function (prefill) {
              if (typeof window.addLrItem === 'function') {
                window.addLrItem(prefill);
              }
            });

            if (window.LinkedLrFormUI && typeof window.LinkedLrFormUI.updateSummary === 'function') {
              window.LinkedLrFormUI.updateSummary();
            }
          };

          if (typeof window.addLrItem === 'function') {
            populateLinkedLrs();
          } else {
            let retryCount = 0;
            const checkAddLrInterval = setInterval(function () {
              retryCount++;
              if (typeof window.addLrItem === 'function') {
                clearInterval(checkAddLrInterval);
                populateLinkedLrs();
              } else if (retryCount > 40) {
                clearInterval(checkAddLrInterval);
              }
            }, 50);
          }
        }
      }

      // Delayed second pass for Select2 widgets that initialize late
      setTimeout(function () {
        if (window.jQuery) {
          Object.keys(data).forEach(function (name) {
            const field = form.querySelector('select[name="' + name + '"]');
            if (field) {
              window.jQuery(field).trigger('change.select2').trigger('change');
            }
          });
          form.querySelectorAll('#lr-items-container .select2-hidden-accessible').forEach(function (s2) {
            window.jQuery(s2).trigger('change.select2');
          });
        }
        const truckSel = form.querySelector('#truck_id');
        if (truckSel && truckSel.value) {
          truckSel.dispatchEvent(new Event('change', { bubbles: true }));
          if (typeof window.syncLrPayeeVisibility === 'function') {
            window.syncLrPayeeVisibility();
          }
        }
        if (data.transporter_type) {
          const pType = typeof data.transporter_type === 'object' ? data.transporter_type.value : data.transporter_type;
          document.querySelectorAll('.lrf-pay-pill').forEach(function (pill) {
            pill.classList.toggle('is-active', pill.getAttribute('data-pay-type') === pType);
          });
          if (typeof window.syncLrPayeeVisibility === 'function') {
            window.syncLrPayeeVisibility();
          }
        }
        if (window.LinkedLrFormUI && typeof window.LinkedLrFormUI.updateSummary === 'function') {
          window.LinkedLrFormUI.updateSummary();
        }
      }, 350);

      // ONLY show toast if there were meaningful user changes restored!
      if (actuallyChangedCount > 0) {
        showDraftRestoredToast();
      } else {
        try { localStorage.removeItem(storageKey); } catch (e) {}
      }
    } catch (e) {}
  }

  function showDraftRestoredToast() {
    const existing = document.getElementById('tbDraftToast');
    if (existing) existing.remove();

    const toastWrapper = document.createElement('div');
    toastWrapper.id = 'tbDraftToast';
    toastWrapper.style.cssText = 'position: fixed; top: 15px; right: 24px; z-index: 9999; pointer-events: auto; animation: tbToastFadeIn 0.25s ease-out;';

    toastWrapper.innerHTML = `
      <div style="
        background: #00c0ef;
        color: #111827;
        padding: 10px 18px;
        border-radius: 8px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16);
        display: flex;
        align-items: center;
        gap: 20px;
        font-family: inherit;
        font-size: 0.90rem;
        font-weight: 500;
        min-width: 250px;
        justify-content: space-between;
      ">
        <span style="letter-spacing: 0.01em;">Restored unsaved draft.</span>
        <button type="button" aria-label="Close" style="
          background: transparent;
          border: none;
          color: #ffffff;
          font-size: 1.35rem;
          line-height: 1;
          cursor: pointer;
          opacity: 0.95;
          padding: 0 0 0 8px;
          display: flex;
          align-items: center;
        " onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.95'">
          &times;
        </button>
      </div>
    `;

    document.body.appendChild(toastWrapper);

    const closeBtn = toastWrapper.querySelector('button');
    let timer = null;

    const dismiss = function () {
      clearTimeout(timer);
      toastWrapper.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
      toastWrapper.style.opacity = '0';
      toastWrapper.style.transform = 'translateY(-6px)';
      setTimeout(function () {
        toastWrapper.remove();
      }, 250);
    };

    if (closeBtn) {
      closeBtn.addEventListener('click', dismiss);
    }

    timer = setTimeout(dismiss, 4000);
  }

  function handleFormClear(form, storageKey) {
    window.__tbDraftDiscardActive = true;
    if (form) {
      form.dataset.tbDraftDiscarding = '1';
    }
    try {
      localStorage.removeItem(storageKey);
      const moduleKey = form ? (form.getAttribute('data-draft-key') || getDraftModuleKey(form) || '') : '';
      const isLinkedLr = moduleKey && (moduleKey.includes('lorry_receipts_create_linked') || (form && form.classList.contains('linked-lr-form')));
      for (let i = localStorage.length - 1; i >= 0; i--) {
        const k = localStorage.key(i);
        if (k && k.startsWith('tb_draft_')) {
          if (
            k === storageKey ||
            (moduleKey && k.includes(moduleKey)) ||
            k.includes('lorry_receipts_create_linked') ||
            (isLinkedLr && k.includes('lorry_receipts_create'))
          ) {
            localStorage.removeItem(k);
          }
        }
      }
    } catch (e) {}
    document.getElementById('tbDraftToast')?.remove();
  }

  function clearFormDirectly(form, storageKey) {
    window.__tbDraftDiscardActive = true;
    const moduleKey = form ? (form.getAttribute('data-draft-key') || getDraftModuleKey(form) || '') : '';
    const isLinkedLr = moduleKey && (moduleKey.includes('lorry_receipts_create_linked') || (form && form.classList.contains('linked-lr-form')));

    try {
      if (window.sessionStorage && moduleKey) {
        window.sessionStorage.setItem('tb_draft_just_cleared_' + moduleKey, '1');
        if (isLinkedLr) {
          window.sessionStorage.setItem('tb_draft_just_cleared_lorry_receipts_create_linked', '1');
          window.sessionStorage.setItem('tb_draft_just_cleared_lorry_receipts_create', '1');
        }
      }
    } catch (e) {}

    handleFormClear(form, storageKey);

    try {
      if (form) {
        form.dataset.tbDraftDiscarding = '1';
        form.reset();
        form.classList.remove('was-validated');

        const container = form.querySelector('#lr-items-container');
        if (container) {
          container.innerHTML = '';
        }

        form.querySelectorAll('input:not([type="hidden"]), textarea').forEach(function (el) {
          if (el.type === 'checkbox' || el.type === 'radio') {
            el.checked = false;
          } else {
            el.value = '';
          }
        });
        form.querySelectorAll('select').forEach(function (sel) {
          sel.value = '';
          if (window.jQuery && window.jQuery(sel).hasClass('select2-hidden-accessible')) {
            window.jQuery(sel).val(null);
          }
        });
      }
    } catch (e) {}

    window.location.replace(window.location.href);
  }

  function ensureClearDraftButton(form, storageKey) {
    if (!form) return;

    // 1. If form already has a Clear button (like #lrClearFormBtn in Bilty Create or #fbClearFormBtn)
    const existingClearBtn = form.querySelector('#lrClearFormBtn, #fbClearFormBtn, [data-draft-clear="true"]');
    if (existingClearBtn) {
      existingClearBtn.addEventListener('click', function (e) {
        window.__tbDraftDiscardActive = true;
        clearFormDirectly(form, storageKey);
      }, true);
      return;
    }

    // 2. Find the primary form action container at the bottom of the create form
    // NEVER attach inside an input-group, lookup section, or modal
    let actionContainer = form.querySelector(
      '.app-form-actions .card-body, .app-form-actions, .lrf-actions, .form-actions, .card-footer'
    );

    if (!actionContainer) {
      // Find the main submit / save button of the form, explicitly excluding input-groups and lookup buttons
      const candidates = Array.from(form.querySelectorAll(
        'button[type="submit"], input[type="submit"], button[onclick*="Confirmation"], button[onclick*="submit"], button.btn-brand-primary, button.btn-success'
      ));

      for (const btn of candidates) {
        if (btn.closest('.input-group, .modal, #panLookupSection, [id*="Lookup"], [id*="lookup"], .app-form-section__body')) {
          continue;
        }
        const container = btn.closest('.d-flex, .card-body, .card-footer') || btn.parentElement;
        if (container && !container.closest('.input-group')) {
          actionContainer = container;
          break;
        }
      }
    }

    if (!actionContainer) return;
    if (actionContainer.closest('.input-group, .modal, #panLookupSection')) return;
    if (actionContainer.querySelector('[data-draft-clear="true"], #lrClearFormBtn, #fbClearFormBtn')) return;

    // Create Clear button styled identical to Bilty create (btn btn-outline-secondary btn-sm)
    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'btn btn-outline-secondary btn-sm flex-fill flex-sm-grow-0 text-nowrap w-auto';
    clearBtn.setAttribute('data-draft-clear', 'true');
    clearBtn.innerHTML = '<i class="bi bi-eraser me-1"></i> Clear';

    // Place Clear button right next to Cancel/Back button if present, otherwise before submit
    const cancelBtn = actionContainer.querySelector('a.btn-secondary, a.btn-back, a.btn-outline-secondary, button.btn-secondary, [data-bs-dismiss="modal"]');
    const submitBtn = actionContainer.querySelector('button[type="submit"], input[type="submit"], button.btn-brand-primary, button.btn-success, button');

    if (cancelBtn) {
      cancelBtn.parentNode.insertBefore(clearBtn, cancelBtn.nextSibling);
    } else if (submitBtn) {
      submitBtn.parentNode.insertBefore(clearBtn, submitBtn);
    } else {
      actionContainer.appendChild(clearBtn);
    }

    clearBtn.addEventListener('click', function (e) {
      e.preventDefault();
      clearFormDirectly(form, storageKey);
    });
  }

  function boot() {
    ensureSwalDefaults();
    tagModals();
    interceptNativeConfirms();
    initIndexFiltersToggle();
    initFormDrafts();
  }

  window.TruckbillUI = {
    confirm,
    alert,
    swalClasses: SWAL_CLASSES,
    ensureSwalDefaults,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})(window, document);
