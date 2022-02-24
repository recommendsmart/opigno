<?php

namespace Drupal\flow\Plugin\flow\Task;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
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

  use EntityContentConfigurationTrait;
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
      $merge_values = $source_item_list->getValue();
      $current_values = $target_item_list->getValue();
      $cardinality = $target_item_list->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
      $values_changed = FALSE;

      // Determine if we have different values to merge.
      // @todo Find a better way to determine this.
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
      if ($values_changed) {
        $target_item_list->setValue(array_values($merge_values));
        $needs_save = TRUE;
      }
    }

    if ($needs_save) {
      Flow::needsSave($target, $this);
    }
  }

}
