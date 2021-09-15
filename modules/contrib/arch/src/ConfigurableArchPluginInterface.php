<?php

namespace Drupal\arch;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Configurable Arch plugin Interface.
 *
 * @package Drupal\arch
 */
interface ConfigurableArchPluginInterface extends ArchPluginInterface, PluginWithFormsInterface {

  /**
   * Alter plugin config form.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function configFormAlter(array &$form, FormStateInterface $form_state);

  /**
   * Config form validate callback.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function configFormValidate(array &$form, FormStateInterface $form_state);

  /**
   * Config form pre-submit callback.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function configFormPreSubmit(array &$form, FormStateInterface $form_state);

  /**
   * Config form post-submit callback.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function configFormPostSubmit(array &$form, FormStateInterface $form_state);

}
