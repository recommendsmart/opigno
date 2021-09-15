<?php

namespace Drupal\arch_payment_saferpay\Controller;

use Drupal\arch_payment\Controller\PaymentControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\arch_payment_saferpay\Saferpay\SaferpayHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;

/**
 * Saferpay payment controller.
 */
class SaferpayPaymentController extends PaymentControllerBase {

  /**
   * SaferPay handler service.
   *
   * @var \Drupal\arch_payment_saferpay\Saferpay\SaferpayHandlerInterface
   */
  protected $saferPayHandler;

  /**
   * Constructs a SaferpayPaymentController object.
   *
   * @param \Drupal\arch_payment_saferpay\Saferpay\SaferpayHandlerInterface $saferpay_handler
   *   The module handler service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(
    SaferpayHandlerInterface $saferpay_handler,
    MessengerInterface $messenger
  ) {
    $this->saferPayHandler = $saferpay_handler;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('arch_payment_saferpay_handler'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function paymentSuccess(Request $request) {

    $order_id = $request->get('order', NULL);
    if (
      !empty($order_id)
      && $this->saferPayHandler->setOrder($order_id)
    ) {
      $response = $this->saferPayHandler->callAssert();
      if (isset($response['Transaction']['Status']) && strtolower($response['Transaction']['Status']) != 'captured') {
        $this->saferPayHandler->callCapture($response['Transaction']['Id']);
      }
    }

    return $this->redirect('arch_checkout.complete', ['order_id' => $request->get('order')]);
  }

  /**
   * {@inheritdoc}
   */
  public function redirectPage(Request $request) {
    $order_id = $request->get('order', NULL);
    if (
      !empty($order_id)
      && $this->saferPayHandler->setOrder($order_id)
    ) {
      try {
        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->saferPayHandler->callInitialize();
        if (!empty($response)) {
          $body = (array) Json::decode($response->getBody());
          if (!empty($body['RedirectUrl'])) {
            return new TrustedRedirectResponse($body['RedirectUrl']);
          }
        }
      }
      catch (RequestException $exception) {
        watchdog_exception('error', $exception);
      }
    }

    $this->messenger()->addError($this->t('An error occurred and processing did not complete. If this error is persistent please contact the site administrator.'));
    return $this->redirect('arch_checkout.checkout');
  }

  /**
   * {@inheritdoc}
   */
  public function paymentCancel(Request $request) {
    $this->messenger->addMessage(
      $this->t('This process has been cancelled.'),
      'error'
    );
    return $this->redirect('arch_checkout.checkout');
  }

  /**
   * {@inheritdoc}
   */
  public function paymentError(Request $request) {
    $this->messenger->addMessage(
      $this->t('An error occurred and processing did not complete.'),
      'error'
    );
    return $this->redirect('arch_checkout.checkout');
  }

}
