/**
 * @file
 * Price widget.
 */

(function ($, Drupal, drupalSettings) {

  function round(number, decimals) {
    if (!decimals) {
      decimals = 0;
    }
    // @see https://stackoverflow.com/a/6437258.
    decimals = parseInt(decimals, 10);
    var dec = Math.pow(10, decimals);
    // Fixed the .X99999999999.
    number = '' + Math.round(parseFloat(number) * dec + 0.0000000000001);
    return parseFloat(number.slice(0, -1 * decimals) + '.' + number.slice(-1 * decimals));
  }

  function getValues(delta, old) {
    var values = {
      _delta: delta,
      _changed: false
    };
    $(':input[data-price-widget--delta=' + delta + ']').each(function () {
      var name = $(this).attr('data-price-widget--field');
      if (name) {
        values[name] = old
          ? $(this).attr('data-price-widget--old-value')
          : $(this).val();
      }
    });

    return massageValue(values);
  }

  function massageValue(values) {
    if (!values.date_from) {
      values.date_from = null;
    }
    if (!values.date_to) {
      values.date_to = null;
    }

    if (!values.vat_rate) {
      values.vat_rate_percent = 0;
      values.vat_rate = 0;
    }
    else {
      values.vat_rate_percent = parseFloat(values.vat_rate) / 100;
      values.vat_rate = parseFloat(values.vat_rate);
    }

    values.gross = parseFloat(values.gross);
    values.net = parseFloat(values.net);
    values.vat_value = round(values.gross - values.net, 2);

    return values;
  }

  function setValues(values, oldValues, name) {
    $(':input[data-price-widget--delta=' + values._delta + ']').each(function () {
      var name = $(this).attr('data-price-widget--field')
        , oldValue = oldValues[name]
        , value = values[name]
        ;
      if (name && oldValue !== value) {
        $(this).val(values[name]);
        $(this).attr('data-price-widget--old-value', values[name]);
      }
    });

    if (oldValues.base !== values.base) {
      var $base = $(':input[data-price-widget--delta=' + values._delta + '][data-price-widget--field=base]');
      $base.trigger('change');
    }
  }

  function calcValues(oldValues, field) {
    var values = JSON.parse(JSON.stringify(oldValues));
    if (field === 'net' || field === 'gross') {
      // Do nothing.
    }
    else if (field === 'price_type') {
      var price_type = getPriceType(values.price_type);
      if (price_type) {
        if (price_type.currency) {
          values.currency = price_type.currency;
        }

        if (price_type.base === 'net' || price_type.base === 'gross') {
          values.base = price_type.base;
        }

        if (price_type.vat_category) {
          var category = getVatCategory(price_type.vat_category);
          if (category && category.id) {
            values.vat_category = category.id;
            _setVatCategory(values);
          }
        }
      }
    }

    else if (field === 'vat_category') {
      _setVatCategory(values);
    }

    else if (field === 'vat_rate') {
      values.vat_rate_percent = values.vat_rate / 100;
      values._changed = true;
    }

    _calcPrice(oldValues, values);
    return massageValue(values);
  }

  function _setVatCategory(values) {
    var category = getVatCategory(values.vat_category);
    if (category.custom) {
      values.vat_rate = 0;
      values.vat_rate_percent = 0;
    }
    else {
      values.vat_rate = round(category.rate * 100, 2);
      values.vat_rate_percent = category.rate;
    }
    values._changed = true;
  }

  function _calcPrice(oldValues, newValues) {
    var rate = getRate(oldValues);
    if (newValues.base === 'net') {
      newValues.gross = round(oldValues.net * (1 + rate), 2);
    }
    else if (newValues.base === 'gross') {
      newValues.net = round(oldValues.gross / (1 + rate), 2);
    }

    if (
      (oldValues.net !== newValues.net)
      || (oldValues.gross !== newValues.gross)
    ) {
      newValues._changed = true;
    }

    newValues.vat_value = round(newValues.gross - newValues.net, 2);
  }

  function getRate(values) {
    var category = getVatCategory(values.vat_category);
    if (category.custom) {
      return values.vat_rate_percent;
    }
    return category.rate;
  }

  function getPriceType(name) {
    if (
      name
      && drupalSettings
      && drupalSettings.arch_price
      && drupalSettings.arch_price.price_types
      && drupalSettings.arch_price.price_types[name]
    ) {
      return drupalSettings.arch_price.price_types[name];
    }

    return {
      id: null,
      name: Drupal.t('Unknown', {}, {context: 'arch_price_type'}),
      base: 'net',
      vat_category: 'default'
    };
  }

  function getVatCategory(name) {
    if (
      name
      && drupalSettings
      && drupalSettings.arch_price
      && drupalSettings.arch_price.vat_categories
      && drupalSettings.arch_price.vat_categories[name]
    ) {
      return drupalSettings.arch_price.vat_categories[name];
    }

    return {
      id: null,
      name: Drupal.t('Unknown', {}, {context: 'arch_vat_category'}),
      custom: true,
      rate: 0
    };
  }

  Drupal.behaviors.archPriceWidget = {
    attach: function attach(context) {
      var processedClass = 'price-widget--processed'
        , $body = $('body')
        ;
      if ($body.hasClass(processedClass)) {
        return;
      }

      $body.addClass(processedClass)
        .on('change keyup', '.price-edit-table [data-price-widget--field]', function (ev) {
          var $field = $(this)
            , name = $field.attr('data-price-widget--field')
            , delta = $field.attr('data-price-widget--delta')
            , values = getValues(delta, false)
            , oldValues = getValues(delta, true)
            , calc = calcValues(values, name)
            ;

          if (calc._changed) {
            setValues(calc, oldValues, name);
          }
        })
      ;
    }
  };
})(jQuery, Drupal, drupalSettings);
