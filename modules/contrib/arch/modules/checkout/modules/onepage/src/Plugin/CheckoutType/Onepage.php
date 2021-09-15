<?php

namespace Drupal\arch_onepage\Plugin\CheckoutType;

use Drupal\arch_checkout\CheckoutType\CheckoutType;

/**
 * Defines a onepage plugin for checkout type plugins.
 *
 * @CheckoutType(
 *   id = "onepage",
 *   label = @Translation("Onepage Checkout"),
 *   admin_label = @Translation("Onepage Checkout"),
 *   description = @Translation("Makes it possible to customers to do the checkout within a single page."),
 *   form_class = "Drupal\arch_onepage\Form\OnepageCheckoutForm"
 * )
 */
class Onepage extends CheckoutType {

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    $form = parent::buildForm();
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'checkout--wrapper',
        ],
      ],
      'checkout_form' => [
        '#theme' => 'arch_checkout_op',
        '#checkoutform' => $form,
      ],
      'summary' => [
        '#theme' => 'arch_checkout_op_summary',
        '#cart' => $this->cart,
      ],
      '#cache' => ['max-age' => 0],
    ];

    return $build;
  }

}
