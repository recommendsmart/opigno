<?php

namespace Drupal\eca_user\EventSubscriber;

use Drupal\eca\EcaEvents;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_user\Event\UserBase;
use Drupal\eca_user\Plugin\ECA\Event\UserEvent;

/**
 * ECA event subscriber.
 */
class EcaUser extends EcaBase {

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    if ($event instanceof UserBase && ($user = $this->entityTypeManager->getStorage('user')->load($event->getAccount()->id()))) {
      $this->tokenService->addTokenData('entity', $user);
      $this->tokenService->addTokenData('account', $user);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (UserEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = ['onBeforeInitialExecution'];
    return $events;
  }

}
