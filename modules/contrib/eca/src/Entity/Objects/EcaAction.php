<?php

namespace Drupal\eca\Entity\Objects;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Plugin\Action\ActionInterface;
use Drupal\eca\Entity\Eca;
use Drupal\Core\Action\ActionInterface as CoreActionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\eca\EcaEvents;
use Drupal\eca\Event\AfterActionExecutionEvent;
use Drupal\eca\Event\BeforeActionExecutionEvent;
use Drupal\eca\Plugin\ObjectWithPluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EcaAction extends EcaObject implements ObjectWithPluginInterface {

  /**
   * @var \Drupal\Core\Action\ActionInterface
   */
  protected CoreActionInterface $plugin;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|null
   */
  protected ?EventDispatcherInterface $eventDispatcher;

  /**
   * Event constructor.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   * @param string $id
   * @param string $label
   * @param \Drupal\eca\Entity\Objects\EcaEvent $event
   * @param \Drupal\Core\Action\ActionInterface $plugin
   */
  public function __construct(Eca $eca, string $id, string $label, EcaEvent $event, CoreActionInterface $plugin) {
    parent::__construct($eca, $id, $label, $event);
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EcaObject $predecessor, Event $event, array $context): bool {
    if (!parent::execute($predecessor, $event, $context)) {
      return FALSE;
    }

    $exception_thrown = FALSE;
    if ($this->plugin instanceof ActionInterface) {
      $this->plugin->setEvent($event);
    }
    $objects = $this->getObjects($this->plugin);
    foreach ($objects as $object) {
      if ($object instanceof TypedDataInterface) {
        $value = $object->getValue();
        if ($value instanceof EntityInterface) {
          $object = $value;
        }
      }

      $before_event = new BeforeActionExecutionEvent($this, $object, $event, $predecessor);
      $this->eventDispatcher()->dispatch($before_event, EcaEvents::BEFORE_ACTION_EXECUTION);

      try {
        $access_granted = FALSE;
        if (!($access_granted = $this->plugin->access($object))) {
          $this->logger()->warning('Access denied to %actionlabel (%actionid) from ECA %ecalabel (%ecaid) for event %event.', $context);
        }
        else {
          $this->plugin->execute($object);
        }
      }
      catch (\Exception $ex) {
        $context['%exmsg'] = $ex->getMessage();
        $context['%extrace'] = $ex->getTraceAsString();
        $this->logger()->error('Failed execution of %actionlabel (%actionid) from ECA %ecalabel (%ecaid) for event %event: %exmsg.\n\n%extrace', $context);
        $exception_thrown = TRUE;
      }
      finally {
        $this->eventDispatcher()->dispatch(new AfterActionExecutionEvent($this, $object, $event, $predecessor, $before_event->getPrestate(NULL), $access_granted, $exception_thrown));
      }
    }

    return !$exception_thrown;
  }

  /**
   * Get the plugin instance.
   *
   * @return \Drupal\Core\Action\ActionInterface
   *   The plugin instance.
   */
  public function getPlugin(): CoreActionInterface {
    return $this->plugin;
  }

  /**
   * Get the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher.
   */
  protected function eventDispatcher(): EventDispatcherInterface {
    if (!isset($this->eventDispatcher)) {
      $this->eventDispatcher = \Drupal::service('event_dispatcher');
    }
    return $this->eventDispatcher;
  }

}
