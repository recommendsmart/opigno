<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eca\Event\TokenReceiverInterface;
use Drupal\eca\Event\TokenReceiverTrait;

/**
 * Provides a custom event that is entity aware.
 *
 * @package Drupal\eca_content\Event
 */
class ContentEntityCustomEvent extends ContentEntityBaseEntity implements TokenReceiverInterface {

  use TokenReceiverTrait;

  /**
   * The (optional) id for this event.
   *
   * @var string
   */
  protected string $eventId;

  /**
   * Additional arguments provided by the triggering context.
   *
   * @var array
   */
  protected array $arguments = [];

  /**
   * Provides a custom event that is entity aware.

   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which the custom event got triggered.
   * @param string $event_id
   *   The (optional) ID for this event, so that it only applies, if it matches
   *   the given event ID in the arguments.
   * @param array $arguments
   *   Additional arguments provided by the triggering context. This may at
   *   least contain the key "event_id" to filter custom events to apply only
   *   if that ID matches this ID. To trigger all custom events, the event ID
   *   should be omitted or left empty.
   */
  public function __construct(ContentEntityInterface $entity, string $event_id, array $arguments) {
    parent::__construct($entity);
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
