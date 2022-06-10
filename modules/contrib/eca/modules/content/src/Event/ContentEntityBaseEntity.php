<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eca\Event\ContentEntityEventInterface;
use Drupal\eca_content\Service\EntityTypes;

/**
 * Base class for content entity related events.
 */
abstract class ContentEntityBaseEntity extends ContentEntityBase implements ContentEntityEventInterface {

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * The entity type service.
   *
   * @var \Drupal\eca_content\Service\EntityTypes
   */
  protected EntityTypes $entityTypes;

  /**
   * ContentEntityBaseEntity constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\eca_content\Service\EntityTypes $entity_types
   *   The entity type service.
   */
  public function __construct(ContentEntityInterface $entity, EntityTypes $entity_types) {
    $entity->eca_context = TRUE;
    $this->entity = $entity;
    $this->entityTypes = $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    $entity = $this->getEntity();
    return in_array($wildcard, [
      '*',
      $entity->getEntityTypeId(),
      $entity->getEntityTypeId() . '::' . $entity->bundle(),
    ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    return $this->entityTypes->bundleFieldApplies($this->getEntity(), $arguments['type']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

}
