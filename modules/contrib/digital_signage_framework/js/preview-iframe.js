(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.digital_signage_preview_iframe = Drupal.digital_signage_preview_iframe || {};

  Drupal.behaviors.digital_signage_preview_iframe = {
    attach: function () {
      $('a:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .on('click', function () {
          Drupal.digital_signage_preview_iframe.handleClick(this);
          return false;
        });
      $('div[data-drupal-digitalsignage-dynamic="true"]:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .each(function () {
          Drupal.digital_signage_preview_iframe.loadDynamicBlock(this);
        });
    }
  };

  Drupal.digital_signage_preview_iframe.handleClick = function ($linkElement) {
    window.parent.postMessage({
      'action': 'pause',
    }, drupalSettings.digital_signage_preview_iframe.origin);
    let browser = document.createElement('iframe');
    browser.src = $($linkElement).attr('href');
    browser.setAttribute('class', 'browser');
    $('body')
      .append(browser)
      .append('<ul class="digital-signage-preview controls browser"><li class="close" title="close"></li></ul>');
    $('body > .controls.browser').on('click', function () {
      $('body > .browser').remove();
      window.parent.postMessage({
        'action': 'resume',
      }, drupalSettings.digital_signage_preview_iframe.origin);
    });
  }

  Drupal.digital_signage_preview_iframe.loadDynamicBlock = function ($blockElement) {
    setTimeout(function () {
      let blockid = $blockElement.getAttribute('data-drupal-digitalsignage-blockid');
      Drupal.ajax({
        url: '/api/digital_signage/block/' + blockid,
        error: function (e) {
          if (e.status === 200) {
            let parser = new DOMParser();
            let dom = parser.parseFromString(e.responseText, 'text/html');
            let newContent = dom.querySelector('div[data-drupal-digitalsignage-dynamic="true"]');
            $blockElement.innerHTML = newContent.innerHTML;
          } else {
            console.log(e);
          }
          Drupal.digital_signage_preview_iframe.loadDynamicBlock($blockElement);
        }
      }).execute();
    }, 10000);

  }

})(jQuery, Drupal, drupalSettings);
