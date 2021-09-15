<?php

namespace Drupal\arch_addressbook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the AddressBookItem entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class AddressbookitemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if (
      $account->hasPermission('administer addressbookitem entity')
      && $account->isAuthenticated()
    ) {
      return AccessResult::allowed();
    }

    $uid = $entity->getOwnerId();

    // Check if authors can view their own addressbookitem entity.
    if (
      $operation === 'view'
      && !$account->hasPermission('view addressbookitem entity')
      && $account->isAuthenticated()
      && $account->id() == $uid
    ) {
      return AccessResult::allowedIfHasPermission($account, 'view own addressbookitem entity')
        ->cachePerPermissions()
        ->cachePerUser()
        ->addCacheableDependency($entity);
    }

    // Check if authors can edit their own addressbookitem entity.
    if (
      $operation === 'edit'
      && !$account->hasPermission('edit addressbookitem entity')
      && $account->isAuthenticated()
      && $account->id() == $uid
    ) {
      return AccessResult::allowedIfHasPermission($account, 'edit own addressbookitem entity')
        ->cachePerPermissions()
        ->cachePerUser()
        ->addCacheableDependency($entity);
    }

    // Check if authors can delete their own addressbookitem entity.
    if (
      $operation === 'delete'
      && !$account->hasPermission('delete addressbookitem entity')
      && $account->isAuthenticated()
      && $account->id() == $uid
    ) {
      return AccessResult::allowedIfHasPermission($account, 'delete own addressbookitem entity')
        ->cachePerPermissions()
        ->cachePerUser()
        ->addCacheableDependency($entity);
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view addressbookitem entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit addressbookitem entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete addressbookitem entity');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add addressbookitem entity');
  }

}
