<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Load referenced entity into token environment.
 *
 * @Action(
 *   id = "eca_token_load_entity_ref",
 *   label = @Translation("Entity: load via reference"),
 *   description = @Translation("Load a single entity that is referenced by an entity from the current scope or by certain properties, and store it as a token."),
 *   type = "entity"
 * )
 */
class LoadEntityRef extends LoadEntity {

  /**
   * {@inheritdoc}
   */
  protected function loadEntity($entity = NULL): ?EntityInterface {
    $entity = parent::loadEntity($entity);
    if (is_null($entity)) {
      return NULL;
    }
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \InvalidArgumentException('No content entity provided.');
    }
    $reference_field_name = $this->configuration['field_name_entity_ref'];
    if (!$entity->hasField($reference_field_name)) {
      throw new \InvalidArgumentException(sprintf('Field %s does not exist for entity type %s/%s.', $reference_field_name, $entity->getEntityTypeId(), $entity->bundle()));
    }
    $item_list = $entity->get($reference_field_name);
    if (!($item_list instanceof EntityReferenceFieldItemListInterface)) {
      throw new \InvalidArgumentException(sprintf('Field %s is not an entity reference field for entity type %s/%s.', $reference_field_name, $entity->getEntityTypeId(), $entity->bundle()));
    }
    $referenced = $item_list->referencedEntities();
    $this->entity = $referenced ? reset($referenced) : NULL;
    return $this->entity ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'field_name_entity_ref' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['field_name_entity_ref'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name entity reference'),
      '#default_value' => $this->configuration['field_name_entity_ref'],
      '#weight' => -8,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['field_name_entity_ref'] = $form_state->getValue('field_name_entity_ref');
    parent::submitConfigurationForm($form, $form_state);
  }

}
