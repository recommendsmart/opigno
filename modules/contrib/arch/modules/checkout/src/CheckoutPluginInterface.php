<?php

namespace Drupal\arch_checkout;

use Drupal\arch\ArchPluginInterface;
use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Checkout plugin interface.
 *
 * @package Drupal\arch_checkout
 */
interface CheckoutPluginInterface extends ArchPluginInterface {

  /**
   * Checkout form alter.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order instance.
   */
  public function checkoutFormAlter(array &$form, FormStateInterface $form_state, OrderInterface $order);

  /**
   * Checkout form alter.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order instance.
   */
  public function checkoutFormValidate(array &$form, FormStateInterface $form_state, OrderInterface $order);

  /**
   * Checkout form alter.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order instance.
   */
  public function checkoutFormPreSubmit(array &$form, FormStateInterface $form_state, OrderInterface $order);

  /**
   * Checkout form alter.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order instance.
   */
  public function checkoutFormPostSubmit(array &$form, FormStateInterface $form_state, OrderInterface $order);

}
