<?php

namespace Drupal\eca_content\Event;

/**
 * Base class for entity bundle related events.
 */
abstract class ContentEntityBaseBundle extends ContentEntityBase {

  /**
   * The entity type id.
   *
   * @var string
   */
  protected string $entityTypeId;

  /**
   * The bundle.
   *
   * @var string
   */
  protected string $bundle;

  /**
   * ContentEntityBaseBundle constructor.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   */
  public function __construct(string $entity_type_id, string $bundle) {
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;
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
   * Gets the bundle.
   *
   * @return string
   *   The bundle.
   */
  public function getBundle(): string {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return in_array($wildcard, [
      '*',
      $this->entityTypeId,
      $this->entityTypeId . '::' . $this->bundle,
    ]
    );
  }

}
