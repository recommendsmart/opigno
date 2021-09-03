<?php

namespace Drupal\crm_core_contact\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the Individual entity.
 */
class IndividualForm extends ContentEntityForm {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->messenger = $container->get('messenger');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $individual = $this->entity;

    $status = $individual->save();

    $args = ['%name' => $individual->label(), 'link' => $individual->toLink()->toString()];

    if ($status == SAVED_UPDATED) {
      $this->messenger->addMessage($this->t('The individual %name has been updated.', $args));
      if ($individual->access('view')) {
        $form_state->setRedirect('entity.crm_core_individual.canonical', ['crm_core_individual' => $individual->id()]);
      }
      else {
        $form_state->setRedirect('entity.crm_core_individual.collection');
      }
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger->addMessage($this->t('The individual %name has been added.', $args));
      $this->logger('crm_core_individual')->notice('Added individual %name.', $args);
      $form_state->setRedirect('entity.crm_core_individual.collection');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save @individual_type', [
      '@individual_type' => $this->entity->get('type')->entity->label(),
    ]);
    return $actions;
  }

}
