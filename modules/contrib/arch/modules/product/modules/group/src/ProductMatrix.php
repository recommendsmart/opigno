<?php

namespace Drupal\arch_product_group;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Product matrix service.
 *
 * @package Drupal\arch_product_group
 */
class ProductMatrix implements ProductMatrixInterface, ContainerInjectionInterface {

  /**
   * Group handler.
   *
   * @var \Drupal\arch_product_group\GroupHandlerInterface
   */
  protected $groupHandler;

  /**
   * Product storage.
   *
   * @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface
   */
  protected $productStorage;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * Group info runtime cache.
   *
   * @var array
   */
  protected $groupInfo = [];

  /**
   * ProductMatrix constructor.
   *
   * @param \Drupal\arch_product_group\GroupHandlerInterface $group_handler
   *   Group handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    GroupHandlerInterface $group_handler,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    ModuleHandlerInterface $module_handler,
    LanguageManagerInterface $language_manager
  ) {
    $this->groupHandler = $group_handler;
    $this->productStorage = $entity_type_manager->getStorage('product');
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('product_group.handler'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldValue($field_name, ProductInterface $product, AccountInterface $account = NULL) {
    $info = $this->buildInfo($product, $account);
    if (!empty($info['product_values'][$product->id()][$field_name])) {
      return $info['product_values'][$product->id()][$field_name];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldValueMatrix(array $fields, ProductInterface $product, AccountInterface $account = NULL) {
    $info = $this->buildInfo($product, $account);
    $fields = array_values(array_unique($fields));
    $field_names = $fields;
    sort($field_names);

    if (
      empty($info)
      || empty($field_names)
    ) {
      return [];
    }

    return array_filter($info['field_values'], function ($item, $key) use ($fields) {
      return in_array($key, $fields);
    }, ARRAY_FILTER_USE_BOTH);
  }

  /**
   * Collect every group info.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Customer.
   *
   * @return array
   *   Product group info.
   */
  protected function buildInfo(ProductInterface $product, AccountInterface $account = NULL) {
    if (!$this->groupHandler->isPartOfGroup($product)) {
      return [];
    }

    $group_id = $this->groupHandler->getGroupId($product);
    if (empty($group_id)) {
      return [];
    }

    if (!isset($account)) {
      $account = $this->currentUser;
    }

    $cid = $group_id . ':' . $account->id();
    if (!empty($this->groupInfo[$cid])) {
      return $this->groupInfo[$cid];
    }

    $fields = array_keys($product->getFields());
    $field_names = $fields;
    sort($field_names);

    /** @var \Drupal\arch_product\Entity\ProductInterface[] $group */
    $group = array_filter($this->groupHandler->getGroupProducts($product), function (ProductInterface $group_item) use ($account) {
      return $group_item->availableForSell($account);
    });
    $group_id = $this->groupHandler->getGroupId($product);
    $info = [];

    $keys = array_fill_keys(array_keys($group), FALSE);

    foreach ($group as $group_item) {
      foreach ($field_names as $field_name) {
        $info['matrix'][$field_name][$group_item->id()] = [];
        if (!$group_item->hasField($field_name)) {
          continue;
        }

        $field = $group_item->get($field_name);
        if ($field->isEmpty()) {
          continue;
        }

        $key = 'product:' . $group_item->id();

        $field_values = $field->getValue();
        $info['fields'][$field_name][$key] = [
          'product_id' => $group_item->id(),
          'group_id' => $group_id,
          'field_name' => $field_name,
          'value' => [],
        ];

        $main_property_name = $field->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
        foreach ($field_values as $delta => $field_value) {
          if (!array_key_exists($main_property_name, $field_value)) {
            continue;
          }
          $value = $field_value[$main_property_name];

          $info['matrix'][$field_name][$value][$group_item->id()] = $group_item->id();

          $info['fields'][$field_name][$key]['value'][$delta] = [
            $main_property_name => $value,
          ];

          if (!isset($info['values'][$field_name][$value])) {
            $info['field_values'][$field_name][$value] = $keys;
            $info['values'][$field_name . ':' . $value] = $keys;
          }
          $info['field_values'][$field_name][$value][$group_item->id()] = $group_item->id();
          $info['values'][$field_name . ':' . $value][$group_item->id()] = $group_item->id();

          $info['product_values'][$group_item->id()][$field_name] = $value;
        }
      }
    }

    $this->groupInfo[$cid] = $info;
    return $info;
  }

}
