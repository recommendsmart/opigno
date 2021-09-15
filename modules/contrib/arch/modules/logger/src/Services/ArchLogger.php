<?php

namespace Drupal\arch_logger\Services;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Arch logger service.
 *
 * @package Drupal\arch_logger\Service
 */
class ArchLogger extends UserCacheContextBase {

  const TABLE_NAME = 'arch_log';
  const CART_STORE_NAME = 'cart_log';

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $store;

  /**
   * ArchLogger constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $store_factory
   *   Store.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Current user.
   */
  public function __construct(
    Connection $database,
    PrivateTempStoreFactory $store_factory,
    AccountInterface $user
  ) {
    parent::__construct($user);
    $this->store = $store_factory->get('arch_logger');
    $this->database = $database;
  }

  /**
   * Save log for cart.
   *
   * @param array $original
   *   Original cart object.
   * @param array $new
   *   New cart object.
   * @param string $message
   *   Log message.
   * @param array|null $data
   *   Log details.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function storeCartLog(array $original, array $new, $message, array $data = NULL) {
    $logs = $this->store->get(self::CART_STORE_NAME);
    if (!isset($logs)) {
      $logs = [];
    }

    if (!isset($data) && isset($original)) {
      $data = self::buildDefaultData($original, $new);
    }

    $logs[] = [
      'uid' => $this->user->id(),
      'status' => 'cart',
      'message' => $message,
      'data' => serialize($data),
      'created' => time(),
    ];

    $this->store->set(self::CART_STORE_NAME, $logs);
  }

  /**
   * Save cart log into database.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order_entity
   *   Order entity.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Exception
   */
  public function saveCartLogs(OrderInterface $order_entity) {
    $cart_logs = $this->store->get(self::CART_STORE_NAME);
    if (!empty($cart_logs)) {
      foreach ($cart_logs as $log) {
        $log['oid'] = $order_entity->id();
        $this->database->insert(self::TABLE_NAME)
          ->fields($log)
          ->execute();
      }
      $this->store->delete(self::CART_STORE_NAME);
    }
  }

  /**
   * Insert new log for order.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order_entity
   *   Order entity.
   * @param string $message
   *   Log message.
   * @param array|null $data
   *   Log details.
   *
   * @throws \Exception
   */
  public function insert(OrderInterface $order_entity, $message, array $data = NULL) {
    if (!isset($data) && isset($order_entity->original)) {
      $data = self::buildDefaultData(
        $order_entity->original->toArray(),
        $order_entity->toArray()
      );
    }

    $this->database->insert(self::TABLE_NAME)
      ->fields([
        'uid' => $this->user->id(),
        'oid' => $order_entity->id(),
        'status' => $order_entity->get('status')->getString(),
        'message' => $message,
        'data' => serialize($data),
        'created' => time(),
      ])
      ->execute();
  }

  /**
   * Get log records by order entity.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order_entity
   *   Order entity.
   *
   * @return null|array
   *   Logs.
   */
  public function getByOrder(OrderInterface $order_entity) {
    if (!$order_entity) {
      return NULL;
    }

    $query = $this->database->select(self::TABLE_NAME, 't');
    $query->fields('t');
    $query->condition('oid', $order_entity->id());

    return $query->execute()->fetchAll();
  }

  /**
   * Get log records by order entity and log ID.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order_entity
   *   Order entity.
   * @param int $lid
   *   Log ID.
   *
   * @return null|array
   *   Logs.
   */
  public function getByOrderAndId(OrderInterface $order_entity, $lid) {
    $lid = (int) $lid;
    if (!$order_entity || empty($lid)) {
      return NULL;
    }

    $query = $this->database->select(self::TABLE_NAME, 't');
    $query->fields('t');
    $query->condition('lid', $lid);
    $query->condition('oid', $order_entity->id());

    return $query->execute()->fetch();
  }

  /**
   * Build default data field value.
   *
   * @param array $original
   *   Original content.
   * @param array $new
   *   New content.
   *
   * @return null|array
   *   Default data array.
   */
  private static function buildDefaultData(array $original, array $new) {
    self::removeIgnoredKeysFromOrderArray($original);
    self::removeIgnoredKeysFromOrderArray($new);

    return [
      'old_values' => self::arrayDiff($original, $new),
      'new_values' => self::arrayDiff($new, $original),
    ];
  }

  /**
   * Get differences between multidimensional arrays.
   *
   * @param array $array1
   *   First array.
   * @param array $array2
   *   Second array.
   *
   * @return array
   *   Diff array.
   */
  private static function arrayDiff(array $array1, array $array2) {
    $result = [];

    foreach ($array1 as $key => $val) {
      if (is_array($val) && isset($array2[$key])) {
        $tmp = self::arrayDiff($val, $array2[$key]);
        if (!empty($tmp)) {
          $result[$key] = $tmp;
        }
      }
      elseif (!isset($array2[$key])) {
        $result[$key] = NULL;
      }
      elseif ($val !== $array2[$key]) {
        $result[$key] = $array2[$key];
      }

      if (isset($array2[$key])) {
        unset($array2[$key]);
      }
    }

    $result = array_merge($result, $array2);

    return $result;
  }

  /**
   * Remove ignored keys from order array.
   *
   * @param array $order_array
   *   Order entity in array.
   */
  private static function removeIgnoredKeysFromOrderArray(array &$order_array) {
    $ignored_keys = [
      'vid',
      'revision_timestamp',
      'created',
      'changed',
    ];

    foreach ($ignored_keys as $key) {
      if (isset($order_array[$key])) {
        unset($order_array[$key]);
      }
    }
  }

}
