<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\eca\EcaEvents;
use Drupal\eca\Event\AfterInitialExecutionEvent;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Prepares and cleans up the Token service when executing ECA logic.
 */
class EcaExecutionTokenSubscriber implements EventSubscriberInterface {

  /**
   * The Token services.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenServices;

  /**
   * The EcaExecutionTokenSubscriber constructor.
   *
   * @param \Drupal\eca\Token\TokenInterface $token_services
   *   The Token services.
   */
  public function __construct(TokenInterface $token_services) {
    $this->tokenServices = $token_services;
  }

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $event) {
    // Hold the current state of Token data to restore it after execution.
    $token_data = $this->tokenServices->getTokenData();
    $event->setPrestate('token_data', $token_data);
    // Reset the data state of the Token services. This step is necessary
    // to make sure, that variables are only valid within this scope.
    // After execution of successors, the data will be cleared once again,
    // so that any change will not affect logic outside of this scope.
    $this->tokenServices->clearTokenData();
  }

  /**
   * Subscriber method after initial execution.
   *
   * @param \Drupal\eca\Event\AfterInitialExecutionEvent $event
   *   The according event.
   */
  public function onAfterInitialExecution(AfterInitialExecutionEvent $event) {
    // Clear the Token data once more, and restore the state of Token data
    // for the wrapping logic (if any).
    $this->tokenServices->clearTokenData();
    $token_data = $event->getPrestate('token_data') ?? [];
    foreach ($token_data as $key => $data) {
      $this->tokenServices->addTokenData($key, $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = ['onBeforeInitialExecution'];
    $events[EcaEvents::AFTER_INITIAL_EXECUTION][] = ['onAfterInitialExecution'];
    return $events;
  }

}
