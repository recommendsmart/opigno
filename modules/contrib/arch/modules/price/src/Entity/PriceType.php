<?php

namespace Drupal\arch_price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the price type entity.
 *
 * @ConfigEntityType(
 *   id = "price_type",
 *   label = @Translation("Price type", context = "arch_price"),
 *   label_singular = @Translation("price type", context = "arch_price"),
 *   label_plural = @Translation("price types", context = "arch_price"),
 *   label_collection = @Translation("Price types", context = "arch_price"),
 *   label_count = @PluralTranslation(
 *     singular = "@count price type",
 *     plural = "@count price types",
 *     context = "arch_price"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\arch_price\Entity\Storage\PriceTypeStorage",
 *     "list_builder" = "Drupal\arch_price\Entity\Builder\PriceTypeListBuilder",
 *     "access" = "Drupal\arch_price\Access\PriceTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\arch_price\Form\PriceTypeForm",
 *       "delete" = "Drupal\arch_price\Form\PriceTypeDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\arch_price\Entity\Routing\PriceTypeRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer prices",
 *   config_prefix = "price_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "collection" = "/admin/store/price/type",
 *     "add-form" = "/admin/store/price/type/add",
 *     "edit-form" = "/admin/store/price/type/{price_type}",
 *     "delete-form" = "/admin/store/price/type/{price_type}/delete",
 *   },
 *   config_export = {
 *     "name",
 *     "id",
 *     "description",
 *     "currency",
 *     "base",
 *     "vat_category",
 *     "weight",
 *     "locked",
 *   }
 * )
 */
class PriceType extends ConfigEntityBase implements PriceTypeInterface {

  /**
   * The price type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Name of the price type.
   *
   * @var string
   */
  protected $name;

  /**
   * Default currency code.
   *
   * @var string
   */
  protected $currency;

  /**
   * Default calculation base.
   *
   * @var string
   */
  protected $base;

  /**
   * Default VAT category ID.
   *
   * @var string
   */
  protected $vat_category;

  /**
   * Description of the price type.
   *
   * @var string
   */
  protected $description;

  /**
   * The weight of this price type in relation to other price types.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The locked status of this price type.
   *
   * @var bool
   */
  protected $locked = FALSE;

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
  public function getDefaultCurrency() {
    return $this->currency;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultVatCategory() {
    return $this->vat_category;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCalculationBase() {
    return $this->base;
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

    $price_types = [];
    foreach ($entities as $price_type) {
      $price_types[$price_type->id()] = $price_type->id();
    }
    // Load all price module fields and delete those which use only this
    // price type.
    $field_storages = \Drupal::entityTypeManager()
      ->getStorage('field_storage_config')
      ->loadByProperties(['module' => 'arch_price']);
    foreach ($field_storages as $field_storage) {
      $modified_storage = FALSE;
      // Price reference fields may reference prices from more than one
      // price type.
      foreach ((array) $field_storage->getSetting('allowed_values') as $key => $allowed_value) {
        if (isset($price_types[$allowed_value['price_type']])) {
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
