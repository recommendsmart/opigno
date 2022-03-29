<?php

namespace Drupal\eca_content\EventSubscriber;

use Drupal\eca\EcaEvents;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\Event\ContentEntityEventInterface;
use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_content\Plugin\ECA\Event\ContentEntityEvent;

/**
 * ECA event subscriber.
 */
class EcaContent extends EcaBase {

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    if ($event instanceof ContentEntityEventInterface) {
      $this->tokenService->addTokenData('entity', $event->getEntity());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (ContentEntityEvent::actions() as $action) {
      $events[$action['event_name']][] = ['onEvent'];
    }
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = ['onBeforeInitialExecution'];
    return $events;
  }

}
