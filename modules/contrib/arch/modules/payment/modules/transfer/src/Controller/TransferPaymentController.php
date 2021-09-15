<?php

namespace Drupal\arch_payment_transfer\Controller;

use Drupal\arch_payment\Controller\PaymentControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Transfer payment controller.
 */
class TransferPaymentController extends PaymentControllerBase {

  /**
   * {@inheritdoc}
   */
  public function paymentSuccess(Request $request) {
    // @todo Send payment information email.
    return $this->redirect('arch_checkout.complete', ['order_id' => $request->get('order')]);
  }

  /**
   * {@inheritdoc}
   */
  public function paymentCancel(Request $request) {
    // We can ignore this since we're unable to cancel transfer payment process.
  }

  /**
   * {@inheritdoc}
   */
  public function paymentError(Request $request) {
    // We can ignore this since transfer payment has no off-site part.
  }

}
