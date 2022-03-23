<?php

namespace Drupal\eca_queue\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;
use Drupal\eca_queue\QueueEvents;
use Drupal\eca\Event\Tag;
use Drupal\eca_queue\Event\ProcessingTaskEvent;

/**
 * Deriver for ECA Queue event plugins.
 */
class QueueEventDeriver extends EventDeriverBase {

  /**
   * Get the derivative definitions of ECA queue events.
   */
  public static function definitions(): array {
    $definitions = [];
    $definitions['processing_task'] = [
      'label' => 'ECA processing queued task',
      'drupal_id' => QueueEvents::PROCESSING_TASK,
      'drupal_event_class' => ProcessingTaskEvent::class,
      'tags' => Tag::RUNTIME,
    ];
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(): array {
    return static::definitions();
  }

}
