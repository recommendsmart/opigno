CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

  Flexible Event Calendar API is a javascript API to integrate with evo-calendar library.  
  It provides a render element to generate a calendar from entity type.
  It provides additional javascript file to customize this plugin easily.

  You can find more information about this plugin in the below link,
   - https://github.com/edlynvillegas/evo-calendar 

  Usage : 

    This module require parameters to pass to the render element. 
    Create a custom block plugin and pass the value in the below
    format as return value.

    $build['flexible_event_calendar'] = [
      '#data' => [
        'events' => [
          [
            'id' => '111' , 'name' => 'New Year', 'date' => "January/1/2020", 'type' => "event"
          ],
          [
            'id' => '111' , 'name' => 'Organization Meeting', 'date' => "January/1/2020", 'type' => "event"
          ],
          [
            'id' => '222' , 'name' => 'Board Meeting', 'date' => "January/31/2020", 'type' => "event"
          ],          
        ],
      ],      
      '#type' => 'flexible_event_calendar'          
    ];

    We can easily customize this plugin using the js file "evo-calendar.custom.js" 
    included in the flexible_event_calendar js folder.
    Include this js file inside custom module and set as 
    dependencies:
    - flexible_event_calendar/flexible_event_calendar_js
    in the custom library file and call should be 

    $build['flexible_event_calendar'] = [
      '#data' => [
        'events' => [
          [
            'id' => '111' , 'name' => 'New Year', 'date' => "January/1/2020", 'type' => "event"
          ],                    
        ],
      ],      
      '#type' => 'flexible_event_calendar'  
      '#attached' => [
        'library' => [
          'custom_module_name/custom_library_name',
        ],
      ],      
    ];

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Flexible Event Calendar API module as you would normally install a 
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 
   for further information.

CONFIGURATION
-------------

 This module does not require any configuration.


MAINTAINERS
-----------

Current maintainers:
  Elavarasan R - https://www.drupal.org/user/1902634
