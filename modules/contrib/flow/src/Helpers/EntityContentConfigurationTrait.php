<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Flow;
use Drupal\flow\Helpers\EntityFromStackTrait;
use Drupal\flow\Helpers\EntitySerializationTrait;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Drupal\flow\Helpers\FormBuilderTrait;
use Drupal\flow\Helpers\ModuleHandlerTrait;
use Drupal\flow\Helpers\TokenTrait;

/**
 * Trait for Flow-related components that configure a content entity.
 */
trait EntityContentConfigurationTrait {

  use EntityFromStackTrait;
  use EntitySerializationTrait;
  use EntityTypeManagerTrait;
  use FormBuilderTrait;
  use ModuleHandlerTrait;
  use StringTranslationTrait;
  use TokenTrait;

  /**
   * The configured content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $configuredContentEntity;

  /**
   * The form object used for configuring the content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityFormInterface|null
   */
  protected ?ContentEntityFormInterface $entityForm;

  /**
   * The entity form display to use for configuring the content entity.
   *
   * @var string
   */
  protected string $entityFormDisplay = 'flow';

  /**
   * The data to use for Token replacement.
   *
   * Can be either the subject item ("subject") or the Flow-related entity
   * ("flow"). Default is set to "subject".
   *
   * @var string
   */
  protected string $tokenTarget = 'subject';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    if ($definition = $this->getPluginDefinition()) {
      $values = [];
      $entity_type = $this->getEntityTypeManager()->getDefinition($definition['entity_type']);
      if ($entity_type->hasKey('bundle')) {
        $values[$entity_type->getKey('bundle')] = $definition['bundle'];
      }
      return ['settings' => ['values' => $values]];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['token_info'] = [
      '#type' => 'container',
      'allowed_text' => [
        '#markup' => $this->t('Tokens are allowed.') . '&nbsp;',
        '#weight' => 10,
      ],
      '#weight' => -100,
    ];
    $entity_type_id = $this->tokenTarget === 'subject' ? $this->getPluginDefinition()['entity_type'] : ($this->configuration['entity_type_id'] ?? NULL);
    if (isset($entity_type_id) && $this->moduleHandler->moduleExists('token')) {
      $form['token_info']['browser'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->getTokenTypeForEntityType($entity_type_id)],
        '#dialog' => TRUE,
        '#weight' => 10,
      ];
    }
    else {
      $form['token_info']['no_browser'] = [
        '#markup' => $this->t('To get a list of available tokens, install the <a target="_blank" rel="noreferrer noopener" href=":drupal-token" target="blank">contrib Token</a> module.', [':drupal-token' => 'https://www.drupal.org/project/token']),
        '#weight' => 10,
      ];
    }
    $form['entity_form_info'] = [
      '#type' => 'container',
      'display_mode' => [
        '#markup' => $this->t('This configuration is using the "@mode" form display mode. <a href=":url" target="_blank">Manage form display modes</a>.', [
          '@mode' => $this->entityFormDisplay,
          ':url' => '/admin/structure/display-modes/form',
        ]),
        '#weight' => 10,
      ],
      '#weight' => -50,
    ];
    // We need to use a process callback for embedding the entity fields,
    // because the fields to embed need to know their "#parents".
    $form['values'] = [
      '#process' => [[$this, 'processForm']],
    ];
    return $form;
  }

  /**
   * Form process callback that embeds the fields of the entity to configure.
   *
   * @param array &$form
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array &$complete_form
   *   The complete form.
   *
   * @return array
   *   The form element, enriched by the entity form.
   */
  public function processForm(array &$form, FormStateInterface $form_state, array &$complete_form): array {
    $entity_form_object = $this->getEntityFormObject();
    $subform_state = SubformState::createForSubform($form, $complete_form, $form_state);
    $form = $entity_form_object->buildForm($form, $subform_state);
    unset($form['actions']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasValue(['values'])) {
      return;
    }

    $entity_form_object = $this->getEntityFormObject();
    $entity_form_state = (new FormState())
      ->disableCache()
      ->setFormObject($entity_form_object)
      ->setFormState($form_state->getCacheableArray())
      ->setValues($form_state->getValue(['values']));
    $entity_form = [];
    $entity_form_object->buildForm($entity_form, $entity_form_state);
    $this->getFormBuilder()->prepareForm($entity_form_object->getFormId(), $entity_form, $entity_form_state);
    $entity_form_object->validateForm($entity_form, $entity_form_state);
    $form_state
      ->setFormState($entity_form_state->getCacheableArray())
      ->setValue('values', $entity_form_state->getValues());
    $form_state->setLimitValidationErrors($entity_form_state->getLimitValidationErrors());
    foreach ($entity_form_state->getErrors() as $name => $error) {
      $form_state->setErrorByName($name, $error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasValue(['values'])) {
      return;
    }

    $entity_form_object = $this->getEntityFormObject();
    $entity_form_state = (new FormState())
      ->disableCache()
      ->setFormObject($entity_form_object)
      ->setFormState($form_state->getCacheableArray())
      ->setValues($form_state->getValue(['values']));
    $entity_form = [];
    $entity_form_object->buildForm($entity_form, $entity_form_state);
    $this->getFormBuilder()->prepareForm($entity_form_object->getFormId(), $entity_form, $entity_form_state);
    $entity_form_object->submitForm($entity_form, $entity_form_state);

    $this->configuredContentEntity = $entity_form_object->getEntity();
    $values = $this->getSerializer()->normalize($this->configuredContentEntity, get_class($this->configuredContentEntity));

    // @todo Remove this workaround once #2972988 is fixed.
    foreach ($values as &$field_values) {
      foreach ($field_values as &$field_value) {
        unset($field_value['processed']);
      }
    }

    $entity_type = $this->configuredContentEntity->getEntityType();
    // Remove the UUID as it won't be used at all for configuration, and do a
    // little cleanup by filtering out empty values. Also only include field
    // values that are available on the used form display mode.
    $uuid_key = $entity_type->hasKey('uuid') ? $entity_type->getKey('uuid') : 'uuid';
    unset($values[$uuid_key]);
    $entity_keys = $entity_type->getKeys();
    $form_display = EntityFormDisplay::collectRenderDisplay($this->configuredContentEntity, $this->entityFormDisplay, TRUE);
    $components = $form_display->getComponents();
    foreach ($values as $k_1 => $v_1) {
      if ((!isset($components[$k_1]) && !in_array($k_1, $entity_keys)) || (!is_scalar($v_1) && empty($v_1))) {
        unset($values[$k_1]);
      }
      elseif (is_iterable($v_1)) {
        $is_empty = TRUE;
        foreach ($v_1 as $v_2) {
          if (!empty($v_2) || (!is_null($v_2) && $v_2 !== '' && $v_2 !== 0 && $v_2 !== '0')) {
            $is_empty = FALSE;
            break;
          }
        }
        if ($is_empty) {
          unset($values[$k_1]);
        }
      }
    }
    $this->settings['values'] = $values;
  }

  /**
   * Instantiates the configured content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $subject_item
   *   (optional) The current subject item to operate on.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The initialized entity.
   */
  public function initConfiguredContentEntity(?ContentEntityInterface $subject_item = NULL): ContentEntityInterface {
    $flow_is_active = Flow::isActive();
    Flow::setActive(FALSE);
    try {
      $entity_type_id = $this->getPluginDefinition()['entity_type'];
      $values = $this->settings['values'];
      // Apply Token replacement when operating on a subject item.
      if ($subject_item) {
        $token_data = [$this->getTokenTypeForEntityType($subject_item->getEntityTypeId()) => $subject_item];
        if ($this->entityFromStack && ($this->entityFromStack->getEntityTypeId() !== $subject_item->getEntityTypeId())) {
          $token_data[$this->getTokenTypeForEntityType($this->entityFromStack->getEntityTypeId())] = $this->entityFromStack;
        }
        array_walk_recursive($values, function (&$value) use (&$token_data) {
          if (is_string($value) && !empty($value)) {
            $value = $this->tokenReplace($value, $token_data);
          }
        });
      }
      $this->configuredContentEntity = $this->getSerializer()->denormalize($values, $this->getEntityTypeManager()->getDefinition($entity_type_id)->getClass());
    }
    finally {
      Flow::setActive($flow_is_active);
    }
    return $this->configuredContentEntity;
  }

  /**
   * Get the configured content entity object.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The initialized entity.
   */
  public function getConfiguredContentEntity(): ContentEntityInterface {
    if (!isset($this->configuredContentEntity)) {
      $this->initConfiguredContentEntity();
    }
    return $this->configuredContentEntity;
  }

  /**
   * Set the configured content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to set as configured content entity.
   */
  public function setConfiguredContentEntity(ContentEntityInterface $entity): void {
    $this->configuredContentEntity = $entity;
  }

  /**
   * Get the form object used for configuring the field values to merge.
   *
   * @return \Drupal\Core\Entity\ContentEntityFormInterface
   *   The form object.
   */
  protected function getEntityFormObject(): ContentEntityFormInterface {
    if (!isset($this->entityForm)) {
      $this->entityForm = ContentEntityForm::create(\Drupal::getContainer());
      $this->entityForm->setModuleHandler(\Drupal::moduleHandler());
      $this->entityForm->setEntityTypeManager(\Drupal::entityTypeManager());
      $this->entityForm->setStringTranslation(\Drupal::translation());
      $this->entityForm->setEntity($this->configuredContentEntity);
      $this->entityForm->setOperation($this->entityFormDisplay);
    }
    return $this->entityForm;
  }

}
