<?php

namespace Drupal\storage\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StorageTypeForm.
 */
class StorageTypeForm extends BundleEntityFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs the NodeTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $storage_type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add storage type');
      $fields = $this->entityFieldManager->getBaseFieldDefinitions('storage');
    } else {
      $form['#title'] = $this->t('Edit %label storage type', ['%label' => $storage_type->label()]);
      $fields = $this->entityFieldManager->getFieldDefinitions('storage', $storage_type->id());
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $storage_type->label(),
      '#description' => $this->t('The human-readable name of this storage type. This text will be displayed as part of the list on the <em>storage date</em> page. This name must be unique.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $storage_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$storage_type->isNew(),
      '#machine_name' => [
        'exists' => ['\Drupal\storage\Entity\StorageType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this storage type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %storage-add page.', [
        '%storage-add' => $this->t('Add storage data'),
      ]),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $storage_type->getDescription(),
      '#description' => $this->t('This text will be displayed on the <em>Add new storage</em> page.'),
    ];

    $form['title_label'] = [
      '#title' => $this->t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $fields['name']->getLabel(),
      '#description' => $this->t('This text will be used as the label for the title field when creating or editing data of this storage type.'),
      '#required' => TRUE,
    ];
    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $storage_type = $this->entity;
    $status = $storage_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Storage type.', [
          '%label' => $storage_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Storage type.', [
          '%label' => $storage_type->label(),
        ]));
    }

    $fields = $this->entityFieldManager->getFieldDefinitions('storage', $storage_type->id());
    // Update title field definition.
    $title_field = $fields['name'];
    $title_label = $form_state->getValue('title_label');
    if ($title_field && $title_field->getLabel() != $title_label) {
      $title_field->getConfig($storage_type->id())->setLabel($title_label)->save();
    }
    $form_state->setRedirectUrl($storage_type->toUrl('collection'));
  }

}
