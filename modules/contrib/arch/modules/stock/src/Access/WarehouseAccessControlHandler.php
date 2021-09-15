<?php

namespace Drupal\arch_stock\Access;

use Drupal\arch_stock\Entity\WarehouseInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the warehouse entity type.
 *
 * @see \Drupal\arch_stock\Entity\Warehouse
 */
class WarehouseAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    RoleStorageInterface $role_storage
  ) {
    parent::__construct($entity_type);
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage('user_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(
    EntityInterface $entity,
    $operation,
    AccountInterface $account = NULL,
    $return_as_object = FALSE
  ) {
    /** @var \Drupal\arch_stock\Entity\WarehouseInterface $entity */
    $account = $this->prepareUser($account);

    if ($operation === 'delete' && $entity->isLocked()) {
      $result = AccessResult::forbidden('The warehouse config entity is locked.')
        ->addCacheableDependency($entity);
      return $return_as_object ? $result : $result->isAllowed();
    }

    if ($operation !== 'view' && $account->hasPermission('administer stock')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    return parent::access($entity, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\arch_stock\Entity\WarehouseInterface $entity */
    if ($operation == 'view') {
      return $this->checkViewAccess($entity, $account);
    }
    elseif ($operation == 'delete') {
      if ($entity->isLocked()) {
        return AccessResult::forbidden('The warehouse config entity is locked.')
          ->addCacheableDependency($entity);
      }
      else {
        return parent::checkAccess($entity, $operation, $account)
          ->addCacheableDependency($entity);
      }
    }

    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * Performs view access check.
   *
   * @param \Drupal\arch_stock\Entity\WarehouseInterface $warehouse
   *   The price type for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkViewAccess(WarehouseInterface $warehouse, AccountInterface $account) {
    // Get list of roles without the "administrator" one.
    $roles = array_flip($account->getRoles());
    unset($roles['administrator']);
    $roles = array_flip($roles);

    $access = TRUE;
    $permissions = [
      "purchase from {$warehouse->id()} stock",
    ];
    foreach ($permissions as $permission) {
      if (!$this->accountHasPermission($roles, $permission)) {
        $access = FALSE;
        break;
      }
    }
    return AccessResult::forbiddenIf(!$access)->addCacheContexts(['user.permissions']);
  }

  /**
   * Returns whether a permission is in one of the passed in roles.
   *
   * @param array $roles
   *   The list of role IDs to check.
   * @param string $permission
   *   The permission.
   *
   * @return bool
   *   TRUE is the permission is in at least one of the roles. FALSE otherwise.
   */
  protected function accountHasPermission(array $roles, $permission) {
    return $this->roleStorage->isPermissionInRoles($permission, $roles);
  }

}
