/**
 * @file
 * Mini-cart JS Behavior.
 */

/* global ArchApiCartRequest */

(function (Drupal, $, _, drupalSettings, window) {
  'use strict';

  var CART = {};
  var CART_CONTENT = '';

  function initable() {
    return (
      drupalSettings.arch_api_cart
      && drupalSettings.arch_api_cart.templates
      && drupalSettings.arch_api_cart.templates.cart
      && drupalSettings.arch_api_cart.templates.item
      && $(drupalSettings.arch_api_cart.templates.cart).length
      && $(drupalSettings.arch_api_cart.templates.item).length
    );
  }

  Drupal.behaviors.arch_cart_api_cart = {
    requests: [],

    attach: function (context) {
      // Prevent error when BigPipe is enabled.
      if (!initable()) {
        return;
      }

      // Prevent multiple init.
      var processedClass = 'api-cart--processed'
        , $body = $('body')
        ;
      if ($body.hasClass(processedClass)) {
        return;
      }

      var $mini_cart = $('#mini-cart-wrapper');
      var templates = getTemplates();

      var _self = this;

      $body.attr('data-minicart-status', 'hidden');
      $body.attr('cart-has-items', false);
      $body
        .addClass(processedClass)
        .on('arch_cart_api_cart_update arch_cart_api_cart_update_and_show', function (ev) {
          updateCart()
            .done(function (data, status, jqXHR) {
              CART = data.cart;
              if (drupalSettings.arch_api_cart.settings.click_event === 'open') {
                CART_CONTENT = renderCart(templates);
                $mini_cart.empty().append(CART_CONTENT);
              }

              $('.api-cart--link').attr('data-count', CART.products);
              if (drupalSettings.arch_api_cart.settings.show_cart_item_count) {
                $('.api-cart--link .item-count').html(templates.count({
                  count: CART.products
                }));
              }

              if (CART.products > 0) {
                $body.attr('cart-has-items', true);
              }
              else {
                $body.attr('cart-has-items', false);
              }

              if (ev.type === 'arch_cart_api_cart_update_and_show') {
                $body.trigger('arch_cart_api_cart_show');
              }
            });
        })
        .on('arch_cart_api_cart_show', function () {
          if (drupalSettings.arch_api_cart.settings.click_event === 'open') {
            toggleCart($mini_cart, 'show');
          }
        })
        .on('click', '.api-cart--link', function (ev) {
          if ($(this).attr('data-api-cart-disabled') === 'disabled') {
            ev.preventDefault();
          }
          else if (drupalSettings.arch_api_cart.settings.click_event === 'open') {
            ev.preventDefault();
            ev.stopPropagation();
            toggleCart($mini_cart);
          }
        })
        .on('change', ':input.mini-cart-item-quantity[data-type][data-id]', function (ev) {
          var $input = $(this);

          var data = {
            key: $input.attr('data-key'),
            type: $input.attr('data-type'),
            id: $input.attr('data-id'),
            quantity: parseFloat($input.val())
          };

          if (_self.requests[data.key]) {
            _self.requests[data.key].abort();
            delete _self.requests[data.key];
          }

          _self.requests[data.key] = ArchApiCartRequest(
            'quantity',
            data.quantity > 0
              ? drupalSettings.arch_api_cart.api.quantity
              : drupalSettings.arch_api_cart.api.remove,
            data,
            CART
          );
        })
        .on('click', '.mini-cart-item-remove[data-type][data-id]', function (ev) {
          var $btn = $(this);
          var workingClass = 'api-cart-item-quantity--working';
          $btn.addClass(workingClass);
          var data = {
            key: $btn.attr('data-key'),
            type: $btn.attr('data-type'),
            id: $btn.attr('data-id')
          };
          ArchApiCartRequest('remove', drupalSettings.arch_api_cart.api.remove, data, CART)
            .always(function () {
              setTimeout(function () {
                $btn.removeClass(workingClass);
              }, 1500);
            });
        })
        .on('arch_cart_api_do', function (ev, params) {
          runTasks(
            (params.tasks || []),
            (params.data || {})
          );
        })
        .trigger('arch_cart_api_cart_update')
      ;

      $(document).on('click', function (e) {
        if (
          $mini_cart.is(':visible')
          && $mini_cart.has(e.target).length === 0
          && !$mini_cart.is(e.target)
        ) {
          toggleCart($mini_cart, 'hide');
        }
      });

      $(window).on('keyup', function (ev) {
        if (ev.keyCode === 27) {
          toggleCart($mini_cart, 'hide');
        }
      });
    }
  };

  function getTemplates() {
    var cart_tpl = $(drupalSettings.arch_api_cart.templates.cart).html()
      , message_tpl = $(drupalSettings.arch_api_cart.templates.message).html()
      , item_tpl = $(drupalSettings.arch_api_cart.templates.item).html()
      , quantity_tpl = $(drupalSettings.arch_api_cart.templates.itemQuantity).html()
      , remove_tpl = $(drupalSettings.arch_api_cart.templates.itemRemove).html()
      , count_tpl = $(drupalSettings.arch_api_cart.templates.count).html()
    ;
    return {
      cart: _.template(cart_tpl),
      message: _.template(message_tpl),
      item: _.template(item_tpl),
      quantity: _.template(quantity_tpl),
      remove: _.template(remove_tpl),
      count: _.template(count_tpl)
    };
  }

  function updateCart() {
    var conf = {
      url: drupalSettings.arch_api_cart.api.cart,
      method: 'get',
      dataType: 'json',
      data: {
        theme: drupalSettings.arch_api_cart.settings.theme
      }
    };
    return $.ajax(conf)
      .fail(function (jqXHR, textStatus, errorThrown) {
        // @todo alter user about error.
        // eslint-disable-next-line no-console
        console.warn(textStatus, errorThrown, jqXHR);
      });
  }

  function renderCart(templates) {
    var allowModifyQuantity = false
      , allowRemove = false
      ;
    if (
      drupalSettings
      && drupalSettings.arch_api_cart
      && drupalSettings.arch_api_cart.settings
    ) {
      allowModifyQuantity = drupalSettings.arch_api_cart.settings.allow_modify_quantity || false;
      allowRemove = drupalSettings.arch_api_cart.settings.allow_remove || false;
    }

    var items = '';
    for (var ii = 0, li = CART.items.length; ii < li; ii++) {
      items += renderItem(CART.items[ii], templates, allowModifyQuantity, allowRemove);
    }

    var messages = '';
    for (var im = 0, lm = CART.messages.length; im < lm; im++) {
      messages += templates.message({message: CART.messages[im]});
    }

    return templates.cart({
      messages: messages,
      items: items,
      grand_total: CART.total
    });
  }

  function renderItem(item, templates, allow_modify, allow_remove) {
    item.formatted_quantity = item.quantity;
    item.remove = '';

    var line_item = item._line_item;
    if (allow_modify) {
      item.formatted_quantity = templates.quantity({
        quantity: line_item.quantity,
        input_max: ((typeof line_item.max_quantity === 'undefined') ? false : parseInt(line_item.max_quantity)),
        key: item._index,
        type: line_item.type,
        id: line_item.id
      });
    }
    if (allow_remove) {
      item.remove = templates.remove({
        key: item._index,
        type: line_item.type,
        id: line_item.id
      });
    }

    return templates.item(item);
  }

  function runTasks(tasks, data) {
    var $body = $('body');
    for (var i = 0, l = tasks.length; i < l; i++) {
      if (typeof tasks[i] === 'string') {
        if (tasks[i] === 'update_cart') {
          $body.trigger('arch_cart_api_cart_update', data);
        }
        else if (tasks[i] === 'show_cart') {
          $body.trigger('arch_cart_api_cart_show', data);
        }
      }
      else if (typeof tasks[i] === 'object') {
        if (tasks[i].message) {
          // @todo implement this.
        }
      }
    }
  }

  function toggleCart($mini_cart, action) {
    var $body = $('body');
    var status = $body.attr('data-minicart-status');
    if (
      action
      && (
        (action === 'show' && status === 'visible')
        || (action === 'hide' && status === 'hidden')
      )
    ) {
      return;
    }

    if (
      $body.attr('data-minicart-status') === 'visible'
      || action === 'hide'
    ) {
      $body.attr('data-minicart-status', 'hidden');
    }
    else if (drupalSettings.arch_api_cart.settings.click_event === 'open') {
      $body.attr('data-minicart-status', 'visible');
    }
  }

})(Drupal, jQuery, _, drupalSettings, window);
