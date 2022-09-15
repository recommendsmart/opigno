<?php

namespace Drupal\comments_order;

use Drupal\comment\CommentStorage;
use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;

/**
 * Defines the storage handler class for comments.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for comment entities.
 */
class CommentsOrderStorage extends CommentStorage {

  /**
   * {@inheritdoc}
   */
  public function getDisplayOrdinal(CommentInterface $comment, $comment_mode, $divisor = 1) {

    $entity_type_id = $comment->getCommentedEntityTypeId();
    $field_name = $comment->get('field_name')->getString();
    $bundle = $comment->getCommentedEntity()->bundle();

    $field = $this->entityTypeManager->getStorage('field_config')->load($entity_type_id . '.' . $bundle . '.' . $field_name);
    $field_order = $field->getThirdPartySetting('comments_order', 'order', 'ASC');

    // Count how many comments (c1) are before $comment (c2) in display order.
    // This is the 0-based display ordinal.
    $data_table = $this->getDataTable();
    $query = $this->database->select($data_table, 'c1');
    $query->innerJoin($data_table, 'c2', 'c2.entity_id = c1.entity_id AND c2.entity_type = c1.entity_type AND c2.field_name = c1.field_name');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('c2.cid', $comment->id());
    if (!$this->currentUser->hasPermission('administer comments')) {
      $query->condition('c1.status', CommentInterface::PUBLISHED);
    }

    if ($comment_mode == CommentManagerInterface::COMMENT_MODE_FLAT) {
      // For rendering flat comments, cid is used for ordering comments due to
      // unpredictable behavior with timestamp, so we make the same assumption
      // here.

      if (substr($field_order, 0, 4) == 'DESC') {
        $query->condition('c1.cid', $comment->id(), '>');
      }
      else {
        $query->condition('c1.cid', $comment->id(), '<');
      }
    }
    else {
      // For threaded comments, the c.thread column is used for ordering. We can
      // use the sorting code for comparison, but must remove the trailing
      // slash.
      if (substr($field_order, 0, 4) == 'DESC') {
        $query->where('SUBSTRING(c1.thread, 1, (LENGTH(c1.thread) - 1)) > SUBSTRING(c2.thread, 1, (LENGTH(c2.thread) - 1))');
      }
      else {
        $query->where('SUBSTRING(c1.thread, 1, (LENGTH(c1.thread) - 1)) < SUBSTRING(c2.thread, 1, (LENGTH(c2.thread) - 1))');
      }
    }

    $query->condition('c1.default_langcode', 1);
    $query->condition('c2.default_langcode', 1);

    $ordinal = $query->execute()->fetchField();

    return ($divisor > 1) ? floor($ordinal / $divisor) : $ordinal;
  }

}
