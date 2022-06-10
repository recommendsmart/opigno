<?php

namespace Drupal\eca\Plugin\Action;

use Drupal\Component\EventDispatcher\Event;

/**
 * Interface for ECA provided actions.
 */
interface ActionInterface {

  /**
   * Sets the triggered event that leads to this action.
   *
   * @param \Drupal\Component\EventDispatcher\Event $event
   *   The triggered event.
   *
   * @return \Drupal\eca\Plugin\Action\ActionInterface
   *   This.
   */
  public function setEvent(Event $event): ActionInterface;

  /**
   * Get the triggered event that leads to this action.
   *
   * @return \Drupal\Component\EventDispatcher\Event
   *   The trigered event.
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
