<?php

namespace Drupal\arch_order\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;

/**
 * Defines the 'order_status' data type.
 *
 * The plain value of a language is the language object, i.e. an instance of
 * \Drupal\Core\Language\Language. For setting the value the language object or
 * the language code as string may be passed.
 *
 * @DataType(
 *   id = "order_status",
 *   label = @Translation("Order status", context = "arch_order"),
 *   description = @Translation("An order status object.", context = "arch_order")
 * )
 */
class OrderStatus extends TypedData {

  /**
   * The id of the order status.
   *
   * @var string
   */
  protected $id;

  /**
   * Order Status entity.
   *
   * @var \Drupal\arch_order\Entity\OrderStatusInterface
   */
  protected $orderStatus;

  /**
   * Overrides TypedData::getValue().
   *
   * @return \Drupal\arch_order\Entity\OrderStatusInterface|null
   *   Order status entity, or NULL.
   */
  public function getValue() {
    if (!isset($this->orderStatus) && $this->id) {
      /** @var \Drupal\arch_order\Services\OrderStatusServiceInterface $order_statuses_service */
      $order_statuses_service = \Drupal::service('order.statuses');
      $this->orderStatus = $order_statuses_service->load($this->id);
    }
    return $this->orderStatus;
  }

  /**
   * Overrides TypedData::setValue().
   *
   * Both the order status code and the object may be passed as value.
   */
  public function setValue($value, $notify = TRUE) {
    // Support passing order_status objects.
    if (is_object($value)) {
      $this->id = $value->getId();
      $this->orderStatus = $value;
    }
    elseif (isset($value) && !is_scalar($value)) {
      throw new \InvalidArgumentException('Value is no valid order status code or order_status object.');
    }
    else {
      $this->id = $value;
      $this->orderStatus = NULL;
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    $order_status = $this->getValue();
    return $order_status ? $order_status->getLabel() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    if (isset($this->id)) {
      return $this->id;
    }
    elseif (isset($this->orderStatus)) {
      return $this->orderStatus->getId();
    }
  }

}
