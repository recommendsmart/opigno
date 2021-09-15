<?php

namespace Drupal\arch_addressbook\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;

/**
 * Form controller for the addressbookitem entity edit forms.
 */
class AddressbookitemForm extends ContentEntityForm {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    AccountInterface $current_user,
    MessengerInterface $messenger
  ) {
    parent::__construct(
      $entity_repository,
      $entity_type_bundle_info,
      $time
    );

    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Set new Revision.
    // @todo Why we create new revision on every save?
    $this->entity->setNewRevision();
    $this->entity->setRevisionUserId($this->currentUser->id());

    $this->entity->setRevisionLogMessage($this->t('Changes has made by the user.'));
    $this->entity->setRevisionCreationTime(time());

    // parent::save() MUST CALL AFTER ENTITY MODIFICATIONS!
    $status = parent::save($form, $form_state);

    if ($status === SAVED_UPDATED) {
      $message = $this->t('The %address has been updated.', [
        '%address' => $this->entity->toLink($this->t('address'))->toString(),
      ], ['context' => 'arch_addressbook']);
    }
    else {
      $message = $this->t('The %address has been added.', [
        '%address' => $this->entity->toLink($this->t('address'))->toString(),
      ], ['context' => 'arch_addressbook']);
    }

    $this->messenger->addMessage($message);

    if ($this->currentUser->hasPermission('administer addressbookitem entity')) {
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
    else {
      $form_state->setRedirectUrl(Url::fromRoute('entity.user.canonical', ['user' => $this->currentUser->id()]));
    }

    return $status;
  }

}
