<?php

namespace Drupal\eca_form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Event\BaseHookHandler;

/**
 * The handler for hook implementations within the eca_form.module file.
 */
class HookHandler extends BaseHookHandler {

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param string $form_id
   */
  public function alter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $this->triggerEvent->dispatchFromPlugin('form:form_build', $form, $form_state, $form_id);
    // Add the handler on class-level, to avoid expensive and possibly faulty
    // serialization of nested object references during form submissions.
    $form['#validate'][] = [static::class, 'validate'];
    $form['#submit'][] = [static::class, 'submit'];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function validate(array $form, FormStateInterface $form_state): void {
    \Drupal::service('eca_form.hook_handler')->triggerEvent
      ->dispatchFromPlugin('form:form_validate', $form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function submit(array $form, FormStateInterface $form_state): void {
    \Drupal::service('eca_form.hook_handler')->triggerEvent
      ->dispatchFromPlugin('form:form_submit', $form, $form_state);
  }

}
