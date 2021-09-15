<?php

namespace Drupal\arch_payment;

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
 * Payment method base.
 *
 * @package Drupal\arch_payment
 */
abstract class PaymentMethodBase extends PluginBase implements PaymentMethodInterface {

  /**
   * Payment method settings.
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
   * Plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Price factory.
   *
   * @var \Drupal\arch_price\Price\PriceFactoryInterface
   */
  protected $priceFactory;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   Key value factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_factory
   *   Plugin form factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\arch_price\Price\PriceFactoryInterface $priceFactory
   *   Price factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    KeyValueFactoryInterface $key_value_factory,
    ModuleHandlerInterface $module_handler,
    PluginFormFactoryInterface $plugin_form_factory,
    AccountInterface $current_user,
    PriceFactoryInterface $priceFactory
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->settings = $key_value_factory->get('arch_payment.' . $this->getPluginId());
    $this->moduleHandler = $module_handler;
    $this->pluginFormFactory = $plugin_form_factory;
    $this->currentUser = $current_user;
    $this->priceFactory = $priceFactory;
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
    return $this->settings->get('status', TRUE);
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
  public function isAvailable(OrderInterface $order) {
    if (!$this->isActive()) {
      return FALSE;
    }

    // @todo Check this.
    $result = AccessResult::neutral();
    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $access = $this->moduleHandler->invokeAll('payment_method_access', [
      $this,
      $order,
      $this->currentUser,
    ]);
    foreach ($access as $other) {
      $result = $result->orIf($other);
    }
    return !$result->isForbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getCallbackRoute() {
    $definition = $this->getPluginDefinition();
    return $definition['callback_route'];
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

  /**
   * {@inheritdoc}
   */
  public function getPaymentFee(OrderInterface $order) {
    $price_default = [
      'currency' => $order->get('currency')->getString(),
      'base' => 'gross',
      'net' => 0,
      'gross' => 0,
      'vat_category' => 'custom',
      'vat_rate' => 0,
      'vat_value' => 0,
    ];

    $fees = $this->settings->get('fees');
    if (
      !empty($fees)
      && isset($fees['default'])
      && (
        isset($fees['default'])
        && !empty($fees['default']['fee'])
      )
    ) {
      $default = $fees['default'];

      $price_default = [
        'base' => 'gross',
        'price_type' => 'default',
        'currency' => $default['currency'],
        'net' => 0,
        'gross' => (float) $default['fee'],
        'vat_category' => 'custom',
        'vat_rate' => ((float) $default['vat_rate'] / 100),
        'vat_value' => 0,
        'date_from' => NULL,
        'date_to' => NULL,
      ];
    }
    $price = $this->priceFactory->getInstance($price_default);

    $context = [
      'settings' => $this->getSettings(),
      'plugin_id' => $this->getPluginId(),
    ];
    $this->moduleHandler->alter('payment_method_fee', $order, $price, $context);

    return $price;
  }

}
