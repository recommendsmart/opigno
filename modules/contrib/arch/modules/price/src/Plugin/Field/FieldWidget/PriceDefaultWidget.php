<?php

namespace Drupal\arch_price\Plugin\Field\FieldWidget;

use Drupal\arch_price\Manager\PriceTypeManagerInterface;
use Drupal\arch_price\Manager\VatCategoryManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'price_default' widget.
 *
 * @FieldWidget(
 *   id = "price_default",
 *   label = @Translation("Price default", context = "arch_price__field_widget"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Price type manager.
   *
   * @var \Drupal\arch_price\Manager\PriceTypeManagerInterface
   */
  protected $priceTypeManager;

  /**
   * VAT category manager.
   *
   * @var \Drupal\arch_price\Manager\VatCategoryManagerInterface
   */
  protected $vatCategoryManager;

  /**
   * Currency storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Price type select options.
   *
   * @var array
   */
  protected $priceTypeOptions;

  /**
   * VAT category select options.
   *
   * @var array
   */
  protected $vatCategoryOptions;

  /**
   * Currency select options.
   *
   * @var array
   */
  protected $currencyOptions;

  /**
   * Constructs an PriceDefaultWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\arch_price\Manager\PriceTypeManagerInterface $price_type_manager
   *   The price type manager.
   * @param \Drupal\arch_price\Manager\VatCategoryManagerInterface $vat_category_manager
   *   The VAT category manager.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage
   *   Currency entity storage.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    PriceTypeManagerInterface $price_type_manager,
    VatCategoryManagerInterface $vat_category_manager,
    ConfigEntityStorageInterface $currency_storage
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings
    );

    $this->priceTypeManager = $price_type_manager;
    $this->vatCategoryManager = $vat_category_manager;
    $this->currencyStorage = $currency_storage;
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
      $configuration['third_party_settings'],
      $container->get('price_type.manager'),
      $container->get('vat_category.manager'),
      $container->get('entity_type.manager')->getStorage('currency')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $raw_value = [];
    if ($items->get($delta)) {
      /** @var \Drupal\arch_price\Plugin\Field\FieldType\PriceFieldItemList $items */
      $raw_value = $items->get($delta)->getValue();
    }
    $value = $this->massageValue($raw_value, FALSE);

    $field_name = $items->getFieldDefinition()->getName();
    $parents = array_merge($element['#field_parents'], [$field_name, $delta]);

    $element['base'] = [
      '#type' => 'select',
      '#title' => $this->t('Calculation base', [], ['context' => 'arch_price']),
      '#options' => [
        'net' => $this->t('Net', [], ['context' => 'arch_price_calc_base']),
        'gross' => $this->t('Gross', [], ['context' => 'arch_price_calc_base']),
      ],
      '#required' => $element['#required'],
      '#default_value' => $value['base'],
      '#attributes' => [
        'data-price-widget--delta' => $delta,
        'data-price-widget--field' => 'base',
        'data-price-widget--original-value' => $value['base'],
        'data-price-widget--old-value' => $value['base'],
      ],
    ];

    $element['price_type'] = [
      '#type' => 'select',
      '#options' => $this->getPriceTypeOptions(),
      '#title' => $this->t('Price type', [], ['context' => 'arch_price']),
      '#required' => $element['#required'],
      '#default_value' => $value['price_type'],
      '#attributes' => [
        'data-price-widget--delta' => $delta,
        'data-price-widget--field' => 'price_type',
        'data-price-widget--original-value' => $value['price_type'],
        'data-price-widget--old-value' => $value['price_type'],
      ],
    ];

    $element['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency', [], ['context' => 'arch_price']),
      '#options' => $this->getCurrencyOptions(),
      '#required' => $element['#required'],
      '#default_value' => $value['currency'],
      '#attributes' => [
        'data-price-widget--delta' => $delta,
        'data-price-widget--field' => 'currency',
        'data-price-widget--original-value' => $value['currency'],
        'data-price-widget--old-value' => $value['currency'],
      ],
    ];

    $element['vat_category'] = [
      '#type' => 'select',
      '#options' => $this->getVatCategoryOptions(),
      '#title' => $this->t('VAT category', [], ['context' => 'arch_price']),
      '#required' => $element['#required'],
      '#default_value' => $value['vat_category'],
      '#attributes' => [
        'data-price-widget--delta' => $delta,
        'data-price-widget--field' => 'vat_category',
        'data-price-widget--original-value' => $value['vat_category'],
        'data-price-widget--old-value' => $value['vat_category'],
      ],
    ];

    $element['net'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#title' => $this->t('Net', [], ['context' => 'arch_price']),
      '#default_value' => $value['net'],
      '#states' => [
        'disabled' => [
          ':input[name="price[' . $delta . '][base]"]' => ['value' => 'gross'],
        ],
      ],
      '#attributes' => [
        'data-price-widget--delta' => $delta,
        'data-price-widget--field' => 'net',
        'data-price-widget--original-value' => $value['net'],
        'data-price-widget--old-value' => $value['net'],
      ],
    ];
    $element['gross'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#title' => $this->t('Gross', [], ['context' => 'arch_price']),
      '#default_value' => $value['gross'],
      '#states' => [
        'disabled' => [
          ':input[name="price[' . $delta . '][base]"]' => ['value' => 'net'],
        ],
      ],
      '#attributes' => [
        'data-price-widget--delta' => $delta,
        'data-price-widget--field' => 'gross',
        'data-price-widget--original-value' => $value['gross'],
        'data-price-widget--old-value' => $value['gross'],
      ],
    ];
    $element['vat_rate'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#title' => $this->t('VAT rate', [], ['context' => 'arch_price']),
      '#field_suffix' => '%',
      '#default_value' => $value['vat_rate'] ? ($value['vat_rate'] * 100) : NULL,
      '#states' => [
        '!disabled' => [
          ':input[name="price[' . $delta . '][vat_category]"]' => ['value' => 'custom'],
        ],
      ],
      '#attributes' => [
        'class' => [
          'price--value--vat-rate',
        ],
        'data-price-widget--delta' => $delta,
        'data-price-widget--field' => 'vat_rate',
        'data-price-widget--original-value' => $value['vat_rate'],
        'data-price-widget--old-value' => $value['vat_rate'],
      ],
    ];
    $element['vat_value'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#title' => $this->t('VAT value', [], ['context' => 'arch_price']),
      '#default_value' => $value['vat_value'],
      '#disabled' => TRUE,
      '#attributes' => [
        'data-price-widget--delta' => $delta,
        'data-price-widget--field' => 'vat_value',
        'data-price-widget--original-value' => $value['vat_value'],
        'data-price-widget--old-value' => $value['vat_value'],
      ],
    ];

    $element['date_limitation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Date limitation', [], ['context' => 'arch_price']),
      '#default_value' => FALSE,
    ];
    $element['dates'] = [
      '#type' => 'container',
      '#attributes' => [],
      '#tree' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="price[' . $delta . '][date_limitation]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="price[' . $delta . '][date_limitation]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['dates']['date_from'] = [
      '#title' => $this->t('Date from', [], ['context' => 'arch_price']),
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => DateTimeItemInterface::STORAGE_TIMEZONE,
      '#parents' => array_merge($parents, ['date_from']),
    ];
    if (isset($value['date_from'])) {
      $date = $items[$delta]->available_from;
      // The date was created and verified during field_load(), so it is safe to
      // use without further inspection.
      $date->setTimezone(new \DateTimeZone($element['dates']['date_from']['#date_timezone']));
      $element['dates']['date_from']['#default_value'] = $date;
      $element['date_limitation']['#default_value'] = TRUE;
    }

    $element['dates']['date_to'] = [
      '#title' => $this->t('Date to', [], ['context' => 'arch_price']),
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => DateTimeItemInterface::STORAGE_TIMEZONE,
      '#parents' => array_merge($parents, ['date_to']),
    ];
    if (isset($value['date_to'])) {
      $date = $items[$delta]->available_to;
      // The date was created and verified during field_load(), so it is safe to
      // use without further inspection.
      $date->setTimezone(new \DateTimeZone($element['dates']['date_to']['#date_timezone']));
      $element['dates']['date_to']['#default_value'] = $date;
      $element['date_limitation']['#default_value'] = TRUE;
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $output = parent::form($items, $form, $form_state, $get_delta);
    $output['widget']['#theme'] = 'price_form_table';
    $output['#attached']['library'][] = 'arch_price/price_widget';
    return $output;
  }

  /**
   * Get price type options.
   *
   * @return array
   *   Options list.
   */
  protected function getPriceTypeOptions() {
    if (!isset($this->priceTypeOptions)) {
      $this->priceTypeOptions = [];

      foreach ($this->priceTypeManager->getPriceTypes() as $type) {
        $this->priceTypeOptions[$type->id()] = $type->label();
      }
    }
    return $this->priceTypeOptions;
  }

  /**
   * Get VAT category options.
   *
   * @return array
   *   Options list.
   */
  protected function getVatCategoryOptions() {
    if (!isset($this->vatCategoryOptions)) {
      $this->vatCategoryOptions = [];

      foreach ($this->vatCategoryManager->getVatCategories() as $category) {
        $this->vatCategoryOptions[$category->id()] = $this->t('%label (%percent)', [
          '%label' => $category->label(),
          '%percent' => $category->getRatePercent() . '%',
        ], ['context' => 'arch_price']);
      }
    }
    return $this->vatCategoryOptions;
  }

  /**
   * Currency options.
   *
   * @return array
   *   Currency options.
   */
  protected function getCurrencyOptions() {
    if (!isset($this->currencyOptions)) {
      $this->currencyOptions = [];
      foreach ($this->currencyStorage->loadMultiple() as $currency) {
        if ($currency->id() == 'XXX') {
          continue;
        }

        /** @var \Drupal\currency\Entity\Currency $currency */
        $this->currencyOptions[$currency->id()] = $currency->id();
      }
    }
    return $this->currencyOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $return = [];
    foreach ($values as $value) {
      if ((string) $value['net'] === '' && (string) $value['gross'] === '') {
        continue;
      }

      $return[] = $this->massageValue($value, TRUE);
    }

    return $return;
  }

  /**
   * Massage value.
   *
   * @param array $value
   *   Values.
   * @param bool $for_storage
   *   Massage values for storage or for form elements.
   */
  protected function massageValue(array $value, $for_storage) {
    $value += [
      'base' => 'net',
      'price_type' => 'default',
      'currency' => NULL,
      'net' => NULL,
      'gross' => NULL,
      'vat_category' => 'default',
      'vat_rate' => NULL,
      'vat_value' => NULL,
      'date_from' => NULL,
      'date_to' => NULL,
    ];
    if ($value['vat_category'] == 'custom') {
      $raw_rate = (float) $value['vat_rate'];
      if ($for_storage) {
        $raw_rate = $raw_rate / 100;
      }
      $vat_rate = round($raw_rate, 4);
    }
    else {
      $vat_rate = $this->vatCategoryManager->getVatRate($value['vat_category']);
    }
    $value['vat_rate'] = $vat_rate;

    if ($value['base'] == 'net') {
      $value['net'] = round((float) $value['net'], 2);
      $value['gross'] = $value['net'] * (1 + $vat_rate);
    }
    elseif ($value['base'] == 'gross') {
      $value['gross'] = round((float) $value['gross'], 2);
      $value['net'] = $value['gross'] / (1 + $vat_rate);
    }
    $value['vat_value'] = $value['gross'] - $value['net'];

    if ($value['net'] == 0 && $value['gross'] == 0) {
      $value['net'] = NULL;
      $value['gross'] = NULL;
      $value['vat_rate'] = NULL;
      $value['vat_value'] = NULL;
    }

    foreach (['date_from', 'date_to'] as $prop) {
      if (!empty($value[$prop]) && $value[$prop] instanceof DrupalDateTime) {
        $date = $value[$prop];
        $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
        $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
        $value[$prop] = $date->format($format);
      }
    }

    return $value;
  }

}
