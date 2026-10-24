(function ($) {
  'use strict';

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

  function bindAdminDigitFields() {
    $(document).on(
      'input paste blur',
      '#adev-otp-test-phone, textarea[name*="[phone_blacklist]"], input[name*="[sender]"], input[name*="[body_id]"], input[name*="[otp_length]"], input[name*="[otp_ttl]"]',
      function () {
        var el = this;
        setTimeout(function () {
          var next = toLatinDigits(el.value);
          if (next !== el.value) {
            el.value = next;
          }
        }, 0);
      }
    );
  }

  function showGatewayPanels() {
    var selected = $('[data-adev-gateway-select]').val();
    $('.adev-otp-gateway-panel').each(function () {
      var id = $(this).data('gateway');
      $(this).prop('hidden', id !== selected);
    });
  }

  $(function () {
    bindAdminDigitFields();
    $('[data-adev-gateway-select]').on('change', showGatewayPanels);
    showGatewayPanels();

    $('#adev-otp-test-send').on('click', function () {
      var $btn = $(this);
      var $result = $('#adev-otp-test-result');
      var phone = toLatinDigits($('#adev-otp-test-phone').val());
      $('#adev-otp-test-phone').val(phone);

      if (!phone) {
        $result
          .prop('hidden', false)
          .removeClass('is-ok')
          .addClass('is-err')
          .text(adevOtpAdmin.i18n.error);
        return;
      }

      $btn.prop('disabled', true).text(adevOtpAdmin.i18n.sending);
      $result.prop('hidden', true);

      $.post(adevOtpAdmin.ajaxUrl, {
        action: 'adev_otp_test_sms',
        nonce: adevOtpAdmin.nonce,
        phone: phone,
      })
        .done(function (res) {
          var ok = res && res.success;
          var msg =
            (res && res.data && res.data.message) ||
            (ok ? adevOtpAdmin.i18n.success : adevOtpAdmin.i18n.error);
          $result
            .prop('hidden', false)
            .toggleClass('is-ok', !!ok)
            .toggleClass('is-err', !ok)
            .text(msg);
        })
        .fail(function () {
          $result
            .prop('hidden', false)
            .removeClass('is-ok')
            .addClass('is-err')
            .text(adevOtpAdmin.i18n.error);
        })
        .always(function () {
          $btn.prop('disabled', false).text(adevOtpAdmin.i18n.testSend || 'Send test SMS');
        });
    });

    $('#adev-otp-clear-limits').on('click', function () {
      var $btn = $(this);
      var $result = $('#adev-otp-test-result');
      $btn.prop('disabled', true);
      $.post(adevOtpAdmin.ajaxUrl, {
        action: 'adev_otp_clear_limits',
        nonce: adevOtpAdmin.nonce,
      })
        .done(function (res) {
          var ok = res && res.success;
          var msg =
            (res && res.data && res.data.message) ||
            (ok ? (adevOtpAdmin.i18n.done || 'Done.') : adevOtpAdmin.i18n.error);
          $result
            .prop('hidden', false)
            .toggleClass('is-ok', !!ok)
            .toggleClass('is-err', !ok)
            .text(msg);
        })
        .fail(function () {
          $result
            .prop('hidden', false)
            .removeClass('is-ok')
            .addClass('is-err')
            .text(adevOtpAdmin.i18n.error);
        })
        .always(function () {
          $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.adev-otp-copy-shortcode', function () {
      var $btn = $(this);
      var code = $btn.data('code') || '';
      var done = function () {
        var prev = $btn.text();
        $btn.addClass('is-copied').text(adevOtpAdmin.i18n.copied || 'Copied');
        setTimeout(function () {
          $btn.removeClass('is-copied').text(prev);
        }, 1500);
      };

      var promptLabel = adevOtpAdmin.i18n.copyPrompt || 'Copy shortcode:';
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(String(code)).then(done).catch(function () {
          window.prompt(promptLabel, String(code));
        });
      } else {
        window.prompt(promptLabel, String(code));
        done();
      }
    });
  });
})(jQuery);
