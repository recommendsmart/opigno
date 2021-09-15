/**
 * @file
 * Mini-cart JS Behavior.
 */

var ArchApiCartRequest;

(function ($, drupalSettings) {
  'use strict';

  if (ArchApiCartRequest) {
    return;
  }

  ArchApiCartRequest = function requestPromise(action, url, data, cartBeforeChange, responseDataTransform) {
    data.theme = drupalSettings.arch_api_cart.settings.theme;
    var conf = {
      url: url,
      method: 'post',
      dataType: 'json',
      data: data
    };

    return $.ajax(conf)
      .fail(function (jqXHR, textStatus, errorThrown) {
        // @todo alter user about error.
        // eslint-disable-next-line no-console
        console.warn(textStatus, errorThrown, jqXHR);
      })
      .done(function (cartData, status, jqXHR) {
        if (typeof responseDataTransform === 'function') {
          responseDataTransform(cartData);
        }
        $('body').trigger('arch_cart_api_do', {
          tasks: cartData.do || [],
          data: cartData
        });
        $('body').trigger('arch_cart_api_success', {
          action: action,
          data: data,
          cartData: cartData,
          cartBeforeChange: cartBeforeChange
        });
      });
  };

})(jQuery, drupalSettings);
