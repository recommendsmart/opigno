<?php

namespace Drupal\eca_content\Plugin\ECA\Condition;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;

/**
 * Plugin implementation of the ECA condition for empty entity field value.
 *
 * @EcaCondition(
 *   id = "eca_entity_field_value_empty",
 *   label = @Translation("Entity: field value is empty"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", label = @Translation("Entity"))
 *   }
 * )
 */
class EntityFieldValueEmpty extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $entity = $this->getValueFromContext('entity');
    $field_name = $this->tokenServices->replaceClear($this->configuration['field_name']);
    if ($entity instanceof FieldableEntityInterface && $entity->hasField($field_name)) {
      return $this->negationCheck($entity->get($field_name)->isEmpty());
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'field_name' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name'),
      '#default_value' => $this->configuration['field_name'],
      '#weight' => -10,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['field_name'] = $form_state->getValue('field_name');
    parent::submitConfigurationForm($form, $form_state);
  }

}
