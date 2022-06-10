<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eca_content\Service\EntityTypes;

/**
 * Provides an event when a content entity revision is being created.
 *
 * @package Drupal\eca_content\Event
 */
class ContentEntityRevisionCreate extends ContentEntityBaseEntity {

  /**
   * The new revision.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $newRevision;

  /**
   * Flag to keep untranslatable fields.
   *
   * @var bool
   */
  protected bool $keepUntranslatableFields;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $new_revision
   *   The new revision.
   * @param \Drupal\eca_content\Service\EntityTypes $entity_types
   *   The entity types.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool|null $keep_untranslatable_fields
   *   The flag to keep untranslatable fields.
   */
  public function __construct(ContentEntityInterface $new_revision, EntityTypes $entity_types, ContentEntityInterface $entity, ?bool $keep_untranslatable_fields) {
    parent::__construct($entity, $entity_types);
    $this->newRevision = $new_revision;
    $this->keepUntranslatableFields = (bool) $keep_untranslatable_fields;
  }

  /**
   * Gets the new revision.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The new revision.
   */
  public function getNewRevision(): ContentEntityInterface {
    return $this->newRevision;
  }

  /**
   * Gets the flag to keep untranslatable fields.
   *
   * @return bool
   *   The flag to keep untranslatable fields.
   */
  public function isKeepUntranslatableFields(): bool {
    return $this->keepUntranslatableFields;
  }

}
