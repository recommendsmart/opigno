<?php

declare(strict_types = 1);

namespace Drupal\group_comment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;

/**
 * Helper class for commented entity.
 */
class CommentedEntityHelper {

  /**
   * Get groups by entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   Group(s) of the entity, empty array if the entity is not commentable.
   */
  public static function getGroupsByEntity(ContentEntityInterface $entity): array {
    // Check whether the entity type is commentable.
    $fields = \Drupal::service('comment.manager')->getFields($entity->getEntityTypeId());

    if (empty($fields)) {
      return [];
    }

    // Check whether the bundle of the entity is commentable.
    $is_bundle_commentable = FALSE;
    foreach ($fields as $field) {
      if (isset($field['bundles'][$entity->bundle()])) {
        $is_bundle_commentable = TRUE;
        break;
      }
    }

    if (!$is_bundle_commentable) {
      return [];
    }

    /** @var \Drupal\group\Entity\GroupInterface[] $groups */
    $groups = [];

    // Check whether the entity is a group or grouped entity.
    if ($entity instanceof GroupInterface) {
      $groups[] = $entity;
    }
    elseif ($group_contents = GroupContent::loadByEntity($entity)) {
      foreach ($group_contents as $group_content) {
        /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
        $group = $group_content->getGroup();
        $groups[$group->id()] = $group;
      }
    }

    return $groups;
  }

}
