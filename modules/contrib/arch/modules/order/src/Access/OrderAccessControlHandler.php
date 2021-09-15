<?php

namespace Drupal\arch_order\Access;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the order entity type.
 *
 * @package Drupal\arch_order\Access
 *
 * @ingroup order_access
 */
class OrderAccessControlHandler extends EntityAccessControlHandler implements OrderAccessControlHandlerInterface, EntityHandlerInterface {

  /**
   * The order grant storage.
   *
   * @var \Drupal\arch_order\Access\OrderGrantDatabaseStorageInterface
   */
  protected $grantStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    OrderGrantDatabaseStorageInterface $grant_storage
  ) {
    parent::__construct($entity_type);
    $this->grantStorage = $grant_storage;
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
      $container->get('order.grant_storage')
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
    $account = $this->prepareUser($account);

    if ($account->hasPermission('bypass order access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    if (!$account->hasPermission('view order')) {
      $result = AccessResult::forbidden("The 'view order' permission is required.")->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    $result = parent::access($entity, $operation, $account, TRUE)->cachePerPermissions();

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(
    $entity_bundle = NULL,
    AccountInterface $account = NULL,
    array $context = [],
    $return_as_object = FALSE
  ) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('bypass order access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    if (!$account->hasPermission('view order')) {
      $result = AccessResult::forbidden()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $result = parent::createAccess($entity_bundle, $account, $context, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(
    EntityInterface $order,
    $operation,
    AccountInterface $account
  ) {
    /** @var \Drupal\arch_order\Entity\OrderInterface $order */
    // Fetch information from the order object if possible.
    $uid = $order->getOwnerId();

    if (
      $operation === 'view'
      && $account->isAuthenticated()
      && $account->id() == $uid
    ) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->cachePerUser()
        ->addCacheableDependency($order);
    }

    // Evaluate order grants.
    return $this->grantStorage->access($order, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(
    AccountInterface $account,
    array $context,
    $entity_bundle = NULL
  ) {
    $result = $account->hasPermission('create order');
    return AccessResult::allowedIf($result)->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess(
    $operation,
    FieldDefinitionInterface $field_definition,
    AccountInterface $account,
    FieldItemListInterface $items = NULL
  ) {
    // Only users with the administer orders permission can edit
    // administrative fields.
    $administrative_fields = [
      'uid',
      'status',
      'created',
      'promote',
      'sticky',
    ];
    if (
      $operation == 'edit'
      && in_array($field_definition->getName(), $administrative_fields, TRUE)
    ) {
      return AccessResult::allowedIfHasPermission($account, 'administer orders');
    }

    // No user can change read only fields.
    $read_only_fields = [
      'revision_timestamp',
      'revision_uid',
    ];
    if (
      $operation == 'edit'
      && in_array($field_definition->getName(), $read_only_fields, TRUE)
    ) {
      return AccessResult::forbidden();
    }

    // Users have access to the revision_log field either if they have
    // administrative permissions or if the new revision option is enabled.
    if (
      $operation == 'edit'
      && $field_definition->getName() == 'revision_log'
    ) {
      if ($account->hasPermission('administer orders')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      return AccessResult::allowedIf($items->getEntity()->type->entity->isNewRevision())->cachePerPermissions();
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function acquireGrants(OrderInterface $order) {
    $grants = $this->moduleHandler->invokeAll('order_access_records', [$order]);
    // Let modules alter the grants.
    $this->moduleHandler->alter('order_access_records', $grants, $order);
    if (empty($grants)) {
      $grants[] = [
        'realm' => 'all',
        'gid' => 0,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];
    }
    return $grants;
  }

  /**
   * {@inheritdoc}
   */
  public function writeGrants(OrderInterface $order, $delete = TRUE) {
    $grants = $this->acquireGrants($order);
    $this->grantStorage->write($order, $grants, NULL, $delete);
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefaultGrant() {
    $this->grantStorage->writeDefault();
  }

  /**
   * {@inheritdoc}
   */
  public function countGrants() {
    return $this->grantStorage->count();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAllGrants(AccountInterface $account) {
    return $this->grantStorage->checkAll($account);
  }

}
