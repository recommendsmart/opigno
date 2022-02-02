<?php

namespace Drupal\expose_actions\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class Confirm extends ConfirmFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\System\Entity\Action
   */
  protected $action;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Confirm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    try {
      $this->loadAction($this->getRequest()->attributes->get('action'));
      $this->loadEntity($this->getRequest()->attributes->get('entity_type'), $this->getRequest()->attributes->get('entity_id'));
    } catch (InvalidPluginDefinitionException $e) {
      // TODO: Handle exception.
    } catch (PluginNotFoundException $e) {
      // TODO: Handle exception.
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Confirm {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * @param string|null $action_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadAction($action_id = NULL): void {
    if ($action_id !== NULL && $this->action === NULL) {
      $this->action = $this->entityTypeManager->getStorage('action')
        ->load($action_id);
    }
  }

  /**
   * @param string|null $entity_type
   * @param int|null $entity_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadEntity($entity_type = NULL, $entity_id = NULL): void {
    if ($entity_type !== NULL && $entity_id !== NULL && $this->entity === NULL) {
      $this->entity = $this->entityTypeManager->getStorage($entity_type)
        ->load($entity_id);
    }
  }

  /**
   * Check whether the user has 'administer' or 'overview' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @param string $action
   * @param string $entity_type
   * @param int $entity_id
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkAccess(AccountInterface $account, $action, $entity_type, $entity_id): AccessResultInterface {
    $this->loadAction($action);
    if ($this->action === NULL || !$account->hasPermission('access exposed action ' . $this->action->id())) {
      return AccessResult::forbidden();
    }
    $this->loadEntity($entity_type, $entity_id);
    return $this->entity->access('view', $account, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'confirm_expose_action';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('You are about to execute %action on @type %entity. This can not be undone.', [
      '%action' => $this->action->label(),
      '@type' => $this->entity->getEntityType()->getLabel(),
      '%entity' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getCancelUrl(): Url {
    return $this->entity->toUrl();
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->action->execute([$this->entity]);
    $this->messenger()->addStatus($this->t('%action completed!', [
      '%action' => $this->action->label(),
    ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
