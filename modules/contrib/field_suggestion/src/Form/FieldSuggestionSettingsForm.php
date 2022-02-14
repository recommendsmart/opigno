<?php

namespace Drupal\field_suggestion\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the Field Suggestion settings form.
 */
class FieldSuggestionSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field names duplication resolver.
   *
   * Shows which entity type fields have an extra character in the name to not
   * duplicate the name of other fields.
   *
   * @var bool[]
   */
  protected $hasSuffix = [];

  /**
   * FieldSuggestionSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TranslationInterface $translation,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct($config_factory);

    $this->setStringTranslation($translation);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['field_suggestion.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_suggestion_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->getEditableConfigNames()[0]);

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#description' => $this->t('The number of displayed values in a list of suggestions.'),
      '#default_value' => $config->get('limit'),
      '#min' => 1,
    ];

    $form['own'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter by owner'),
      '#description' => $this->t('Looking for suggestions only in entities of the current user.'),
      '#default_value' => $config->get('own'),
    ];

    $form['entity_types'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Entity types'),
    ];

    $selected_fields = (array) $config->get('fields');

    if (!empty($selected_fields)) {
      $form['entity_types']['#default_tab'] = 'edit-' . str_replace('_', '-', array_keys($selected_fields)[0]);
    }

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }

      $allowed_fields = [];

      foreach ($this->entityFieldManager->getBaseFieldDefinitions($entity_type_id) as $field_name => $field) {
        if (
          !$field->isReadOnly() &&
          $field->getDisplayOptions('form') !== NULL
        ) {
          $allowed_fields[$field_name] = $field->getLabel();
        }
      }

      if (empty($allowed_fields)) {
        continue;
      }

      $this->hasSuffix[$entity_type_id] = isset($form[$entity_type_id]);

      $form[$entity_type_id . ($this->hasSuffix[$entity_type_id] ? '_' : '')] = [
        '#type' => 'details',
        '#title' => $entity_type->getLabel(),
        '#group' => 'entity_types',
        '#tree' => TRUE,
        'fields' => [
          '#type' => 'checkboxes',
          '#title' => $this->t('Fields'),
          '#options' => $allowed_fields,
          '#default_value' => $selected_fields[$entity_type_id] ?? [],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $all_fields = [];

    foreach ($this->hasSuffix as $entity_type_id => $has_suffix) {
      $fields = array_filter(array_values($form_state->getValue([
        $entity_type_id . ($has_suffix ? '_' : ''),
        'fields',
      ])));

      if (count($fields) > 0) {
        $all_fields[$entity_type_id] = $fields;
      }
    }

    $this->config($this->getEditableConfigNames()[0])
      ->set('limit', $form_state->getValue('limit'))
      ->set('own', $form_state->getValue('own'))
      ->set('fields', $all_fields)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
