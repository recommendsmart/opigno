<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\eca\EcaEvents;
use Drupal\eca\Event\AfterInitialExecutionEvent;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\Event\FormEventInterface;

/**
 * Adds currently involved form events into a publicly available stack.
 */
class EcaExecutionFormSubscriber extends EcaBase {

  /**
   * A stack of form events, which the subscriber involved for execution.
   *
   * @var \Drupal\eca\Event\FormEventInterface[]
   */
  protected array $eventStack = [];

  /**
   * Get the service instance of this class.
   *
   * @return \Drupal\eca\EventSubscriber\EcaExecutionFormSubscriber
   *   The service instance.
   */
  public static function get(): EcaExecutionFormSubscriber {
    return \Drupal::service('eca.execution.form_subscriber');
  }

  /**
   * Subscriber method before initial execution.
   *
   * Adds a form data provider to the Token service.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    if ($event instanceof FormEventInterface) {
      array_unshift($this->eventStack, $event);
    }
  }

  /**
   * Subscriber method after initial execution.
   *
   * Removes the form data provider from the Token service.
   *
   * @param \Drupal\eca\Event\AfterInitialExecutionEvent $after_event
   *   The according event.
   */
  public function onAfterInitialExecution(AfterInitialExecutionEvent $after_event): void {
    $event = $after_event->getEvent();
    if ($event instanceof FormEventInterface) {
      array_shift($this->eventStack);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = [
      'onBeforeInitialExecution',
      -100,
    ];
    $events[EcaEvents::AFTER_INITIAL_EXECUTION][] = [
      'onAfterInitialExecution',
      100,
    ];
    return $events;
  }

  /**
   * Get the stack of form events, which the subscriber involved for execution.
   *
   * @return \Drupal\eca\Event\FormEventInterface[]
   *   The stack of involved form events, which is an array ordered by the most
   *   recent events at the beginning and the first added events at the end.
   */
  public function getStackedFormEvents(): array {
    return $this->eventStack;
  }

}
