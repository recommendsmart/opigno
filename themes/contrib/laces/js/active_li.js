(function ($) {
  $(document).on('show.bs.modal', '#reservationModal', function (e) {
    console.log('works');
    $('a.active').parent().addClass('active');
  });
})(jQuery);
