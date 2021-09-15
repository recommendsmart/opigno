<?php

namespace Drupal\arch_product_group;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group handler.
 *
 * @package Drupal\arch_product_group
 */
class GroupHandler implements GroupHandlerInterface, ContainerInjectionInterface {

  /**
   * Database connection.
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
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Product cache.
   *
   * @var \Drupal\arch_product\Entity\ProductInterface[][]
   */
  protected $cache = [];

  /**
   * GroupHandler constructor.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   Database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    Connection $db,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    LanguageManagerInterface $language_manager
  ) {
    $this->db = $db;
    $this->productStorage = $entity_type_manager->getStorage('product');
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isPartOfGroup(ProductInterface $product) {
    $group_id = $this->getGroupIdValue($product);
    if (empty($group_id)) {
      return FALSE;
    }

    $members = $this->findGroupMembers($group_id);
    return count($members) > 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isGroupParent(ProductInterface $product) {
    if (!$this->isPartOfGroup($product)) {
      return FALSE;
    }

    return (int) $product->id() === $this->getGroupIdValue($product);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId(ProductInterface $product) {
    if (!$this->isPartOfGroup($product)) {
      return FALSE;
    }
    return $this->getGroupIdValue($product);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupParent(ProductInterface $product) {
    if (!$this->isPartOfGroup($product)) {
      return NULL;
    }

    if ($this->isGroupParent($product)) {
      return $product;
    }

    $group_id = $this->getGroupIdValue($product);
    /** @var \Drupal\arch_product\Entity\ProductInterface $parent */
    $parent = $this->productStorage->load($group_id);
    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupProducts(ProductInterface $product) {
    if ($group_id = $this->getGroupId($product)) {
      return $this->findGroupMembers($group_id);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function createGroup(array $products, $group_id = NULL) {
    if (empty($products)) {
      throw new \InvalidArgumentException('Empty product list');
    }

    foreach ($products as $product) {
      if ($product instanceof ProductInterface) {
        continue;
      }

      throw new \InvalidArgumentException('Only Product entities allowed.');
    }

    if (empty($group_id)) {
      $group_id = current($products)->id();
    }

    try {
      // @todo Check this!
      $this->db->startTransaction('product_group_create' . $group_id);
      foreach ($products as $product) {
        $product->set('group_id', $group_id)->save();
      }
      return TRUE;
    }
    catch (\Exception $e) {
      $this->db->rollBack('product_group_create' . $group_id);
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeFromGroup(ProductInterface $product, $group_id) {
    $current_group = $this->getGroupIdValue($product);
    if ($current_group !== (int) $group_id) {
      // @todo maybe we should throw an exception.
      return FALSE;
    }

    return (bool) $product->set('group_id', $product->id())->save();
  }

  /**
   * {@inheritdoc}
   */
  public function leaveGroup(ProductInterface $product) {
    if ($this->isGroupParent($product)) {
      throw new \LogicException('Parent group could not leave group!');
    }

    if (!$this->isPartOfGroup($product)) {
      return TRUE;
    }

    return (bool) $product->set('group_id', $product->id())->save();
  }

  /**
   * {@inheritdoc}
   */
  public function addToGroup(ProductInterface $product, $group_id) {
    $current_group = $this->getGroupId($product);
    if ($current_group === (int) $group_id) {
      return TRUE;
    }

    if ($this->isPartOfGroup($product)) {
      // @todo maybe we should throw exception.
      // @todo Deny move of product between groups.
      return FALSE;
    }

    return (bool) $product->set('group_id', $group_id)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function dismissGroup($group_id) {
    $products = $this->findGroupMembers($group_id);
    if (empty($products)) {
      return TRUE;
    }

    try {
      // @todo Check this!
      $this->db->startTransaction('product_group_dismiss' . $group_id);
      foreach ($products as $product) {
        if ((int) $product->id() === (int) $group_id) {
          continue;
        }
        $this->removeFromGroup($product, $group_id);
      }
      return TRUE;
    }
    catch (\Exception $e) {
      $this->db->rollBack('product_group_dismiss' . $group_id);
    }

    return FALSE;
  }

  /**
   * Get group ID value from product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return int
   *   Stored group ID.
   */
  protected function getGroupIdValue(ProductInterface $product) {
    $group_id = (int) $product->get('group_id')->value;
    return $group_id ?: $product->id();
  }

  /**
   * Find members of group.
   *
   * @param int $group_id
   *   Group ID.
   * @param bool $reread
   *   Force reread DB.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface[]
   *   List of group members.
   */
  protected function findGroupMembers($group_id, $reread = FALSE) {
    if ($reread || !isset($this->cache[$group_id])) {
      $this->cache[$group_id] = $this->productStorage->loadByProperties([
        'group_id' => $group_id,
      ]);

      $current_language_id = $this->languageManager->getCurrentLanguage()->getId();
      foreach ($this->cache[$group_id] as $pid => $product) {
        if (
          $product->language()->getId() != $current_language_id
          && $product->hasTranslation($current_language_id)
        ) {
          $this->cache[$group_id][$pid] = $product->getTranslation($current_language_id);
        }
      }
    }

    return $this->cache[$group_id];
  }

}
