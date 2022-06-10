<?php

namespace Drupal\eca_content\Event;

/**
 * Provides an event before a content entity is being loaded.
 *
 * @package Drupal\eca_content\Event
 */
class ContentEntityPreLoad extends ContentEntityBase {

  /**
   * The ids.
   *
   * @var array
   */
  protected array $ids;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected string $entityTypeId;

  /**
   * Constructor.
   *
   * @param array $ids
   *   The ids.
   * @param string $entity_type_id
   *   The entity type id.
   */
  public function __construct(array $ids, string $entity_type_id) {
    $this->ids = $ids;
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * Gets the ids.
   *
   * @return array
   *   The ids.
   */
  public function getIds(): array {
    return $this->ids;
  }

  /**
   * Gets the entity type id.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return in_array($wildcard, ['*', $this->entityTypeId]);
  }

}
