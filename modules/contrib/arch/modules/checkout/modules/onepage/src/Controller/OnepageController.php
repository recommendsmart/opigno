<?php

namespace Drupal\arch_onepage\Controller;

use Drupal\arch_cart\Cart\CartHandlerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * One page checkout controller.
 *
 * @package Drupal\arch_onepage\Controller
 */
class OnepageController extends ControllerBase {

  /**
   * Cart handler.
   *
   * @var \Drupal\arch_cart\Cart\CartInterface
   */
  protected $cart;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Page cache kill switcher.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * OnepageController constructor.
   *
   * @param \Drupal\arch_cart\Cart\CartHandlerInterface $cartHandler
   *   Cart handler.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   Page cache kill switch.
   */
  public function __construct(
    CartHandlerInterface $cartHandler,
    FormBuilderInterface $formBuilder,
    KillSwitch $killSwitch
  ) {
    $this->cart = $cartHandler->getCart();
    $this->formBuilder = $formBuilder;
    $this->killSwitch = $killSwitch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('arch_cart_handler'),
      $container->get('form_builder'),
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * Checkout Onepage action.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Checkout onepage form.
   */
  public function checkout(Request $request) {
    if ($this->cart->getCount() < 1) {
      $this->messenger()->addError(
        $this->t('To checkout, please place a product first to your shopping cart.', [], ['context' => 'arch_onepage'])
      );
      return $this->redirect('<front>');
    }

    $form = $this->formBuilder->getForm('Drupal\arch_onepage\Form\OnepageCheckoutForm');
    $this->killSwitch->trigger();

    return [
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
  }

}
