<?php

namespace Drupal\arch_cart\EventSubscriber;

use Drupal\arch_cart\Cart\CartHandlerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Login request event subscriber.
 *
 * @package Drupal\arch_cart\EventSubscriber
 */
class LoginRequestEventSubscriber implements EventSubscriberInterface, ContainerInjectionInterface {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Cart handler service.
   *
   * @var \Drupal\arch_cart\Cart\CartHandlerInterface
   */
  protected $cartHandler;

  /**
   * Cart store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $cartStoreFactory;

  /**
   * Previous cart values.
   *
   * @var array
   */
  protected $cartValues;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * LoginRequestEventSubscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match.
   * @param \Drupal\arch_cart\Cart\CartHandlerInterface $cart_handler
   *   Cart handler.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $cart_store_factory
   *   Cart store factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    RequestStack $request_stack,
    RouteMatchInterface $route_match,
    CartHandlerInterface $cart_handler,
    PrivateTempStoreFactory $cart_store_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
    $this->cartHandler = $cart_handler;
    $this->cartStoreFactory = $cart_store_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('arch_cart_handler'),
      $container->get('private.cart_store'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32. Otherwise, that aborts the request if no matching
    // route is found.
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckLogin'];
    $events[KernelEvents::FINISH_REQUEST][] = ['onKernelRequestFinish'];
    return $events;
  }

  /**
   * Get original cart content.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   */
  public function onKernelRequestCheckLogin(GetResponseEvent $event) {
    if (!$this->isLoginRequest()) {
      return;
    }

    $cart = $this->cartHandler->getCart(TRUE);
    $this->cartValues = $cart->getValues();
  }

  /**
   * Update new cart with original content.
   *
   * @param \Symfony\Component\HttpKernel\Event\FinishRequestEvent $event
   *   The finish request event.
   */
  public function onKernelRequestFinish(FinishRequestEvent $event) {
    if (!$this->isLoginRequest()) {
      return;
    }

    $store = $this->cartStoreFactory->get('arch_cart');
    $prev_cart = $store->get('cart');
    if (
      (
        is_null($prev_cart)
        || (is_array($prev_cart) && count($prev_cart) > 0)
      )
      && !empty($this->cartValues)
    ) {
      if (is_null($prev_cart)) {
        $new_cart = $this->cartValues;
      }
      else {
        $new_cart = NestedArray::mergeDeep($prev_cart, $this->cartValues);
      }

      try {
        $store->set('cart', $new_cart);
      }
      catch (\Exception $e) {
        // @todo Handle error.
      }
    }
  }

  /**
   * Check if current request is a login POST one.
   *
   * @return bool
   *   Returns TRUE if current request is a login POST request.
   */
  protected function isLoginRequest() {
    if (
      $this->routeMatch->getRouteName() == 'user.reset'
      && $this->requestStack->getCurrentRequest()->isMethod('GET')
    ) {
      return TRUE;
    }

    if (!$this->requestStack->getCurrentRequest()->isMethod('POST')) {
      return FALSE;
    }

    $login_routes = [
      // Normal login.
      'user.login',
      'user.login.http',

      // One time login or password reset form.
      'user.reset.form',
      'user.reset.login',
    ];
    $this->moduleHandler->alter('is_login_request', $login_routes);

    $route_name = $this->routeMatch->getRouteName();

    $first_try = in_array($route_name, $login_routes);
    if ($first_try) {
      return TRUE;
    }

    return $this->requestStack->getCurrentRequest()->request->get('form_id') === 'user_login_form';
  }

}
