<?php

namespace Drupal\arch_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Product type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "product_type",
 *   label = @Translation("Product type", context = "arch_product"),
 *   handlers = {
 *     "access" = "Drupal\arch_product\Access\ProductTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\arch_product\Form\ProductTypeForm",
 *       "edit" = "Drupal\arch_product\Form\ProductTypeForm",
 *       "delete" = "Drupal\arch_product\Form\ProductTypeDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\arch_product\Entity\Builder\ProductTypeListBuilder",
 *   },
 *   admin_permission = "administer product types",
 *   config_prefix = "type",
 *   bundle_of = "product",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/store/product-types/manage/{product_type}",
 *     "delete-form" = "/admin/store/product-types/manage/{product_type}/delete",
 *     "collection" = "/admin/store/product-types",
 *   },
 *   config_export = {
 *     "name",
 *     "type",
 *     "description",
 *     "help",
 *     "new_revision",
 *     "preview_mode",
 *   }
 * )
 */
class ProductType extends ConfigEntityBundleBase implements ProductTypeInterface {

  /**
   * The machine name of this product type.
   *
   * @var string
   */
  protected $type;

  /**
   * The human-readable name of the product type.
   *
   * @var string
   */
  protected $name;

  /**
   * A brief description of this product type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating a Product of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * Default value of the 'Create new revision' checkbox of this product type.
   *
   * @var bool
   */
  protected $newRevision = TRUE;

  /**
   * The preview mode.
   *
   * @var int
   */
  protected $previewMode = DRUPAL_OPTIONAL;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('product.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($newRevision) {
    $this->newRevision = $newRevision;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewMode() {
    return $this->previewMode;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviewMode($previewMode) {
    $this->previewMode = $previewMode;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id()) {
      $update_count = product_type_update_products($this->getOriginalId(), $this->id());
      if ($update_count) {
        \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural($update_count,
          'Changed the product type of 1 product from %old-type to %type.',
          'Changed the product type of @count products from %old-type to %type.',
          [
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          ]));
      }
    }
    if ($update) {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      $this->entityFieldManager()->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the product type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->newRevision;
  }

  /**
   * Entity field manager service.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   Entity field manager.
   */
  protected function entityFieldManager() {
    return \Drupal::service('entity_field.manager');
  }

}
