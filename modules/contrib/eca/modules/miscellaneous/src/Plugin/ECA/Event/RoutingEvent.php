<?php

namespace Drupal\eca_misc\Plugin\ECA\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * Plugin implementation of the ECA Events for routing.
 *
 * @EcaEvent(
 *   id = "routing",
 *   deriver = "Drupal\eca_misc\Plugin\ECA\Event\RoutingEventDeriver"
 * )
 */
class RoutingEvent extends EventBase {

  /**
   * @return array[]
   */
  public static function actions(): array {
    return [
      'alter' => [
        'label' => 'Alter route',
        'drupal_id' => RoutingEvents::ALTER,
        'drupal_event_class' => RouteBuildEvent::class,
      ],
      'dynamic' => [
        'label' => 'Allow new routes',
        'drupal_id' => RoutingEvents::DYNAMIC,
        'drupal_event_class' => RouteBuildEvent::class,
      ],
      'finished' => [
        'label' => 'Route building finished',
        'drupal_id' => RoutingEvents::FINISHED,
        'drupal_event_class' => Event::class,
      ],
    ];
  }

}
