<?php

namespace Drupal\arch_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Order Status configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "order_status",
 *   label = @Translation("Order status", context = "arch_order"),
 *   label_collection = @Translation("Order statuses", context = "arch_order"),
 *   label_singular = @Translation("order status", context = "arch_order"),
 *   label_plural = @Translation("order statuses", context = "arch_order"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order status",
 *     plural = "@count order statuses",
 *     context = "arch_order"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\arch_order\Entity\Builder\OrderStatusListBuilder",
 *     "access" = "Drupal\arch_order\Access\OrderStatusAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\arch_order\Form\OrderStatusAddForm",
 *       "edit" = "Drupal\arch_order\Form\OrderStatusEditForm",
 *       "delete" = "Drupal\arch_order\Form\OrderStatusDeleteForm"
 *     }
 *   },
 *   links = {
 *     "delete-form" = "/admin/store/order-status/delete/{configurable_language}",
 *     "edit-form" = "/admin/store/order-status/edit/{configurable_language}",
 *     "collection" = "/admin/store/order-statuses",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "default" = "locked",
 *     "locked" = "locked",
 *     "description" = "description"
 *   },
 *   admin_permission = "administer order status configuration",
 *   list_cache_tags = { "rendered" },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "description",
 *     "default",
 *     "locked"
 *   }
 * )
 */
class OrderStatus extends ConfigEntityBase implements OrderStatusInterface {

  /**
   * The order status machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the order status entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the order status entity.
   *
   * @var string
   */
  protected $description;

  /**
   * The weight of the order status entity.
   *
   * @var string
   */
  protected $weight;

  /**
   * The default status of this order status.
   *
   * @var bool
   */
  protected $default = FALSE;

  /**
   * The locked status of this order status.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->weight;
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
  public function isLocked() {
    return (bool) $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsDefault() {
    return (bool) $this->default;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   *   Exception thrown if we're trying to delete the default order status
   *   entity. This is not allowed as a site must have a default order status.
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    $defaultOrderStatus = static::getDefaultOrderStatusId();
    foreach ($entities as $entity) {
      if ($entity->id() == $defaultOrderStatus && !$entity->isUninstalling()) {
        throw new \Exception('Can not delete the default order status');
      }
    }
  }

  /**
   * Gets the ID of the default order status.
   *
   * @return string
   *   The current default order status.
   */
  protected static function getDefaultOrderStatusId() {
    $orderStatus = \Drupal::service('order.statuses')->getDefaultOrderStatus();
    return $orderStatus->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    if ($a->weight == $b->weight) {
      $a_label = $a->label();
      $b_label = $b->label();
      return strnatcasecmp($a_label, $b_label);
    }
    return $a->weight > $b->weight ? 1 : -1;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return ['rendered'];
  }

}
