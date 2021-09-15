
(function ($, Drupal, drupalSettings, window) {
  'use strict';

  function getCurrentState() {
    return {
      state: {
        url: location.href,
        documentTitle: document.title,
        pageTitle: $('h1.page-title').text(),
        selector: '.product--full',
        content: $('<div>').append($('.product--full').clone()).html()
      },
      title: document.title,
      href: location.href
    };
  }

  function createDocumentTitle(siteName, title) {
    var oldTitle = document.title;
    var escapedSiteName = siteName.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
    var re = new RegExp('.+ (.) ' + escapedSiteName);
    return oldTitle.replace(re, title + ' $1 ' + siteName);
  }

  function getNewState(response) {
    var newTitle = createDocumentTitle(response.siteName, response.title);
    return {
      state: {
        url: location.href,
        documentTitle: document.title,
        pageTitle: $('h1.page-title').text(),
        selector: response.selector,
        content: $('<div>').append($('.product--full').clone()).html()
      },
      title: newTitle,
      href: response.url
    };
  }

  function replaceContent(documentTitle, pageTitle, selector, content) {
    document.title = documentTitle;
    $('h1.page-title').text(pageTitle);

    var $wrapper = $(selector);
    var method = 'replaceWith';
    var effect = {
      showEffect: 'show',
      hideEffect: 'hide',
      showSpeed: ''
    };
    var settings = drupalSettings;
    var $newContent = $($.parseHTML(content, document, true));
    Drupal.detachBehaviors($wrapper.get(0), settings);
    $wrapper[method]($newContent);

    var $ajaxNewContent = $newContent.find('.ajax-new-content');
    if ($ajaxNewContent.length) {
      $ajaxNewContent.hide();
      $newContent.show();
      $ajaxNewContent[effect.showEffect](effect.showSpeed);
    }
    else if (effect.showEffect !== 'show') {
      $newContent[effect.showEffect](effect.showSpeed);
    }

    if ($newContent.parents('html').length) {
      $newContent.each(function (index, element) {
        if (element.nodeType === Node.ELEMENT_NODE) {
          Drupal.attachBehaviors(element, settings);
        }
      });
    }
  }

  Drupal.AjaxCommands.prototype.productReplaceUrlTitle = function (ajax, response, status) {
    var newState = getNewState(response);
    if (window.history && window.history.pushState) {
      window.history.pushState(newState.state, newState.title, newState.href);
    }

    replaceContent(newState.title, response.title, response.selector, response.content);
  };

  Drupal.behaviors.arch_product_matrix_ajax = {
    attach: function attach(context) {
      var processedClass = 'arch-product-matrix-navigation-processed';
      var $body = $('body');

      if ($body.hasClass(processedClass)) {
        return;
      }

      var currentState = getCurrentState();
      window.history.replaceState(currentState.state, currentState.title, currentState.href);
    }
  };

  window.onpopstate = function (event) {
    replaceContent(event.state.documentTitle, event.state.pageTitle, event.state.selector, event.state.content);
  };

})(jQuery, Drupal, drupalSettings, window);
