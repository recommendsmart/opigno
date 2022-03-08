<?php

namespace Drupal\flow\Plugin\flow\Task;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Flow;
use Drupal\flow\Helpers\EntityContentConfigurationTrait;
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

  use EntityContentConfigurationTrait {
    buildConfigurationForm as buildContentConfigurationForm;
    submitConfigurationForm as submitContentConfigurationForm;
  }
  use EntityFromStackTrait;
  use EntitySerializationTrait;
  use EntityTypeManagerTrait;
  use FormBuilderTrait;
  use ModuleHandlerTrait;
  use SingleTaskOperationTrait;
  use StringTranslationTrait;
  use TokenTrait;

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
    if (empty($instance->settings['values'])) {
      $default_config = $instance->defaultConfiguration();
      $instance->settings += $default_config['settings'];
    }
    $instance->initEntityFromStack();
    $instance->initConfiguredContentEntity();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doOperate(ContentEntityInterface $entity): void {
    $source = $this->initConfiguredContentEntity($entity);
    $target = $entity;

    if (!empty($this->settings['check_langcode'])) {
      // Do not merge values when the language is different.
      if ($source->language()->getId() != $target->language()->getId()) {
        return;
      }
    }

    $merge_single = $this->settings['method']['single'] ?? 'set:clear';
    $merge_multi = $this->settings['method']['multi'] ?? 'unify';

    $field_names = $this->settings['fields'] ?? [];
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
      $merge_values = $source_item_list->getValue();
      $current_values = $target_item_list->getValue();
      $cardinality = $target_item_list->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

      if ($cardinality === 1 && $merge_single === 'set:not_set' && count($current_values)) {
        continue;
      }
      if ($cardinality !== 1 && $merge_multi === 'set:not_set' && count($current_values)) {
        continue;
      }

      // Determine if we have different values to merge.
      // @todo Find a better way to determine this.
      $values_changed = FALSE;
      /** @var \Drupal\Core\Field\FieldItemInterface $source_item */
      foreach ($source_item_list as $source_item) {
        $property_name = $source_item->mainPropertyName();
        $source_value = isset($property_name) ? $source_item->$property_name : $source_item->getValue();
        if (is_string($source_value)) {
          $source_value = nl2br(trim($source_value));
        }
        /** @var \Drupal\Core\Field\FieldItemInterface $target_item */
        foreach ($target_item_list as $target_item) {
          $target_value = isset($property_name) ? $target_item->$property_name : $target_item->getValue();
          if (is_string($target_value)) {
            $target_value = nl2br(trim($target_value));
          }
          if ($source_value == $target_value) {
            continue 2;
          }
        }
        $values_changed = TRUE;
        break;
      }

      if ($merge_multi === 'unify') {
        $num_values = count($merge_values);
        if ($values_changed && ($cardinality === $unlimited || $num_values < $cardinality)) {
          foreach ($current_values as $current_value) {
            if ($cardinality !== $unlimited && $num_values > $cardinality) {
              break;
            }
            if (in_array($current_value, $merge_values)) {
              continue;
            }
            $merge_values[] = $current_value;
            $num_values++;
          }
        }
      }

      if ($values_changed) {
        $target_item_list->setValue(array_values($merge_values));
        $needs_save = TRUE;
      }
    }

    if ($needs_save) {
      if ($target instanceof EntityChangedInterface) {
        $target->setChangedTime(\Drupal::time()->getCurrentTime());
      }
      Flow::needsSave($target, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += $this->buildContentConfigurationForm($form, $form_state);

    $weight = -1000;

    $entity_type = $this->configuredContentEntity->getEntityType();
    $form_display = EntityFormDisplay::collectRenderDisplay($this->configuredContentEntity, $this->entityFormDisplay, TRUE);
    $visible_fields = array_keys($form_display->getComponents());
    $visible_fields = array_combine($visible_fields, $visible_fields);
    $langcode_key = $entity_type->hasKey('langcode') ? $entity_type->getKey('langcode') : 'langcode';
    unset($visible_fields[$langcode_key], $visible_fields['default_langcode']);
    $field_options = [];
    foreach ($visible_fields as $field_name) {
      if (!$this->configuredContentEntity->hasField($field_name)) {
        continue;
      }
      $field_options[$field_name] = $this->configuredContentEntity->get($field_name)->getFieldDefinition()->getLabel();
    }
    $weight += 10;
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields to merge'),
      '#options' => $field_options,
      '#default_value' => isset($this->settings['fields']) ? array_combine($this->settings['fields'], $this->settings['fields']) : $visible_fields,
      '#weight' => $weight++,
    ];

    $weight += 10;
    $form['method'] = [
      '#weight' => $weight++,
    ];
    $single_options = [
      'set:clear' => $this->t('Set and clear any previously set value'),
      'set:not_set' => $this->t('Set when no other value was set before'),
    ];
    $form['method']['single'] = [
      '#type' => 'select',
      '#title' => $this->t('Merging single-value fields'),
      '#required' => TRUE,
      '#options' => $single_options,
      '#default_value' => 'set:clear',
      '#weight' => 10,
    ];
    $multi_options = ['unify' => $this->t('Unify all values')] + $single_options;
    $form['method']['multi'] = [
      '#type' => 'select',
      '#title' => $this->t('Merging multi-value fields'),
      '#required' => TRUE,
      '#options' => $multi_options,
      '#default_value' => 'unify',
      '#weight' => 20,
    ];
    $weight += 10;
    $form['check_langcode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not merge when the translation language is different.'),
      '#default_value' => $this->settings['check_langcode'] ?? TRUE,
      '#weight' => $weight++,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->submitContentConfigurationForm($form, $form_state);
    $this->settings['check_langcode'] = (bool) $form_state->getValue('check_langcode', FALSE);
    $this->settings['method'] = $form_state->getValue('method');
    $this->settings['fields'] = array_keys(array_filter($form_state->getValue('fields'), function ($value) {
      return !empty($value);
    }));
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if (($entity = $this->getConfiguredContentEntity()) && !empty($this->settings['fields'])) {
      foreach ($this->settings['fields'] as $field_name) {
        if (!$entity->hasField($field_name)) {
          continue;
        }
        if ($field_config = $this->getEntityTypeManager()->getStorage('field_config')->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $field_name)) {
          $dependencies[$field_config->getConfigDependencyKey()][] = $field_config->getConfigDependencyName();
        }
        if ($field_storage_config = $this->getEntityTypeManager()->getStorage('field_storage_config')->load($entity->getEntityTypeId() . '.' . $field_name)) {
          $dependencies[$field_storage_config->getConfigDependencyKey()][] = $field_storage_config->getConfigDependencyName();
        }
      }
    }
    return $dependencies;
  }

}
