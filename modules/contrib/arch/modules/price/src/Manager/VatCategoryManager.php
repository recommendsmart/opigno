<?php

namespace Drupal\arch_price\Manager;

use Drupal\arch_price\Entity\VatCategory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * VAT category manager.
 *
 * @package Drupal\arch_price\Manager
 */
class VatCategoryManager implements VatCategoryManagerInterface, ContainerInjectionInterface {

  /**
   * Defined categories.
   *
   * @var \Drupal\arch_price\Entity\VatCategoryInterface[]
   */
  protected $categories;

  /**
   * Settings.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $settings;

  /**
   * Default VAT category ID.
   *
   * @var string
   */
  protected $defaultVatCategory;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * VatCategoryManager constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   Key value store factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(
    KeyValueFactoryInterface $key_value_factory,
    ConfigFactoryInterface $config_factory
  ) {
    $this->settings = $key_value_factory->get('arch_price.settings');
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue'),
      $container->get('config.factory')
    );
  }

  /**
   * Get default VAT category.
   *
   * @return \Drupal\arch_price\Entity\VatCategoryInterface
   *   Default VAT category.
   */
  public function getDefaultVatCategory() {
    if (!isset($this->defaultVatCategory)) {
      $this->getVatCategories();
      $default = $this->settings->get('default_vat_category', 'default');
      $this->defaultVatCategory = !empty($this->categories[$default]) ? $default : 'default';
    }
    return $this->categories[$this->defaultVatCategory];
  }

  /**
   * List of defined VAT categories.
   *
   * @return \Drupal\arch_price\Entity\VatCategoryInterface[]
   *   VAT category list.
   */
  public function getVatCategories() {
    if (!isset($this->categories)) {
      $config_ids = $this->configFactory->listAll('arch_price.vat_category.');
      foreach ($this->configFactory->loadMultiple($config_ids) as $config) {
        $data = $config->get();
        $categories[$data['id']] = VatCategory::create($data);
      }

      uasort($categories, '\Drupal\arch_price\Entity\VatCategory::sort');
      $this->categories = $categories;
    }
    return $this->categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getVatCategoryListForWidget() {
    $skipp_fields = [
      'uuid',
      'langcode',
      'dependencies',
      'locked',
      'status',
      'description',
    ];
    $categories = [];
    foreach ($this->getVatCategories() as $vat_category) {
      if (!$vat_category->status()) {
        continue;
      }

      $category = $vat_category->toArray();
      foreach ($skipp_fields as $field) {
        unset($category[$field]);
      }
      $category['name'] = (string) $vat_category->getName();
      $categories[$vat_category->id()] = $category;
    }
    return $categories;
  }

  /**
   * Get VAT category.
   *
   * @param string $id
   *   VAT category ID.
   *
   * @return null|\Drupal\arch_price\Entity\VatCategoryInterface
   *   VAT category or NULL.
   */
  public function getVatCategory($id) {
    $categories = $this->getVatCategories();
    return isset($categories[$id]) ? $categories[$id] : NULL;
  }

  /**
   * Get rate for VAT category.
   *
   * @param string $id
   *   VAT category ID.
   *
   * @return float
   *   Rate.
   */
  public function getVatRate($id) {
    $category = $this->getVatCategory($id);
    if (!$category) {
      return 0;
    }
    return $category->getRate();
  }

  /**
   * Get percent rate for VAT category.
   *
   * @param string $id
   *   VAT category ID.
   *
   * @return float
   *   Rate percent.
   */
  public function getVatRatePercent($id) {
    $category = $this->getVatCategory($id);
    if (!$category) {
      return 0;
    }
    return $category->getRatePercent();
  }

}
