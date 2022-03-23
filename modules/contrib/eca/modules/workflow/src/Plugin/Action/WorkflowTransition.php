<?php

namespace Drupal\eca_workflow\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\OptionsInterface;
use Drupal\workflows\Entity\Workflow;

/**
 * Perform a workflow transition on an entity.
 *
 * @Action(
 *   id = "eca_workflow_transition",
 *   type = "entity",
 *   deriver = "Drupal\eca_workflow\Plugin\Action\WorkflowTransitionDeriver"
 * )
 */
class WorkflowTransition extends ConfigurableActionBase implements OptionsInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $entity = $storage->createRevision($entity, $entity->isDefaultRevision());
    $entity->set('moderation_state', $this->configuration['new_state']);
    if ($entity instanceof RevisionLogInterface) {
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionLogMessage($this->configuration['revision_log']);
      $entity->setRevisionUserId($this->currentUser->id());
    }
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::forbidden();
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    if ($object instanceof ContentEntityInterface && $workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($object)) {
      $current_state = $object->moderation_state->value;
      $workflowPlugin = $workflow->getTypePlugin();
      if ($workflowPlugin->hasState($current_state) && $workflowPlugin->getState($current_state)->canTransitionTo($this->configuration['new_state'])) {
        $result = AccessResult::allowed();
      }
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'new_state' => '',
      'revision_log' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'new_state') {
      $options = [];
      /** @var \Drupal\workflows\WorkflowInterface $workflow */
      $workflow = Workflow::load($this->getPluginDefinition()['workflow_id']);
      foreach ($workflow->getTypePlugin()->getStates() as $state) {
        $options[$state->id()] = $state->label();
      }
      return $options;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['new_state'] = [
      '#type' => 'select',
      '#title' => $this->t('New state'),
      '#options' => $this->getOptions('new_state'),
      '#default_value' => $this->configuration['new_state'],
      '#weight' => -10,
    ];
    $form['revision_log'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision Log'),
      '#default_value' => $this->configuration['revision_log'],
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['new_state'] = $form_state->getValue('new_state');
    $this->configuration['revision_log'] = $form_state->getValue('revision_log');
    parent::submitConfigurationForm($form, $form_state);
  }

}
