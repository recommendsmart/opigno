<?php

namespace Drupal\arch_checkout\Controller;

use Drupal\arch_cart\Cart\CartHandlerInterface;
use Drupal\arch_checkout\CheckoutType\CheckoutTypeManagerInterface;
use Drupal\arch_checkout\CheckoutType\CheckoutTypePluginInterface;
use Drupal\arch_checkout\CheckoutType\Exception\CheckoutTypeException;
use Drupal\arch_checkout\Services\CheckoutCompletePageAccess;
use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_order\Services\OrderStatusService;
use Drupal\arch_payment\PaymentMethodManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Checkout page controller.
 *
 * @package Drupal\arch_checkout\Controller
 */
class CheckoutController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Checkout Type manager.
   *
   * @var \Drupal\arch_checkout\CheckoutType\CheckoutTypeManagerInterface
   */
  protected $checkoutTypeManager;

  /**
   * Payment method manager.
   *
   * @var \Drupal\arch_payment\PaymentMethodManagerInterface
   */
  protected $paymentMethodManager;

  /**
   * The page cache kill switch service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Cart.
   *
   * @var \Drupal\arch_cart\Cart\CartInterface
   */
  protected $cart;

  /**
   * Order status service.
   *
   * @var \Drupal\arch_order\Services\OrderStatusService
   */
  protected $orderStatus;

  /**
   * Checkout complete page access checker service.
   *
   * @var \Drupal\arch_checkout\Services\CheckoutCompletePageAccess
   */
  protected $checkoutCompletePageAccess;

  /**
   * CheckoutController constructor.
   *
   * @param \Drupal\arch_cart\Cart\CartHandlerInterface $cart_handler
   *   Cart handler.
   * @param \Drupal\arch_checkout\CheckoutType\CheckoutTypeManagerInterface $checkout_type_manager
   *   The checkout type manager.
   * @param \Drupal\arch_payment\PaymentMethodManagerInterface $payment_method_manager
   *   Payment manager.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   Page cache kill switch service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Theme manager.
   * @param \Drupal\arch_order\Services\OrderStatusService $order_status
   *   Order status service.
   * @param \Drupal\arch_checkout\Services\CheckoutCompletePageAccess $checkoutCompletePageAccess
   *   Checkout complete page access checker service.
   */
  public function __construct(
    CartHandlerInterface $cart_handler,
    CheckoutTypeManagerInterface $checkout_type_manager,
    PaymentMethodManagerInterface $payment_method_manager,
    KillSwitch $kill_switch,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager,
    OrderStatusService $order_status,
    CheckoutCompletePageAccess $checkoutCompletePageAccess
  ) {
    $this->cart = $cart_handler->getCart();
    $this->checkoutTypeManager = $checkout_type_manager;
    $this->paymentMethodManager = $payment_method_manager;
    $this->pageCacheKillSwitch = $kill_switch;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->orderStatus = $order_status;
    $this->checkoutCompletePageAccess = $checkoutCompletePageAccess;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('arch_cart_handler'),
      $container->get('plugin.manager.checkout_type'),
      $container->get('plugin.manager.payment_method'),
      $container->get('page_cache_kill_switch'),
      $container->get('module_handler'),
      $container->get('theme.manager'),
      $container->get('order.statuses'),
      $container->get('arch_checkout.checkout_complete_page.access')
    );
  }

  /**
   * Access callback.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Result.
   */
  public function checkoutAccess(AccountInterface $account) {
    if (
      $account->isAnonymous()
      && !$this->checkoutTypeManager->isAnonymousCheckoutAllowed()
    ) {
      $reason = $this->t(
        'Please <a href="@register_url">create an account</a> or <a href="@login_url">sign in</a> to checkout!',
        [
          '@register_url' => Url::fromRoute('user.register')->toString(),
          '@login_url' => Url::fromRoute('user.login')->toString(),
        ],
        ['context' => 'arch_checkout']
      );
      $this->messenger()->addError($reason);
      return AccessResult::forbidden((string) $reason);
    }

    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * Checkout page handler.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response or render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function checkout() {
    if (
      !$this->cart->hasProduct()
      && $this->checkoutTypeManager->shouldRedirectIfCartEmpty()
    ) {
      return $this->redirect('arch_cart.content');
    }

    try {
      $default_plugin = $this->checkoutTypeManager->getDefaultCheckoutType();

      // Let other modules alterate the selected plugin for checkout page.
      $this->moduleHandler->alter('checkout_plugin', $default_plugin, $this->checkoutTypeManager);
    }
    catch (CheckoutTypeException $ce) {
      $this->getLogger('Checkout')->error($ce->getMessage());
      $this->messenger()->addError('Sorry, something went wrong during processing your request. Please contact site admin if it is persist.');
      return $this->redirect('<front>');
    }

    if (empty($default_plugin)) {
      $this->getLogger('Checkout')->error('No default checkout type is set.');
      $this->messenger()->addError('Sorry, something went wrong during processing your request. Please contact site admin if it is persist.');
      return $this->redirect('<front>');
    }

    /** @var \Drupal\arch_checkout\CheckoutType\CheckoutTypePluginInterface $plugin */
    $plugin = $this->checkoutTypeManager->createInstance($default_plugin['id'], []);
    if (!($plugin instanceof CheckoutTypePluginInterface)) {
      $this->getLogger('Checkout')->error('The default checkout type which is set is not implements the required interface.');
      $this->messenger()->addError('Sorry, something went wrong during processing your request. Please contact site admin if it is persist.');
      return $this->redirect('<front>');
    }

    $form = $plugin->build();
    $form['#cache']['contexts'][] = 'user';
    $form['#cache']['contexts'][] = 'session';
    $form['#cache']['max-age'] = 0;

    // Let modules alterate the output.
    $this->moduleHandler->alter('checkout_page', $form, $plugin);
    $this->themeManager->alter('checkout_page', $form, $plugin);

    return $form;
  }

  /**
   * Page title callback for complete page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title.
   */
  public function completeTitle(Request $request) {
    /** @var \Drupal\arch_order\Entity\Order $order */
    $order = $request->get('order_id');
    $title = $this->t('Thank you for your purchase!', [], ['context' => 'arch_checkout']);

    // Let modules alterate the title.
    $this->moduleHandler->alter('checkout_complete_page_title', $title, $order);

    return $title;
  }

  /**
   * Checkout complete action.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Complete page content.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function complete(Request $request) {
    /** @var \Drupal\arch_order\Entity\Order $order */
    $order = $request->get('order_id');

    if ($order->getOwner()->getEmail() !== $this->currentUser()->getEmail()) {
      if ($this->currentUser()->isAuthenticated()) {
        throw new AccessDeniedHttpException();
      }
      elseif (!$this->checkoutTypeManager->isAnonymousCheckoutAllowed()) {
        throw new AccessDeniedHttpException();
      }
    }

    if (!$this->checkoutCompletePageAccess->canAccess($order)) {
      return $this->redirect('<front>');
    }

    // Make sure we will not see a cached checkout complete page.
    $this->pageCacheKillSwitch->trigger();

    $payment_method = NULL;
    $payment_method_id = $order->get('payment_method')->getString();
    if (!empty($payment_method_id)) {
      /** @var \Drupal\arch_payment\PaymentMethodInterface $payment_method */
      $payment_method = $this->paymentMethodManager->createInstance($payment_method_id);
    }

    // Make it possible to Payment Methods alterate the output.
    $checkout_complete_info = NULL;
    if ($payment_method instanceof CheckoutCompleteInterface) {
      $checkout_complete_info = $payment_method->checkoutCompleteInfo($order);
    }

    $output = [
      '#theme' => 'arch_checkout_complete',
      '#order' => $order,
      '#message' => $this->t('We have received your order, and we are getting it ready.', [], ['context' => 'arch_checkout']),
      '#message_extra' => $this->t('You will receive a confirmation e-mail shortly.', [], ['context' => 'arch_checkout']),
      '#checkout_complete_info' => $checkout_complete_info,
    ];

    // Let modules alterate the output.
    $this->moduleHandler->alter('checkout_complete_page', $output, $order);
    $this->themeManager->alter('checkout_complete_page', $output, $order);

    /** @var \Drupal\arch_order\Entity\OrderStatusInterface[] $orderStatuses */
    $orderStatuses = $this->orderStatus->getOrderStatuses();

    $new_status = 'completed';
    $this->moduleHandler->alter('checkout_complete_order_status', $new_status, $order);
    if (!in_array($new_status, array_keys($orderStatuses))) {
      $new_status = 'completed';
    }

    if (
      $order->get('status')->getString() != 'completed'
      && $this->shouldChangeOrderStatusToCompleted($order)
    ) {
      $order->setNewRevision();
      $order->setRevisionUserId($this->currentUser()->id());
      $order->setRevisionLogMessage('Order status has changed from "' . $order->get('status')->getString() . '" to "' . $new_status . '".');
      $order->setRevisionCreationTime(time());

      // Complete order.
      $order->set('status', $new_status);

      // Update order.
      $order->save();

      $this->moduleHandler->invokeAll('checkout_completed', [$order]);
      $this->moduleHandler->alter('checkout_completed_page', $output, $order);
      $this->themeManager->alter('checkout_completed_page', $output, $order);
    }

    // Reset cart.
    $this->cart->resetStore();

    return $output;
  }

  /**
   * Check we should update order status to "complete".
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order to change.
   *
   * @return bool
   *   Return TRUE of order status change is not disallowed.
   */
  protected function shouldChangeOrderStatusToCompleted(OrderInterface $order) {
    $result = $this->moduleHandler->invokeAll('checkout_complete_page_should_update_order_status', [
      $order,
    ]);
    return !in_array(FALSE, $result, TRUE);
  }

}
