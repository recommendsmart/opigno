<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Set the value of an entity field.
 *
 * @Action(
 *   id = "eca_set_field_value",
 *   label = @Translation("Entity: set field value"),
 *   description = @Translation("Allows to set, unset or change the value(s) of any field in an entity."),
 *   type = "entity"
 * )
 */
class SetFieldValue extends FieldUpdateActionBase implements EcaFieldUpdateActionInterface {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    $name = $this->tokenServices->replaceClear($this->configuration['field_name']);

    // Process the field values.
    $values = $this->configuration['field_value'];
    $use_token_replace = TRUE;
    // When the given input is not too large, check whether it wants to directly
    // use defined data.
    if ((mb_strlen($values) <= 255) && ($data = $this->tokenServices->getTokenData($values))) {
      if (!($data instanceof TypedDataInterface) || !empty($data->getValue())) {
        $use_token_replace = FALSE;
        $values = $data;
      }
    }
    if ($use_token_replace) {
      $values = $this->tokenServices->replaceClear($values);
    }

    return [$name => $values];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'field_name' => '',
      'field_value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name'),
      '#description' => $this->t('The name of the field, that should be changed; identified by the field ID. This property supports tokens.'),
      '#default_value' => $this->configuration['field_name'],
      '#weight' => -10,
    ];
    $form['field_value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Field value'),
      '#description' => $this->t('The new field value. This property supports tokens.'),
      '#default_value' => $this->configuration['field_value'],
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['field_name'] = $form_state->getValue('field_name');
    $this->configuration['field_value'] = $form_state->getValue('field_value');
    parent::submitConfigurationForm($form, $form_state);
  }

}
