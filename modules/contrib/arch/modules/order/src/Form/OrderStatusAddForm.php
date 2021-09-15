<?php

namespace Drupal\arch_order\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for order status addition forms.
 *
 * @internal
 */
class OrderStatusAddForm extends OrderStatusBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // @todo Remove in favour of base method.
    return 'order_status_admin_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $this->commonForm($form);

    $form['#title'] = $this->t('Add order status', [], ['context' => 'arch_order']);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add order status', [], ['context' => 'arch_order']),
      '#name' => 'add_new_order_status',
      '#validate' => ['::validateCustom'],
      '#submit' => ['::submitForm', '::save'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $t_args = [
      '%order_status_label' => $this->entity->label(),
      '%order_status' => $this->entity->id(),
    ];
    $this->logger('Order Status')->notice(
      'The %order_status_label (%order_status) order status has been created.',
      $t_args
    );
    $this->messenger()
      ->addStatus(
        $this->t('The order status %order_status_label has been created and can now be used.', $t_args)
      );

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    // No actions needed.
    return [];
  }

  /**
   * Validates the language addition form on custom language button.
   */
  public function validateCustom(array $form, FormStateInterface $form_state) {
    $this->validateCommon($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $entity->set('id', $form_state->getValue('order_status'));
    $entity->set('label', $form_state->getValue('label'));
    $entity->set('description', $form_state->getValue('description'));
    $entity->set('default', $form_state->getValue('default'));
    $entity->set('locked', $form_state->getValue('locked'));

    // There is no weight on the edit form. Fetch all configurable languages
    // ordered by weight and set the new language to be placed after them.
    /** @var \Drupal\arch_order\Entity\OrderStatusInterface[] $order_statuses */
    $order_statuses = $this->orderStatusService->getOrderStatuses();
    $last_order_status = 0;
    foreach ($order_statuses as $order_status) {
      if ($order_status->getWeight() > $last_order_status) {
        $last_order_status = $order_status->getWeight();
      }
    }
    $entity->set('weight', $last_order_status + 1);
  }

}
