<?php

namespace Drupal\if_then_else\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\if_then_else\Entity\IfthenelseRuleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a ifthenelseRule clone entity form.
 */
class IfthenelseEntityCloneForm extends FormBase {

  /**
   * Constructs an IfthenelseEntityCloneForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ifthenelse_clone_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, IfthenelseRuleInterface $ifthenelserule = NULL) {
    $form['#ifthenelserule'] = $ifthenelserule;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#default_value' => $ifthenelserule->label() . ' Clone',
      '#weight' => 10,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ifthenelserule->id() . '_clone',
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => FALSE,
      '#weight' => 15,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->exist($form_state->getValue('id'))) {
      $form_state->setErrorByName('label', $this->t('The machine-readable name is already in use. It must be unique.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ifthenelserule = $form['#ifthenelserule'];
    $clone_rule = $ifthenelserule->createDuplicate();
    $clone_rule->id = $form_state->getValue('id');
    $clone_rule->label = $form_state->getValue('label');
    $clone_rule->save();
    $this->messenger->addMessage($this->t('%rule_label is duplicated.', ['%rule_label' => $ifthenelserule->label()]));
    $path = Url::fromRoute(
      'entity.ifthenelserule.edit_form',
      ['ifthenelserule' => $clone_rule->id]
    );
    $form_state->setRedirectUrl($path);
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('ifthenelserule')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
