/**
 * @file
 * Compare selectors.
 */

var ArchCompareSelectors = ArchCompareSelectors || {};

(function (drupalSettings) {
  'use strict';

  ArchCompareSelectors.selectors = function getSelectors() {
    var selectors = (drupalSettings.arch_compare || {})
      , itemSelector = (selectors.compare_item || '.compare-item')
      , itemInputSelector = (selectors.compare_item_input || '.compare-item input')
      , itemRemoveSelector = (selectors.compare_item_remove || '.compare-block .compare-item-remove')
      , clearAllSelector = (selectors.compare_clear_all || '.compare-block .compare-list--clear-all')
    ;
    return {
      compare_item: itemSelector,
      compare_item_input: itemInputSelector,
      compare_item_remove: itemRemoveSelector,
      compare_clear_all: clearAllSelector
    };
  };

  ArchCompareSelectors.item = function itemSelector() {
    return ArchCompareSelectors.selectors().compare_item || '.compare-item';
  };
  ArchCompareSelectors.itemInput = function itemInputSelector() {
    return ArchCompareSelectors.selectors().compare_item_input || '.compare-item input';
  };
  ArchCompareSelectors.itemRemove = function itemInputSelector() {
    return ArchCompareSelectors.selectors().compare_item_remove || '.compare-block .compare-item-remove';
  };

  ArchCompareSelectors.clearAll = function clearAllSelector() {
    return ArchCompareSelectors.selectors().compare_clear_all || '.compare-block .compare-list--clear-all';
  };

})(drupalSettings);
