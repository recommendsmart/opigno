<?php

namespace Drupal\arch_price\Form;

use Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface;
use Drupal\arch_price\Manager\VatCategoryManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for price type edit forms.
 *
 * @internal
 */
class PriceTypeForm extends BundleEntityFormBase {

  /**
   * The price type storage.
   *
   * @var \Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface
   */
  protected $priceTypeStorage;

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
   * Constructs a new price type form.
   *
   * @param \Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface $price_type_storage
   *   The price type storage.
   * @param \Drupal\arch_price\Manager\VatCategoryManagerInterface $vat_category_manager
   *   The VAT category manager.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage
   *   Currency entity storage.
   */
  public function __construct(
    PriceTypeStorageInterface $price_type_storage,
    VatCategoryManagerInterface $vat_category_manager,
    ConfigEntityStorageInterface $currency_storage
  ) {
    $this->priceTypeStorage = $price_type_storage;
    $this->vatCategoryManager = $vat_category_manager;
    $this->currencyStorage = $currency_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container
  ) {
    return new static(
      $container->get('entity_type.manager')->getStorage('price_type'),
      $container->get('vat_category.manager'),
      $container->get('entity_type.manager')->getStorage('currency')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_price\Entity\PriceTypeInterface $price_type */
    $price_type = $this->entity;
    if ($price_type->isNew()) {
      $form['#title'] = $this->t('Add price type', [], ['context' => 'arch_price_type']);
    }
    else {
      $form['#title'] = $this->t('Edit price type', [], ['context' => 'arch_price_type']);
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name', [], ['context' => 'arch_price_type']),
      '#default_value' => $price_type->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $price_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['name'],
      ],
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description', [], ['context' => 'arch_price_type']),
      '#default_value' => $price_type->getDescription(),
    ];

    // $form['langcode'] is not wrapped in an
    // if ($this->moduleHandler->moduleExists('language')) check because the
    // language_select form element works also without the language module being
    // installed. https://www.drupal.org/node/1749954 documents the new element.
    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $price_type->language()->getId(),
    ];

    $form['currency'] = [
      '#type' => 'select',
      '#options' => ['' => $this->t('- Select -')] + $this->getCurrencyOptions(),
      '#required' => TRUE,
      '#default_value' => $price_type->getDefaultCurrency(),
      '#title' => $this->t('Default currency', [], ['context' => 'arch_price_type']),
    ];
    $form['base'] = [
      '#type' => 'select',
      '#options' => [
        '' => $this->t('- Select -'),
        'net' => $this->t('Net', [], ['context' => 'arch_price_calc_base']),
        'gross' => $this->t('Gross', [], ['context' => 'arch_price_calc_base']),
      ],
      '#required' => TRUE,
      '#default_value' => $price_type->getDefaultCalculationBase(),
      '#title' => $this->t('Default calculation base', [], ['context' => 'arch_price_type']),
    ];
    $form['vat_category'] = [
      '#type' => 'select',
      '#options' => ['' => $this->t('- Select -')] + $this->getVatCategoryOptions(),
      '#required' => TRUE,
      '#default_value' => $price_type->getDefaultVatCategory(),
      '#title' => $this->t('Default VAT category', [], ['context' => 'arch_price_type']),
    ];

    $form = parent::form($form, $form_state);
    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save price type', [], ['context' => 'arch_price']);
    $actions['delete']['#value'] = $this->t('Delete price type', [], ['context' => 'arch_price']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_price\Entity\PriceTypeInterface $price_type */
    $price_type = $this->entity;

    // Prevent leading and trailing spaces in price type names.
    $price_type->set('name', trim($price_type->label()));

    $status = $price_type->save();
    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t(
          'Created new price type %name.',
          ['%name' => $price_type->label()],
          ['context' => 'arch_price']
        ));
        $this->logger('arch')->notice(
          'Created new price type %name.',
          [
            '%name' => $price_type->label(),
            'link' => $edit_link,
          ]
        );
        $form_state->setRedirectUrl(
          $price_type->toUrl('collection')
        );
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t(
          'Updated price type %name.',
          ['%name' => $price_type->label()],
          ['context' => 'arch_price']
        ));
        $this->logger('arch')->notice(
          'Updated price type %name.',
          [
            '%name' => $price_type->label(),
            'link' => $edit_link,
          ]
        );
        $form_state->setRedirectUrl(
          $price_type->toUrl('collection')
        );
        break;
    }

    $form_state->setValue('id', $price_type->id());
    $form_state->set('id', $price_type->id());
  }

  /**
   * Determines if the price type already exists.
   *
   * @param string $id
   *   The price type ID.
   *
   * @return bool
   *   TRUE if the price type exists, FALSE otherwise.
   */
  public function exists($id) {
    $action = $this->priceTypeStorage->load($id);
    return !empty($action);
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
   * Get currency options.
   *
   * @return array
   *   Options list.
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

}
