<?php

namespace Drupal\arch_stock\Form;

use Drupal\arch_stock\Manager\WarehouseManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stock settings form.
 *
 * @package Drupal\arch_stock\Form
 */
class StockSettingsForm extends FormBase {

  /**
   * A configuration instance.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueStore;

  /**
   * The settings.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   * StockSettingsForm constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   Key value store.
   * @param \Drupal\arch_stock\Manager\WarehouseManagerInterface $warehouse_manager
   *   Warehouse manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    KeyValueFactoryInterface $key_value,
    WarehouseManagerInterface $warehouse_manager,
    MessengerInterface $messenger
  ) {
    $this->keyValueStore = $key_value;
    $this->keyValue = $this->keyValueStore->get('arch_stock.settings');

    $this->warehouseManager = $warehouse_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue'),
      $container->get('warehouse.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stock_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['default_warehouse'] = [
      '#type' => 'select',
      '#title' => $this->t('Default warehouse', [], ['context' => 'arch_stock']),
      '#options' => $this->getWarehouseOptions(),
      '#default_value' => $this->keyValue->get('default_warehouse', 'default'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->keyValue->set('default_warehouse', $form_state->getValue('default_warehouse'));

    $this->messenger->addMessage($this->t('New settings have been saved.'));
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

}
