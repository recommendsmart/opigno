<?php

namespace Drupal\arch_order\Services;

use Drupal\arch_order\OrderAddressDataInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Order address service.
 *
 * @package Drupal\arch_addressbook\Services
 */
class OrderAddressService implements ContainerInjectionInterface, OrderAddressServiceInterface {

  use LoggerChannelTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new ProductRevisionDeleteForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(
    Connection $connection
  ) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function insertAddress($type, OrderAddressDataInterface $data) {
    $data->setAddressType($type);
    $insertion = $this->connection->insert(OrderAddressServiceInterface::TABLE_ORDER_ADDRESS);
    $insertion->fields($data->toArray());
    return (bool) $insertion->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateAddress(OrderAddressDataInterface $data) {
    if (!$data->getAddressType()) {
      throw new \InvalidArgumentException('Cannot update address without type!');
    }
    if (!$data->getOrderId()) {
      throw new \InvalidArgumentException('Cannot update address without order ID!');
    }

    $update = $this->connection->update(OrderAddressServiceInterface::TABLE_ORDER_ADDRESS);
    $fields = $data->toArray();
    unset($fields['order_id']);
    unset($fields['address_type']);
    $update->condition('order_id', $data->getOrderId());
    $update->condition('address_type', $data->getAddressType());
    $update->fields($fields);
    return (bool) $update->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getByType($orderId, $type = OrderAddressServiceInterface::TYPE_BILLING) {
    $query = $this->connection->select(OrderAddressServiceInterface::TABLE_ORDER_ADDRESS, 'oa');
    $query->fields('oa');
    $query->condition('order_id', Xss::filter($orderId));
    $query->condition('address_type', Xss::filter($type));
    $execute = $query->execute();
    $execute->setFetchMode(\PDO::FETCH_CLASS, '\Drupal\arch_order\OrderAddressData');
    return $execute->fetch();
  }

  /**
   * {@inheritdoc}
   */
  public function getAddresses($orderId) {
    $query = $this->connection->select(OrderAddressServiceInterface::TABLE_ORDER_ADDRESS, 'oa');
    $query->fields('oa');
    $query->condition('order_id', Xss::filter($orderId));
    return $query->execute()->fetchAll(\PDO::FETCH_CLASS, '\Drupal\arch_order\OrderAddressData');
  }

}
