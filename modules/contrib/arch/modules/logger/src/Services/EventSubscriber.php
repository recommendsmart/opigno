<?php

namespace Drupal\arch_logger\Services;

use Drupal\arch_cart\Cart\CartHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the dynamic route events.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Cart handler service.
   *
   * @var \Drupal\arch_cart\Cart\CartHandlerInterface
   */
  protected $cartHandler;

  /**
   * Logger service.
   *
   * @var \Drupal\arch_logger\Services\ArchLogger
   */
  protected $logger;

  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Original content of cart.
   *
   * @var array
   */
  protected $originalCart;

  /**
   * New content of cart.
   *
   * @var array
   */
  protected $newCart;

  /**
   * EventSubscriber constructor.
   *
   * @param \Drupal\arch_cart\Cart\CartHandlerInterface $cart_handler
   *   Cart handler service.
   * @param \Drupal\arch_logger\Services\ArchLogger $logger
   *   Logger service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   */
  public function __construct(
    CartHandlerInterface $cart_handler,
    ArchLogger $logger,
    RouteMatchInterface $route_match,
    RequestStack $request_stack
  ) {
    $this->routeMatch = $route_match;
    $this->cartHandler = $cart_handler;
    $this->logger = $logger;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['getOriginalCart'];
    $events[KernelEvents::FINISH_REQUEST][] = ['logCartChanges'];
    return $events;
  }

  /**
   * Get original cart content.
   */
  public function getOriginalCart(GetResponseEvent $event) {
    if (!$this->isCartRequest()) {
      return;
    }

    $this->originalCart = $this->cartHandler->getCart()->getValues();
  }

  /**
   * Log cart changes.
   */
  public function logCartChanges(FinishRequestEvent $event) {
    if (!$this->isCartRequest()) {
      return;
    }

    $this->newCart = $this->cartHandler->getCart()->getValues();

    $route_name = $this->routeMatch->getRouteName();
    switch ($route_name) {
      case 'arch_cart.api.cart_add':
        $message = 'Product added to cart by user.';
        break;

      case 'arch_cart.api.cart_quantity':
        $message = 'The number of pieces in your cart is modified by user.';
        break;

      case 'arch_cart.api.cart_remove':
        $message = 'Product removed from cart by user.';
        break;

      default:
        $message = 'Cart changed.';
    }

    $this->logger->storeCartLog(
      $this->originalCart,
      $this->newCart,
      $message
    );
  }

  /**
   * Validate current route name.
   *
   * @return bool
   *   True if the current route is an cart route.
   */
  private function isCartRequest() {
    if (!$this->requestStack->getCurrentRequest()->isMethod('POST')) {
      return FALSE;
    }

    $cart_routes = [
      'arch_cart.content',
      'arch_cart.api.cart_add',
      'arch_cart.api.cart_quantity',
      'arch_cart.api.cart_remove',
    ];

    $route_name = $this->routeMatch->getRouteName();

    return in_array($route_name, $cart_routes);
  }

}
