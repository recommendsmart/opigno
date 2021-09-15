<?php

namespace Drupal\arch_price\Form;

use Drupal\arch_price\Entity\Storage\VatCategoryStorageInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for VAT category edit forms.
 *
 * @internal
 */
class VatCategoryForm extends BundleEntityFormBase {

  /**
   * The VAT category storage.
   *
   * @var \Drupal\arch_price\Entity\Storage\VatCategoryStorageInterface
   */
  protected $vatCategoryStorage;

  /**
   * Constructs a new VAT category form.
   *
   * @param \Drupal\arch_price\Entity\Storage\VatCategoryStorageInterface $vat_category_storage
   *   The price type storage.
   */
  public function __construct(
    VatCategoryStorageInterface $vat_category_storage
  ) {
    $this->vatCategoryStorage = $vat_category_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container
  ) {
    return new static(
      $container->get('entity_type.manager')->getStorage('vat_category')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_price\Entity\VatCategoryInterface $vat_category */
    $vat_category = $this->entity;
    if ($vat_category->isNew()) {
      $form['#title'] = $this->t('Add VAT category', [], ['context' => 'arch_vat_category']);
    }
    else {
      $form['#title'] = $this->t('Edit VAT category', [], ['context' => 'arch_vat_category']);
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name', [], ['context' => 'arch_vat_category']),
      '#default_value' => $vat_category->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $vat_category->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['name'],
      ],
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description', [], ['context' => 'arch_vat_category']),
      '#default_value' => $vat_category->getDescription(),
    ];
    $form['custom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Custom rate', [], ['context' => 'arch_vat_category']),
      '#default_value' => $vat_category->isCustom(),
    ];
    $form['rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate', [], ['context' => 'arch_price']),
      '#default_value' => $vat_category->getRatePercent(),
      '#step' => 0.01,
      '#size' => 5,
      '#field_suffix' => '%',
      '#states' => [
        'visible' => [
          'input[name="custom"]' => ['checked' => FALSE],
        ],
        'required' => [
          'input[name="custom"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // $form['langcode'] is not wrapped in an
    // if ($this->moduleHandler->moduleExists('language')) check because the
    // language_select form element works also without the language module being
    // installed. https://www.drupal.org/node/1749954 documents the new element.
    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $vat_category->language()->getId(),
    ];

    $form = parent::form($form, $form_state);
    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save VAT category', [], ['context' => 'arch_vat_category']);
    $actions['delete']['#value'] = $this->t('Delete VAT category', [], ['context' => 'arch_vat_category']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_price\Entity\VatCategoryInterface $vat_category */
    $vat_category = $this->entity;

    // Prevent leading and trailing spaces in price type names.
    $vat_category->set('name', trim($vat_category->label()));
    $vat_category->set('rate', round((float) $form_state->getValue('rate') / 100, 4));

    $status = $vat_category->save();

    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t(
          'Created new VAT category %name.',
          ['%name' => $vat_category->label()],
          ['context' => 'arch_price']
        ));
        $this->logger('arch')->notice(
          'Created new VAT category %name.',
          [
            '%name' => $vat_category->label(),
            'link' => $edit_link,
          ]
        );
        $form_state->setRedirectUrl(
          $vat_category->toUrl('collection')
        );
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t(
          'Updated VAT category %name.',
          ['%name' => $vat_category->label()],
          ['context' => 'arch_price']
        ));
        $this->logger('arch')->notice(
          'Updated VAT category %name.',
          [
            '%name' => $vat_category->label(),
            'link' => $edit_link,
          ]
        );
        $form_state->setRedirectUrl(
          $vat_category->toUrl('collection')
        );
        break;
    }

    $form_state->setValue('id', $vat_category->id());
    $form_state->set('id', $vat_category->id());
  }

  /**
   * Determines if the VAT category already exists.
   *
   * @param string $id
   *   The price type ID.
   *
   * @return bool
   *   TRUE if the price type exists, FALSE otherwise.
   */
  public function exists($id) {
    $action = $this->vatCategoryStorage->load($id);
    return !empty($action);
  }

}
