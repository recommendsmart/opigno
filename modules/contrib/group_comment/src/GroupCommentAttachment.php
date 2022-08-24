<?php

declare(strict_types = 1);

namespace Drupal\group_comment;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;

/**
 * Class for attaching and detaching comments from groups.
 *
 * @package Drupal\group_comment
 */
class GroupCommentAttachment implements GroupCommentAttachmentInterface {

  /**
   * Group enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupEnabler;

  /**
   * Group content storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected $groupContentStorage;

  /**
   * The comment storage service.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * The comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * GroupCommentAttachment constructor.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $group_enabler_manager
   *   Group content enabler plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(GroupContentEnablerManagerInterface $group_enabler_manager, EntityTypeManagerInterface $entity_type_manager, CommentManagerInterface $comment_manager, ModuleHandlerInterface $module_handler) {
    $this->groupEnabler = $group_enabler_manager;
    $this->groupContentStorage = $entity_type_manager->getStorage('group_content');
    $this->commentStorage = $entity_type_manager->getStorage('comment');
    $this->commentManager = $comment_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(EntityInterface $entity): void {
    $comments = $this->getComments($entity);
    if (empty($comments)) {
      return;
    }

    // Store plugins by group type.
    /** @var \Drupal\group\Plugin\GroupContentEnablerCollection $plugins_by_group_type[] */
    $plugins_by_group_type = [];

    // Process the comments.
    foreach ($comments as $comment) {
      // Get commented entity.
      $commented_entity = $comment->getCommentedEntity();
      if (!$commented_entity instanceof ContentEntityInterface) {
        continue;
      }

      // Get groups of the commented entity.
      $groups = [];
      if (!isset($groups[$commented_entity->id()])) {
        $groups[$commented_entity->id()] = CommentedEntityHelper::getGroupsByEntity($commented_entity);
      }

      // Allow other modules to alter.
      $this->moduleHandler->alter('group_comment_attach_groups', $groups, $comment, $commented_entity);

      // Get groups of the commented entity.
      if ($commented_entity_groups = $groups[$commented_entity->id()]) {
        // Build the instance ID.
        $instance_id = 'group_comment:' . $comment->bundle();

        /** @var \Drupal\group\Entity\GroupInterface $group */
        foreach ($commented_entity_groups as $group) {
          if (!isset($plugins_by_group_type[$group->bundle()])) {
            // Get installed group content plugins by the type of the group.
            $plugins_by_group_type[$group->bundle()] = $this->groupEnabler
              ->getInstalled($group->getGroupType());
          }

          // Check if the group type supports the plugin of type $instance_id.
          if ($plugins_by_group_type[$group->bundle()]->has($instance_id)) {
            // We don't have to check the group cardinality and entity
            // cardinality because they are fixed.
            // @see \Drupal\group_comment\Plugin\GroupContentEnabler\GroupComment::buildConfigurationForm
            // As the entity cardinality is 1, we should make sure the comment
            // is not yet in the group.
            $group_relations = $group->getContentByEntityId($instance_id, $comment->id());
            if (empty($group_relations)) {
              $group->addContent($comment, $instance_id);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function detach(GroupContentInterface $entity): void {
    $comments = $this->getComments($entity);

    if (empty($comments)) {
      return;
    }

    // Detach comments from the group.
    foreach ($comments as $comment) {
      $group_contents = $this->groupContentStorage->loadByEntity($comment);

      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      foreach ($group_contents as $group_content) {
        if ($group_content->getGroup()->id() === $entity->getGroup()->id()) {
          $group_content->delete();
        }
      }
    }
  }

  /**
   * Gets comments.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to check.
   *
   * @return \Drupal\comment\CommentInterface[]
   *   Comments.
   */
  protected function getComments(EntityInterface $entity): array {
    $comments = [];

    if ($entity instanceof CommentInterface) {
      // If the entity is a comment entity, return it.
      $comments[] = $entity;
    }
    elseif ($entity instanceof GroupContentInterface) {
      // If the entity is group_content (relation) entity, check whether the
      // entity of the relation is commentable. If yes, get all comments of the
      // entity.
      if ($target_entity = $entity->getEntity()) {
        $fields = $this->commentManager->getFields($target_entity->getEntityTypeId());
        // When $fields is not empty, the entity is commentable.
        if (!empty($fields)) {
          // Get all comments by commentable entity id and type.
          $comments_query = $this->commentStorage->getQuery()->accessCheck(FALSE);
          $comments_query->condition('entity_id', (int) $entity->getEntity()->id());
          $comments_query->condition('entity_type', $entity->getEntity()->getEntityTypeId());
          $comments = $this->commentStorage->loadMultiple($comments_query->execute());
        }
      }
    }

    return $comments;
  }

}
