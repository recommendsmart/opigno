<?php

namespace Drupal\eca_queue;

/**
 * Task that will be proccessed in a queue.
 */
class Task {

  /**
   * The name of the task.
   *
   * @var string
   */
  protected string $taskName;

  /**
   * An according value of the task, if any.
   *
   * @var string|null
   */
  protected ?string $taskValue;

  /**
   * The according contexts, keyed by purpose and unqualified context ID.
   *
   * @var \Drupal\Core\Plugin\Context\ContextInterface[][]
   */
  protected array $contexts;

  /**
   * The timestamp when this task should be processed the earliest.
   *
   * @var int
   */
  protected int $notBefore;

  /**
   * The Task constructor.
   *
   * @param string $task_name
   *   The name of the task.
   * @param string|null $task_value
   *   (optional) An according value of the task.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   (optional) The according contexts, keyed by purpose and unqualified
   *   context ID.
   * @param int $notBefore
   *   (optional) The timestamp when this task should be processed the earliest.
   */
  public function __construct(string $task_name, ?string $task_value = NULL, array $contexts = [], int $notBefore = 0) {
    $this->taskName = $task_name;
    $this->taskValue = $task_value;
    $this->contexts = $contexts;
    $this->notBefore = $notBefore;
  }

  /**
   * Get the name of the task.
   *
   * @return string
   *   The task name.
   */
  public function getTaskName(): string {
    return $this->taskName;
  }

  /**
   * Get the according task value.
   *
   * @return string|null
   *   The task value, or NULL if not given.
   */
  public function getTaskValue(): ?string {
    return $this->taskValue ?? NULL;
  }

  /**
   * Get the according contexts.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[][]
   *   The array of contexts, keyed by purpose and unqualified context ID.
   */
  public function getContexts(): array {
    return $this->contexts;
  }

  /**
   * Determine if the task is due for processing.
   *
   * @return bool
   */
  public function isDueForProcessing(): bool {
    return \Drupal::time()->getCurrentTime() >= $this->notBefore;
  }

}
