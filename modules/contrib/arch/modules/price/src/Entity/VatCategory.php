<?php

namespace Drupal\arch_price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the VAT category entity.
 *
 * @ConfigEntityType(
 *   id = "vat_category",
 *   label = @Translation("VAT category", context = "arch_price"),
 *   label_singular = @Translation("VAT category", context = "arch_price"),
 *   label_plural = @Translation("VAT categories", context = "arch_price"),
 *   label_collection = @Translation("VAT Category", context = "arch_price"),
 *   label_count = @PluralTranslation(
 *     singular = "@count VAT category",
 *     plural = "@count VAT categories",
 *     context = "arch_price"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\arch_price\Entity\Storage\VatCategoryStorage",
 *     "list_builder" = "Drupal\arch_price\Entity\Builder\VatCategoryListBuilder",
 *     "access" = "Drupal\arch_price\Access\VatCategoryAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\arch_price\Form\VatCategoryForm",
 *       "delete" = "Drupal\arch_price\Form\VatCategoryDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\arch_price\Entity\Routing\VatCategoryRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer prices",
 *   config_prefix = "vat_category",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "collection" = "/admin/store/price/vat-category",
 *     "add-form" = "/admin/store/price/vat-category/add",
 *     "edit-form" = "/admin/store/price/vat-category/{vat_category}",
 *     "delete-form" = "/admin/store/price/vat-category/{vat_category}/delete",
 *   },
 *   config_export = {
 *     "name",
 *     "id",
 *     "description",
 *     "rate",
 *     "weight",
 *     "custom",
 *     "locked",
 *   }
 * )
 */
class VatCategory extends ConfigEntityBase implements VatCategoryInterface {

  /**
   * The VAT category ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Name of the VAT category.
   *
   * @var string
   */
  protected $name;

  /**
   * Description of the VAT category.
   *
   * @var string
   */
  protected $description;

  /**
   * The weight of this VAT category in relation to other VAT categories.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The rate for this VAT category.
   *
   * @var float
   */
  protected $rate = 0.0;

  /**
   * The locked status of this VAT category.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * Custom rate.
   *
   * @var bool
   */
  protected $custom = FALSE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRate() {
    return round((float) $this->rate, 4);
  }

  /**
   * {@inheritdoc}
   */
  public function getRatePercent() {
    return round($this->getRate() * 100, 2);
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public function isCustom() {
    return $this->custom;
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
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Reset caches.
    $storage->resetCache(array_keys($entities));

    if (reset($entities)->isSyncing()) {
      return;
    }

    $vat_categories = [];
    foreach ($entities as $vat_category) {
      $vat_categories[$vat_category->id()] = $vat_category->id();
    }
    // Load all price module fields and delete those which use only this
    // VAT category.
    $field_storages = \Drupal::entityTypeManager()
      ->getStorage('field_storage_config')
      ->loadByProperties(['module' => 'arch_price']);
    foreach ($field_storages as $field_storage) {
      $modified_storage = FALSE;
      // Price reference fields may reference prices from more than one
      // price type.
      foreach ((array) $field_storage->getSetting('allowed_values') as $key => $allowed_value) {
        if (isset($vat_categories[$allowed_value['vat_category']])) {
          $allowed_values = $field_storage->getSetting('allowed_values');
          unset($allowed_values[$key]);
          $field_storage->setSetting('allowed_values', $allowed_values);
          $modified_storage = TRUE;
        }
      }
      if ($modified_storage) {
        $allowed_values = $field_storage->getSetting('allowed_values');
        if (empty($allowed_values)) {
          $field_storage->delete();
        }
        else {
          // Update the field definition with the new allowed values.
          $field_storage->save();
        }
      }
    }
  }

}
