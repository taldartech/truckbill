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
  // Cache human-readable labels into sessionStorage so on draft restore we never flash raw IDs
  document.addEventListener('change', function (e) {
    if (e.target && e.target.tagName === 'SELECT') {
      const opt = e.target.selectedOptions ? e.target.selectedOptions[0] : null;
      if (opt && opt.value && opt.text) {
        const sVal = String(opt.value).trim();
        const sText = String(opt.text).trim();
        if (sVal && sText && sText !== sVal && !sText.startsWith('#') && !sText.toLowerCase().startsWith('select ') && sText !== 'Loading...') {
          try { sessionStorage.setItem('tb_label_' + sVal, sText); } catch (err) {}
        }
        const dataId = opt.getAttribute('data-id');
        if (dataId) {
          try { sessionStorage.setItem('tb_label_' + String(dataId).trim(), sText); } catch (err) {}
        }
      }
    }
  }, true);

  if (window.jQuery) {
    window.jQuery(document).on('select2:select', function (e) {
      const d = e.params ? e.params.data : null;
      if (d && d.id && d.text) {
        const sVal = String(d.id).trim();
        const sText = String(d.text).trim();
        if (sVal && sText && sText !== sVal && !sText.startsWith('#') && !sText.toLowerCase().startsWith('select ') && sText !== 'Loading...') {
          try { sessionStorage.setItem('tb_label_' + sVal, sText); } catch (err) {}
        }
      }
    });
  }

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
      } else if (el.tagName === 'SELECT' && (el.name === 'good_type' || el.id === 'good_type' || el.name === 'truck_type' || el.id === 'truck_type')) {
        const opt = el.options[el.selectedIndex];
        const gId = opt ? opt.getAttribute('data-id') : null;
        snap[el.name] = (gId || el.value || '').trim();
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
      if (key === '_linked_lrs' || key === '_s2_meta') continue;
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

  function purgeDeletedEntityFromDrafts(entityType, id) {
    if (!id || typeof window === 'undefined') return;
    const sId = String(id).trim();
    try { sessionStorage.removeItem('tb_label_' + sId); } catch (e) {}
    try {
      if (!window.localStorage) return;
      for (let i = localStorage.length - 1; i >= 0; i--) {
        const key = localStorage.key(i);
        if (key && key.startsWith('tb_draft_')) {
          const raw = localStorage.getItem(key);
          if (!raw) continue;
          const parsed = JSON.parse(raw);
          if (!parsed || !parsed.data) continue;
          let changed = false;

          const type = String(entityType || '').toLowerCase();
          if (type.includes('truck')) {
            if (String(parsed.data.truck_id) === sId) {
              delete parsed.data.truck_id;
              delete parsed.data.transporter_type;
              delete parsed.data.transporter_id;
              delete parsed.data.owner_id;
              changed = true;
            }
          } else if (type.includes('consignee') || type.includes('customer')) {
            ['customer_id', 'consignor_id', 'consignee_id'].forEach(function (f) {
              if (String(parsed.data[f]) === sId) {
                delete parsed.data[f];
                changed = true;
              }
            });
            if (Array.isArray(parsed.data._linked_lrs)) {
              parsed.data._linked_lrs.forEach(function (lr) {
                ['customer_id', 'consignor_id', 'consignee_id'].forEach(function (f) {
                  if (String(lr[f]) === sId) {
                    delete lr[f];
                    changed = true;
                  }
                });
              });
            }
          } else if (type.includes('transporter') || type.includes('owner')) {
            if (String(parsed.data.transporter_id) === sId || String(parsed.data.owner_id) === sId) {
              delete parsed.data.transporter_id;
              delete parsed.data.owner_id;
              changed = true;
            }
            if (String(parsed.data.reference_by) === sId) {
              delete parsed.data.reference_by;
              changed = true;
            }
          }

          if (changed) {
            localStorage.setItem(key, JSON.stringify(parsed));
          }
        }
      }
    } catch (e) {}
  }

  function initMasterDeleteDraftWatcher() {
    if (typeof window === 'undefined') return;

    // Hook native form submit calls
    try {
      const origFormSubmit = HTMLFormElement.prototype.submit;
      HTMLFormElement.prototype.submit = function () {
        try {
          const isDelete = this.querySelector('input[name="_method"][value="DELETE" i]') ||
            (this.getAttribute('method') || '').toUpperCase() === 'DELETE';
          if (isDelete) {
            const action = this.getAttribute('action') || '';
            const m = action.match(/\/(trucks|consignees|customers|transporters|truck_owners)\/(\d+)/i);
            if (m && m[1] && m[2]) {
              purgeDeletedEntityFromDrafts(m[1], m[2]);
            }
          }
        } catch (err) {}
        return origFormSubmit.apply(this, arguments);
      };
    } catch (e) {}

    // Hook submit events
    document.addEventListener('submit', function (e) {
      const form = e.target;
      if (!form || !form.action) return;
      try {
        const isDelete = form.querySelector('input[name="_method"][value="DELETE" i]') ||
          (form.getAttribute('method') || '').toUpperCase() === 'DELETE';
        if (isDelete) {
          const action = form.getAttribute('action') || '';
          const m = action.match(/\/(trucks|consignees|customers|transporters|truck_owners)\/(\d+)/i);
          if (m && m[1] && m[2]) {
            purgeDeletedEntityFromDrafts(m[1], m[2]);
          }
        }
      } catch (err) {}
    }, true);

    // Hook modal confirm delete button clicks
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('#confirmTruckDelete, #confirmConsigneeDelete, #confirmTransporterDelete, .confirm-delete-btn, .truck-delete-btn, .consignee-delete-btn');
      if (!btn) return;
      try {
        if (btn.id === 'confirmTruckDelete') {
          const form = window.jQuery ? window.jQuery(btn).data('form') : null;
          const action = form ? (form.attr('action') || '') : '';
          const m = action.match(/\/trucks\/(\d+)/i);
          if (m && m[1]) purgeDeletedEntityFromDrafts('trucks', m[1]);
        } else if (btn.id === 'confirmConsigneeDelete') {
          const form = window.jQuery ? window.jQuery(btn).data('form') : null;
          const action = form ? (form.attr('action') || '') : '';
          const m = action.match(/\/(consignees|customers)\/(\d+)/i);
          if (m && m[1]) purgeDeletedEntityFromDrafts('consignees', m[2]);
        }
      } catch (err) {}
    }, true);
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
      // Purge legacy un-scoped freight_bookings_create drafts without _lr_ so old drafts from other bilities don't leak
      for (let i = localStorage.length - 1; i >= 0; i--) {
        const key = localStorage.key(i);
        if (key && key.startsWith('tb_draft_') && key.endsWith('_freight_bookings_create')) {
          localStorage.removeItem(key);
        }
      }
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
      const lrId = params.get('lorry_receipt_id') || params.get('lr_id') || form.querySelector('#lr_id, input[name="lr_id"], input[name="lorry_receipt_id"]')?.value;
      if (lrId) suffix.push('lr_' + String(lrId).trim());
      const fbId = params.get('freight_booking_id') || params.get('fb_id') || form.querySelector('#freight_booking_id, input[name="freight_booking_id"]')?.value;
      if (fbId) suffix.push('fb_' + String(fbId).trim());
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
      const fbId = params.get('freight_booking_id') || params.get('fb_id');
      if (fbId) suffix.push('fb_' + String(fbId).trim());
      return 'lorry_receipts_create' + (suffix.length ? '_' + suffix.join('_') : '');
    }
    if (path.startsWith('/freight_bookings/create')) {
      const params = new URLSearchParams(window.location.search);
      const lrId = (
        params.get('lorry_receipt_id') ||
        params.get('lr_id') ||
        form.querySelector('#lr_id, input[name="lr_id"], input[name="lorry_receipt_id"]')?.value
      );
      const cleanLrId = lrId ? String(lrId).trim() : '';
      return 'freight_bookings_create' + (cleanLrId ? '_lr_' + cleanLrId : '');
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
              return o.value;
            }).filter(Boolean);
            if (selected.length) {
              data[el.name] = selected;
            }
          } else {
            const val = el.value;
            if (val !== undefined && val !== null && String(val).trim().length > 0) {
              const opt = el.options[el.selectedIndex];
              if (opt && opt.text) {
                const sText = opt.text.trim();
                const sVal = String(val).trim();
                if (sText && sText !== sVal && !sText.startsWith('#') && !sText.toLowerCase().startsWith('select ') && sText !== 'Loading...') {
                  try { sessionStorage.setItem('tb_label_' + sVal, sText); } catch (e) {}
                }
              }
              if (el.name === 'good_type' || el.id === 'good_type' || el.name === 'truck_type' || el.id === 'truck_type') {
                const gId = opt ? opt.getAttribute('data-id') : null;
                data[el.name] = (gId || String(val)).trim();
              } else {
                data[el.name] = String(val).trim();
              }
              if (el.name === 'consignor_id' || el.id === 'consignor_id') {
                const cContact = form.querySelector('#consignor_contact_number, input[name="consignor_contact_number"]')?.value;
                const cCity = form.querySelector('#source_city, input[name="source_city"]')?.value;
                const cState = form.querySelector('#source_state, input[name="source_state"]')?.value;
                if (cContact || cCity || cState) {
                  try {
                    sessionStorage.setItem('tb_consignee_data_' + String(val).trim(), JSON.stringify({
                      contact_number: (cContact || '').trim(),
                      city: (cCity || '').trim(),
                      state: (cState || '').trim()
                    }));
                  } catch (e) {}
                }
              }
              if (el.name === 'consignee_id' || el.id === 'consignee_id') {
                const ceContact = form.querySelector('#consignee_contact_number, input[name="consignee_contact_number"]')?.value;
                const ceCity = form.querySelector('#destination_city, input[name="destination_city"]')?.value;
                const ceState = form.querySelector('#destination_state, input[name="destination_state"]')?.value;
                if (ceContact || ceCity || ceState) {
                  try {
                    sessionStorage.setItem('tb_consignee_data_' + String(val).trim(), JSON.stringify({
                      contact_number: (ceContact || '').trim(),
                      city: (ceCity || '').trim(),
                      state: (ceState || '').trim()
                    }));
                  } catch (e) {}
                }
              }
              if (el.name === 'transporter_id' || el.id === 'transporter_id') {
                data['owner_id'] = String(val).trim();
              }
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
          const item = {};
          const addIfNotEmpty = function (key, val) {
            if (val !== undefined && val !== null && String(val).trim().length > 0) {
              item[key] = String(val).trim();
            }
          };

          ['customer-select', 'consignor-select', 'consignee-select', 'goods-type'].forEach(function (cls) {
            const sel = card.querySelector('.' + cls);
            if (sel && sel.selectedIndex >= 0) {
              const o = sel.options[sel.selectedIndex];
              if (o && o.value && o.text) {
                const sText = o.text.trim();
                const sVal = String(o.value).trim();
                if (sText && sText !== sVal && !sText.startsWith('#') && !sText.toLowerCase().startsWith('select ') && sText !== 'Loading...') {
                  try { sessionStorage.setItem('tb_label_' + sVal, sText); } catch (e) {}
                }
              }
            }
          });

          addIfNotEmpty('lr_number', getVal('lr-number-input'));
          addIfNotEmpty('invoice_number', getVal('invoice-number-input'));
          addIfNotEmpty('customer_id', getVal('customer-select'));
          addIfNotEmpty('gr_number', getVal('gr-number-input'));
          addIfNotEmpty('consignor_id', getVal('consignor-select'));
          addIfNotEmpty('consignee_id', getVal('consignee-select'));
          addIfNotEmpty('loading_station', getVal('loading-station-input'));
          addIfNotEmpty('unloading_station', getVal('unloading-station-input'));
          const goodsSel = card.querySelector('.goods-type');
          const goodsOpt = goodsSel && goodsSel.selectedIndex >= 0 ? goodsSel.options[goodsSel.selectedIndex] : null;
          const goodsId = goodsOpt ? (goodsOpt.getAttribute('data-id') || goodsSel.value) : getVal('goods-type');
          addIfNotEmpty('goods_type', goodsId);
          addIfNotEmpty('units', getVal('units'));
          addIfNotEmpty('goods_value', getVal('goods-value'));
          addIfNotEmpty('weight', getVal('weight'));
          addIfNotEmpty('fr_rate', getVal('fr-rate'));
          if (card.querySelector('.is-fixed-amount')?.checked) {
            item.is_fixed_amount = '1';
          }
          addIfNotEmpty('amount', getVal('amount'));

          if (isRealLinkedLrItem(item)) {
            savedLrs.push(item);
          }
        });
        if (savedLrs.length > 0) {
          data._linked_lrs = savedLrs;
        }
      }

      // Explicitly capture lr_id for Lorry Hire scoping
      const lrInput = form.querySelector('#lr_id, input[name="lr_id"], input[name="lorry_receipt_id"]');
      if (lrInput && lrInput.value) {
        data.lr_id = String(lrInput.value).trim();
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

      // Strict LR matching for Lorry Hire: only restore if the draft belongs to THIS bilty
      const currentLrEl = form.querySelector('#lr_id, input[name="lr_id"], input[name="lorry_receipt_id"]');
      if (currentLrEl && currentLrEl.value) {
        const curLrId = String(currentLrEl.value).trim();
        const draftLrId = data.lr_id ? String(data.lr_id).trim() : '';
        if (curLrId && draftLrId && curLrId !== draftLrId) {
          // Draft belongs to a different bilty! Do not restore onto this bilty
          return;
        }
      }

      // FIRST: Check if the saved draft actually contains real user input differing from pristine render
      if (!hasMeaningfulUserData(data, initialSnapshot, isLinkedLr)) {
        try { localStorage.removeItem(storageKey); } catch (e) {}
        return;
      }

      function updateSelect2Option(targetField, optVal, optLabel, optAttrs) {
        if (!targetField || !optLabel) return;
        const sVal = String(optVal).trim();
        try { sessionStorage.setItem('tb_label_' + sVal, optLabel); } catch (e) {}
        if (window.jQuery && window.jQuery(targetField).hasClass('select2-hidden-accessible')) {
          const $tf = window.jQuery(targetField);
          $tf.find('option[value="' + CSS.escape(sVal) + '"]').remove();
          const newOption = new Option(optLabel, sVal, true, true);
          if (optAttrs) {
            if (optAttrs.transporterId) newOption.setAttribute('data-transporter-id', optAttrs.transporterId);
            if (optAttrs.transporterName) newOption.setAttribute('data-transporter-name', optAttrs.transporterName);
            if (optAttrs.driverName) newOption.setAttribute('data-driver-name', optAttrs.driverName);
          }
          $tf.append(newOption).trigger('change');
        } else {
          let o = targetField.querySelector('option[value="' + CSS.escape(sVal) + '"]');
          if (o) {
            o.text = optLabel;
            if (optAttrs) {
              if (optAttrs.transporterId) o.setAttribute('data-transporter-id', optAttrs.transporterId);
              if (optAttrs.transporterName) o.setAttribute('data-transporter-name', optAttrs.transporterName);
              if (optAttrs.driverName) o.setAttribute('data-driver-name', optAttrs.driverName);
            }
          } else {
            const newOption = new Option(optLabel, sVal, true, true);
            targetField.add(newOption);
          }
        }
      }

      let appliedCount = 0;
      let actuallyChangedCount = 0;

      function syncLrCardLabelsIfPresent() {
        const consignorEl = form.querySelector('#consignor_id');
        const consigneeEl = form.querySelector('#consignee_id');
        if (!consignorEl && !consigneeEl) return;

        let cContact = form.querySelector('#consignor_contact_number')?.value || '';
        let sCity = form.querySelector('#source_city')?.value || '';
        let sState = form.querySelector('#source_state')?.value || '';

        if (consignorEl && consignorEl.value && (!cContact || !sCity || !sState)) {
          try {
            const raw = sessionStorage.getItem('tb_consignee_data_' + consignorEl.value);
            if (raw) {
              const cd = JSON.parse(raw);
              if (cd) {
                if (!cContact && cd.contact_number) {
                  cContact = cd.contact_number;
                  const el = form.querySelector('#consignor_contact_number');
                  if (el) el.value = cContact;
                }
                if (!sCity && cd.city) {
                  sCity = cd.city;
                  const el = form.querySelector('#source_city');
                  if (el) el.value = sCity;
                }
                if (!sState && cd.state) {
                  sState = cd.state;
                  const el = form.querySelector('#source_state');
                  if (el) el.value = sState;
                }
              }
            }
          } catch (e) {}
        }

        let ceContact = form.querySelector('#consignee_contact_number')?.value || '';
        let dCity = form.querySelector('#destination_city')?.value || '';
        let dState = form.querySelector('#destination_state')?.value || '';

        if (consigneeEl && consigneeEl.value && (!ceContact || !dCity || !dState)) {
          try {
            const raw = sessionStorage.getItem('tb_consignee_data_' + consigneeEl.value);
            if (raw) {
              const cd = JSON.parse(raw);
              if (cd) {
                if (!ceContact && cd.contact_number) {
                  ceContact = cd.contact_number;
                  const el = form.querySelector('#consignee_contact_number');
                  if (el) el.value = ceContact;
                }
                if (!dCity && cd.city) {
                  dCity = cd.city;
                  const el = form.querySelector('#destination_city');
                  if (el) el.value = dCity;
                }
                if (!dState && cd.state) {
                  dState = cd.state;
                  const el = form.querySelector('#destination_state');
                  if (el) el.value = dState;
                }
              }
            }
          } catch (e) {}
        }

        const setMeta = function (id, val) {
          const el = form.querySelector('#' + id);
          if (!el) return;
          const txt = (val || '').trim();
          el.textContent = txt || '—';
          el.classList.toggle('is-empty', !txt);
        };

        setMeta('consignorContactLabel', cContact);
        setMeta('sourceCityLabel', sCity);
        setMeta('sourceStateLabel', sState);
        setMeta('consigneeContactLabel', ceContact);
        setMeta('destinationCityLabel', dCity);
        setMeta('destinationStateLabel', dState);

        const lane = form.querySelector('#lrGoodsLane');
        const rf = form.querySelector('#lrRouteFlowLabel');
        const rSub = form.querySelector('#lrRouteFlowSub');
        const fromCard = form.querySelector('#lrConsignorCard');
        const toCard = form.querySelector('#lrConsigneeCard');

        const hasFrom = Boolean(consignorEl && consignorEl.value);
        const hasTo = Boolean(consigneeEl && consigneeEl.value);

        if (fromCard) fromCard.classList.toggle('is-filled', hasFrom);
        if (toCard) toCard.classList.toggle('is-filled', hasTo);

        if (lane && rf && rSub) {
          lane.classList.toggle('is-partial', hasFrom !== hasTo || (hasFrom && hasTo && (!sCity || !dCity)));
          lane.classList.toggle('is-ready', hasFrom && hasTo && Boolean(sCity) && Boolean(dCity));

          if (hasFrom && hasTo && sCity && dCity) {
            rf.textContent = `${sCity} → ${dCity}`;
            rSub.textContent = 'Goods moving to consignee';
          } else if (hasFrom && hasTo) {
            const fn = sessionStorage.getItem('tb_label_' + consignorEl.value) || '';
            const tn = sessionStorage.getItem('tb_label_' + consigneeEl.value) || '';
            rf.textContent = fn && tn ? `${fn.split('(')[0].trim()} → ${tn.split('(')[0].trim()}` : 'Route almost ready';
            rSub.textContent = 'Loading location details…';
          } else if (hasFrom) {
            rf.textContent = sCity ? `From ${sCity}` : 'Consignor selected';
            rSub.textContent = 'Select consignee (TO)';
          } else if (hasTo) {
            rf.textContent = dCity ? `To ${dCity}` : 'Consignee selected';
            rSub.textContent = 'Select consignor (FROM)';
          } else {
            rf.textContent = 'Select parties to start route';
            rSub.textContent = 'Goods move from consignor → consignee';
          }
        }
      }

      function clearDeletedSelectOption(targetField, optVal, fieldName) {
        if (!targetField) return;
        const sVal = String(optVal).trim();
        try {
          sessionStorage.removeItem('tb_label_' + sVal);
          sessionStorage.removeItem('tb_consignee_data_' + sVal);
        } catch (e) {}

        // Remove from DOM / Select2
        if (window.jQuery && window.jQuery(targetField).hasClass('select2-hidden-accessible')) {
          const $tf = window.jQuery(targetField);
          $tf.find('option[value="' + CSS.escape(sVal) + '"]').remove();
          if ($tf.val() === sVal) {
            $tf.val(null).trigger('change.select2').trigger('change');
          }
        } else {
          const o = targetField.querySelector('option[value="' + CSS.escape(sVal) + '"]');
          if (o) o.remove();
          if (targetField.value === sVal) {
            targetField.value = '';
            targetField.dispatchEvent(new Event('change', { bubbles: true }));
          }
        }

        // Clean up from stored localStorage draft
        try {
          const raw = localStorage.getItem(storageKey);
          if (raw) {
            const parsed = JSON.parse(raw);
            if (parsed && parsed.data) {
              if (fieldName && parsed.data[fieldName] !== undefined) {
                delete parsed.data[fieldName];
              }
              if (fieldName === 'consignor_id') {
                delete parsed.data['consignor_contact_number'];
                delete parsed.data['source_city'];
                delete parsed.data['source_state'];
              }
              if (fieldName === 'consignee_id') {
                delete parsed.data['consignee_contact_number'];
                delete parsed.data['destination_city'];
                delete parsed.data['destination_state'];
              }
              if (fieldName === 'truck_id') {
                delete parsed.data['transporter_type'];
                delete parsed.data['transporter_id'];
                delete parsed.data['owner_id'];
              }
              if (fieldName === 'transporter_id') {
                delete parsed.data['owner_id'];
              }
              if (hasMeaningfulUserData(parsed.data, initialSnapshot, isLinkedLr)) {
                localStorage.setItem(storageKey, JSON.stringify(parsed));
              } else {
                localStorage.removeItem(storageKey);
              }
            }
          }
        } catch (e) {}

        // Reset dependent fields or cards
        if (targetField.id === 'truck_id' || fieldName === 'truck_id') {
          const lrPayToField = document.getElementById('lrPayToField');
          if (lrPayToField) lrPayToField.hidden = true;
          const lrPayeeRow = document.getElementById('lrPayeeRow');
          if (lrPayeeRow) lrPayeeRow.hidden = true;
          const tTypeSelect = form.querySelector('#transporter_type');
          if (tTypeSelect) {
            tTypeSelect.value = '';
            tTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
          }
          const payeeSelect = form.querySelector('#transporter_id');
          if (payeeSelect) {
            if (window.jQuery && window.jQuery(payeeSelect).hasClass('select2-hidden-accessible')) {
              window.jQuery(payeeSelect).val(null).trigger('change.select2').trigger('change');
            } else {
              payeeSelect.value = '';
              payeeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
          }
          if (typeof window.syncLrTruckMetaAfterClear === 'function') {
            window.syncLrTruckMetaAfterClear();
          }
          if (typeof window.syncLrPayeeVisibility === 'function') {
            window.syncLrPayeeVisibility();
          }
        } else if (targetField.id === 'consignor_id' || fieldName === 'consignor_id') {
          const cInput = document.getElementById('consignor_contact_number');
          if (cInput) cInput.value = '';
          const scInput = document.getElementById('source_city');
          if (scInput) scInput.value = '';
          const ssInput = document.getElementById('source_state');
          if (ssInput) ssInput.value = '';
          const cLabel = document.getElementById('consignorContactLabel');
          if (cLabel) { cLabel.textContent = '—'; cLabel.classList.add('is-empty'); }
          const scLabel = document.getElementById('sourceCityLabel');
          if (scLabel) { scLabel.textContent = '—'; scLabel.classList.add('is-empty'); }
          const ssLabel = document.getElementById('sourceStateLabel');
          if (ssLabel) { ssLabel.textContent = '—'; ssLabel.classList.add('is-empty'); }
          const fromCard = document.getElementById('lrConsignorCard');
          if (fromCard) fromCard.classList.remove('is-filled');
        } else if (targetField.id === 'consignee_id' || fieldName === 'consignee_id') {
          const cInput = document.getElementById('consignee_contact_number');
          if (cInput) cInput.value = '';
          const dcInput = document.getElementById('destination_city');
          if (dcInput) dcInput.value = '';
          const dsInput = document.getElementById('destination_state');
          if (dsInput) dsInput.value = '';
          const cLabel = document.getElementById('consigneeContactLabel');
          if (cLabel) { cLabel.textContent = '—'; cLabel.classList.add('is-empty'); }
          const dcLabel = document.getElementById('destinationCityLabel');
          if (dcLabel) { dcLabel.textContent = '—'; dcLabel.classList.add('is-empty'); }
          const dsLabel = document.getElementById('destinationStateLabel');
          if (dsLabel) { dsLabel.textContent = '—'; dsLabel.classList.add('is-empty'); }
          const toCard = document.getElementById('lrConsigneeCard');
          if (toCard) toCard.classList.remove('is-filled');
        }
      }

      Object.keys(data).forEach(function (name) {
        if (name === '_linked_lrs' || name === '_s2_meta') return;
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
          const field = form.querySelector(`[name="${name}"]`) || (name === 'owner_id' ? form.querySelector('[name="transporter_id"]') : null);
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
                let text = typeof fieldData === 'object' && fieldData !== null ? (fieldData.text || '') : '';
                let attrs = typeof fieldData === 'object' && fieldData !== null && fieldData.attrs ? Object.assign({}, fieldData.attrs) : {};

                if (data._s2_meta && data._s2_meta[name]) {
                  if (data._s2_meta[name].text) text = data._s2_meta[name].text;
                  if (data._s2_meta[name].attrs) Object.assign(attrs, data._s2_meta[name].attrs);
                }

                const isAjaxEntity = (
                  field.id === 'customer_id' ||
                  field.id === 'consignor_id' ||
                  field.id === 'consignee_id' ||
                  field.id === 'truck_id' ||
                  field.id === 'transporter_id' ||
                  field.id === 'reference_by' ||
                  name === 'customer_id' ||
                  name === 'consignor_id' ||
                  name === 'consignee_id' ||
                  name === 'truck_id' ||
                  name === 'transporter_id' ||
                  name === 'reference_by'
                );

                if (isAjaxEntity) {
                  // Optimistic instant render (0ms): display saved labels from session cache immediately
                  const cachedOptLabel = sessionStorage.getItem('tb_label_' + val);
                  if (cachedOptLabel) {
                    updateSelect2Option(field, val, cachedOptLabel, attrs);
                    field.value = val;
                    appliedCount++;
                    actuallyChangedCount++;
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    if (window.jQuery) {
                      window.jQuery(field).trigger('change');
                    }
                    if (typeof window.syncLrPayeeVisibility === 'function') {
                      window.syncLrPayeeVisibility();
                    }
                  }

                  // Verify against API before accepting. If entity was deleted from master, leave blank!
                  if (field.id === 'customer_id' || field.id === 'consignor_id' || field.id === 'consignee_id' || name === 'customer_id' || name === 'consignor_id' || name === 'consignee_id') {
                    fetch('/api/consignees/' + encodeURIComponent(val))
                      .then(function (r) {
                        if (!r.ok) throw new Error('Not found');
                        return r.json();
                      })
                      .then(function (d) {
                        if (d && d.name && d.name !== 'Not provided') {
                          try { sessionStorage.setItem('tb_consignee_data_' + val, JSON.stringify(d)); } catch (e) {}
                          const label = d.city && d.city !== 'Not provided' ? `${d.name} (${d.city})` : d.name;
                          try { sessionStorage.setItem('tb_label_' + val, label); } catch (e) {}
                          updateSelect2Option(field, val, label, attrs);
                          if (!cachedOptLabel) {
                            field.value = val;
                            appliedCount++;
                            actuallyChangedCount++;
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                            if (window.jQuery) {
                              window.jQuery(field).trigger('change');
                            }
                          }
                        } else {
                          try {
                            sessionStorage.removeItem('tb_label_' + val);
                            sessionStorage.removeItem('tb_consignee_data_' + val);
                          } catch (e) {}
                          clearDeletedSelectOption(field, val, name);
                        }
                      })
                      .catch(function () {
                        try {
                          sessionStorage.removeItem('tb_label_' + val);
                          sessionStorage.removeItem('tb_consignee_data_' + val);
                        } catch (e) {}
                        clearDeletedSelectOption(field, val, name);
                      });
                  } else if (field.id === 'truck_id' || name === 'truck_id') {
                    fetch('/api/trucks/' + encodeURIComponent(val))
                      .then(function (r) {
                        if (!r.ok) throw new Error('Not found');
                        return r.json();
                      })
                      .then(function (d) {
                        if (d && (d.truck_number || d.name)) {
                          const num = d.truck_number || d.name;
                          const driver = d.driver?.name || d.driver_name;
                          const owner = d.transporter?.name || d.transporter_name;
                          let label = num;
                          if (driver) label += ' - Driver: ' + driver;
                          if (owner) label += ' (' + owner + ')';
                          try { sessionStorage.setItem('tb_label_' + val, label); } catch (e) {}
                          const optAttrs = Object.assign({}, attrs);
                          if (d.transporter_id) optAttrs.transporterId = d.transporter_id;
                          if (owner) optAttrs.transporterName = owner;
                          if (driver) optAttrs.driverName = driver;
                          updateSelect2Option(field, val, label, optAttrs);
                          if (!cachedOptLabel) {
                            field.value = val;
                            appliedCount++;
                            actuallyChangedCount++;
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                            if (window.jQuery) {
                              window.jQuery(field).trigger('change');
                            }
                            if (typeof window.syncLrPayeeVisibility === 'function') {
                              window.syncLrPayeeVisibility();
                            }
                          }
                        } else {
                          try { sessionStorage.removeItem('tb_label_' + val); } catch (e) {}
                          clearDeletedSelectOption(field, val, name);
                        }
                      })
                      .catch(function () {
                        try { sessionStorage.removeItem('tb_label_' + val); } catch (e) {}
                        clearDeletedSelectOption(field, val, name);
                      });
                  } else if (field.id === 'transporter_id' || field.id === 'reference_by' || name === 'transporter_id' || name === 'reference_by') {
                    fetch('/api/transporters/' + encodeURIComponent(val))
                      .then(function (r) {
                        if (!r.ok) throw new Error('Not found');
                        return r.json();
                      })
                      .then(function (d) {
                        const tName = String(d ? (d.name ?? d.transporterMaster?.name ?? d.transporter_master?.name ?? '') : '').trim();
                        if (tName) {
                          const shortName = String(d.short_name ?? '').trim();
                          const fullLabel = shortName ? `${tName} (${shortName})` : tName;
                          try { sessionStorage.setItem('tb_label_' + val, fullLabel); } catch (e) {}
                          updateSelect2Option(field, val, fullLabel, attrs);
                          if (!cachedOptLabel) {
                            field.value = val;
                            appliedCount++;
                            actuallyChangedCount++;
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                            if (window.jQuery) {
                              window.jQuery(field).trigger('change');
                            }
                          }
                        } else {
                          try { sessionStorage.removeItem('tb_label_' + val); } catch (e) {}
                          clearDeletedSelectOption(field, val, name);
                        }
                      })
                      .catch(function () {
                        try { sessionStorage.removeItem('tb_label_' + val); } catch (e) {}
                        clearDeletedSelectOption(field, val, name);
                      });
                  }
                } else if (field.id === 'state_id' || field.id === 'city_id' || name === 'state_id' || name === 'city_id') {
                  // Cascading location dropdowns: save target value for cascade loader without wiping
                  field.dataset.restoreVal = val;
                  appliedCount++;
                  if (!isBoilerplateField(name) && String(val).length > 0) {
                    actuallyChangedCount++;
                  }
                } else {
                  // Non-AJAX static dropdowns: only restore if option genuinely exists in master select
                  let opt = field.querySelector('option[value="' + CSS.escape(val) + '"]');
                  if (!opt && (field.id === 'good_type' || name === 'good_type' || field.id === 'truck_type' || name === 'truck_type')) {
                    opt = Array.from(field.options).find(function (o) {
                      return (o.getAttribute('data-id') && String(o.getAttribute('data-id')).trim() === String(val).trim()) || o.value === String(val).trim();
                    });
                  }
                  if (opt) {
                    opt.selected = true;
                    field.value = opt.value;
                    appliedCount++;
                    const oldVal = (initialSnapshot && initialSnapshot[name] !== undefined) ? initialSnapshot[name] : '';
                    if (!isBoilerplateField(name) && opt.value !== oldVal && opt.value.length > 0 && opt.value !== '0' && opt.value !== '0.00') {
                      actuallyChangedCount++;
                    }
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    if (window.jQuery) {
                      window.jQuery(field).trigger('change');
                    }
                  } else {
                    // Option does not exist in master list; clear it!
                    clearDeletedSelectOption(field, val, name);
                  }
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

      // Special handling for cascading location dropdowns (country_id -> state_id -> city_id)
      const countryEl = form.querySelector('#country_id') || form.querySelector('select[name="country_id"]');
      const stateEl = form.querySelector('#state_id') || form.querySelector('select[name="state_id"]');
      const cityEl = form.querySelector('#city_id') || form.querySelector('select[name="city_id"]');

      if (countryEl && stateEl) {
        const targetCountry = data.country_id !== undefined ? (typeof data.country_id === 'object' ? data.country_id.value : data.country_id) : countryEl.value;
        const targetState = data.state_id !== undefined ? (typeof data.state_id === 'object' ? data.state_id.value : data.state_id) : (stateEl.dataset.restoreVal || '');
        const targetCity = data.city_id !== undefined ? (typeof data.city_id === 'object' ? data.city_id.value : data.city_id) : (cityEl ? (cityEl.dataset.restoreVal || '') : '');

        if (targetCountry) {
          countryEl.value = targetCountry;

          if (data._labels) {
            delete data._labels;
          }

          // Optimistic instant render (0ms): display saved State and City labels from sessionStorage without waiting for network
          const targetStateLabel = targetState ? (sessionStorage.getItem('tb_label_' + targetState) || '') : '';
          if (targetState && targetStateLabel && !stateEl.querySelector(`option[value="${CSS.escape(targetState)}"]`)) {
            stateEl.innerHTML = '';
            stateEl.appendChild(new Option('Select State', ''));
            stateEl.appendChild(new Option(targetStateLabel, targetState, true, true));
            stateEl.value = targetState;
          }

          const targetCityLabel = targetCity ? (sessionStorage.getItem('tb_label_' + targetCity) || '') : '';
          if (cityEl && targetCity && targetCityLabel && !cityEl.querySelector(`option[value="${CSS.escape(targetCity)}"]`)) {
            cityEl.innerHTML = '';
            cityEl.appendChild(new Option('Select City', ''));
            cityEl.appendChild(new Option(targetCityLabel, targetCity, true, true));
            cityEl.value = targetCity;
          }

          if (typeof window.DynamicDropdowns?.loadCascade === 'function') {
            window.DynamicDropdowns.loadCascade(targetCountry, targetState, targetCity);
          } else {
            fetch('/api/states/' + encodeURIComponent(targetCountry), {
              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
              .then(function (r) { return r.ok ? r.json() : []; })
              .then(function (states) {
                stateEl.innerHTML = '<option value="">Select State</option>';
                states.forEach(function (s) {
                  const opt = document.createElement('option');
                  opt.value = s.id;
                  opt.textContent = s.name;
                  stateEl.appendChild(opt);
                });
                if (targetState) {
                  const sOpt = stateEl.querySelector('option[value="' + CSS.escape(targetState) + '"]');
                  if (sOpt) {
                    stateEl.value = targetState;
                    delete stateEl.dataset.restoreVal;
                    stateEl.dispatchEvent(new Event('input', { bubbles: true }));
                    stateEl.dispatchEvent(new Event('change', { bubbles: true }));
                    if (window.jQuery) window.jQuery(stateEl).trigger('change');

                    if (cityEl && targetCity) {
                      fetch('/api/cities/' + encodeURIComponent(targetState), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                      })
                        .then(function (cr) { return cr.ok ? cr.json() : []; })
                        .then(function (cities) {
                          cityEl.innerHTML = '<option value="">Select City</option>';
                          cities.forEach(function (c) {
                            const cOpt = document.createElement('option');
                            cOpt.value = c.id;
                            cOpt.textContent = c.name;
                            cityEl.appendChild(cOpt);
                          });
                          const matchedCityOpt = cityEl.querySelector('option[value="' + CSS.escape(targetCity) + '"]');
                          if (matchedCityOpt) {
                            cityEl.value = targetCity;
                            delete cityEl.dataset.restoreVal;
                            cityEl.dispatchEvent(new Event('input', { bubbles: true }));
                            cityEl.dispatchEvent(new Event('change', { bubbles: true }));
                            if (window.jQuery) window.jQuery(cityEl).trigger('change');
                          }
                        })
                        .catch(function () {});
                    }
                  }
                }
              })
              .catch(function () {});
          }
        }
      }

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

            validLrs.forEach(function (prefill, idx) {
              if (typeof window.addLrItem === 'function') {
                window.addLrItem(prefill);
                const cards = container.querySelectorAll('.lr-item');
                const card = cards[idx];
                if (card) {
                  const clearLinkedLrCardSelect = function (card, selectCls, id, isConsignor, isConsignee) {
                    try {
                      sessionStorage.removeItem('tb_label_' + id);
                      sessionStorage.removeItem('tb_consignee_data_' + id);
                    } catch (e) {}
                    const sel = card.querySelector('.' + selectCls);
                    if (sel) {
                      if (window.jQuery && window.jQuery(sel).hasClass('select2-hidden-accessible')) {
                        const $s = window.jQuery(sel);
                        $s.find('option[value="' + CSS.escape(id) + '"]').remove();
                        $s.val(null).trigger('change.select2').trigger('change');
                      } else {
                        let opt = sel.querySelector('option[value="' + CSS.escape(id) + '"]');
                        if (opt) opt.remove();
                        sel.value = '';
                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                      }
                    }
                    if (isConsignor) {
                      const cInput = card.querySelector('.consignor-contact');
                      if (cInput) cInput.value = '';
                      const cText = card.querySelector('.consignor-contact-text');
                      if (cText) cText.textContent = '';
                      const scInput = card.querySelector('.source-city');
                      if (scInput) scInput.value = '';
                      const scText = card.querySelector('.consignor-city-text');
                      if (scText) scText.textContent = '';
                      const ssInput = card.querySelector('.source-state');
                      if (ssInput) ssInput.value = '';
                      const ssText = card.querySelector('.consignor-state-text');
                      if (ssText) ssText.textContent = '';
                    }
                    if (isConsignee) {
                      const cInput = card.querySelector('.consignee-contact');
                      if (cInput) cInput.value = '';
                      const cText = card.querySelector('.consignee-contact-text');
                      if (cText) cText.textContent = '';
                      const dcInput = card.querySelector('.destination-city');
                      if (dcInput) dcInput.value = '';
                      const dcText = card.querySelector('.consignee-city-text');
                      if (dcText) dcText.textContent = '';
                      const dsInput = card.querySelector('.destination-state');
                      if (dsInput) dsInput.value = '';
                      const dsText = card.querySelector('.consignee-state-text');
                      if (dsText) dsText.textContent = '';
                    }
                    if (window.LinkedLrFormUI) {
                      window.LinkedLrFormUI.updateRoutePreview(card);
                      window.LinkedLrFormUI.updateItemMeta(card);
                    }
                    saveDraft(form, storageKey);
                  };

                  const fetchAndSetSelect = function (selectCls, id, isConsignor, isConsignee) {
                    if (!id) return;
                    const cachedLabel = sessionStorage.getItem('tb_label_' + id);
                    const cachedDataRaw = sessionStorage.getItem('tb_consignee_data_' + id);
                    let cachedData = null;
                    if (cachedDataRaw) {
                      try { cachedData = JSON.parse(cachedDataRaw); } catch (e) {}
                    }
                    if (cachedLabel) {
                      const sel = card.querySelector('.' + selectCls);
                      if (sel) {
                        if (window.jQuery && window.jQuery(sel).hasClass('select2-hidden-accessible')) {
                          const $s = window.jQuery(sel);
                          $s.find('option[value="' + CSS.escape(id) + '"]').remove();
                          $s.append(new Option(cachedLabel, id, true, true)).trigger('change.select2');
                        } else {
                          let opt = sel.querySelector('option[value="' + CSS.escape(id) + '"]');
                          if (opt) opt.text = cachedLabel;
                        }
                      }
                      if (cachedData) {
                        const cityVal = cachedData.city?.name || cachedData.city || '';
                        const stateVal = cachedData.state?.name || cachedData.state || '';
                        if (isConsignor) {
                          if (cachedData.contact_number) {
                            const cInput = card.querySelector('.consignor-contact');
                            if (cInput) cInput.value = cachedData.contact_number;
                            const cText = card.querySelector('.consignor-contact-text');
                            if (cText) cText.textContent = 'Contact Number: ' + cachedData.contact_number;
                          }
                          if (cityVal) {
                            const scInput = card.querySelector('.source-city');
                            if (scInput) scInput.value = cityVal;
                            const scText = card.querySelector('.consignor-city-text');
                            if (scText) scText.textContent = 'Source City: ' + cityVal;
                          }
                          if (stateVal) {
                            const ssInput = card.querySelector('.source-state');
                            if (ssInput) ssInput.value = stateVal;
                            const ssText = card.querySelector('.consignor-state-text');
                            if (ssText) ssText.textContent = 'Source State: ' + stateVal;
                          }
                        }
                        if (isConsignee) {
                          if (cachedData.contact_number) {
                            const cInput = card.querySelector('.consignee-contact');
                            if (cInput) cInput.value = cachedData.contact_number;
                            const cText = card.querySelector('.consignee-contact-text');
                            if (cText) cText.textContent = 'Contact Number: ' + cachedData.contact_number;
                          }
                          if (cityVal) {
                            const dcInput = card.querySelector('.destination-city');
                            if (dcInput) dcInput.value = cityVal;
                            const dcText = card.querySelector('.consignee-city-text');
                            if (dcText) dcText.textContent = 'Destination City: ' + cityVal;
                          }
                          if (stateVal) {
                            const dsInput = card.querySelector('.destination-state');
                            if (dsInput) dsInput.value = stateVal;
                            const dsText = card.querySelector('.consignee-state-text');
                            if (dsText) dsText.textContent = 'Destination State: ' + stateVal;
                          }
                        }
                        if (window.LinkedLrFormUI) {
                          window.LinkedLrFormUI.updateRoutePreview(card);
                          window.LinkedLrFormUI.updateItemMeta(card);
                        }
                      }
                    }

                    fetch('/api/consignees/' + encodeURIComponent(id))
                      .then(function (r) {
                        if (!r.ok) throw new Error('Not found');
                        return r.json();
                      })
                      .then(function (d) {
                        if (d && d.name && d.name !== 'Not provided') {
                          const label = d.city && d.city !== 'Not provided' ? `${d.name} (${d.city})` : d.name;
                          const sel = card.querySelector('.' + selectCls);
                          if (sel) {
                            if (window.jQuery && window.jQuery(sel).hasClass('select2-hidden-accessible')) {
                              const $s = window.jQuery(sel);
                              $s.find('option[value="' + CSS.escape(id) + '"]').remove();
                              $s.append(new Option(label, id, true, true)).trigger('change.select2');
                            } else {
                              let opt = sel.querySelector('option[value="' + CSS.escape(id) + '"]');
                              if (opt) opt.text = label;
                            }
                          }
                          const cityVal = d.city?.name || d.city || '';
                          const stateVal = d.state?.name || d.state || '';
                          if (isConsignor) {
                            if (d.contact_number) {
                              const cInput = card.querySelector('.consignor-contact');
                              if (cInput) cInput.value = d.contact_number;
                              const cText = card.querySelector('.consignor-contact-text');
                              if (cText) cText.textContent = 'Contact Number: ' + d.contact_number;
                            }
                            if (cityVal) {
                              const scInput = card.querySelector('.source-city');
                              if (scInput) scInput.value = cityVal;
                              const scText = card.querySelector('.consignor-city-text');
                              if (scText) scText.textContent = 'Source City: ' + cityVal;
                            }
                            if (stateVal) {
                              const ssInput = card.querySelector('.source-state');
                              if (ssInput) ssInput.value = stateVal;
                              const ssText = card.querySelector('.consignor-state-text');
                              if (ssText) ssText.textContent = 'Source State: ' + stateVal;
                            }
                          }
                          if (isConsignee) {
                            if (d.contact_number) {
                              const cInput = card.querySelector('.consignee-contact');
                              if (cInput) cInput.value = d.contact_number;
                              const cText = card.querySelector('.consignee-contact-text');
                              if (cText) cText.textContent = 'Contact Number: ' + d.contact_number;
                            }
                            if (cityVal) {
                              const dcInput = card.querySelector('.destination-city');
                              if (dcInput) dcInput.value = cityVal;
                              const dcText = card.querySelector('.consignee-city-text');
                              if (dcText) dcText.textContent = 'Destination City: ' + cityVal;
                            }
                            if (stateVal) {
                              const dsInput = card.querySelector('.destination-state');
                              if (dsInput) dsInput.value = stateVal;
                              const dsText = card.querySelector('.consignee-state-text');
                              if (dsText) dsText.textContent = 'Destination State: ' + stateVal;
                            }
                          }
                          if (window.LinkedLrFormUI) {
                            window.LinkedLrFormUI.updateRoutePreview(card);
                            window.LinkedLrFormUI.updateItemMeta(card);
                          }
                        } else {
                          clearLinkedLrCardSelect(card, selectCls, id, isConsignor, isConsignee);
                        }
                      })
                      .catch(function () {
                        clearLinkedLrCardSelect(card, selectCls, id, isConsignor, isConsignee);
                      });
                  };

                  if (prefill.customer_id) fetchAndSetSelect('customer-select', prefill.customer_id, false, false);
                  if (prefill.consignor_id) fetchAndSetSelect('consignor-select', prefill.consignor_id, true, false);
                  if (prefill.consignee_id) fetchAndSetSelect('consignee-select', prefill.consignee_id, false, true);

                  if (prefill.goods_type || prefill.good_type) {
                    const gt = String(prefill.goods_type || prefill.good_type).trim();
                    const gSel = card.querySelector('.goods-type');
                    if (gSel) {
                      const matchedOpt = Array.from(gSel.options).find(function (o) {
                        return (o.getAttribute('data-id') && String(o.getAttribute('data-id')).trim() === gt) || o.value === gt;
                      });
                      if (matchedOpt) {
                        gSel.value = matchedOpt.value;
                        if (window.jQuery && window.jQuery(gSel).hasClass('select2-hidden-accessible')) {
                          window.jQuery(gSel).val(matchedOpt.value).trigger('change');
                        }
                      }
                    }
                  }
                }
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

      // Ensure late-initializing Select2 instances and UI badges are fully synced
      setTimeout(function () {
        if (window.jQuery) {
          form.querySelectorAll('select.select2-hidden-accessible, #lr-items-container .select2-hidden-accessible').forEach(function (s2) {
            if (s2.value) {
              window.jQuery(s2).trigger('change.select2');
            }
          });
        }
        syncLrCardLabelsIfPresent();
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
      }, 50);

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
    initMasterDeleteDraftWatcher();
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
