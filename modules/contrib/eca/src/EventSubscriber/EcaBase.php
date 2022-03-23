<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\eca\Processor;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base ECA event subscriber.
 */
abstract class EcaBase implements EventSubscriberInterface {

  /**
   * @var \Drupal\eca\Processor
   */
  protected Processor $processor;

  /**
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenService;

  /**
   * ContentEntity constructor.
   *
   * @param \Drupal\eca\Processor $processor
   * @param \Drupal\eca\Token\TokenInterface $token_service
   */
  public function __construct(Processor $processor, TokenInterface $token_service) {
    $this->processor = $processor;
    $this->tokenService = $token_service;
  }

  /**
   * @param \Drupal\Component\EventDispatcher\Event $event
   * @param string $event_name
   */
  public function onEvent(Event $event, string $event_name): void {
    try {
      $this->processor->execute($event, $event_name);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // This is thrown during installation of eca and we can ignore this.
    }
  }

}
