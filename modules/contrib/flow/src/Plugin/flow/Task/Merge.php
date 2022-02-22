<?php

namespace Drupal\flow\Plugin\flow\Task;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Flow;
use Drupal\flow\Helpers\EntityFromStackTrait;
use Drupal\flow\Helpers\EntitySerializationTrait;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Drupal\flow\Helpers\FormBuilderTrait;
use Drupal\flow\Helpers\ModuleHandlerTrait;
use Drupal\flow\Helpers\SingleTaskOperationTrait;
use Drupal\flow\Helpers\TokenTrait;
use Drupal\flow\Plugin\FlowTaskBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Task for merging values from content.
 *
 * @FlowTask(
 *   id = "merge",
 *   label = @Translation("Merge values from content"),
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Task\MergeDeriver"
 * )
 */
class Merge extends FlowTaskBase implements PluginFormInterface {

  use EntityFromStackTrait;
  use EntitySerializationTrait;
  use EntityTypeManagerTrait;
  use FormBuilderTrait;
  use ModuleHandlerTrait;
  use SingleTaskOperationTrait;
  use StringTranslationTrait;
  use TokenTrait;

  /**
   * The entity that holds the values to merge.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $entity;

  /**
   * The form object used for configuring the field values to merge.
   *
   * @var \Drupal\Core\Entity\ContentEntityFormInterface|null
   */
  protected ?ContentEntityFormInterface $entityForm;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\flow\Plugin\flow\Task\Merge $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setModuleHandler($container->get(self::$moduleHandlerServiceName));
    $instance->setFormBuilder($container->get(self::$formBuilderServiceName));
    $instance->setEntityTypeManager($container->get(self::$entityTypeManagerServiceName));
    $instance->setSerializer($container->get(self::$serializerServiceName));
    $instance->setToken($container->get(self::$tokenServiceName));
    if (empty($instance->settings)) {
      $default_config = $instance->defaultConfiguration();
      $instance->settings += $default_config['settings'];
    }
    $instance->initEntityFromStack();
    $instance->initializeEntity();
    return $instance;
  }

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
    if (isset($this->configuration['entity_type_id']) && $this->moduleHandler->moduleExists('token')) {
      $form['token_info']['browser'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->getTokenTypeForEntityType($this->configuration['entity_type_id'])],
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
    // We need to use a process callback for embedding the entity fields,
    // because the fields to embed need to know their "#parents".
    $form['#process'][] = [$this, 'processForm'];
    return $form;
  }

  /**
   * Form process callback that embeds the fields of the entity to merge.
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
    $entity_form_object = $this->getEntityFormObject();
    $entity_form_state = (new FormState())
      ->disableCache()
      ->setFormObject($entity_form_object)
      ->setFormState($form_state->getCacheableArray())
      ->setValues($form_state->getValues());
    $entity_form = [];
    $entity_form_object->buildForm($entity_form, $entity_form_state);
    $this->formBuilder->prepareForm($entity_form_object->getFormId(), $entity_form, $entity_form_state);
    $entity_form_object->validateForm($entity_form, $entity_form_state);
    $form_state
      ->setFormState($entity_form_state->getCacheableArray())
      ->setValues($entity_form_state->getValues());
    $form_state->setLimitValidationErrors($entity_form_state->getLimitValidationErrors());
    foreach ($entity_form_state->getErrors() as $name => $error) {
      $form_state->setErrorByName($name, $error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $entity_form_object = $this->getEntityFormObject();
    $entity_form_state = (new FormState())
      ->disableCache()
      ->setFormObject($entity_form_object)
      ->setFormState($form_state->getCacheableArray())
      ->setValues($form_state->getValues());
    $entity_form = [];
    $entity_form_object->buildForm($entity_form, $entity_form_state);
    $this->formBuilder->prepareForm($entity_form_object->getFormId(), $entity_form, $entity_form_state);
    $entity_form_object->submitForm($entity_form, $entity_form_state);

    $this->entity = $entity_form_object->getEntity();
    $values = $this->serializer->normalize($this->entity, get_class($this->entity));
    $entity_type = $this->entity->getEntityType();

    // Remove UUID as it won't be used at all for merging, and do a little
    // cleanup by filtering out empty values. Also only include field values
    // that are available on the "flow" form mode.
    $uuid_key = $entity_type->hasKey('uuid') ? $entity_type->getKey('uuid') : 'uuid';
    unset($values[$uuid_key]);
    $entity_keys = $entity_type->getKeys();
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'flow', TRUE);
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
   * {@inheritdoc}
   */
  public function doOperate(ContentEntityInterface $entity): void {
    $source = $this->initializeEntity($entity);
    $target = $entity;
    $field_names = array_keys($this->settings['values']);
    $unlimited = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
    $needs_save = FALSE;
    foreach ($field_names as $field_name) {
      if (!$target->hasField($field_name) || !$source->hasField($field_name)) {
        continue;
      }
      $source_item_list = $source->get($field_name);
      $source_item_list->filterEmptyItems();
      if ($source_item_list->isEmpty()) {
        continue;
      }
      $target_item_list = $target->get($field_name);
      $target_item_list->filterEmptyItems();
      $source_values = $source_item_list->getValue();
      $target_values = $target_item_list->getValue();
      $cardinality = $target_item_list->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
      if ($cardinality === $unlimited || count($target_values) < $cardinality) {
        $i = count($target_values);
        $values_changed = FALSE;
        foreach ($source_values as $source_value) {
          if ($cardinality !== $unlimited && $i > $cardinality) {
            break;
          }
          if (in_array($source_value, $target_values)) {
            continue;
          }
          $target_values[] = $source_value;
          $values_changed = TRUE;
          $i++;
        }
        if ($values_changed) {
          $target_item_list->setValue(array_values($target_values));
          $needs_save = TRUE;
        }
      }
    }
    if ($needs_save) {
      Flow::needsSave($target, $this);
    }
  }

  /**
   * Instantiates the entity object, holding the values to merge.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $subject_item
   *   (optional) The current subject item to operate on.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The initialized entity.
   */
  protected function initializeEntity(?ContentEntityInterface $subject_item = NULL): ContentEntityInterface {
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
      $this->entity = $this->serializer->denormalize($values, $this->entityTypeManager->getDefinition($entity_type_id)->getClass());
    }
    finally {
      Flow::setActive($flow_is_active);
    }
    return $this->entity;
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
      $this->entityForm->setEntity($this->entity);
      $this->entityForm->setOperation('flow');
    }
    return $this->entityForm;
  }

}
