<?php

namespace Drupal\eca_log\Logger;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\eca_log\Event\LogMessageEvent;
use Drupal\eca_log\LogEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Trigger an ECA LogMessageEvent for each created log message.
 */
class EcaLog extends LoggerChannel {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Construct the EcaLog class.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    parent::__construct('');
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if (is_string($level)) {
      $level = $this->levelTranslation[$level];
    }
    $event = new LogMessageEvent($level, $message, $context);
    $this->eventDispatcher->dispatch($event, LogEvents::MESSAGE);
  }

}
