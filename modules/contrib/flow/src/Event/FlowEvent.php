<?php

namespace Drupal\flow\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class for any Flow-related event.
 */
class FlowEvent extends Event {

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * The task mode.
   *
   * @var string
   */
  protected string $taskMode;

  /**
   * Constructs a new FlowEvent object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which flow is being applied.
   * @param string $task_mode
   *   The applied task mode.
   */
  public function __construct(EntityInterface $entity, string $task_mode) {
    $this->entity = $entity;
    $this->taskMode = $task_mode;
  }

  /**
   * Get the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Get the task mode.
   *
   * @return string
   *   The task mode.
   */
  public function getTaskMode(): string {
    return $this->taskMode;
  }

}
