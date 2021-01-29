<?php

namespace Drupal\grequest\Entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grequest\MembershipRequestManager;
use Drupal\group\Plugin\Validation\Constraint\GroupContentCardinality;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for requesting a group membership.
 */
class GroupMembershipRequestForm extends ContentEntityConfirmFormBase {

  /**
   * Membership request manager.
   *
   * @var Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * Constructs a request membership form.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param Drupal\grequest\MembershipRequestManager $membership_request_manager
   *   Membership request manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, MembershipRequestManager $membership_request_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->membershipRequestManager = $membership_request_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('grequest.membership_request_manager')
    );
  }

  /**
   * Returns the plugin responsible for this piece of group content.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The responsible group content enabler plugin.
   */
  protected function getContentPlugin() {
    return $this->getEntity()->getContentPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Request membership for group %label.', ['%label' => $this->getEntity()->getGroup()->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->getGroup()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Request group membership');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $violations = $this->getEntity()->validate();
    foreach ($violations as $violation) {
      $constraint = $violation->getConstraint();
      if ($constraint instanceof GroupContentCardinality && $constraint->entityMessage == $violation->getMessage()->getUntranslatedString()) {
        $form_state->setError($form, $this->t('You have already sent a request'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $group_content = $this->getEntity();

    $result = $this->membershipRequestManager->saveRequest($group_content);

    if ($result) {
      $this->messenger()->addStatus($this->t('Your request is waiting for approval'));
    }
    else {
      $this->messenger()->addError($this->t('You has not been sent!'));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
