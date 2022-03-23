<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionTrait;
use Drupal\eca\Plugin\OptionsInterface;
use Drupal\eca\Service\Conditions;
use Drupal\eca\TypedData\PropertyPathTrait;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Replaces Drupal\Core\Field\FieldUpdateActionBase.
 *
 * We need to replace the core base class because within the ECA context
 * entities should not be saved after modifying a field value.
 *
 * The replacement is achieved with PHP's class_alias(), see eca_content.module
 */
abstract class FieldUpdateActionBase extends ActionBase  implements ConfigurableInterface, DependentPluginInterface, PluginFormInterface, OptionsInterface {

  use ConfigurableActionTrait;
  use PropertyPathTrait;

  /**
   * Gets an array of values to be set.
   *
   * @return array
   *   Array of values with field names as keys.
   */
  abstract protected function getFieldsToUpdate();

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    if (!($this instanceof EcaFieldUpdateActionInterface)) {
      return [];
    }
    return [
      'method' => 'set:clear',
      'strip_tags' => FALSE,
      'trim' => FALSE,
      'save_entity' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    if (!($this instanceof EcaFieldUpdateActionInterface)) {
      return $form;
    }
    $form['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#default_value' => $this->configuration['method'],
      '#weight' => -11,
      '#options' => $this->getOptions('method'),
    ];
    $form['strip_tags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip tags'),
      '#default_value' => $this->configuration['strip_tags'],
      '#weight' => 1,
    ];
    $form['trim'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Trim'),
      '#default_value' => $this->configuration['trim'],
      '#weight' => 2,
    ];
    $form['save_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save entity'),
      '#default_value' => $this->configuration['save_entity'],
      '#weight' => 3,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    if (!($this instanceof EcaFieldUpdateActionInterface)) {
      return;
    }
    $this->configuration['method'] = $form_state->getValue('method');
    $this->configuration['strip_tags'] = $form_state->getValue('strip_tags');
    $this->configuration['trim'] = $form_state->getValue('trim');
    $this->configuration['save_entity'] = $form_state->getValue('save_entity');
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'method') {
      return [
        'set:clear' => $this->t('Set and clear previous value'),
        'set:empty' => $this->t('Set only when empty'),
        'append:not_full' => $this->t('Append when not full yet'),
        'append:drop_first' => $this->t('Append and drop first when full'),
        'append:drop_last' => $this->t('Append and drop last when full'),
        'prepend:not_full' => $this->t('Prepend when not full yet'),
        'prepend:drop_first' => $this->t('Prepend and drop first when full'),
        'prepend:drop_last' => $this->t('Prepend and drop last when full'),
        'remove' => $this->t('Remove value instead of adding it'),
      ];
    }
    return NULL;
  }

  /**
   * Helper function to save the entity only outside ECA context or when
   * requested explicitly.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity which might have to be saved.
   */
  protected function save(ContentEntityInterface $entity): void {
    if (empty($entity->eca_context) || (($this->configuration['save_entity'] ?? Conditions::OPTION_NO) === Conditions::OPTION_YES)) {
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!($entity instanceof EntityInterface)) {
      return;
    }

    $method_settings = explode(':', $this->configuration['method'] ?? ($this->defaultConfiguration()['method'] ?? 'set:clear'));
    $all_entities_to_save = [];
    $options = ['auto_append' => TRUE, 'access' => 'update'];
    $entity_adapter = EntityAdapter::createFromEntity($entity);
    $values_changed = FALSE;
    foreach ($this->getFieldsToUpdate() as $field => $values) {
      $metadata = [];
      if (!($update_target = $this->getTypedProperty($entity_adapter, $field, $options, $metadata))) {
        throw new \InvalidArgumentException(sprintf("The provided field %s does not exist as a property path on the %s entity having ID %s.", $field, $entity->getEntityTypeId(), $entity->id()));
      }
      if (empty($metadata['entities'])) {
        throw new \RuntimeException(sprintf("The provided field %s does not resolve for entities to be saved from the %s entity having ID %s.", $field, $entity->getEntityTypeId(), $entity->id()));
      }
      $property_name = $update_target->getName();
      while ($update_target = $update_target->getParent()) {
        if ($update_target instanceof FieldItemListInterface) {
          break;
        }
      }
      if (!($update_target instanceof FieldItemListInterface)) {
        throw new \InvalidArgumentException(sprintf("The provided field %s does not resolve to a field on the %s entity having ID %s.", $field, $entity->getEntityTypeId(), $entity->id()));
      }
      if ($values instanceof ListInterface) {
        $values = $values->getValue();
      }
      elseif (!is_array($values)) {
        $values = [$values];
      }

      // Apply configured filters and normalize the array of values.
      foreach ($values as $i => $value) {
        if ($value instanceof TypedDataInterface) {
          $value = $value->getValue();
        }
        if (is_array($value)) {
          $value = array_key_exists($property_name, $value) ? $value[$property_name] : reset($value);
        }
        if (is_scalar($value) || is_null($value)) {
          if (($this->configuration['strip_tags'] ?? Conditions::OPTION_NO) === Conditions::OPTION_YES) {
            $value = preg_replace('/[\t\n\r\0\x0B]/', '', strip_tags((string) $value));
          }
          if (($this->configuration['trim'] ?? Conditions::OPTION_NO) === Conditions::OPTION_YES) {
            $value = trim((string) $value);
          }
          if ($value === '' || $value === NULL) {
            unset($values[$i]);
          }
          else {
            $values[$i] = [$property_name => $value];
          }
        }
        else {
          $values[$i] = $value;
        }
      }

      // Create a map of indices that refer to the already existing counterpart.
      $existing = [];
      /** @var \Drupal\Core\Field\FieldItemListInterface $update_target */
      $current_values = ($update_target instanceof EntityReferenceFieldItemListInterface) && (reset($values) instanceof EntityInterface) ? $update_target->referencedEntities() : $update_target->filterEmptyItems()->getValue();

      if (empty($values) && !empty($current_values) && (($this->configuration['method'] ?? 'set:clear') === 'set:clear')) {
        // Shorthand for setting a field to be empty.
        $update_target->setValue([]);
        foreach ($metadata['entities'] as $entity_to_save) {
          if (!in_array($entity_to_save, $all_entities_to_save, TRUE)) {
            $all_entities_to_save[] = $entity_to_save;
          }
        }
        continue;
      }

      foreach ($current_values as $k => $current_item) {
        $current_value = !is_array($current_item) ? $current_item : (array_key_exists($property_name, $current_item) ? $current_item[$property_name] : reset($current_item));
        if (is_string($current_value)) {
          // Extra processing is needed for strings, in order to prevent false
          // comparison when dealing with values that are the same but
          // encoded differently.
          $current_value = nl2br(trim($current_value));
        }
        elseif ($current_value instanceof EntityInterface) {
          $current_value = $current_value->uuid() ?: $current_value;
        }

        foreach ($values as $i => $value) {
          $new_value = !is_array($value) ? $value : (array_key_exists($property_name, $value) ? $value[$property_name] : reset($value));
          if (is_string($new_value)) {
            $new_value = nl2br(trim($new_value));
          }
          elseif ($new_value instanceof EntityInterface) {
            $new_value = $new_value->uuid() ?: $new_value;
          }
          if ((is_object($new_value) && $current_value === $new_value) || $current_value == $new_value) {
            $existing[$i] = $k;
          }
        }
      }

      if ((reset($method_settings) !== 'remove') && (count($existing) === count($values)) && (count($existing) === count($current_values))) {
        continue;
      }

      $cardinality = $update_target->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
      $is_unlimited = $cardinality === FieldStorageConfigInterface::CARDINALITY_UNLIMITED;
      foreach ($method_settings as $method_setting) {
        switch ($method_setting) {

          case 'clear':
            $keep = [];
            foreach ($existing as $k) {
              $keep[] = $current_values[$k];
            }
            if (count($current_values) !== count($keep)) {
              $values_changed = TRUE;
            }
            $current_values = $keep;
            break;

          case 'empty':
            if (!empty($current_values)) {
              continue 2;
            }
            break;

          case 'not_full':
            if (!$is_unlimited && !(count($current_values) < $cardinality)) {
              continue 2;
            }
            break;

          case 'drop_first':
            if (!$is_unlimited) {
              $num_required = count($values) - ($cardinality - count($current_values));
              $keep = array_flip($existing);
              reset($current_values);
              while ($num_required > 0 && ($k = key($current_values)) !== NULL) {
                next($current_values);
                $num_required--;
                if (!isset($keep[$k])) {
                  unset($current_values[$k]);
                  $values_changed = TRUE;
                }
              }
            }
            break;

          case 'drop_last':
            if (!$is_unlimited) {
              $num_required = count($values) - ($cardinality - count($current_values));
              $keep = array_flip($existing);
              end($current_values);
              while ($num_required > 0 && ($k = key($current_values)) !== NULL) {
                prev($current_values);
                $num_required--;
                if (!isset($keep[$k])) {
                  unset($current_values[$k]);
                  $values_changed = TRUE;
                }
              }
            }
            break;

        }
      }

      foreach ($method_settings as $method_setting) {
        switch ($method_setting) {

          case 'append':
          case 'set':
            $current_num = count($current_values);
            foreach ($values as $i => $value) {
              if (!$is_unlimited && $cardinality <= $current_num) {
                break;
              }
              if (!isset($existing[$i])) {
                $current_values[] = $value;
                $current_num++;
                $values_changed = TRUE;
              }
            }
            break;

          case 'prepend':
            $current_num = count($current_values);
            foreach (array_reverse($values, TRUE) as $i => $value) {
              if (!$is_unlimited && $cardinality <= $current_num) {
                break;
              }
              if (!isset($existing[$i])) {
                array_unshift($current_values, $value);
                $current_num++;
                $values_changed = TRUE;
              }
            }
            break;

          case 'remove':
            foreach ($existing as $k) {
              unset($current_values[$k]);
              $values_changed = TRUE;
            }
            break;

        }
      }

      if ($values_changed) {
        // Try to set the values. If that attempt fails, then it would throw an
        // exception, and the exception would be logged as an error.
        $update_target->setValue(array_values($current_values));
        $update_target->filterEmptyItems();
        foreach ($metadata['entities'] as $entity_to_save) {
          if (!in_array($entity_to_save, $all_entities_to_save, TRUE)) {
            $all_entities_to_save[] = $entity_to_save;
          }
        }
      }
    }
    foreach ($all_entities_to_save as $to_save) {
      $this->save($to_save);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $object;
    $entity_op = 'update';

    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = $entity->access($entity_op, $account, TRUE);

    $options = ['auto_append' => TRUE, 'access' => 'update'];
    foreach (array_keys($this->getFieldsToUpdate()) as $field) {
      $metadata = [];
      if ($this->getTypedProperty(EntityAdapter::createFromEntity($entity), $field, $options, $metadata)) {
        $result->andIf($metadata['access']);
      }
      else {
        throw new \InvalidArgumentException(sprintf("The provided field %s does not exist as a property path on the %s entity having ID %s.", $field, $entity->getEntityTypeId(), $entity->id()));
      }
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

}
