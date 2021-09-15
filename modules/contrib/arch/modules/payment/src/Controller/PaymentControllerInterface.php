<?php

namespace Drupal\arch_payment\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Payment controller interface.
 *
 * @package Drupal\arch_payment
 */
interface PaymentControllerInterface {

  /**
   * Handle successful payment.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to one of a checkout module defined end-point.
   */
  public function paymentSuccess(Request $request);

  /**
   * Handle cancelled payment.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to one of a checkout module defined end-point.
   */
  public function paymentCancel(Request $request);

  /**
   * Handle payment error.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to one of a checkout module defined end-point.
   */
  public function paymentError(Request $request);

}
