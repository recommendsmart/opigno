/**
 * @file
 * Compare item JS Behavior.
 */

/* global ArchCompareStorage */
/* global ArchCompareSelectors */

(function (Drupal, $, drupalSettings, window) {
  'use strict';

  Drupal.behaviors.arch_compare_item = {
    attach: function (context) {
      var processedClass = 'compare-item-processed'
        , $body = $('body')
        ;

      if (!$body.hasClass(processedClass)) {
        $body.addClass(processedClass)
          .on('change', ArchCompareSelectors.itemInput(), function () {
            var checkbox = $(this)
              , $item = $(this).parents(ArchCompareSelectors.item())
              , pid = checkbox.data('pid')
            ;

            // Remove product from compare list.
            if (!checkbox.prop('checked')) {
              ArchCompareStorage.remove(pid);
            }
            // Add product to compare list.
            else if (
              ArchCompareStorage.has(pid)
              || ArchCompareStorage.isUnderLimit()
            ) {
              var item = {
                pid: pid,
                data: checkbox.data()
              };
              item.data.pid = pid;
              ArchCompareStorage.add(item);
            }
            // If adding to compare list is denied reset checked value.
            else {
              checkbox.prop('checked', false);
            }

            $item.trigger('productListChanged');
          });
      }

      var items = ArchCompareStorage.list();
      for (var i = 0, l = items.length; i < l; i++) {
        $(ArchCompareSelectors.itemInput()).filter('[data-pid="' + items[i].pid + '"]')
          .prop('checked', true)
          .trigger('change');
      }
    }
  };

})(Drupal, jQuery, drupalSettings, window);
