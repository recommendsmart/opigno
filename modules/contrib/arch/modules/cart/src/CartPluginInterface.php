<?php

namespace Drupal\arch_cart;

use Drupal\arch\ArchPluginInterface;
use Drupal\arch_cart\Cart\CartInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Cart plugin interface.
 *
 * @package Drupal\arch_cart
 */
interface CartPluginInterface extends ArchPluginInterface {

  /**
   * Cart form alter callback.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\arch_cart\Cart\CartInterface $cart
   *   Cart instance.
   */
  public function cartFormAlter(array &$form, FormStateInterface $form_state, CartInterface $cart);

  /**
   * Cart form validate callback.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\arch_cart\Cart\CartInterface $cart
   *   Cart instance.
   */
  public function cartFormValidate(array &$form, FormStateInterface $form_state, CartInterface $cart);

  /**
   * Cart form pre-submit callback.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\arch_cart\Cart\CartInterface $cart
   *   Cart instance.
   */
  public function cartFormPreSubmit(array &$form, FormStateInterface $form_state, CartInterface $cart);

  /**
   * Cart form post-submit callback.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\arch_cart\Cart\CartInterface $cart
   *   Cart instance.
   */
  public function cartFormPostSubmit(array &$form, FormStateInterface $form_state, CartInterface $cart);

}
