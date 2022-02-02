<?php

namespace Drupal\digital_signage_computed_content\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the digsig_computed_content entity edit forms.
 */
class ComputedContentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New computed content %label has been created.', $message_arguments));
      $this->logger('digital_signage_computed_content')->notice('Created new digsig_computed_content %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The computed content %label has been updated.', $message_arguments));
      $this->logger('digital_signage_computed_content')->notice('Updated new digsig_computed_content %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.digsig_computed_content.canonical', ['digsig_computed_content' => $entity->id()]);
  }

}
