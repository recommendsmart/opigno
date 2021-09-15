<?php

namespace Drupal\arch_price\Form;

use Drupal\arch_price\Manager\PriceTypeManagerInterface;
use Drupal\arch_price\Manager\VatCategoryManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Price settings form.
 *
 * @package Drupal\arch_price\Form
 */
class PriceSettingsForm extends FormBase {

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
   * PriceSettingsForm constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   Key value store.
   * @param \Drupal\arch_price\Manager\PriceTypeManagerInterface $price_type_manager
   *   Price type manager.
   * @param \Drupal\arch_price\Manager\VatCategoryManagerInterface $vat_category_manager
   *   VAT category manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    KeyValueFactoryInterface $key_value,
    PriceTypeManagerInterface $price_type_manager,
    VatCategoryManagerInterface $vat_category_manager,
    MessengerInterface $messenger
  ) {
    $this->keyValueStore = $key_value;
    $this->keyValue = $this->keyValueStore->get('arch_price.settings');

    $this->priceTypeManager = $price_type_manager;
    $this->vatCategoryManager = $vat_category_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue'),
      $container->get('price_type.manager'),
      $container->get('vat_category.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'price_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['default_price_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default price type', [], ['context' => 'arch_price']),
      '#options' => $this->getPriceTypeOptions(),
      '#default_value' => $this->keyValue->get('default_price_type', 'default'),
    ];

    $form['default_vat_category'] = [
      '#type' => 'select',
      '#title' => $this->t('Default VAT category', [], ['context' => 'arch_price']),
      '#options' => $this->getVatCategoryOptions(),
      '#default_value' => $this->keyValue->get('default_vat_category', 'default'),
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
    $this->keyValue->set('default_price_type', $form_state->getValue('default_price_type'));
    $this->keyValue->set('default_vat_category', $form_state->getValue('default_vat_category'));

    $this->messenger->addMessage($this->t('New settings have been saved.'));
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

}
