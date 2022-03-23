<?php

namespace Drupal\eca\Plugin\Action;

use Drupal\Component\EventDispatcher\Event;

/**
 *
 */
interface ActionInterface {

  /**
   * @param \Drupal\Component\EventDispatcher\Event $event
   *
   * @return \Drupal\eca\Plugin\Action\ActionInterface
   */
  public function setEvent(Event $event): ActionInterface;

  /**
   * @return \Drupal\Component\EventDispatcher\Event
   */
  public function getEvent(): Event;

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration(): array;

}
