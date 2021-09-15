<?php

namespace Drupal\arch_stock\Form;

use Drupal\arch_product\Entity\ProductAvailability;
use Drupal\arch_stock\Entity\Storage\WarehouseStorageInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for warehouse edit forms.
 *
 * @internal
 */
class WarehouseForm extends BundleEntityFormBase {

  /**
   * The warehouse storage.
   *
   * @var \Drupal\arch_stock\Entity\Storage\WarehouseStorageInterface
   */
  protected $warehouseStorage;

  /**
   * Constructs a new warehouse form.
   *
   * @param \Drupal\arch_stock\Entity\Storage\WarehouseStorageInterface $warehouse_storage
   *   The warehouse storage.
   */
  public function __construct(
    WarehouseStorageInterface $warehouse_storage
  ) {
    $this->warehouseStorage = $warehouse_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container
  ) {
    return new static(
      $container->get('entity_type.manager')->getStorage('warehouse')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_stock\Entity\WarehouseInterface $warehouse */
    $warehouse = $this->entity;
    if ($warehouse->isNew()) {
      $form['#title'] = $this->t('Add warehouse', [], ['context' => 'arch_stock']);
    }
    else {
      $form['#title'] = $this->t('Edit warehouse', [], ['context' => 'arch_stock']);
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name', [], ['context' => 'arch_stock_warehouse']),
      '#default_value' => $warehouse->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -100,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $warehouse->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['name'],
      ],
      '#weight' => -99,
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description', [], ['context' => 'arch_stock_warehouse']),
      '#default_value' => $warehouse->getDescription(),
      '#weight' => -80,
    ];
    $form['allow_negative'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow over booking', [], ['context' => 'arch_stock_warehouse']),
      '#default_value' => $warehouse->allowNegative(),
      '#weight' => -60,
    ];

    $options = [
      '' => $this->t('Do not change', [], ['context' => 'arch_stock_warehouse']),
    ];
    $options += ProductAvailability::getOptions();
    $form['overbooked_availability'] = [
      '#type' => 'select',
      '#title' => $this->t('Change product Availability when overbooked', [], ['context' => 'arch_stock_warehouse']),
      '#default_value' => $warehouse->getOverBookedAvailability(),
      '#options' => $options,
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-allow-negative"]' => ['checked' => TRUE],
        ],
      ],
      '#weight' => -59,
    ];

    // $form['langcode'] is not wrapped in an
    // if ($this->moduleHandler->moduleExists('language')) check because the
    // language_select form element works also without the language module being
    // installed. https://www.drupal.org/node/1749954 documents the new element.
    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $warehouse->language()->getId(),
      '#weight' => -90,
    ];

    $form = parent::form($form, $form_state);
    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save warehouse', [], ['context' => 'arch_stock']);
    $actions['delete']['#value'] = $this->t('Delete warehouse', [], ['context' => 'arch_stock']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_stock\Entity\WarehouseInterface $warehouse */
    $warehouse = $this->entity;

    // Prevent leading and trailing spaces in warehouse names.
    $warehouse->set('name', trim($warehouse->label()));

    $status = $warehouse->save();
    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t(
          'Created new warehouse %name.',
          ['%name' => $warehouse->label()],
          ['context' => 'arch_stock']
        ));
        $this->logger('arch')->notice(
          'Created new warehouse %name.',
          [
            '%name' => $warehouse->label(),
            'link' => $edit_link,
          ]
        );
        $form_state->setRedirectUrl(
          $warehouse->toUrl('collection')
        );
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t(
          'Updated warehouse %name.',
          ['%name' => $warehouse->label()],
          ['context' => 'arch_stock']
        ));
        $this->logger('arch')->notice(
          'Updated warehouse %name.',
          [
            '%name' => $warehouse->label(),
            'link' => $edit_link,
          ]
        );
        $form_state->setRedirectUrl(
          $warehouse->toUrl('collection')
        );
        break;
    }

    $form_state->setValue('id', $warehouse->id());
    $form_state->set('id', $warehouse->id());
  }

  /**
   * Determines if the warehouse already exists.
   *
   * @param string $id
   *   The warehouse ID.
   *
   * @return bool
   *   TRUE if the warehouse exists, FALSE otherwise.
   */
  public function exists($id) {
    $action = $this->warehouseStorage->load($id);
    return !empty($action);
  }

}
