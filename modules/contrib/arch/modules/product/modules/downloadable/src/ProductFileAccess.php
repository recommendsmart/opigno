<?php

namespace Drupal\arch_downloadable_product;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Product file access check.
 *
 * @package Drupal\arch_downloadable_product
 */
class ProductFileAccess implements ProductFileAccessInterface, ContainerInjectionInterface {

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Product storage.
   *
   * @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface
   */
  protected $productStorage;

  /**
   * File storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Download url builder.
   *
   * @var \Drupal\arch_downloadable_product\DownloadUrlBuilderInterface
   */
  protected $downloadUrlBuilder;

  /**
   * ProductFileAccess constructor.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   Database.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\arch_downloadable_product\DownloadUrlBuilderInterface $download_url_builder
   *   Download url builder.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    Connection $db,
    EntityTypeManagerInterface $entity_type_manager,
    DownloadUrlBuilderInterface $download_url_builder
  ) {
    $this->db = $db;
    $this->productStorage = $entity_type_manager->getStorage('product');
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->downloadUrlBuilder = $download_url_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function check(ProductInterface $product, FileInterface $file, AccountInterface $user) {
    if (!$this->productHasFile($product, $file)) {
      return FALSE;
    }

    if (!$this->customerHasProduct($user, $product)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkWithToken(ProductInterface $product, FileInterface $file, AccountInterface $account, $token_to_check) {
    if (!$this->check($product, $file, $account)) {
      return FALSE;
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($account->id());
    $generated_token = $this->downloadUrlBuilder->getToken($product, $file, $user);
    return $token_to_check === $generated_token;
  }

  /**
   * {@inheritdoc}
   */
  public function checkByIds($product_id, $file_uuid, $user_uuid) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->productStorage->load($product_id);
    if (!$product) {
      return FALSE;
    }
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->loadFileByUuid($file_uuid);
    if (!$file) {
      return FALSE;
    }

    if (!$this->productHasFile($product, $file)) {
      return FALSE;
    }

    $user = $this->loadUserByUuid($user_uuid);
    if (!$user) {
      return FALSE;
    }

    if (!$this->customerHasProduct($user, $product)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkByIdsWithToken($product_id, $file_uuid, $user_uuid, $token_to_check) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->productStorage->load($product_id);
    if (!$product) {
      return FALSE;
    }
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->loadFileByUuid($file_uuid);
    if (!$file) {
      return FALSE;
    }

    if (!$this->productHasFile($product, $file)) {
      return FALSE;
    }

    $user = $this->loadUserByUuid($user_uuid);
    if (!$user) {
      return FALSE;
    }

    if (!$this->customerHasProduct($user, $product)) {
      return FALSE;
    }

    $generated_token = $this->downloadUrlBuilder->getToken($product, $file, $user);
    return $token_to_check === $generated_token;
  }

  /**
   * Load product by ID.
   *
   * @param int $pid
   *   Product ID.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   Product.
   */
  protected function loadProduct($pid) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->productStorage->load($pid);
    return $product;
  }

  /**
   * Load file by UUID.
   *
   * @param string $file_uuid
   *   File UUID.
   *
   * @return \Drupal\file\FileInterface|null
   *   File instance or NULL on failure.
   */
  protected function loadFileByUuid($file_uuid) {
    $files = $this->fileStorage->loadByProperties([
      'uuid' => $file_uuid,
    ]);
    if (empty($files)) {
      return NULL;
    }

    return current($files);
  }

  /**
   * Load user by UUID.
   *
   * @param string $user_uuid
   *   User UUID.
   *
   * @return \Drupal\user\UserInterface|null
   *   User instance or NULL on failure.
   */
  protected function loadUserByUuid($user_uuid) {
    $users = $this->userStorage->loadByProperties([
      'uuid' => $user_uuid,
    ]);
    if (empty($users)) {
      return NULL;
    }

    return current($users);
  }

  /**
   * Get file of product by File UUID.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   * @param string $file_uuid
   *   File UUID.
   *
   * @return \Drupal\file\FileInterface|null
   *   Selected file or NULL on failure.
   */
  protected function getProductFile(ProductInterface $product, $file_uuid) {
    $file = $this->loadFileByUuid($file_uuid);
    if ($this->productHasFile($product, $file)) {
      return $file;
    }

    return NULL;
  }

  /**
   * Check product has given file as "product_file".
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product instance.
   * @param \Drupal\file\FileInterface $file
   *   File instance.
   *
   * @return bool
   *   Return TRUE if product has file in its "product_file" field.
   */
  protected function productHasFile(ProductInterface $product, FileInterface $file) {
    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $files */
    $files = $product->product_file;

    foreach ($files->referencedEntities() as $referenced_file) {
      /** @var \Drupal\file\FileInterface $referenced_file */
      if ($referenced_file->id() == $file->id()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check customer already ordered given product.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Customer.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   *
   * @return bool
   *   Returns TRUE if customer purchased product.
   */
  protected function customerHasProduct(AccountInterface $account, ProductInterface $product) {
    if ($account->isAnonymous()) {
      return FALSE;
    }

    $select = $this->db->select('arch_order', 'o');
    $select->distinct(TRUE);
    $select->leftJoin('order__line_items', 'l', 'o.oid = l.entity_id');
    $select->condition('o.uid', $account->id());
    $select->condition('o.status', 'completed');
    $select->condition('l.line_items_product_id', $product->id());
    $select->addField('o', 'oid');
    $order_ids = $select->execute()->fetchCol();
    return !empty($order_ids);
  }

}
