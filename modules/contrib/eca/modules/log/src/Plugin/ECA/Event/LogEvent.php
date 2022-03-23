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
   * @return array[]
   */
  public static function actions(): array {
    $actions = [];
    $actions['log_message'] = [
      'label' => 'Log message created',
      'drupal_id' => LogEvents::MESSAGE,
      'drupal_event_class' => LogMessageEvent::class,
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    if ($this->drupalEventClass() === LogMessageEvent::class) {
      return LogMessageEvent::fields();
    }
    return parent::fields();
  }

}
