<?php

namespace Drupal\arch_stock\Plugin\Field\FieldWidget;

use Drupal\arch_stock\Manager\WarehouseManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'stock_default' widget.
 *
 * @FieldWidget(
 *   id = "stock_default",
 *   label = @Translation("Stock default", context = "arch_stock__field_widget"),
 *   field_types = {
 *     "stock"
 *   }
 * )
 */
class StockDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Warehouse manager.
   *
   * @var \Drupal\arch_stock\Manager\WarehouseManagerInterface
   */
  protected $warehouseManager;

  /**
   * Warehouse select options.
   *
   * @var array
   */
  protected $warehouseOptions;

  /**
   * Constructs an StockDefaultWidget object.
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
   * @param \Drupal\arch_stock\Manager\WarehouseManagerInterface $warehouse_manager
   *   The warehouse manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    WarehouseManagerInterface $warehouse_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings
    );

    $this->warehouseManager = $warehouse_manager;

    // Fix cardinality the number of warehouses.
    $warehouse_count = count($this->warehouseManager->getWarehouses());
    $this->fieldDefinition->getFieldStorageDefinition()->setCardinality($warehouse_count);
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
      $container->get('warehouse.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = [];
    if ($items->get($delta)) {
      /** @var \Drupal\arch_stock\Plugin\Field\FieldType\StockFieldItemList $items */
      $value = $items->get($delta)->getValue();
    }
    $value = $this->massageValue($value);

    $element['#product_id'] = $items->getEntity()->id();
    $element['#item_value'] = $value;

    $warehouse_options = $this->getWarehouseOptions();

    // Warehouse display.
    $element['warehouse'] = [
      '#type' => 'value',
      '#default_value' => $value['warehouse'],
      '#attributes' => [
        'data-stock-widget--delta' => $delta,
        'data-stock-widget--field' => 'warehouse',
        'data-stock-widget--old-value' => $value['warehouse'],
      ],
      '#suffix' => $warehouse_options[$value['warehouse']],
    ];

    $element['quantity'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#title' => $this->t('Quantity', [], ['context' => 'arch_stock']),
      '#default_value' => $value['quantity'],
      '#attributes' => [
        'data-stock-widget--delta' => $delta,
        'data-stock-widget--field' => 'quantity',
        'data-stock-widget--old-value' => $value['quantity'],
      ],
    ];

    if ($value['cart_quantity']) {
      if ($value['quantity'] > $value['cart_quantity']) {
        $element['quantity']['#min'] = $value['cart_quantity'];
      }
      $element['quantity']['#description'] = $this->formatPlural(
        $value['cart_quantity'],
        'Currently @count item in customer cart',
        'Currently @count items in customer cart',
        ['@count' => $value['cart_quantity']],
        ['context' => 'arch_stock']
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $warehouses = $this->warehouseManager->getFormOptions();
    _arch_stock_prepare_stock_field_value($items, $warehouses);

    $output = parent::form($items, $form, $form_state, $get_delta);
    $output['widget']['#theme'] = 'stock_form_table';
    $output['widget']['#warehouses'] = $this->getWarehouseOptions();
    $output['widget']['#product_id'] = $items->getEntity()->id();
    return $output;
  }

  /**
   * Get warehouse options.
   *
   * @return array
   *   Options list.
   */
  protected function getWarehouseOptions() {
    if (!isset($this->warehouseOptions)) {
      $this->warehouseOptions = $this->warehouseManager->getFormOptions();
    }
    return $this->warehouseOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $return = [];
    foreach ($values as $value) {
      if ((string) $value['quantity'] === '') {
        continue;
      }

      $return[] = $this->massageValue($value);
    }

    return $return;
  }

  /**
   * Massage value.
   */
  protected function massageValue(array $value) {
    $value += [
      'warehouse' => NULL,
      'quantity' => NULL,
      'cart_quantity' => NULL,
    ];

    $value['quantity'] = round((float) $value['quantity'], 2);
    $value['cart_quantity'] = round((float) $value['cart_quantity'], 2);
    return $value;
  }

}
