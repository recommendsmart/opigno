<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class ContentEntityRevisionCreate
 *
 * @package Drupal\eca_content\Event
 */
class ContentEntityRevisionCreate extends ContentEntityBaseEntity {

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $newRevision;

  /**
   * @var bool
   */
  protected bool $keepUntranslatableFields;

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $new_revision
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param bool|null $keep_untranslatable_fields
   */
  public function __construct(ContentEntityInterface $new_revision, ContentEntityInterface $entity, $keep_untranslatable_fields) {
    parent::__construct($entity);
    $this->newRevision = $new_revision;
    $this->keepUntranslatableFields = (bool) $keep_untranslatable_fields;
  }

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function getNewRevision(): ContentEntityInterface {
    return $this->newRevision;
  }

  /**
   * @return bool
   */
  public function isKeepUntranslatableFields(): bool {
    return $this->keepUntranslatableFields;
  }

}
