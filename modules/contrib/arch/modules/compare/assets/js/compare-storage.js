
var ArchCompareStorage = {};

(function (drupalSettings) {
  'use strict';

  var limit = ((drupalSettings.arch_compare || {}).limit || 0)
    , max_age = ((drupalSettings.arch_compare || {}).max_age || 0)
    , storage = localStorage
    ;

  ArchCompareStorage.remove = function (pid) {
    var items = ArchCompareStorage.list().filter(function (item) {
      return item.pid !== pid;
    });
    ArchCompareStorage.save(items);
  };

  ArchCompareStorage.clear = function () {
    ArchCompareStorage.save([]);
  };

  ArchCompareStorage.save = function (items) {
    if (
      !storage
      || (typeof storage.setItem) !== 'function'
    ) {
      return;
    }
    storage.setItem('arch_compare_items', JSON.stringify(items));
    storage.setItem('arch_compare_item_last_saved', currentTimestamp());
  };

  ArchCompareStorage.has = function (pid) {
    var items = ArchCompareStorage.list();
    for (var i = 0, l = items.length; i < l; i++) {
      if (items[i].pid && items[i].pid === pid) {
        return true;
      }
    }
    return false;
  };

  ArchCompareStorage.add = function (data) {
    if (
      !ArchCompareStorage.has(data.pid)
      && ArchCompareStorage.isUnderLimit()
    ) {
      var items = ArchCompareStorage.list();
      items.push(data);
      ArchCompareStorage.save(items);
    }
  };

  ArchCompareStorage.list = function () {
    if (storageExpired()) {
      ArchCompareStorage.clear();
      return [];
    }

    if (
      !storage
      || (typeof storage.getItem) !== 'function'
    ) {
      return [];
    }

    var items = JSON.parse(storage.getItem('arch_compare_items'));
    return items || [];
  };

  ArchCompareStorage.setLimit = function (limit) {
    limit = parseInt(limit);
  };

  ArchCompareStorage.getLimit = function () {
    return limit;
  };

  ArchCompareStorage.isUnderLimit = function () {
    if (!limit) {
      return true;
    }

    return ArchCompareStorage.list().length < limit;
  };

  function currentTimestamp() {
    return parseInt(((new Date()).getTime()) / 1000);
  }

  function storageExpired() {
    if (!max_age) {
      return false;
    }

    if (
      !storage
      || (typeof storage.getItem) !== 'function'
    ) {
      return false;
    }

    var last_saved = storage.getItem('arch_compare_item_last_saved') || 0;
    if (!last_saved) {
      return true;
    }

    return last_saved + max_age <= currentTimestamp();
  }

})(drupalSettings);
