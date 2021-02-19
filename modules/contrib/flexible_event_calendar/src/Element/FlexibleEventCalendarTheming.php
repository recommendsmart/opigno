<?php

namespace Drupal\flexible_event_calendar\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element to display a flexible calender js.
 *
 * @RenderElement("flexible_event_calendar")
 */
class FlexibleEventCalendarTheming extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {    
    return [
      '#attached' => [
        'library' => [
          'flexible_event_calendar/flexible_event_calendar_js',
        ],
      ],
      '#data' => [],
      '#type' => [],     
      '#pre_render' => [
        [self::class, 'preRenderFlexibleEventCalendarTheming'],
      ],
      '#theme' => 'flexible_event_calendar',
    ];
  }

  /**
   * Element pre render callback.
   */
  public static function preRenderFlexibleEventCalendarTheming($element) {
    $element['#attached']['drupalSettings']['flexible_event_calendar'] = [      
      'data' => $element['#data'],       
    ];
    return $element;
  }

}
