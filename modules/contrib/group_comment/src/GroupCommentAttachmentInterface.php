<?php

declare(strict_types = 1);

namespace Drupal\group_comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupContentInterface;

/**
 * Interface for GroupCommentAttachmentInterface service.
 *
 * @package Drupal\group_comment
 */
interface GroupCommentAttachmentInterface {

  /**
   * Attach comments from given entity to the same group(s).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to process.
   */
  public function attach(EntityInterface $entity): void;

  /**
   * Detach comments from group by given group comment.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $entity
   *   The group content entity.
   */
  public function detach(GroupContentInterface $entity): void;

}
