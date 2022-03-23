<?php

namespace Drupal\eca_base\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;

/**
 * Class CustomEvent
 *
 * @package Drupal\eca_base\Event
 */
class CustomEvent extends Event implements ConditionalApplianceInterface {

  protected string $eventId;

  protected array $arguments = [];

  /**
   * @param string $event_id
   * @param array $arguments
   */
  public function __construct(string $event_id, array $arguments) {
    $this->eventId = $event_id;
    $this->arguments = $arguments;
  }

  /**
   * @return string[]
   */
  public static function fields(): array {
    return [[
      'name' => 'event_id',
      'label' => 'Event ID',
      'type' => 'String',
    ]];
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return ($this->eventId === $wildcard) || ($wildcard === '');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $argument_event_id = isset($arguments['event_id']) ? trim($arguments['event_id']) : '';
    return ($argument_event_id === '') || ($this->eventId === $argument_event_id);
  }

}
