<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Load referenced entity into token environment.
 *
 * @Action(
 *   id = "eca_token_load_entity_ref",
 *   label = @Translation("Token: load entity of reference"),
 *   type = "entity"
 * )
 */
class TokenLoadEntityRef extends TokenLoadEntity {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    if ($entity === NULL) {
      throw new \Exception('No entity provided.');
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$entity->hasField($this->configuration['field_name_entity_ref'])) {
      throw new \Exception(sprintf('Field %s does not exist for entity type %s/%s.', $this->configuration['field_name_entity_ref'], $entity->getEntityTypeId(), $entity->bundle()));
    }
    if (($first = $entity->get($this->configuration['field_name_entity_ref'])->first()) && $referencedEntity = $first->get('entity')->getTarget()->getValue()) {
      parent::execute($referencedEntity);
      return;
    }
    throw new \Exception('No entity being referenced.');
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
