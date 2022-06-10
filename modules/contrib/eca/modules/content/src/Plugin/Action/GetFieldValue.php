<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\TypedData\PropertyPathTrait;

/**
 * Get the value of an entity field.
 *
 * @Action(
 *   id = "eca_get_field_value",
 *   label = @Translation("Entity: get field value"),
 *   description = @Translation("Get the value of any field in an entity and store it as a token."),
 *   type = "entity"
 * )
 */
class GetFieldValue extends ConfigurableActionBase {

  use PropertyPathTrait;

  /**
   * {@inheritdoc}
   */
  protected function getFieldName(): string {
    return (string) $this->tokenServices->replace($this->configuration['field_name']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'field_name' => '',
      'token_name' => '',
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
      '#description' => $this->t('The machine name of the field, that holds the value. This property supports tokens.'),
      '#default_value' => $this->configuration['field_name'],
      '#weight' => -10,
    ];
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#description' => $this->t('The field value will be loaded into this specified token.'),
      '#weight' => -5,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    $this->configuration['field_name'] = $form_state->getValue('field_name');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::forbidden();
    if (!($object instanceof AccessibleInterface)) {
      return $return_as_object ? $result : $result->isAllowed();
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $object;

    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = $entity->access('view', $account, TRUE);

    $options = ['access' => 'view'];
    $metadata = [];
    $field_name = $this->getFieldName();
    $read_target = $this->getTypedProperty(EntityAdapter::createFromEntity($entity), $field_name, $options, $metadata);
    if (!isset($metadata['access']) || (!$read_target && $metadata['access']->isAllowed())) {
      throw new \InvalidArgumentException(sprintf("The provided field %s does not exist as a property path on the %s entity having ID %s.", $field_name, $entity->getEntityTypeId(), $entity->id()));
    }
    $result = $result->andIf($metadata['access']);

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!($entity instanceof EntityInterface)) {
      return;
    }
    $options = ['access' => 'view'];
    $metadata = [];
    $token_name = $this->configuration['token_name'];
    $field_name = $this->getFieldName();
    $read_target = $this->getTypedProperty(EntityAdapter::createFromEntity($entity), $field_name, $options, $metadata);
    if (!isset($metadata['access']) || (!$read_target && $metadata['access']->isAllowed())) {
      throw new \InvalidArgumentException(sprintf("The provided field %s does not exist as a property path on the %s entity having ID %s.", $field_name, $entity->getEntityTypeId(), $entity->id()));
    }
    $this->tokenServices->addTokenData($token_name, $read_target);
  }

}
