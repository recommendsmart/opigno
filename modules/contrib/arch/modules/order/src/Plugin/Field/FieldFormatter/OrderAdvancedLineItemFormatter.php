<?php

namespace Drupal\arch_order\Plugin\Field\FieldFormatter;

use Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\arch_price\Price\PriceFormatterInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\arch_payment\PaymentMethodManagerInterface;
use Drupal\arch_shipping\ShippingMethodManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'order_advanced_line_item_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "order_advanced_line_item_formatter",
 *   module = "arch_order",
 *   label = @Translation("Advanced line item", context = "arch_order"),
 *   field_types = {
 *     "order_line_item"
 *   }
 * )
 */
class OrderAdvancedLineItemFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Current language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $currentLanguage;

  /**
   * Amount formatter manager.
   *
   * @var \Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManagerInterface
   */
  protected $amountFormatter;

  /**
   * Price factory.
   *
   * @var \Drupal\arch_price\Price\PriceFactoryInterface
   */
  protected $priceFactory;

  /**
   * Price formatter.
   *
   * @var \Drupal\arch_price\Price\PriceFormatterInterface
   */
  protected $priceFormatter;

  /**
   * Amount formatter.
   *
   * @var \Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterInterface
   */
  protected $currencyIntl;

  /**
   * Current order object.
   *
   * @var \Drupal\arch_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Image style storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Image style options.
   *
   * @var string[][]
   */
  protected $imageStyleOptions;

  /**
   * Payment method manager.
   *
   * @var \Drupal\arch_payment\PaymentMethodManagerInterface
   */
  protected $paymentMethodManager;

  /**
   * Shipping method manager.
   *
   * @var \Drupal\arch_shipping\ShippingMethodManagerInterface
   */
  protected $shippingMethodManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    AmountFormatterManagerInterface $amount_formatter,
    PriceFactoryInterface $price_factory,
    PriceFormatterInterface $price_formatter,
    PaymentMethodManagerInterface $payment_method_manager,
    ShippingMethodManagerInterface $shipping_method_manager
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

    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->amountFormatter = $amount_formatter;
    $this->priceFactory = $price_factory;
    $this->priceFormatter = $price_formatter;

    $this->currentLanguage = $language_manager->getCurrentLanguage();
    $this->currencyIntl = $this->amountFormatter->createInstance('arch_price_currency_intl');
    $this->order = $this->routeMatch->getParameter('order');
    $this->imageStyleStorage = $entity_type_manager->getStorage('image_style');
    $this->paymentMethodManager = $payment_method_manager;
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
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('plugin.manager.currency.amount_formatter'),
      $container->get('price_factory'),
      $container->get('price_formatter'),
      $container->get('plugin.manager.payment_method'),
      $container->get('plugin.manager.shipping_method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'created' => FALSE,
      'quantity' => FALSE,
      'type' => FALSE,
      'product_id' => FALSE,
      'product_bundle' => FALSE,
      'price_net' => FALSE,
      'price_gross' => FALSE,
      'price_vat_rate' => FALSE,
      'price_vat_cat_name' => FALSE,
      'price_vat_amount' => FALSE,
      'calculated_net' => FALSE,
      'calculated_gross' => TRUE,
      'calculated_vat_rate' => FALSE,
      'calculated_vat_cat_name' => FALSE,
      'calculated_vat_amount' => FALSE,
      'reason_of_diff' => FALSE,
      'item_subtotal_net' => FALSE,
      'item_subtotal_gross' => FALSE,
      'data' => FALSE,
      'product_name' => TRUE,
      'product_name_link' => TRUE,
      'product_sku' => TRUE,
      'product_image' => TRUE,
      'product_image_style' => 'thumbnail',
      'quantity_precision' => 2,
      'quantity_unit_singular' => 'piece',
      'quantity_unit_plural' => 'pieces',
      'price_display_mode' => PriceInterface::FORMAT_GROSS,
      'price_display_label' => FALSE,
      'price_display_original_price' => FALSE,
      'price_display_vat_info' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['header'] = [
      '#type' => 'inline_template',
      '#template' => '<h3>{{ title }}</h3><p>{{ description }}</p>',
      '#context' => [
        'title' => $this->t('Display fields', [], ['context' => 'arch_line_item']),
        'description' => $this->t('Note that the sort order of fields can only be changed in template file. To do so, create a copy from <i>order--line-items--advanced.html.twig</i> file.'),
      ],
    ];

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface[] $properties */
    $properties = OrderLineItemFieldItem::propertyDefinitions($this->fieldDefinition);
    foreach ($properties as $field_name => $property) {
      $form[$field_name] = [
        '#type' => 'checkbox',
        '#title' => $property->getLabel(),
        '#default_value' => $this->getSetting($field_name),
      ];
    }

    $form['item_subtotal_net'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Item subtotal net', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('item_subtotal_net'),
    ];

    $form['item_subtotal_gross'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Item subtotal gross', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('item_subtotal_gross'),
    ];

    $form['product_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Product name', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('product_name'),
    ];

    $form['product_name_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Product name link to entity', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('product_name_link'),
    ];

    $form['product_sku'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Product SKU', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('product_sku'),
    ];

    $form['product_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Product image', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('product_image'),
    ];

    $form['hl'] = ['#markup' => '<br><hr></hr><br>'];

    $form['product_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style used for product image', [], ['context' => 'arch_line_item']),
      '#options' => $this->getImageStyleOptions(),
      '#default_value' => $this->getSetting('product_image_style'),
    ];

    $form['quantity_precision'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity precision', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('quantity_precision'),
      '#min' => 0,
      '#max' => 4,
      '#step' => 1,
      '#description' => $this->t('This will applies quantity precision in general for the order view.<br>If you want to set quantity precision individually, please implement<br><i>theme_preprocess_order__line_items__advanced</i> in your theme.'),
    ];

    $form['quantity_unit_singular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quantity unit: Singular', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('quantity_unit_singular'),
    ];

    $form['quantity_unit_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quantity unit: Plural', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('quantity_unit_plural'),
    ];

    $form['price_display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Price display setting: Mode', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('price_display_mode'),
      '#options' => [
        PriceInterface::FORMAT_NET => $this->t('Net price', [], ['arch_price']),
        PriceInterface::FORMAT_GROSS => $this->t('Gross price', [], ['arch_price']),
      ],
    ];

    $form['price_display_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Price display setting: Show label', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('price_display_label'),
    ];

    $form['price_display_original_price'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Price display setting: Show original price', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('price_display_original_price'),
    ];

    $form['price_display_vat_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Price display setting: VAT Info', [], ['context' => 'arch_line_item']),
      '#default_value' => $this->getSetting('price_display_vat_info'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Displayed fields:');

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface[] $properties */
    $properties = OrderLineItemFieldItem::propertyDefinitions($this->fieldDefinition);
    foreach ($properties as $field_name => $property) {
      if ($this->getSetting($field_name)) {
        $summary[] = '- ' . $property->getLabel();
      }
    }

    if ($this->getSetting('product_name')) {
      $summary[] = '- ' . $this->t('Product name');
    }
    if ($this->getSetting('product_sku')) {
      $summary[] = '- ' . $this->t('Product SKU');
    }
    if ($this->getSetting('product_image')) {
      $summary[] = '- ' . $this->t('Product image');
    }
    if ($mode = $this->getSetting('price_display_mode')) {
      $summary[] = '- ' . $this->t('Display @mode price', ['@mode' => $mode]);
    }
    if ($this->getSetting('price_display_label')) {
      $summary[] = '- ' . $this->t('Display label for price');
    }
    if ($this->getSetting('price_display_original_price')) {
      $summary[] = '- ' . $this->t('Display original price');
    }
    if ($this->getSetting('price_display_vat_info')) {
      $summary[] = '- ' . $this->t('Display VAT info for price');
    }

    $summary[] = '';

    $summary[] = $this->t('Image style used for product image: @imagestyle', ['@imagestyle' => $this->getSetting('product_image_style')]);
    $summary[] = $this->t('Quantity precision: @precision', ['@precision' => $this->getSetting('quantity_precision')]);
    $summary[] = $this->t('Quantity unit: Singular: @text', ['@text' => $this->getSetting('quantity_unit_singular')]);
    $summary[] = $this->t('Quantity unit: Plural: @text', ['@text' => $this->getSetting('quantity_unit_plural')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $product_storage = $this->entityTypeManager->getStorage('product');

    if (empty($this->order)) {
      $this->order = $items->getEntity();
    }

    $currency_code = $this->order->currency->value;

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface[] $properties */
    $properties = OrderLineItemFieldItem::propertyDefinitions($this->fieldDefinition);

    $price_format = [
      'label' => $this->getSetting('price_display_label') == '1',
      'vat_info' => $this->getSetting('price_display_vat_info') == '1',
      'display_currency' => strtoupper($this->order->get('currency')->getString()),
    ];
    $price_display_mode = $this->getSetting('price_display_mode');

    $products = [];
    $rows = [];
    foreach ($items as $table) {
      $product = $product_storage->load($table->product_id);
      if (empty($product)) {
        if ($table->isShipping()) {
          $row = [];
          $shipping_methods = $this->shippingMethodManager->getAllShippingMethods();
          $row['line_item_name'] = $shipping_methods[$table->getValue()['product_bundle']]->getPluginDefinition()['label'];

          $price = $this->priceFactory->getInstance([
            'base' => $this->getSetting('price_display_mode'),
            $this->getSetting('price_display_mode') => $table->calculated_gross,
            'currency' => $currency_code,
          ]);
          $row['item_subtotal_gross'] = $this->buildPrice($price, $price_display_mode, $price_format);

          $rows[] = $row;
        }

        if ($table->isPaymentFee()) {
          $row = [];
          $payment_methods = $this->paymentMethodManager->getAllPaymentMethods();
          $row['line_item_name'] = $payment_methods[$table->getValue()['product_bundle']]->getPluginDefinition()['label'];

          $price = $this->priceFactory->getInstance([
            'base' => $this->getSetting('price_display_mode'),
            $this->getSetting('price_display_mode') => $table->calculated_gross,
            'currency' => $currency_code,
          ]);
          $row['item_subtotal_gross'] = $this->buildPrice($price, $price_display_mode, $price_format);

          $rows[] = $row;
        }

        continue;
      }
      $products[] = $product;

      $row = [];
      foreach ($properties as $field_name => $property) {
        if (empty($this->getSetting($field_name))) {
          continue;
        }

        $row[$field_name] = $table->{$field_name};

        if ($field_name == 'created') {
          $row[$field_name] = date('Y.m.d H:i:s', $row[$field_name]);
        }

        if (
          $property->getDataType() == 'float'
          && $field_name != 'quantity'
        ) {
          $original_price = NULL;
          if (
            $field_name == 'calculated_gross'
            && $this->getSetting('price_display_original_price') == '1'
            && ($table->calculated_gross != $table->price_gross)
          ) {
            $original_price = $this->priceFactory->getInstance([
              'base' => $this->getSetting('price_display_mode'),
              $this->getSetting('price_display_mode') => $table->price_gross,
              'currency' => $currency_code,
            ]);
            $row['original_price'] = $this->buildPrice($original_price, $price_display_mode, $price_format);
          }

          if (empty($original_price)) {
            $price = $this->priceFactory->getInstance([
              'base' => $this->getSetting('price_display_mode'),
              $this->getSetting('price_display_mode') => $row[$field_name],
              'currency' => $currency_code,
            ]);
            $row[$field_name] = $this->buildPrice($price, $price_display_mode, $price_format);
          }
          else {
            $price = $this->priceFactory->getModifiedPriceInstance([
              'base' => $this->getSetting('price_display_mode'),
              $this->getSetting('price_display_mode') => $row[$field_name],
              'currency' => $currency_code,
            ], $original_price);
            $row[$field_name] = $this->buildPrice(
              $price,
              $price_display_mode,
              $price_format + ['show_original' => TRUE]
            );
            $row[$field_name]['#original_price'] = FALSE;
            $row[$field_name]['#modified_price'] = TRUE;
            $row['original_price']['#has_modified_price'] = TRUE;

            if (!empty($row[$field_name]['#theme'])) {
              $price_build = $row[$field_name];
              $row[$field_name] = ['#type' => 'container'];
              $row[$field_name][$field_name] = $price_build;
              $row[$field_name]['original_price'] = $row['original_price'];
            }
          }
        }

        if ($field_name == 'quantity') {
          $number_formatted = number_format($row[$field_name], $this->getSetting('quantity_precision'));
          $row[$field_name] = $this->formatPlural(
            $number_formatted,
            '@count ' . $this->getSetting('quantity_unit_singular'),
            '@count ' . $this->getSetting('quantity_unit_plural'),
            ['@count' => $number_formatted]
          );
        }
      }

      if ($this->getSetting('product_sku')) {
        $row['product_sku'] = $product->get('sku')->getString();
      }

      if ($this->getSetting('product_name')) {
        $row['product_name'] = [
          '#type' => 'html_tag',
          '#tag' => 'label',
          '#value' => $product->label(),
        ];

        if ($this->getSetting('product_name_link')) {
          $row['product_name']['#value'] = $product->toLink(NULL, 'canonical', ['absolute' => TRUE])->toString();
        }
      }

      if ($this->getSetting('item_subtotal_net')) {
        $price = $this->priceFactory->getInstance([
          'base' => $this->getSetting('price_display_mode'),
          $this->getSetting('price_display_mode') => $table->calculated_gross * $table->quantity,
          'currency' => $currency_code,
        ]);
        $row['item_subtotal_net'] = $this->buildPrice($price, $price_display_mode, $price_format);
      }

      if ($this->getSetting('item_subtotal_gross')) {
        $price = $this->priceFactory->getInstance([
          'base' => $this->getSetting('price_display_mode'),
          $this->getSetting('price_display_mode') => $table->calculated_gross * $table->quantity,
          'currency' => $currency_code,
        ]);
        $row['item_subtotal_gross'] = $this->buildPrice($price, $price_display_mode, $price_format);
      }

      if (
        $this->getSetting('product_image')
        && $image_style = $this->getSetting('product_image_style')
          && $product->hasField('field_gallery')
          && !$product->get('field_gallery')->isEmpty()
      ) {
        try {
          /** @var \Drupal\media\Entity\Media $image_media */
          $image_media = $product->get('field_gallery')
            ->first()
            ->get('entity')
            ->getTarget()
            ->getValue('entity');

          $media_values = $image_media->get('field_media_image')->getValue()[0];
          /** @var \Drupal\file\FileInterface $image_file */
          $image_file = $this->entityTypeManager->getStorage('file')->load($media_values['target_id']);
          $row['product_image'] = [
            '#theme' => 'image_style',
            '#style_name' => (!empty($image_style) ? $this->getSetting('product_image_style') : 'thumbnail'),
            '#title' => $product->label(),
            '#alt' => $product->label(),
            '#uri' => $image_file->getFileUri(),
          ];
        }
        catch (\Exception $e) {
          // Do nothing. $image is NULL by default.
        }
      }

      $rows[] = $row;
    }

    $render_array = [
      '#theme' => 'order_line_items__advanced',
      '#rows' => $rows,
      '#products' => $products,
      '#order' => $this->order,
      '#url' => $this->order->toUrl('canonical', ['language' => $this->currentLanguage]),
    ];

    return [$render_array];
  }

  /**
   * Get image style options.
   *
   * @return string[][]
   *   Image style options.
   */
  protected function getImageStyleOptions() {
    if (!isset($this->imageStyleOptions)) {
      $options = [
        '' => $this->t('Default'),
      ];
      foreach ($this->imageStyleStorage->loadMultiple() as $image_style) {
        /** @var \Drupal\image\ImageStyleInterface $image_style */
        $options[$image_style->id()] = $image_style->label();
      }
      $this->imageStyleOptions = $options;
    }

    return $this->imageStyleOptions;
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

    $build['#original_price'] = TRUE;
    $build['#modified_price'] = FALSE;
    $build['#has_modified_price'] = FALSE;

    return $build;
  }

}
