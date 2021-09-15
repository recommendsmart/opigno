<?php

namespace Drupal\arch_product\Access;

use Drupal\arch_product\Entity\ProductType;
use Drupal\arch_product\Entity\ProductTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides dynamic permissions for products of different types.
 */
class ProductPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of product type permissions.
   *
   * @return array
   *   The product type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function productTypePermissions() {
    $perms = [];
    // Generate product permissions for all product types.
    foreach (ProductType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of product permissions for a given product type.
   *
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $type
   *   The product type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ProductTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create {$type_id} product" => [
        'title' => $this->t('%type_name: Create new product', $type_params, ['context' => 'arch_product']),
      ],
      "edit own {$type_id} product" => [
        'title' => $this->t('%type_name: Edit own product', $type_params, ['context' => 'arch_product']),
      ],
      "edit any {$type_id} product" => [
        'title' => $this->t('%type_name: Edit any product', $type_params, ['context' => 'arch_product']),
      ],
      "delete own {$type_id} product" => [
        'title' => $this->t('%type_name: Delete own product', $type_params, ['context' => 'arch_product']),
      ],
      "delete any {$type_id} product" => [
        'title' => $this->t('%type_name: Delete any product', $type_params, ['context' => 'arch_product']),
      ],
      "view {$type_id} revisions" => [
        'title' => $this->t('%type_name: View revisions', $type_params, ['context' => 'arch_product']),
        'description' => $this->t('To view a revision, you also need permission to view the product item.', [], ['context' => 'arch_product']),
      ],
      "revert {$type_id} revisions" => [
        'title' => $this->t('%type_name: Revert revisions', $type_params, ['context' => 'arch_product']),
        'description' => $this->t('To revert a revision, you also need permission to edit the product item.', [], ['context' => 'arch_product']),
      ],
      "delete {$type_id} revisions" => [
        'title' => $this->t('%type_name: Delete revisions', $type_params, ['context' => 'arch_product']),
        'description' => $this->t('To delete a revision, you also need permission to delete the product item.', [], ['context' => 'arch_product']),
      ],
    ];
  }

}
