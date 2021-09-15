<?php

namespace Drupal\arch_order\Services;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Order status service.
 *
 * @package Drupal\arch_order\Services
 */
class OrderStatusService implements OrderStatusServiceInterface, ContainerInjectionInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * Order status storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|mixed|object
   */
  private $orderStatusStorage;

  /**
   * Array of order status entities.
   *
   * @var \Drupal\arch_order\Services\OrderStatusServiceInterface[]
   */
  private $orderStatuses;

  /**
   * Constructs an OrderStatusService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity Type Manager object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->orderStatusStorage = $this->entityTypeManager->getStorage('order_status');

    $this->orderStatuses = $this->orderStatusStorage->loadMultiple();
    uasort(
      $this->orderStatuses,
      ['\Drupal\arch_order\Entity\OrderStatus', 'sort']
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load($orderStatus) {
    $orderStatus = Xss::filter($orderStatus);
    return $this->orderStatusStorage->load($orderStatus);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOrderStatus() {
    $statuses = $this->orderStatusStorage->loadByProperties(['default' => TRUE]);
    if (!empty($statuses)) {
      return current($statuses);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderStatuses($locked = self::ALL) {
    if ($locked === self::LOCKED || $locked === self::UNLOCKED) {
      $statuses = $this->orderStatusStorage->loadByProperties(['locked' => ($locked === self::LOCKED)]);
      uasort($statuses, ['\Drupal\arch_order\Entity\OrderStatus', 'sort']);
      return $statuses;
    }

    return $this->orderStatuses;
  }

}
