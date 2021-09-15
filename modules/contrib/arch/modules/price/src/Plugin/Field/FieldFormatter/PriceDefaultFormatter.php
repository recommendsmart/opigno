<?php

namespace Drupal\arch_price\Plugin\Field\FieldFormatter;

use Drupal\arch_price\Negotiation\PriceNegotiation;
use Drupal\arch_price\Price\ModifiedPriceInterface;
use Drupal\arch_price\Price\PriceFormatterInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'price' formatter.
 *
 * @FieldFormatter(
 *   id = "price_default",
 *   label = @Translation("Price default", context = "arch_price__field_formatter"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Price formatter.
   *
   * @var \Drupal\arch_price\Price\PriceFormatterInterface
   */
  protected $priceFormatter;

  /**
   * Price negotiation.
   *
   * @var \Drupal\arch_price\Negotiation\PriceNegotiation
   */
  protected $priceNegotiation;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    PriceFormatterInterface $price_formatter,
    PriceNegotiation $price_negotiotion
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

    $this->priceFormatter = $price_formatter;
    $this->priceNegotiation = $price_negotiotion;
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
      $container->get('price_formatter'),
      $container->get('price.negotiation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => PriceInterface::MODE_NET_GROSS,
      'label' => FALSE,
      'vat_info' => FALSE,
      'show_original' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * Get view mode options.
   *
   * @return array
   *   View mode list.
   */
  protected function getViewModeOptions() {
    return [
      PriceInterface::MODE_NET => $this->t('Net', [], ['context' => 'arch_price']),
      PriceInterface::MODE_GROSS => $this->t('Gross', [], ['context' => 'arch_price']),
      PriceInterface::MODE_NET_GROSS => $this->t('Net + Gross', [], ['context' => 'arch_price']),
      PriceInterface::MODE_GROSS_NET => $this->t('Gross + Net', [], ['context' => 'arch_price']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->getViewModeOptions(),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];

    $elements['label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display price type label', [], ['context' => 'arch_price']),
      '#default_value' => $this->getSetting('label'),
    ];

    $elements['vat_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display VAT information', [], ['context' => 'arch_price']),
      '#default_value' => $this->getSetting('vat_info'),
    ];

    $elements['show_original'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show original price', [], ['context' => 'arch_price']),
      '#default_value' => $this->getSetting('show_original'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $view_modes = $this->getViewModeOptions();
    $view_mode = $this->getSetting('view_mode');
    $summary[] = $this->t('Rendered as @mode', ['@mode' => isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : $view_mode]);
    if ($this->getSetting('label')) {
      $summary[] = $this->t('Display price type label', [], ['context' => 'arch_price']);
    }
    else {
      $summary[] = $this->t('Without price type label', [], ['context' => 'arch_price']);
    }

    if ($this->getSetting('vat_info')) {
      $summary[] = $this->t('Display VAT information', [], ['context' => 'arch_price']);
    }
    else {
      $summary[] = $this->t('Without VAT information', [], ['context' => 'arch_price']);
    }

    if ($this->getSetting('show_original')) {
      $summary[] = $this->t('Display original price', [], ['context' => 'arch_price']);
    }
    else {
      $summary[] = $this->t('Without original price', [], ['context' => 'arch_price']);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if ($items->isEmpty()) {
      return [];
    }

    /** @var \Drupal\arch_price\Plugin\Field\FieldType\PriceFieldItemList $items */
    /** @var \Drupal\arch_price\Price\PriceInterface $price */
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $items->getEntity();
    $price = $this->priceNegotiation->getActivePrice($product);
    $mode = $this->getSetting('view_mode');
    $settings = [
      'label' => $this->getSetting('label'),
      'vat_info' => $this->getSetting('vat_info'),
      'show_original' => $this->getSetting('show_original'),
    ];

    $build = $this->buildPrice($price, $mode, $settings);
    $build['#original_price'] = TRUE;
    $build['#modified_price'] = FALSE;
    $build['#has_modified_price'] = FALSE;

    if (
      !empty($this->getSetting('show_original'))
      && $price instanceof ModifiedPriceInterface
    ) {
      $original_price = $price->getOriginalPrice();
      if ($original_price->getNetPrice() != $price->getNetPrice()) {
        $build_original_price = $this->buildPrice($original_price, $mode, $settings);
        $build_original_price['#original_price'] = TRUE;
        $build_original_price['#modified_price'] = FALSE;
        $build_original_price['#has_modified_price'] = TRUE;
        $build['#original_price'] = FALSE;
        $build['#modified_price'] = TRUE;
        if (!empty($build['#theme'])) {
          $price_build = $build;
          $build = [];
          $build[$mode] = $price_build;
        }
        $build['original_price'] = $build_original_price;
      }
    }

    return [
      $build,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPrice($price, $mode, $settings) {
    switch ($mode) {
      case PriceInterface::MODE_NET:
        $build = $this->priceFormatter->buildNet($price, $settings);
        break;

      case PriceInterface::MODE_GROSS:
        $build = $this->priceFormatter->buildGross($price, $settings);
        break;

      case PriceInterface::MODE_GROSS_NET:
      case PriceInterface::MODE_NET_GROSS:
      default:
        $build = $this->priceFormatter->buildFull($price, $settings);
        if ($mode === PriceInterface::MODE_GROSS_NET) {
          $build['gross']['#weight'] = 1;
          $build['net']['#weight'] = 2;
        }
        elseif ($mode === PriceInterface::MODE_NET_GROSS) {
          $build['net']['#weight'] = 1;
          $build['gross']['#weight'] = 2;
        }

        // Hide VAT information.
        $build['net']['#vat_info_display'] = FALSE;
        $build['gross']['#vat_info_display'] = FALSE;
        break;
    }
    return $build;
  }

}
