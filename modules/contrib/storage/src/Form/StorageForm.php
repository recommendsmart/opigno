<?php

namespace Drupal\storage\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\storage\Entity\StorageType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Storage edit forms.
 *
 * @ingroup storage
 */
class StorageForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\storage\Entity\Storage $entity */
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @type</em> @title', [
        '@type' => $entity->bundle(),
        '@title' => $entity->label(),
      ]);
    }
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('storage', $entity->bundle());
    $form['name']['#title'] = $fields['name']->getLabel();

    // Load the bundle.
    $bundle = StorageType::load($entity->bundle());

    $revision_default = $bundle->get('new_revision');

    // Only expose the log field if so configured.
    if (!$bundle->shouldShowRevisionLog()) {
      $form['revision_log']['#access'] = FALSE;
    }

    if ($bundle->shouldShowRevisionToggle()) {
      if (!$this->entity->isNew()) {
        $form['new_revision'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Create new revision'),
          '#default_value' => $revision_default,
          '#weight' => 10,
        ];
      }
    }
    else {
      $form['new_revision'] = [
        '#type' => 'value',
        '#value' => $revision_default,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Storage.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Storage.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.storage.canonical', ['storage' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    // Explicitly set weights to a high value.
    $element['submit']['#weight'] = 100;
    if (array_key_exists('delete', $element)) {
      $element['delete']['#weight'] = 100;
    }

    return $element;
  }

}
