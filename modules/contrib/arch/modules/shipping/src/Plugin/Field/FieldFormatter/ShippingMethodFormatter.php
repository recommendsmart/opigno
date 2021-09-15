<?php

namespace Drupal\arch_shipping\Plugin\Field\FieldFormatter;

use Drupal\arch_shipping\ShippingMethodManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'shipping_method' formatter.
 *
 * @FieldFormatter(
 *   id = "shipping_method",
 *   label = @Translation("Shipping method", context = "arch_payment"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ShippingMethodFormatter extends StringFormatter {

  /**
   * The shipping method manager.
   *
   * @var \Drupal\arch_shipping\ShippingMethodManagerInterface
   */
  protected $shippingMethodManager;

  /**
   * Shipping methods cache.
   *
   * @var \Drupal\arch_shipping\ShippingMethodInterface
   */
  protected static $shippingMethodsCache;

  /**
   * Constructs an ShippingMethodFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\arch_shipping\ShippingMethodManagerInterface $shipping_method_manager
   *   The shipping method manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityTypeManagerInterface $entity_type_manager,
    ShippingMethodManagerInterface $shipping_method_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings,
      $entity_type_manager
    );

    $this->shippingMethodManager = $shipping_method_manager;
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
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.shipping_method')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    /** @var \Drupal\arch_order\Plugin\Field\FieldType\OrderStatusItem $item */
    $value = $item->getString();

    if (empty(self::$shippingMethodsCache)) {
      self::$shippingMethodsCache = $this->shippingMethodManager->getAllShippingMethods();
    }

    return [
      '#plain_text' => (isset(self::$shippingMethodsCache[$value]) ? self::$shippingMethodsCache[$value]->getPluginDefinition()['label'] : $value),
    ];
  }

}
