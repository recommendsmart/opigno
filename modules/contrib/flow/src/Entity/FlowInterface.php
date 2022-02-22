<?php

namespace Drupal\flow\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\flow\Plugin\FlowSubjectCollection;
use Drupal\flow\Plugin\FlowTaskCollection;

/**
 * Interface for Flow config entities.
 */
interface FlowInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Get the status value, i.e. whether this configuration is enabled.
   *
   * @return bool
   *   The status.
   */
  public function getStatus(): bool;

  /**
   * Set the default status value.
   *
   * @param bool $status
   *   The default status value.
   *
   * @return $this
   */
  public function setStatus($status): FlowInterface;

  /**
   * Gets the entity type for which this flow is used.
   *
   * @return string
   *   The entity type id.
   */
  public function getTargetEntityTypeId(): string;

  /**
   * Gets the bundle for which this flow is used.
   *
   * @return string
   *   The bundle.
   */
  public function getTargetBundle(): string;

  /**
   * Get the task mode, usually one of "create", "save" or "delete".
   *
   * @return string
   *   The task mode.
   */
  public function getTaskMode(): string;

  /**
   * Get the Flow tasks.
   *
   * @param array|null $filter
   *   (optional) Filter by certain tasks, for example when loading only active
   *   tasks, set ['active' => TRUE]. Default is NULL, which is no filter.
   *
   * @return \Drupal\flow\Plugin\FlowTaskCollection
   *   The Flow tasks, sorted by weight.
   */
  public function getTasks(?array $filter = NULL): FlowTaskCollection;

  /**
   * Get the Flow subjects.
   *
   * @param array|null $filter
   *   (optional) Filter subjects by certain tasks, for example when loading
   *   only for active tasks, set ['active' => TRUE]. Default is NULL, which is
   *   no filter.
   *
   * @return \Drupal\flow\Plugin\FlowSubjectCollection
   *   The Flow subjects, sorted in the according order of tasks.
   */
  public function getSubjects(?array $filter = NULL): FlowSubjectCollection;

  /**
   * Set the flow task settings.
   *
   * @param array $tasks
   *   The flow task settings. This list will be re-sorted by weight.
   *
   * @return $this
   */
  public function setTasks(array $tasks): FlowInterface;

}
