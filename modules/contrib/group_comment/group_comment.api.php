<?php

/**
 * @file
 * Describes hooks provided by group comment module.
 */

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Alters the list of groups entity might belong to.
 *
 * @param array $groups
 *   List of groups identified for an entity (comment).
 * @param \Drupal\comment\CommentInterface $comment
 *   The comment that being attached to the group.
 * @param \Drupal\Core\Entity\EntityInterface $commented_entity
 *   The commentable entity.
 */
function hook_group_comment_attach_groups_alter(array &$groups, CommentInterface $comment, EntityInterface $commented_entity) {
  if ($commented_entity->id() === 'foo') {
    $groups[$commented_entity->id()] = [];
  }
}
