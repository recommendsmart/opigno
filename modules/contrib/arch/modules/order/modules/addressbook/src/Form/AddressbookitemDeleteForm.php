<?php

namespace Drupal\arch_addressbook\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a addressbookitem entity.
 */
class AddressbookitemDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if (!\Drupal::currentUser()->hasPermission('administer addressbookitem entity')) {
      $form_state->setRedirectUrl(Url::fromRoute('entity.user.canonical', ['user' => \Drupal::currentUser()->id()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    return \Drupal::currentUser()->hasPermission('administer addressbookitem entity')
      ? $entity->toUrl('collection')
      : Url::fromRoute('entity.user.canonical', ['user' => \Drupal::currentUser()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the address %label?', ['%label' => $this->getEntity()->label()], ['context' => 'arch_addressbook']);
  }

  /**
   * Gets the message to display to the user after deleting the entity.
   *
   * @return string
   *   The translated string of the deletion message.
   */
  protected function getDeletionMessage() {
    return $this->t('The %label address has been deleted.', ['%label' => $this->getEntity()->label()], ['context' => 'arch_addressbook']);
  }
}
