<?php

namespace Drupal\eca_content\Event;

/**
 * Class ContentEntityPreLoad
 *
 * @package Drupal\eca_content\Event
 */
class ContentEntityPreLoad extends ContentEntityBase {

  /**
   * @var array
   */
  protected array $ids;

  /**
   * @var string
   */
  protected string $entityTypeId;

  /**
   * @param array $ids
   * @param string $entity_type_id
   */
  public function __construct(array $ids, string $entity_type_id) {
    $this->ids = $ids;
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * @return array
   */
  public function getIds(): array {
    return $this->ids;
  }

  /**
   * @return string
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
