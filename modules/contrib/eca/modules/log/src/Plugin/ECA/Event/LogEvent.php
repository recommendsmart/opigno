<?php

namespace Drupal\eca_log\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_log\Event\LogMessageEvent;
use Drupal\eca_log\LogEvents;

/**
 * Plugin implementation of the ECA Events for log messages.
 *
 * @EcaEvent(
 *   id = "log",
 *   deriver = "Drupal\eca_log\Plugin\ECA\Event\LogEventDeriver"
 * )
 */
class LogEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $actions = [];
    $actions['log_message'] = [
      'label' => 'Log message created',
      'event_name' => LogEvents::MESSAGE,
      'event_class' => LogMessageEvent::class,
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    if ($this->eventClass() === LogMessageEvent::class) {
      return LogMessageEvent::fields();
    }
    return parent::fields();
  }

}
