<?php

namespace Drupal\eca_content\Event;

/**
 * Class ContentEntityBaseBundle
 *
 * @package Drupal\eca_content\Event
 */
abstract class ContentEntityBaseBundle extends ContentEntityBase {

  /**
   * @var string
   */
  protected string $entityTypeId;

  /**
   * @var string
   */
  protected string $bundle;

  /**
   * ContentEntityBaseBundle constructor.
   *
   * @param string $entity_type_id
   * @param string $bundle
   */
  public function __construct(string $entity_type_id, string $bundle) {
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;
  }

  /**
   * @return string
   */
  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * @return string
   */
  public function getBundle(): string {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return in_array($wildcard, ['*', $this->entityTypeId, $this->entityTypeId . '::' . $this->bundle]);
  }

}
