<?php

namespace Drupal\arch_shipping\Controller;

use Drupal\arch_shipping\ShippingMethodManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Shipping method configure controller.
 *
 * @package Drupal\arch_shipping\Controller
 */
class ShippingMethodConfigureController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Shipping method manager.
   *
   * @var \Drupal\arch_shipping\ShippingMethodManagerInterface
   */
  protected $shippingMethodManager;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * OverviewForm constructor.
   *
   * @param \Drupal\arch_shipping\ShippingMethodManagerInterface $shipping_method_manager
   *   Shipping method manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Form builder.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination helper.
   */
  public function __construct(
    ShippingMethodManagerInterface $shipping_method_manager,
    FormBuilderInterface $form_builder,
    RedirectDestinationInterface $redirect_destination
  ) {
    $this->shippingMethodManager = $shipping_method_manager;
    $this->formBuilder = $form_builder;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.shipping_method'),
      $container->get('form_builder'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Shipping method configure form.
   *
   * @param string $shipping_method
   *   Plugin id.
   *
   * @return array
   *   Render array.
   */
  public function settings($shipping_method) {
    $method = $this->getShippingMethod($shipping_method);

    $form = $this->formBuilder->getForm(
      '\Drupal\arch_shipping\Form\ShippingMethodForm',
      $method
    );

    return $form;
  }

  /**
   * Shipping method disable form.
   *
   * @param string $shipping_method
   *   Plugin id.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array.
   */
  public function disable($shipping_method) {
    $method = $this->getShippingMethod($shipping_method);
    $method->disable();

    $this->messenger()->addMessage(
      $this->t('%label shipping mode is disabled', ['%label' => $method->getLabel()])
    );
    return $this->handleRedirect();
  }

  /**
   * Shipping method enable form.
   *
   * @param string $shipping_method
   *   Plugin id.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array.
   */
  public function enable($shipping_method) {
    $method = $this->getShippingMethod($shipping_method);
    $method->enable();

    $this->messenger()->addMessage(
      $this->t('%label shipping mode is enabled', ['%label' => $method->getLabel()])
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
    return $this->redirect('arch_shipping.shipping_method.overview');
  }

  /**
   * Get shipping method.
   *
   * @param string $shipping_method
   *   Shipping method plugin ID.
   *
   * @return \Drupal\arch_shipping\ShippingMethodInterface
   *   Shipping method plugin instance.
   */
  protected function getShippingMethod($shipping_method) {
    /** @var \Drupal\arch_shipping\ShippingMethodInterface $method */
    $method = $this->shippingMethodManager->getShippingMethod($shipping_method);
    if (empty($method)) {
      throw new NotFoundHttpException();
    }
    return $method;
  }

}
