<?php

namespace Drupal\arch_product\Access;

use Drupal\arch_product\Entity\ProductTypeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to for product add pages.
 *
 * @ingroup product_access
 */
class ProductAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the product add page for the product type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $product_type
   *   (optional) The product type. If not specified, access is allowed if there
   *   exists at least one product type for which the user may create a product.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, ProductTypeInterface $product_type = NULL) {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('product');
    // If checking whether a product of a particular type may be created.
    if ($account->hasPermission('administer product types')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($product_type) {
      return $access_control_handler->createAccess($product_type->id(), $account, [], TRUE);
    }
    // If checking whether a product of any type may be created.
    foreach ($this->entityTypeManager->getStorage('product_type')->loadMultiple() as $product_type) {
      if (($access = $access_control_handler->createAccess($product_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
