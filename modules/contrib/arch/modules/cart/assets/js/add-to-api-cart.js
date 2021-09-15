/**
 * @file
 * Mini-cart JS Behavior.
 */

/* global ArchApiCartRequest */

(function (Drupal, $, drupalSettings) {
  'use strict';

  /**
   * Checks the given value is numeric or not.
   *
   * @param {string|number|float} n
   *   Value that will be checked.
   * @return {boolean}
   *   Is numeric or not.
   */
  function isNumeric(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
  }

  Drupal.behaviors.arch_cart_add_to_api_cart = {
    attach: function (context) {
      var processedClass = 'add-to-api-cart--processed'
        , $body = $('body')
        ;
      if ($body.hasClass(processedClass)) {
        return;
      }

      var workingClass = 'add-to-api-cart--button--working';
      var workingClassBody = 'add-to-api-cart--button--working--on--page';
      $body
        .addClass(processedClass)
        .on('click', '.add-to-api-cart--button[data-enabled]:not(.' + workingClass + ')', function (ev) {
          var $btn = $(this);
          $btn.addClass(workingClass);
          $body.addClass(workingClassBody);

          if (
            !isNumeric($btn.attr('data-product-id'))
            || !isNumeric($btn.attr('data-quantity'))
          ) {
            ev.preventDefault();
            return false;
          }

          var data = {
            id: $btn.attr('data-product-id'),
            quantity: $btn.attr('data-quantity')
          };
          ArchApiCartRequest('add', drupalSettings.arch_api_cart.api.add, data)
            .always(function () {
              setTimeout(function () {
                $btn.removeClass(workingClass);
                $body.removeClass(workingClassBody);
              }, 1500);
            })
          ;
        })
      ;
    }
  };

})(Drupal, jQuery, drupalSettings);
