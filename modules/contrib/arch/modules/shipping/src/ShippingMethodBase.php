<?php

namespace Drupal\arch_shipping;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * InStore shipping method.
 *
 * @package Drupal\arch_shipping_instore\Plugin\ShippingMethod
 */
abstract class ShippingMethodBase extends PluginBase implements ShippingMethodInterface {

  /**
   * Shipping method settings.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $settings;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * Price factory.
   *
   * @var \Drupal\arch_price\Price\PriceFactoryInterface
   */
  protected $priceFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    KeyValueFactoryInterface $key_value_factory,
    ModuleHandlerInterface $module_handler,
    PluginFormFactoryInterface $plugin_form_factory,
    AccountInterface $current_user,
    PriceFactoryInterface $price_factory
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->settings = $key_value_factory->get('arch_shipping.' . $this->getPluginId());
    $this->moduleHandler = $module_handler;
    $this->pluginFormFactory = $plugin_form_factory;
    $this->currentUser = $current_user;
    $this->priceFactory = $price_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('keyvalue'),
      $container->get('module_handler'),
      $container->get('plugin_form.factory'),
      $container->get('current_user'),
      $container->get('price_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel() {
    $definition = $this->getPluginDefinition();
    if (!empty($definition['administrative_label'])) {
      return (string) $definition['administrative_label'];
    }

    return $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    $definition = $this->getPluginDefinition();
    return (string) $definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->settings->get('status', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->settings->set('status', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->settings->set('status', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->settings->get('weight', 0);
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->settings->set('weight', (int) $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getImage() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDescription() {
    $description = (string) $this->getDescription();
    return !empty($description);
  }

  /**
   * {@inheritdoc}
   */
  public function hasImage() {
    $image = $this->getImage();
    if (empty($image)) {
      return FALSE;
    }

    // @todo check this.
    return $image->isValid();
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailableForAddress($address) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(OrderInterface $order) {
    if (!$this->isActive()) {
      return FALSE;
    }

    // @todo Check this.
    $result = AccessResult::neutral();
    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $access = $this->moduleHandler->invokeAll('shipping_method_access', [
      $this,
      $order,
      $this->currentUser,
    ]);
    foreach ($access as $other) {
      $result = $result->orIf($other);
    }
    $result->setCacheMaxAge(-1);
    return !$result->isForbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings->getAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key, $default = NULL) {
    return $this->settings->get($key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($key, $value) {
    $this->settings->set($key, $value);
    return $this;
  }

}
