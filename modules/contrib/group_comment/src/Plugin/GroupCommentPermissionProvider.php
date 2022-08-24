<?php

declare(strict_types = 1);

namespace Drupal\group_comment\Plugin;

use Drupal\group\Plugin\GroupContentPermissionProvider;

/**
 * Provides group permissions for group_comment GroupContent entities.
 */
class GroupCommentPermissionProvider extends GroupContentPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function getRelationCreatePermission() {
    // Cannot add an existing comment entity to the group through the UI.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationUpdatePermission($scope = 'any') {
    // Cannot update comment relation through the UI.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationDeletePermission($scope = 'any') {
    // Cannot delete comment relation through the UI.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityViewUnpublishedPermission($scope = 'any') {
    // The "view $scope unpublished $this->pluginId entity" won't work for
    // comment entity. This is because of the implementation of drupal core.
    // @see \Drupal\comment\CommentStorage::loadThread
    // @todo remove this method when https://www.drupal.org/project/drupal/issues/2980951
    //   is in.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitySkipCommentApprovalPermission() {
    if ($this->definesEntityPermissions) {
      return "skip comment approval $this->pluginId entity";
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = parent::buildPermissions();

    // Provide permission for skipping comment approval.
    $prefix = 'Entity:';
    if ($name = $this->getEntitySkipCommentApprovalPermission()) {
      $permissions[$name] = [
        'title' => "$prefix Skip comment approval",
      ];
    }

    return $permissions;
  }

}
