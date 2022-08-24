<?php

declare(strict_types = 1);

namespace Drupal\group_comment\Plugin\GroupContentEnabler;

use Drupal\comment\CommentTypeInterface;
use Drupal\comment\Entity\CommentType;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for comment.
 *
 * @GroupContentEnabler(
 *   id = "group_comment",
 *   label = @Translation("Group comment"),
 *   description = @Translation("Adds comments to groups."),
 *   entity_type_id = "comment",
 *   entity_access = TRUE,
 *   deriver = "Drupal\group_comment\Plugin\GroupContentEnabler\GroupCommentDeriver",
 *   handlers = {
 *     "access" = "Drupal\group\Plugin\GroupContentAccessControlHandler",
 *     "permission_provider" = "Drupal\group_comment\Plugin\GroupCommentPermissionProvider",
 *   }
 * )
 */
class GroupComment extends GroupContentEnablerBase {

  /**
   * Retrieves the comment type this plugin supports.
   *
   * @return \Drupal\comment\CommentTypeInterface
   *   The comment type this plugin supports.
   */
  protected function getCommentType(): CommentTypeInterface {
    return CommentType::load($this->getEntityBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $description = '<br /><em>' . $info . '</em>';
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= $description;

    // Disable the group cardinality field as the functionality of this module
    // relies on a cardinality of 0. This is because, comments added to a
    // commented entity that is shared across multiple groups, should
    // automatically be attached to each group of the commented entity.
    // @see \Drupal\group_comment\AttachCommentToGroup::attach
    $form['group_cardinality']['#disabled'] = TRUE;
    $form['group_cardinality']['#description'] .= $description;

    // Disable use_creation_wizard field as users should not directly create
    // comments in groups. Instead, comments are attached to the group
    // automatically.
    // @see group_comment_entity_insert.
    $form['use_creation_wizard']['#disabled'] = TRUE;
    $form['use_creation_wizard']['#description'] .= $description;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $dependencies['config'][] = 'comment.type.' . $this->getEntityBundle();
    return $dependencies;
  }

}
