/**
 * @file
 * Flexible event calender custom js file.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.flexible_event_calendar = {
    attach: function (context, settings) {   
      var myEvents = drupalSettings.flexible_event_calendar.data.events;   
      $("#evoCalendar").evoCalendar({
        calendarEvents : myEvents    
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
