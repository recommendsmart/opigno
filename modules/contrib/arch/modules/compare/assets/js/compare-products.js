/**
 * @file
 * ARCH Compare products JS Behavior.
 */

(function (Drupal, drupalSettings, $, window) {
  'use strict';

  Drupal.behaviors.arch_compare_products = {
    attach: function (context) {
      var $filter = $('.compare-page-filter');
      if (!$filter.length) {
        return;
      }

      if ($filter.hasClass('compare-filter-processed')) {
        return;
      }

      $filter.addClass('compare-filter-processed');

      $filter
        .on('change', function () {
          if (this.value === 'all') {
            // Show everything.
            $('.compare-table .field-value-row').show();
          }
          else if (this.value === 'differences') {
            // Show differences only.
            $('.compare-table .field-value-row').hide();
            $('.compare-table .different-values').show();
          }
          else if (this.value === 'similarities') {
            // Show same values only.
            $('.compare-table .field-value-row').hide();
            $('.compare-table .same-values').show();
          }

          var zebra = 0;
          $('.compare-table .field-value-row:visible').each(function () {
            if ($(this).prev().hasClass('group-header-row')) {
              zebra = 0;
            }

            $(this)
              .removeClass('even')
              .removeClass('odd')
              .addClass((zebra++ % 2 === 0) ? 'even' : 'odd')
            ;
          });
        })
      ;
    }
  };

})(Drupal, drupalSettings, jQuery, window);
