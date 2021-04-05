(function ($) {
  let options = {
    rootMargin: '0px',
    threshold: [0, 1]
  };
  const pageHeader = $('.page-header');
  const sentinel = $('.sticky-nav-top');
  const targetBody = $('body')[0];
  const config = {attributes: true};
  const observeTopPadding = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      let toolbarFixed = $('body.toolbar-fixed');
      if (toolbarFixed.length !== 0) {
        $('.sticky-nav').css('top', toolbarFixed.css('padding-top'));
        options.rootMargin = '-' + toolbarFixed.css('padding-top') + ' 0px 0px 0px';
        if (pageHeader.length !== 0) {
          let observeSticky = new IntersectionObserver(onEntry, options);
          observeSticky.observe(sentinel[0]);
        }
      }
    });
  });

//to check when element get's position sticky
  function onEntry(entries) {
    // no intersection
    if (entries[0].intersectionRatio === 0) {
      $(`.page-navigation-header .navbar-brand`).removeClass('visually-hidden');
    }
    // fully intersects
    else if (entries[0].intersectionRatio === 1) {
      $('.page-navigation-header .navbar-brand').addClass('visually-hidden');
    }
  }

  observeTopPadding.observe(targetBody, config);
  if (pageHeader.length !== 0) {
    let observeSticky = new IntersectionObserver(onEntry, options);
    observeSticky.observe(sentinel[0]);
  }
})(jQuery);
