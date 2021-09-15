<?php

namespace Drupal\arch_payment\Plugin\Field\FieldFormatter;

use Drupal\arch_payment\PaymentMethodManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'payment_method' formatter.
 *
 * @FieldFormatter(
 *   id = "payment_method",
 *   label = @Translation("Payment method", context = "arch_payment"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class PaymentMethodFormatter extends StringFormatter {

  /**
   * The payment method manager.
   *
   * @var \Drupal\arch_payment\PaymentMethodManagerInterface
   */
  protected $paymentMethodManager;

  /**
   * Payment methods cache.
   *
   * @var \Drupal\arch_payment\PaymentMethodInterface[]
   */
  protected static $paymentMethodsCache;

  /**
   * Constructs an PaymentMethodFormatter instance.
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
   * @param \Drupal\arch_payment\PaymentMethodManagerInterface $payment_method_manager
   *   The payment method manager.
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
    PaymentMethodManagerInterface $payment_method_manager
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

    $this->paymentMethodManager = $payment_method_manager;
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
      $container->get('plugin.manager.payment_method')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    /** @var \Drupal\arch_order\Plugin\Field\FieldType\OrderStatusItem $item */
    $value = $item->getString();

    if (empty(self::$paymentMethodsCache)) {
      self::$paymentMethodsCache = $this->paymentMethodManager->getAllPaymentMethods();
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => (isset(self::$paymentMethodsCache[$value]) ? self::$paymentMethodsCache[$value]->getPluginDefinition()['label'] : $value),
      '#attributes' => [
        'class' => [
          'payment-method-type',
          'payment-method--' . Html::cleanCssIdentifier($value),
        ],
      ],
    ];
  }

}
