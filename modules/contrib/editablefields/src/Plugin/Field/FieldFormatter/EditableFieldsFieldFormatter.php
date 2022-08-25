<?php

namespace Drupal\editablefields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editablefields\services\EditableFieldsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'editablefields_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "editablefields_formatter",
 *   label = @Translation("Editable field"),
 *   field_types = {}
 * )
 */
class EditableFieldsFieldFormatter extends FormatterBase {

  /**
   * Drupal\editablefields\services\EditableFieldsHelper definition.
   *
   * @var \Drupal\editablefields\services\EditableFieldsHelper
   */
  protected $editablefieldsHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EditableFieldsHelper $editablefieldsHelper) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );
    $this->editablefieldsHelper = $editablefieldsHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\editablefields\services\EditableFieldsHelper $editablefields_helper */
    $editablefields_helper = $container->get('editablefields.helper');
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $editablefields_helper
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'form_mode' => 'default',
        'bypass_access' => FALSE,
        'fallback_access' => FALSE,
        'display_mode_access' => '',
        'fallback_edit' => FALSE,
        'display_mode_edit' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();
    return [
        'form_mode' => [
          '#type' => 'select',
          '#title' => $this->t('Select form mode:'),
          '#default_value' => $this->getSetting('form_mode'),
          '#required' => 'required',
          '#options' => $this->editablefieldsHelper
            ->getFormModesOptions($entity_type_id),
          '#description' => $this
            ->t('The widget for this field in the selected form mode will be used.'),
        ],
        'bypass_access' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Bypass access check'),
          '#default_value' => $this->getSetting('bypass_access'),
          '#description' => $this
            ->t('Allows to bypass check if the user has access to update entity.'),
        ],
        'fallback_access' => [
          '#type' => 'checkbox',
          '#title' => $this->t('No access formatter'),
          '#default_value' => $this->getSetting('fallback_access'),
          '#description' => $this
            ->t('Allows to select fallback formatter in case when user has no access to update entity.'),
          '#states' => [
            'visible' => [
              ':input[name="options[settings][bypass_access]"]' => ['checked' => FALSE],
            ],
          ],
        ],
        'display_mode_access' => [
          '#type' => 'select',
          '#title' => $this->t('Select no access display mode:'),
          '#default_value' => $this->getSetting('display_mode_access'),
          '#options' => $this->editablefieldsHelper
            ->getViewModesOptions($entity_type_id),
          '#description' => $this
            ->t('Use this formatter if user has no access to update entity.'),
          '#states' => [
            'visible' => [
              ':input[name="options[settings][fallback_access]"]' => ['checked' => TRUE],
              ':input[name="options[settings][bypass_access]"]' => ['checked' => FALSE],
            ],
            'required' => [
              ':input[name="options[settings][fallback_access]"]' => ['checked' => TRUE],
              ':input[name="options[settings][bypass_access]"]' => ['checked' => FALSE],
            ],
          ],
        ],
        'fallback_edit' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Use fallback formatter'),
          '#default_value' => $this->getSetting('fallback_edit'),
          '#description' => $this
            ->t('The widget for this field in the selected form mode will be used.'),
        ],
        'display_mode_edit' => [
          '#type' => 'select',
          '#title' => $this->t('Select fallback display mode:'),
          '#default_value' => $this->getSetting('display_mode_edit'),
          '#options' => $this->editablefieldsHelper
            ->getViewModesOptions($entity_type_id),
          '#description' => $this
            ->t('Use this formatter before user clicks "edit" to get the widget.'),
          '#states' => [
            'visible' => [
              ':input[name="options[settings][fallback_edit]"]' => ['checked' => TRUE],
            ],
            'required' => [
              ':input[name="options[settings][fallback_edit]"]' => ['checked' => TRUE],
            ],
          ],
        ],
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Form mode: @form_mode', [
        '@form_mode' => $this->getSetting('form_mode'),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getEntity();
    $has_access = $this->editablefieldsHelper->checkAccess($entity);

    // No bypass access setting and user has no access.
    if (!$has_access && !$this->getSetting('bypass_access')) {
      if ($this->getSetting('fallback_access')) {
        // If we have fallback option for the no-access case - render field.
        return $entity->get($this->fieldDefinition->getName())
          ->view($this->getSetting('display_mode_access'));
      }

      // No access & no fallback - no data.
      return [];
    }

    return [
      $this->editablefieldsHelper->getForm(
        $entity,
        $this->fieldDefinition->getName(),
        $this->getSettings()
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $default = EditableFieldsHelper::DEFAULT_MODE;

    $this->settings = [
      'form_mode' => $settings['form_mode'] ?? $default,
      'bypass_access' => !empty($settings['bypass_access']),
      'fallback_access' => !empty($settings['fallback_access'])
        && !empty($settings['display_mode_access']),
      'display_mode_access' => !empty($settings['display_mode_access'])
        ? $settings['display_mode_access']
        : $default,
      'fallback_edit' => !empty($settings['fallback_edit'])
        && !empty($settings['display_mode_edit']),
      'display_mode_edit' => !empty($settings['display_mode_edit'])
        ? $settings['display_mode_edit']
        : $default,
    ];

    $this->defaultSettingsMerged = FALSE;

    return parent::setSettings($settings);
  }

}
