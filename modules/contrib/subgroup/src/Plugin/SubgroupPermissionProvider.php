<?php

namespace Drupal\subgroup\Plugin;

use Drupal\group\Plugin\GroupContentPermissionProvider;

/**
 * Provides group permissions for subgroup GroupContent entities.
 */
class SubgroupPermissionProvider extends GroupContentPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function getRelationDeletePermission($scope = 'any') {
    // A subgroup cannot be put into the global scope.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationCreatePermission() {
    // Cannot add existing groups as subgroups through the UI.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityViewPermission($scope = 'any') {
    // Handled by the subgroup's permission layer.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityViewUnpublishedPermission($scope = 'any') {
    // Handled by the subgroup's permission layer.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityUpdatePermission($scope = 'any') {
    // Handled by the subgroup's permission layer.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityDeletePermission($scope = 'any') {
    // Handled by the subgroup's permission layer.
    return FALSE;
  }

}
