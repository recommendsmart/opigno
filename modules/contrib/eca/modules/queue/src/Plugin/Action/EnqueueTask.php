<?php

namespace Drupal\eca_queue\Plugin\Action;

use Drupal\context_stack\ContextStackTrait;
use Drupal\Core\Queue\QueueFactory;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca_queue\Task;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enqueue a Task.
 *
 * @Action(
 *   id = "eca_enqueue_task",
 *   label = @Translation("Enqueue a task")
 * )
 */
class EnqueueTask extends ConfigurableActionBase {

  use ContextStackTrait;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The name of the queue.
   *
   * @var string
   */
  static protected $queueName = 'eca_task';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_queue\Plugin\Action\EnqueueTask $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->addContextStack($container->get('context_stack.eca'));
    $instance->addContextStack($container->get('context_stack.account'));
    $instance->setQueueFactory($container->get('queue'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $task_name = $this->tokenServices->replaceClear($this->configuration['task_name']);
    $task_value = $this->tokenServices->replaceClear($this->configuration['task_value']);
    $task_not_before = $this->getEarliestProcessingTime();
    $contexts = [];
    foreach ($this->contextStacks as $context_stack) {
      $contexts[$context_stack->getPurpose()] = $context_stack->getScope();
    }
    $task = new Task($task_name, $task_value, $contexts, $task_not_before);
    $queue = $this->queueFactory->get(static::$queueName, TRUE);
    $queue->createQueue();
    if (FALSE === $queue->createItem($task)) {
      throw new \RuntimeException(sprintf("Failed to create queue item for Task '%s' and value '%s' in queue '%s'.", $task->getTaskName(), $task->getTaskValue(), static::$queueName));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'task_name' => '',
      'task_value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueFactory(QueueFactory $queue_factory) {
    $this->queueFactory = $queue_factory;
  }

  /**
   * Get the delay in seconds for the task to be created.
   *
   * Can be overwritten by sub-classes, if the support delays.
   *
   * @return int
   */
  protected function getEarliestProcessingTime(): int {
    return 0;
  }

}
