/**
 * @file
 * Checkout Onepage JS Behavior.
 */

(function (Drupal, drupalSettings, $) {
  'use strict';

  var $form = $('form.arch-onepage-checkout');
  var $fieldsets = $form.find('> .panel, > fieldset');
  var $fieldsetTitles = $fieldsets.find('.panel-title, .fieldset-legend');
  var panelPadding = $fieldsets.find('.panel-body, .fieldset-wrapper').css('padding');
  var $billingInputs = $fieldsets.filter('.billing-info').find(':input');
  // var $shippingInputs = $fieldsets.filter('.shipping-info').find(':input');
  var $sameas = null;
  var phases = 3;
  var phase = 1;
  var dataFields = [
    'firstname',
    'lastname',
    'company',
    'address',
    'address2',
    'city',
    'country',
    'postcode'
  ];
  var focusable = [
    'a[href]',
    'area[href]',
    'button',
    'details',
    'input',
    'iframe',
    'select',
    'textarea',

    '[contentEditable=""]',
    '[contentEditable="true"]',
    '[contentEditable="TRUE"]',

    '[tabindex]:not([tabindex^="-"])',
    ':not([disabled])'
  ];

  function jumpToPhase(phase_number) {
    phase_number--;

    $fieldsets.find('.panel-body, .fieldset-wrapper').css({
      height: 0,
      padding: 0,
      overflow: 'hidden'
    });

    $fieldsetTitles
      .find('a')
      .hide()
      .each(function (index) {
        if (index >= phase) {
          return;
        }

        $(this).css('display', 'inline-block');
      });

    $fieldsets.eq(phase_number).find('.panel-title a, .fieldset-legend a').hide();
    $fieldsets.eq(phase_number).find('.panel-body, .fieldset-wrapper').css({
      height: 'auto',
      padding: panelPadding,
      overflow: 'visible'
    });

    $fieldsets
      .addClass('section-hidden')
      .each(function (idx) {
        if (idx <= phase_number) {
          $(this).removeClass('section-hidden');
        }
      })
    ;

    if (phase !== phases) {
      $form.find('.form-actions .form-submit').attr('disabled', 'disabled');
    }
    else {
      $form.find('.form-actions .form-submit').removeAttr('disabled');
    }
  }

  function updatePreview() {
    var $previewShippingAddress2 = $('#preview-shipping-address2');
    var $previewShippingCity = $('#preview-shipping-city');
    var $previewShippingSameas = $('#preview-shipping-sameas');

    // Billing Info preview data.
    $('#preview-billing-email').text($('[data-field="email"]').val());
    $.each(dataFields, function (key, item) {
      $('#preview-billing-' + item).text($('[data-field="' + item + '"]').val());
    });
    $previewShippingAddress2.toggle(!($('[data-field="address2"]').val() === ''));
    if ($previewShippingAddress2.text() !== '') {
      $previewShippingAddress2.text(', ' + $previewShippingAddress2.text());
    }

    if ($previewShippingCity.text() !== '') {
      $previewShippingCity.text(', ' + $previewShippingCity.text());
    }

    // Shipping Info preview data.
    $previewShippingSameas.addClass('hidden');
    $.each(dataFields, function (key, item) {
      $('#preview-shipping-' + item).hide();
    });

    $('#preview-shipping-method').text($('input[data-field="shipping-method"]:checked').closest('.form-item').find('label').text());

    var $shipping_address_selector = $('input[name="shipping_address_selector"]');
    switch ($shipping_address_selector.filter(':checked').val()) {
      case 'new_shipping':
        $.each(dataFields, function (key, item) {
          $('#preview-shipping-' + item).text($('[data-field="shipping-' + item + '"]').val());
        });
        $previewShippingAddress2.toggle(!($('[data-field="shipping-address2"]').val() === ''));
        if ($previewShippingAddress2.text() !== '') {
          $previewShippingAddress2.text(', ' + $previewShippingAddress2.text());
        }

        if ($previewShippingCity.text() !== '') {
          $previewShippingCity.text(', ' + $previewShippingCity.text());
        }

        if ($('[data-field="shipping-sameas"]').is(':checked')) {
          $previewShippingSameas.removeClass('hidden');
          $.each(dataFields, function (key, item) {
            $('#preview-shipping-' + item).hide();
          });
        }
        else {
          $previewShippingSameas.addClass('hidden');
          $.each(dataFields, function (key, item) {
            $('#preview-shipping-' + item).show();
          });
        }

        break;

      case 'sameas':
        $previewShippingSameas.removeClass('hidden');
        $.each(dataFields, function (key, item) {
          $('#preview-shipping-' + item).hide();
        });
        break;

      case 'choose_address':
        var $select = $('select[name="choose_address"]');
        $('#preview-shipping-company').text($select.find('option:selected').text()).show();
        break;
    }
  }

  function appendEditSectionButtons() {
    // Append Edit button to each fieldset.
    $('<a class="edit-section">' + Drupal.t('Edit') + '</a>')
      .appendTo($fieldsetTitles.filter(':not(:has(.edit-section))'))
      .on('click', function (e) {
        $fieldsetTitles.find('a').each(function (index, el) {
          if (el === e.target) {
            $(e.target).closest('.panel-default, .checkout-fieldset').nextAll('.section-preview').addClass('hidden');

            phase = index + 1;
            jumpToPhase(phase);
          }
        });
      })
    ;
  }

  function legacyInputValidation(field) {
    var valid = true;
    var val = field.value;
    var type = field.getAttribute('type');
    var chkbox = (type === 'checkbox' || type === 'radio');
    var required = field.getAttribute('required');
    var minlength = field.getAttribute('minlength');
    var maxlength = field.getAttribute('maxlength');
    var pattern = field.getAttribute('pattern');

    // disabled fields should not be validated
    if (field.disabled) {
      return valid;
    }

    // value required?
    valid = valid
      && (
        !required
        || (chkbox && field.checked)
        || (!chkbox && val !== '')
      );

    // minlength or maxlength set?
    valid = valid && (chkbox || (
      (!minlength || val.length >= minlength) &&
      (!maxlength || val.length <= maxlength)
    ));

    // test pattern
    if (valid && pattern) {
      pattern = new RegExp(pattern);
      valid = pattern.test(val);
    }

    return valid;
  }

  function validateSection(section) {
    var $section = $(section);
    if (!$section) {
      return false;
    }

    var valid = true;
    var $field = null;
    $section.find(':input:visible').each(function () {
      if (!valid) {
        return;
      }

      if (typeof $(this)[0].willValidate !== 'undefined') {
        valid = $(this)[0].checkValidity();
      }
      else {
        valid = legacyInputValidation($(this)[0]);
      }

      if (!valid) {
        $field = $(this);
      }
    });

    if ($field) {
      setTimeout(function () {
        $('html, body')
          .stop()
          .animate({
            scrollTop: ($field.offset().top - 130)
          }, 500, 'swing', function () {
            // Check that field is focusable and browser supports HTML5 validation.
            if (
              $field.is(focusable.join(', '))
              && !$field.is('input[type="checkbox"]')
              && !$field.is('input[type="radio"]')
            ) {
              $field.focus();

              if (typeof $field[0].reportValidity !== 'undefined') {
                $field[0].reportValidity();
              }
            }

            $field.closest('.form-item').addClass('has-error');
            $field.addClass('error');
            setTimeout(function () {
              $field.closest('.form-item').removeClass('has-error');
              $field.removeClass('error');
            }, 5000);
          });
      }, 1);
    }

    return valid;
  }

  Drupal.behaviors.arch_checkout_onepage = {
    attach: function () {
      var $body = $('body');
      if ($body.hasClass('js-onepage-checkout-processed')) {
        return;
      }

      $body
        .addClass('js-onepage-checkout-processed')
        .on('checkout.onepage.phaseRecheck', function () {
          $form = $('form.arch-onepage-checkout');
          $fieldsets = $form.find('> .panel, > fieldset');
          $fieldsetTitles = $fieldsets.find('.panel-title, .fieldset-legend');
          panelPadding = $fieldsets.find('.panel-body, .fieldset-wrapper').css('padding');
          $billingInputs = $fieldsets.filter('.billing-info').find(':input');
          // $shippingInputs = $fieldsets.filter('.shipping-info').find(':input');

          appendEditSectionButtons();
          jumpToPhase(3);
          updatePreview();
          $('.section-preview').removeClass('hidden');
        })
      ;

      appendEditSectionButtons();

      // Jump to next phase from Billing to Shipping.
      $('#btn-next-to-shipping')
        .on('click', function (e) {
          // It must prevent to trigger validation functionality on just hidden form section.
          e.preventDefault();

          var sectionClass = '.checkout-billing-info';
          if (!validateSection(sectionClass)) {
            return false;
          }

          updatePreview();
          $('#preview-billing-infos').removeClass('hidden');
          $(sectionClass).removeClass('section-hidden');

          phase = 2;
          jumpToPhase(phase);

          $billingInputs.trigger('blur');

          $('html, body')
            .stop()
            .animate({
              scrollTop: ($('.checkout-shipping-info').offset().top - 50)
            }, 250, 'swing');
        })
      ;

      // Jump to next phase from Shipping to Payment.
      $('#btn-next-to-payment')
        .on('click', function (e) {
          // It must prevent to trigger validation functionality on just hidden form section.
          e.preventDefault();

          var sectionClass = '.checkout-shipping-info';
          if (!validateSection(sectionClass)) {
            return false;
          }

          updatePreview();
          $('#preview-shipping-infos').removeClass('hidden');
          $(sectionClass).removeClass('section-hidden');

          phase = 3;
          jumpToPhase(phase);

          $('html, body')
            .stop()
            .animate({
              scrollTop: ($('.checkout-payment-method').offset().top - 50)
            }, 250, 'swing');
        })
      ;

      // Handling shipping address if it is the same as billing.
      $billingInputs
        .on('keyup paste blur', function () {
          if ($sameas === null) {
            $('.form-item-shipping-address-selector')
              .first()
              .find('label')
              .append('<div class="shipping-sameas-clone"></div>');

            $sameas = $('.shipping-sameas-clone');
          }

          $sameas.html($('.preview-billing-data').html());
        })
      ;

      $form.find('.form-actions .form-submit')
        .on('click', function (e) {
          if (!validateSection('.checkout-payment-method')) {
            e.preventDefault();
            return false;
          }
          if (!validateSection('.form-item-accept')) {
            e.preventDefault();
            return false;
          }
        });

      // Init.
      jumpToPhase(1);

      $('input[name="shipping_address_selector"]')
        .on('change', function (e) {
          $('.form-item-shipping-address-selector > label').removeClass('selected');
          $(e.target).closest('.form-item').find('label').addClass('selected');
        })
        .filter('[checked]')
        .trigger('change')
      ;

      $('input[name="shipping_methods"]')
        .on('change', function (e) {
          if ($(e.target).val() !== 'standard') {
            var $items = $('input[name="shipping_address_selector"]');

            $items.closest('.form-item').find('label').removeClass('selected');

            $items.filter('[value="sameas"]')
              .prop('checked', true)
              .closest('label')
              .addClass('selected')
            ;

            $('.shipping-new-address :input').removeAttr('required').removeAttr('aria-required');
          }
        })
      ;
    }
  };

})(Drupal, drupalSettings, jQuery);
