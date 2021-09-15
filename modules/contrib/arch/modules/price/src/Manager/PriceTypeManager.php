<?php

namespace Drupal\arch_price\Manager;

use Drupal\arch_price\Entity\PriceType;
use Drupal\arch_price\Entity\PriceTypeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Price type manager.
 *
 * @package Drupal\arch_price\Manager
 */
class PriceTypeManager implements PriceTypeManagerInterface, ContainerInjectionInterface {

  /**
   * Defined type.
   *
   * @var \Drupal\arch_price\Entity\PriceTypeInterface[]
   */
  protected $types;

  /**
   * Settings.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $settings;

  /**
   * Default price type ID.
   *
   * @var string
   */
  protected $defaultPriceType;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * PriceTypeManager constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   Key value store factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    KeyValueFactoryInterface $key_value_factory,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->settings = $key_value_factory->get('arch_price.settings');
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue'),
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * Get default price type.
   *
   * @return \Drupal\arch_price\Entity\PriceTypeInterface
   *   Default price type.
   */
  public function getDefaultPriceType() {
    if (!isset($this->defaultPriceType)) {
      $this->getPriceTypes();
      $default = $this->settings->get('default_price_type', 'default');
      $this->defaultPriceType = !empty($this->types[$default]) ? $default : 'default';
    }
    return $this->types[$this->defaultPriceType];
  }

  /**
   * List of defined price types.
   *
   * @return \Drupal\arch_price\Entity\PriceTypeInterface[]
   *   Price type list.
   */
  public function getPriceTypes() {
    if (!isset($this->types)) {
      $config_ids = $this->configFactory->listAll('arch_price.price_type.');
      foreach ($this->configFactory->loadMultiple($config_ids) as $config) {
        $data = $config->get();
        $types[$data['id']] = PriceType::create($data);
      }

      uasort($types, '\Drupal\arch_price\Entity\PriceType::sort');
      $this->types = $types;
    }
    return $this->types;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeListForWidget() {
    $skipp_fields = [
      'uuid',
      'langcode',
      'dependencies',
      'locked',
      'status',
      'description',
    ];
    $price_types = [];
    foreach ($this->getPriceTypes() as $price_type) {
      if (!$price_type->status()) {
        continue;
      }

      $type = $price_type->toArray();
      foreach ($skipp_fields as $field) {
        unset($type[$field]);
      }
      $price_types[$price_type->id()] = $type;
    }

    return $price_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailablePriceTypes(AccountInterface $account, $operation = 'view') {
    $price_types = array_filter($this->getPriceTypes(), function (PriceTypeInterface $price_type) use ($account, $operation) {
      $type_access = $price_type->access($operation, $account, TRUE);
      return !$type_access->isForbidden();
    });

    $this->moduleHandler->alter('arch_available_price_types', $price_types, $account, $operation);
    return $price_types;
  }

}
