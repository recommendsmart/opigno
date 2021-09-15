<?php

namespace Drupal\arch_payment\Controller;

use Drupal\arch_payment\PaymentMethodManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Payment method configure controller.
 *
 * @package Drupal\arch_payment\Controller
 */
class PaymentMethodConfigureController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Payment method manager.
   *
   * @var \Drupal\arch_payment\PaymentMethodManagerInterface
   */
  protected $paymentMethodManager;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * OverviewForm constructor.
   *
   * @param \Drupal\arch_payment\PaymentMethodManagerInterface $payment_method_manager
   *   Payment method manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Form builder.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination helper.
   */
  public function __construct(
    PaymentMethodManagerInterface $payment_method_manager,
    FormBuilderInterface $form_builder,
    RedirectDestinationInterface $redirect_destination
  ) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->formBuilder = $form_builder;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.payment_method'),
      $container->get('form_builder'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Payment method configure form.
   *
   * @param string $payment_method
   *   Plugin id.
   *
   * @return array
   *   Render array.
   *
   * @throws \Exception
   *   If defined class is not a PaymentMethodFormInterface class.
   */
  public function settings($payment_method) {
    $method = $this->getPaymentMethod($payment_method);
    $form = $this->formBuilder->getForm(
      '\Drupal\arch_payment\Form\PaymentMethodForm',
      $method
    );

    return $form;
  }

  /**
   * Payment method disable form.
   *
   * @param string $payment_method
   *   Plugin id.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array.
   */
  public function disable($payment_method) {
    $method = $this->getPaymentMethod($payment_method);
    $method->disable();

    $this->messenger()->addMessage(
      $this->t('%label payment method is disabled', ['%label' => $method->getLabel()])
    );
    return $this->handleRedirect();
  }

  /**
   * Payment method enable form.
   *
   * @param string $payment_method
   *   Plugin id.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array.
   */
  public function enable($payment_method) {
    $method = $this->getPaymentMethod($payment_method);
    $method->enable();

    $this->messenger()->addMessage(
      $this->t('%label payment method is enabled', ['%label' => $method->getLabel()])
    );
    return $this->handleRedirect();
  }

  /**
   * Handle redirect.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  protected function handleRedirect() {
    $destination = Url::fromUserInput($this->redirectDestination->get());
    if ($destination->isRouted()) {
      return $this->redirect($destination->getRouteName());
    }
    return $this->redirect('arch_payment.payment_method.overview');
  }

  /**
   * Get payment method.
   *
   * @param string $payment_method
   *   Payment method plugin ID.
   *
   * @return \Drupal\arch_payment\PaymentMethodInterface
   *   Payment method plugin instance.
   */
  protected function getPaymentMethod($payment_method) {
    /** @var \Drupal\arch_payment\PaymentMethodInterface $method */
    $method = $this->paymentMethodManager->getPaymentMethod($payment_method);
    if (empty($method)) {
      throw new NotFoundHttpException();
    }
    return $method;
  }

}
