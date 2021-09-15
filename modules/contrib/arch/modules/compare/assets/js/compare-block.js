/**
 * @file
 * Compare item JS Behavior.
 */

/* global ArchCompareStorage */
/* global ArchCompareSelectors */

(function (Drupal, $, drupalSettings, window) {
  'use strict';

  Drupal.behaviors.arch_compare_block = {
    attach: function (context) {
      if (!getItemTemplate()) {
        return;
      }
      var processedClass = 'compare-block-processed'
        , $body = $('body')
        ;
      if (!$body.hasClass(processedClass)) {
        $body.addClass(processedClass)
          .on('click', ArchCompareSelectors.itemRemove(), function () {
            var pid = $(this).data('pid');
            ArchCompareStorage.remove(pid);
            $(ArchCompareSelectors.itemInput()).filter('[data-pid="' + pid + '"]')
              .prop('checked', false)
              .trigger('change');
            updateProductList();
          })
          .on('click', ArchCompareSelectors.clearAll(), function () {
            ArchCompareStorage.clear();
            $(ArchCompareSelectors.itemInput()).prop('checked', false);
            updateBlockContent();
          })
          .on('productListChanged', ArchCompareSelectors.item(), function () {
            updateBlockContent();
          });
      }
      updateBlockContent();
    }
  };

  function getItemTemplate() {
    var tpl = $('.compare-block [name="compare-list-item"]');
    if (tpl) {
      return tpl.html();
    }
    return null;
  }

  function updateCompareUrl() {
    var link = $('.compare-block .compare-link');
    if (!link.length) {
      return;
    }

    var params = '';
    if (link.attr('href').indexOf('?') >= 0) {
      var clean_href = link.attr('href').split('?')[0];
      link.attr('href', clean_href);
    }
    var items = ArchCompareStorage.list();
    for (var i = 0, l = items.length; i < l; i++) {
      var item = items[i];
      params += (params.indexOf('?') < 0) ? '?' : '&';
      params += 'products[]=' + item.pid;
    }

    var new_href = link.attr('href') + params;
    link.attr('href', new_href);
  }

  function updateProductList() {
    var items = ArchCompareStorage.list()
      , content = ''
      , $body = $('body')
      , $block = $('.compare-block')
      ;
    for (var i = 0, l = items.length; i < l; i++) {
      content += renderProductListItem(items[i]);
    }

    $block.find('.product-list').empty().append(content);
    $block[items.length > 0 ? 'removeClass' : 'addClass']('compare-items--empty');
    $block.attr('compare-items', items.length);

    // Add/remove extra class to body.
    $body[items.length > 0 ? 'addClass' : 'removeClass']('has-compare-items');

    updateCompareUrl();
    $body.trigger('compareBlockUpdated');
  }

  function renderProductListItem(item) {
    var list_item = getItemTemplate();
    item.data['@pid'] = item.pid;

    for (var property in item.data) {
      if (item.data.hasOwnProperty(property)) {
        var regex = new RegExp('@' + property, 'g');
        list_item = list_item.replace(regex, item.data[property]);
      }
    }
    return list_item;
  }

  function handleLimit() {
    if (!ArchCompareStorage.isUnderLimit()) {
      $(ArchCompareSelectors.itemInput()).prop('disabled', true);
      var list = ArchCompareStorage.list();
      for (var i = 0, l = list.length; i < l; i++) {
        $(ArchCompareSelectors.itemInput())
          .filter('[data-pid="' + list[i].pid + '"]')
          .prop('disabled', false);
      }
    }
    else {
      $(ArchCompareSelectors.itemInput()).prop('disabled', false);
    }
  }

  function updateBlockContent() {
    updateProductList();
    handleLimit();
  }

})(Drupal, jQuery, drupalSettings, window);
