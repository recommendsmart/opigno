<?php

namespace Drupal\arch_stock\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the warehouse entity.
 *
 * @ConfigEntityType(
 *   id = "warehouse",
 *   label = @Translation("Warehouse", context = "arch_stock"),
 *   label_singular = @Translation("warehouse", context = "arch_stock"),
 *   label_plural = @Translation("warehouses", context = "arch_stock"),
 *   label_collection = @Translation("Warehouses", context = "arch_stock"),
 *   label_count = @PluralTranslation(
 *     singular = "@count warehouse",
 *     plural = "@count warehouses",
 *     context = "arch_stock"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\arch_stock\Entity\Storage\WarehouseStorage",
 *     "list_builder" = "Drupal\arch_stock\Entity\Builder\WarehouseListBuilder",
 *     "access" = "Drupal\arch_stock\Access\WarehouseAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\arch_stock\Form\WarehouseForm",
 *       "delete" = "Drupal\arch_stock\Form\WarehouseDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\arch_stock\Entity\Routing\WarehouseRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer stock",
 *   config_prefix = "warehouse",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "collection" = "/admin/store/stock/warehouse",
 *     "add-form" = "/admin/store/stock/warehouse/add",
 *     "edit-form" = "/admin/store/stock/warehouse/{warehouse}",
 *     "delete-form" = "/admin/store/stock/warehouse/{warehouse}/delete",
 *   },
 *   config_export = {
 *     "name",
 *     "id",
 *     "description",
 *     "weight",
 *     "allow_negative",
 *     "overbooked_availability",
 *     "locked",
 *   }
 * )
 */
class Warehouse extends ConfigEntityBase implements WarehouseInterface {

  /**
   * The ID of warehouse.
   *
   * @var string
   */
  protected $id;

  /**
   * Name of the warehouse.
   *
   * @var string
   */
  protected $name;

  /**
   * Description of the warehouse.
   *
   * @var string
   */
  protected $description;

  /**
   * Allow negative stock.
   *
   * @var bool
   */
  protected $allow_negative;

  /**
   * New availability value when overbooked.
   *
   * @var string
   */
  protected $overbooked_availability;

  /**
   * The weight of this warehouse in relation to other warehouses.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The locked status of this warehouse.
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
  public function allowNegative() {
    return (bool) $this->allow_negative;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverBookedAvailability() {
    if (!$this->allowNegative()) {
      return NULL;
    }

    return $this->overbooked_availability ? $this->overbooked_availability : NULL;
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

    $warehouses = [];
    foreach ($entities as $warehouse) {
      $warehouses[$warehouse->id()] = $warehouse->id();
    }
    // Load all stock module fields and delete those which use only this
    // warehouse.
    $field_storages = \Drupal::entityTypeManager()
      ->getStorage('field_storage_config')
      ->loadByProperties(['module' => 'arch_stock']);
    foreach ($field_storages as $field_storage) {
      $modified_storage = FALSE;
      // Warehouse reference fields may reference stocks from more than one
      // warehouse.
      foreach ((array) $field_storage->getSetting('allowed_values') as $key => $allowed_value) {
        if (isset($warehouses[$allowed_value['warehouse']])) {
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
