(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.digital_signage_timer = Drupal.digital_signage_timer || {};
  drupalSettings.digital_signage_timer = drupalSettings.digital_signage_timer || {};

  Drupal.behaviors.digital_signage_timer = {
    attach: function () {
      // Nothing to do yet.
    }
  };

  Drupal.digital_signage_timer.setInitialTimeout = function (seconds) {
    let timer = {
      promise: null,
      resolve: null,
      timeout: null,
    };
    timer.promise = new Promise(function (resolve) {
      timer.resolve = resolve;
    });
    Drupal.digital_signage_timer.resetTimeout(timer, seconds);
    return timer;
  };

  Drupal.digital_signage_timer.resetTimeout = function (timer, seconds) {
    if (timer.timeout) {
      clearTimeout(timer.timeout);
    }
    timer.timeout = setTimeout(function () {
      if (timer.promise) {
        timer.resolve();
      }
    }, seconds * 1000);
  };

})(jQuery, Drupal, drupalSettings);
