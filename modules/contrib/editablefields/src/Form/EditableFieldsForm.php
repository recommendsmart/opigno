<?php

namespace Drupal\editablefields\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EditableFieldsForm.
 */
class EditableFieldsForm extends FormBase implements BaseFormIdInterface {

  /**
   * Entity updated in the form.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Field name.
   *
   * @var string
   */
  protected $field_name;

  /**
   * Form mode.
   *
   * @var string
   */
  protected $form_mode;

  /**
   * Formatter settings.
   *
   * @var array $settings
   */
  protected $settings;

  /**
   * Drupal\editablefields\services\EditableFieldsHelper definition.
   *
   * @var \Drupal\editablefields\services\EditableFieldsHelper
   */
  protected $editablefieldsHelper;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->editablefieldsHelper = $container
      ->get('editablefields.helper');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_' . $this->prepareUniqueFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'editablefields_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $wrapper = str_replace('_', '-', $this->getFormId()) . '-wrapper';
    $fallback = $this->settings['fallback_edit'];
    $form['#prefix'] = "<div id=\"$wrapper\">";
    $form['#suffix'] = '</div>';

    $operation = $form_state->get('operation');

    $field = $this->field_name;
    $form_display = $this->getFormDisplay();
    $is_admin = $this->editablefieldsHelper->isAdmin();

    if ($form_display === NULL || !$form_display->id()) {
      if ($is_admin) {
        return [
          '#markup' => $this->t('Form mode @mode missing', [
            '@mode' => $this->form_mode,
          ]),
        ];
      }
      return [];
    }

    // If fallback formatter selected.
    if ($fallback && (!$operation || $operation === 'cancel')) {
      /** @var FieldItemListInterface $item */
      $item = $this->entity->get($field);
      $form['formatter'] = $item->view($this->settings['display_mode_edit']);
      if (empty($form['formatter'])) {
        $form['formatter'] = [
          '#markup' => $this->t('N/A'),
        ];
      }
      $form['formatter']['#weight'] = 0;
      $form['edit'] = [
        '#type' => 'submit',
        '#op' => 'edit',
        '#value' => $this->t('Edit'),
        '#weight' => 10,
        '#ajax' => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper' => $wrapper,
        ],
      ];

      return $form;
    }

    // Get the field widget from the form mode.
    $component = $form_display->getComponent($field);
    if (!$component) {
      if ($is_admin) {
        return [
          '#markup' => $this->t('The field @field is missing in the @mode', [
            '@field' => $field,
            '@mode' => $this->form_mode,
          ]),
        ];
      }
      return [];
    }

    // Add #parents to avoid error in WidgetBase::form.
    $form['#parents'] = [];

    // Get widget and prepare values for it.
    $widget = $form_display->getRenderer($field);
    if (is_null($widget)) {
      return [];
    }

    $items = $this->entity->get($field);
    $items->filterEmptyItems();

    // Get a widget form.
    $form[$field] = $widget->form($items, $form, $form_state);
    $form[$field]['#access'] = $items->access('edit');

    $form['submit'] = [
      '#type' => 'submit',
      '#op' => 'save',
      '#value' => $this->t('Update'),
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => $wrapper,
      ],
    ];

    if ($operation === 'save' && !$form_state->getErrors()) {
      $form['confirm_message'] = [
        '#markup' => $this->t('Updated'),
      ];
    }

    if ($fallback && $operation && $operation !== 'cancel') {
      $form['cancel'] = [
        '#type' => 'submit',
        '#op' => 'cancel',
        '#value' => $this->t('Cancel'),
        '#weight' => 20,
        '#ajax' => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper' => $wrapper,
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#op'])) {
      $form_state->set('operation', $trigger['#op']);
      // No further processing for edit and cancel.
      if ($trigger['#op'] !== 'save') {
        return;
      }
    }

    // Make sure we load fresh entity to prevent data loss:
    // https://www.drupal.org/project/editablefields/issues/3292392
    $entity = $this->entityTypeManager
      ->getStorage($this->entity->getEntityTypeId())
      ->load($this->entity->id());

    if (!$entity) {
      return;
    }

    $field = $this->field_name;
    $form_display = $this->getFormDisplay();

    if (!$form_display || !$form_display->id()) {
      return;
    }

    // Update the entity.
    if ($form_display->getComponent($field)) {
      $widget = $form_display->getRenderer($field);
      if (!$widget) {
        return;
      }

      $items = $entity->get($field);
      $items->filterEmptyItems();
      $widget->extractFormValues($items, $form, $form_state);
      $entity->save();
    }
  }

  /**
   * Editable field ajax callback.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Updated form.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Loads a form display mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface|NULL
   *   Display mode.
   */
  public function getFormDisplay() {
    return $this->editablefieldsHelper->getFormDisplay(
      $this->entity,
      $this->form_mode
    );
  }

  /**
   * Set defaults to be used for unique form ID.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Edited entity.
   * @param $field_name
   *   Field name.
   * @param $settings
   *   Form mode.
   */
  public function setDefaults(EntityInterface $entity, $field_name, array $settings) {
    $this->entity = $entity;
    $this->field_name = $field_name;
    $this->form_mode = !empty($settings['form_mode'])
      ? $settings['form_mode']
      : $this->editablefieldsHelper::DEFAULT_MODE;
    $this->settings = $settings;
  }

  /**
   * Set unique form id.
   *
   * @return string
   *   Unique part of the form ID.
   */
  public function prepareUniqueFormId() {
    $entity = $this->entity;

    $parts = [
      $entity->getEntityTypeId(),
      $entity->bundle(),
      $entity->id(),
      $this->field_name,
      $this->form_mode,
    ];

    return implode('_', $parts);
  }

}
