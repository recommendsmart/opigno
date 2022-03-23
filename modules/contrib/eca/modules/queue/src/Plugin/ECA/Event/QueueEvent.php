<?php

namespace Drupal\eca_queue\Plugin\ECA\Event;

use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_queue\Event\ProcessingTaskEvent;

/**
 * Plugin implementation for ECA Queue events.
 *
 * @EcaEvent(
 *   id = "eca_queue",
 *   deriver = "Drupal\eca_queue\Plugin\ECA\Event\QueueEventDeriver"
 * )
 */
class QueueEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    if ($this->drupalEventClass() === ProcessingTaskEvent::class) {
      return [
        [
          'name' => 'task_name',
          'label' => 'Task name',
          'type' => 'String',
        ],
        [
          'name' => 'task_value',
          'label' => 'Task value (optional)',
          'type' => 'String',
        ],
      ];
    }
    return parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    $configuration = $ecaEvent->getConfiguration();
    $argument_task_name = isset($configuration['task_name']) ? mb_strtolower(trim($configuration['task_name'])) : '';
    $argument_task_value = isset($configuration['task_value']) ? mb_strtolower(trim($configuration['task_value'])) : '';
    if ($argument_task_name === '') {
      return '*';
    }
    $wildcard = $argument_task_name;
    if ($argument_task_value !== '') {
      $wildcard .= '::' . $argument_task_value;
    }
    return $wildcard;
  }

}
