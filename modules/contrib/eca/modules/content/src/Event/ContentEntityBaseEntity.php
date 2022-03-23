<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eca\Event\ContentEntityEventInterface;

/**
 * Class ContentEntityBaseEntity
 *
 * @package Drupal\eca_content\Event
 */
abstract class ContentEntityBaseEntity extends ContentEntityBase implements ContentEntityEventInterface {

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * ContentEntityBaseEntity constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function __construct(ContentEntityInterface $entity) {
    $entity->eca_context = TRUE;
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    $entity = $this->getEntity();
    return in_array($wildcard, ['*', $entity->getEntityTypeId(), $entity->getEntityTypeId() . '::' . $entity->bundle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    return $this->bundleFieldApplies($this->getEntity(), $arguments['type']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

}
