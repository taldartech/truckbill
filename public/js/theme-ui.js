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

  function boot() {
    ensureSwalDefaults();
    tagModals();
    interceptNativeConfirms();
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
