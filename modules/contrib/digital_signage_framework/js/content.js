(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.digital_signage_content = Drupal.digital_signage_content || {};

  Drupal.behaviors.digital_signage_content = {
    attach: function () {
      $('.fittext:not(.digital-signage-content-processed)')
        .addClass('digital-signage-content-processed')
        .each(function () {
          $(this).css('font-size', 10 * $(this).parent().width() / $(this).width());
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
