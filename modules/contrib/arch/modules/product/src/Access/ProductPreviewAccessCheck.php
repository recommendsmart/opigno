<?php

namespace Drupal\arch_product\Access;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to product previews.
 *
 * @ingroup product_access
 */
class ProductPreviewAccessCheck implements AccessInterface {

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
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the product preview page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\arch_product\Entity\ProductInterface $product_preview
   *   The product that is being previewed.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, ProductInterface $product_preview) {
    if ($product_preview->isNew()) {
      $access_controller = $this->entityTypeManager->getAccessControlHandler('product');
      return $access_controller->createAccess($product_preview->bundle(), $account, [], TRUE);
    }
    else {
      return $product_preview->access('update', $account, TRUE);
    }
  }

}
