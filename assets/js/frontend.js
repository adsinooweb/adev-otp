(function () {
  'use strict';

  if (typeof adevOtp === 'undefined') {
    return;
  }

  var DIGIT_MAP = {
    '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
    '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
    '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
  };

  function toLatinDigits(str) {
    if (!str) return '';
    return String(str).replace(/[۰-۹٠-٩]/g, function (ch) {
      return DIGIT_MAP[ch] || ch;
    });
  }

  function bindDigitConversion(root) {
    var scope = root || document;
    var nodes = scope.querySelectorAll(
      '[data-field="phone"], [data-field="code"], [data-adev-checkout-code], #billing_phone'
    );
    Array.prototype.forEach.call(nodes, function (el) {
      if (el._adevDigitsBound) return;
      el._adevDigitsBound = true;
      var convert = function () {
        var start = el.selectionStart;
        var end = el.selectionEnd;
        var next = toLatinDigits(el.value);
        if (next === el.value) return;
        el.value = next;
        if (typeof start === 'number' && el.setSelectionRange) {
          try {
            el.setSelectionRange(start, end);
          } catch (e) {}
        }
      };
      el.addEventListener('input', convert);
      el.addEventListener('paste', function () {
        setTimeout(convert, 0);
      });
      el.addEventListener('blur', convert);
    });
  }

  function qs(el, sel) {
    return el.querySelector(sel);
  }

  function qsa(el, sel) {
    return Array.prototype.slice.call(el.querySelectorAll(sel));
  }

  function post(action, data) {
    var body = new FormData();
    body.append('action', action);
    body.append('nonce', adevOtp.nonce);
    if (adevOtp.lang) {
      body.append('lang', adevOtp.lang);
    }
    Object.keys(data).forEach(function (k) {
      if (data[k] !== undefined && data[k] !== null) {
        body.append(k, toLatinDigits(String(data[k])));
      }
    });
    return fetch(adevOtp.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body,
    }).then(function (r) {
      return r.json();
    });
  }

  function showMsg(form, text, ok) {
    var msg = qs(form, '[data-msg]');
    if (!msg) return;
    msg.hidden = false;
    msg.textContent = text || '';
    msg.classList.toggle('is-ok', !!ok);
    msg.classList.toggle('is-err', !ok);
  }

  function startCooldown(btn, seconds) {
    var left = parseInt(seconds, 10);
    if (isNaN(left) || left < 1) {
      left = adevOtp.cooldown || 60;
    }
    if (btn._adevCdTimer) {
      clearTimeout(btn._adevCdTimer);
      btn._adevCdTimer = null;
    }
    btn.disabled = true;
    function tick() {
      if (left <= 0) {
        btn.disabled = false;
        btn.textContent = adevOtp.i18n.resend;
        btn._adevCdTimer = null;
        return;
      }
      btn.textContent = adevOtp.i18n.resendIn.replace('%d', String(left));
      left -= 1;
      btn._adevCdTimer = setTimeout(tick, 1000);
    }
    tick();
  }

  function getField(form, name) {
    var el = qs(form, '[data-field="' + name + '"]');
    return el ? toLatinDigits(el.value.trim()) : '';
  }

  function bindForm(form) {
    if (form._adevBound) return;
    form._adevBound = true;
    bindDigitConversion(form);

    var purpose = form.getAttribute('data-purpose') || 'login';
    var resendBtn = qs(form, '[data-action="resend"]');
    if (resendBtn && !resendBtn.textContent.trim()) {
      resendBtn.textContent = adevOtp.i18n.resend;
    }

    form.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-action]');
      if (!btn || !form.contains(btn)) return;
      var action = btn.getAttribute('data-action');

      if (action === 'send' || action === 'resend') {
        e.preventDefault();
        e.stopPropagation();

        if (action === 'resend' && btn.disabled) {
          return;
        }

        var phone = getField(form, 'phone');
        if (!phone) {
          showMsg(form, adevOtp.i18n.phoneReq, false);
          return;
        }
        var hp = qs(form, '.adev-otp-hp');
        var prevLabel = btn.textContent;
        btn.disabled = true;
        btn.textContent = adevOtp.i18n.sending;

        post(action === 'resend' ? 'adev_otp_resend' : 'adev_otp_send', {
          phone: phone,
          purpose: form.getAttribute('data-purpose') || purpose,
          adev_hp: hp ? hp.value : '',
        })
          .then(function (res) {
            if (res && res.success) {
              if (res.data && res.data.purpose) {
                purpose = res.data.purpose;
                form.setAttribute('data-purpose', purpose);
              }
              showMsg(form, (res.data && res.data.message) || '', true);
              var phoneStep = qs(form, '[data-step="phone"]');
              var codeStep = qs(form, '[data-step="code"]');
              if (phoneStep) phoneStep.hidden = true;
              if (codeStep) codeStep.hidden = false;
              var cool =
                (res.data && res.data.cooldown) || adevOtp.cooldown || 60;
              var target = qs(form, '[data-action="resend"]') || btn;
              startCooldown(target, cool);
              if (action === 'send') {
                btn.disabled = false;
                btn.textContent = prevLabel;
              }
            } else {
              var errMsg =
                (res && res.data && res.data.message) || adevOtp.i18n.error;
              showMsg(form, errMsg, false);

              var targetFail = qs(form, '[data-action="resend"]');
              var remain =
                res && res.data && res.data.cooldown
                  ? parseInt(res.data.cooldown, 10)
                  : 0;
              if ((!remain || remain < 1) && /صبر کنید/.test(errMsg)) {
                var match = String(errMsg).match(/(\d+)/);
                remain = match ? parseInt(match[1], 10) : 0;
              }
              if (remain > 0 && targetFail) {
                startCooldown(targetFail, remain);
              } else if (action === 'resend' && targetFail) {
                targetFail.disabled = false;
                targetFail.textContent = adevOtp.i18n.resend;
              } else if (action === 'send') {
                btn.disabled = false;
                btn.textContent = prevLabel;
              }
            }
          })
          .catch(function () {
            showMsg(form, adevOtp.i18n.error, false);
            if (action === 'resend') {
              var rb = qs(form, '[data-action="resend"]');
              if (rb) {
                rb.disabled = false;
                rb.textContent = adevOtp.i18n.resend;
              }
            } else {
              btn.disabled = false;
              btn.textContent = prevLabel;
            }
          });
      }

      if (action === 'verify') {
        e.preventDefault();
        var code = getField(form, 'code');
        if (!code) {
          showMsg(form, adevOtp.i18n.codeReq, false);
          return;
        }
        var hp2 = qs(form, '.adev-otp-hp');
        var redirectWrap =
          form.closest('[data-redirect]') ||
          form.closest('[data-adev-otp-checkout-login]');
        var redirectTo = redirectWrap
          ? redirectWrap.getAttribute('data-redirect') || ''
          : '';
        btn.disabled = true;
        var prev2 = btn.textContent;
        btn.textContent = adevOtp.i18n.verifying;

        post('adev_otp_verify', {
          phone: getField(form, 'phone'),
          code: code,
          purpose: form.getAttribute('data-purpose') || purpose,
          password: getField(form, 'password'),
          name: getField(form, 'name'),
          email: getField(form, 'email'),
          redirect_to: redirectTo,
          adev_hp: hp2 ? hp2.value : '',
        })
          .then(function (res) {
            if (res && res.success) {
              showMsg(form, (res.data && res.data.message) || '', true);
              var go =
                (res.data && res.data.redirect) ||
                redirectTo ||
                '';
              if (go) {
                window.location.href = go;
              }
            } else {
              showMsg(
                form,
                (res && res.data && res.data.message) || adevOtp.i18n.error,
                false
              );
            }
          })
          .catch(function () {
            showMsg(form, adevOtp.i18n.error, false);
          })
          .finally(function () {
            btn.disabled = false;
            btn.textContent = prev2;
          });
      }
    });
  }

  function switchAuthTab(root, tab) {
    if (!root || !tab) return;
    qsa(root, '[data-tab]').forEach(function (t) {
      var on = t.getAttribute('data-tab') === tab;
      t.classList.toggle('is-active', on);
      if (t.hasAttribute('aria-selected')) {
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      }
    });
    qsa(root, '[data-panel]').forEach(function (p) {
      var on = p.getAttribute('data-panel') === tab;
      p.hidden = !on;
      p.classList.toggle('is-active', on);
    });
  }

  function openModal(tab) {
    var modal = document.querySelector('[data-adev-otp-modal]');
    if (!modal) return;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    if (tab) switchAuthTab(modal, tab);
  }

  function closeModal() {
    var modal = document.querySelector('[data-adev-otp-modal]');
    if (!modal) return;
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  function bindModal() {
    document.addEventListener('click', function (e) {
      var openBtn = e.target.closest('[data-adev-otp-open]');
      if (openBtn) {
        e.preventDefault();
        var prefer = openBtn.getAttribute('data-adev-otp-open') || 'login';
        // Prefer inline tabs on the same page (e.g. My Account) over modal.
        var inline = document.querySelector('[data-adev-otp-tabs]');
        if (inline && !openBtn.closest('[data-adev-otp-modal]')) {
          switchAuthTab(inline, prefer === '1' || prefer === '' ? 'register' : prefer);
          inline.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          return;
        }
        openModal(prefer === '1' || prefer === '' ? 'login' : prefer);
        return;
      }
      var closeBtn = e.target.closest('[data-adev-otp-close]');
      if (closeBtn) {
        e.preventDefault();
        closeModal();
        return;
      }
      var tabBtn = e.target.closest('[data-tab]');
      if (tabBtn) {
        var tabsRoot =
          tabBtn.closest('[data-adev-otp-tabs]') ||
          tabBtn.closest('[data-adev-otp-modal]');
        if (tabsRoot) {
          e.preventDefault();
          switchAuthTab(tabsRoot, tabBtn.getAttribute('data-tab'));
        }
      }
      var switchBtn = e.target.closest('[data-adev-switch]');
      if (switchBtn) {
        e.preventDefault();
        var which = switchBtn.getAttribute('data-adev-switch');
        var wrap = document.querySelector('.adev-otp-wp-login');
        if (!wrap) return;
        var login = wrap.querySelector('[data-purpose="login"]');
        var reg = wrap.querySelector('.adev-otp-wp-login__register');
        var forgot = wrap.querySelector('.adev-otp-wp-login__forgot');
        if (login) login.hidden = which !== 'login';
        if (reg) reg.hidden = which !== 'register';
        if (forgot) forgot.hidden = which !== 'forgot';
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });
  }

  function bindCheckout() {
    var box = document.querySelector('[data-adev-otp-checkout]');
    if (!box) return;

    var sendBtn = qs(box, '[data-adev-checkout-send]');
    var verifyBtn = qs(box, '[data-adev-checkout-verify]');
    var codeRow = qs(box, '.adev-otp-checkout__code-row');
    var codeInput = qs(box, '[data-adev-checkout-code]');
    var flag = qs(box, '[data-adev-checkout-flag]');
    var msg = qs(box, '[data-adev-checkout-msg]');

    function setMsg(text, ok) {
      if (!msg) return;
      msg.hidden = false;
      msg.textContent = text;
      msg.classList.toggle('is-ok', !!ok);
      msg.classList.toggle('is-err', !ok);
    }

    function billingPhone() {
      var input =
        document.getElementById('billing_phone') ||
        document.querySelector('[name="billing_phone"]');
      return input ? input.value.trim() : '';
    }

    if (sendBtn) {
      sendBtn.addEventListener('click', function () {
        var phone = billingPhone();
        if (!phone) {
          setMsg(adevOtp.i18n.phoneReq, false);
          return;
        }
        sendBtn.disabled = true;
        post('adev_otp_send', { phone: phone, purpose: 'checkout', adev_hp: '' })
          .then(function (res) {
            if (res && res.success) {
              setMsg((res.data && res.data.message) || '', true);
              if (codeRow) codeRow.hidden = false;
            } else {
              setMsg(
                (res && res.data && res.data.message) || adevOtp.i18n.error,
                false
              );
            }
          })
          .catch(function () {
            setMsg(adevOtp.i18n.error, false);
          })
          .finally(function () {
            sendBtn.disabled = false;
          });
      });
    }

    if (verifyBtn) {
      verifyBtn.addEventListener('click', function () {
        var phone = billingPhone();
        var code = codeInput ? codeInput.value.trim() : '';
        if (!code) {
          setMsg(adevOtp.i18n.codeReq, false);
          return;
        }
        verifyBtn.disabled = true;
        post('adev_otp_verify', {
          phone: phone,
          code: code,
          purpose: 'checkout',
          adev_hp: '',
        })
          .then(function (res) {
            if (res && res.success) {
              setMsg((res.data && res.data.message) || '', true);
              if (flag) flag.value = '1';
            } else {
              setMsg(
                (res && res.data && res.data.message) || adevOtp.i18n.error,
                false
              );
            }
          })
          .catch(function () {
            setMsg(adevOtp.i18n.error, false);
          })
          .finally(function () {
            verifyBtn.disabled = false;
          });
      });
    }
  }

  function init() {
    qsa(document, '[data-adev-otp-form]').forEach(bindForm);
    bindDigitConversion(document);
    bindModal();
    bindCheckout();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
