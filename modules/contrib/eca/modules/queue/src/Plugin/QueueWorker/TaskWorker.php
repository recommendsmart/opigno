<?php

namespace Drupal\eca_queue\Plugin\QueueWorker;

use Drupal\eca_queue\QueueEvents;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\eca_queue\Event\ProcessingTaskEvent;
use Drupal\eca_queue\Task;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Processes enqueued ECA tasks.
 *
 * @QueueWorker(
 *   id = "eca_task",
 *   title = @Translation("ECA Tasks"),
 *   cron = {"time" = 15}
 * )
 */
class TaskWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Constructs a TaskWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!($data instanceof Task)) {
      return;
    }
    $task = $data;
    if (!$task->isDueForProcessing()) {
      throw new \Exception('Task is not yet due for processing.');
    }
    $this->eventDispatcher->dispatch(new ProcessingTaskEvent($task), QueueEvents::PROCESSING_TASK);
  }

}
