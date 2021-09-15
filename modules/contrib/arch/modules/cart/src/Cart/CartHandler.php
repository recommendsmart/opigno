<?php

namespace Drupal\arch_cart\Cart;

use Drupal\arch_price\Manager\PriceTypeManagerInterface;
use Drupal\arch_price\Manager\VatCategoryManagerInterface;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Cart handler service.
 *
 * @package Drupal\arch_cart\Cart
 */
class CartHandler implements CartHandlerInterface, ContainerInjectionInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Price type manager.
   *
   * @var \Drupal\arch_price\Manager\PriceTypeManagerInterface
   */
  protected $priceTypeManager;

  /**
   * VAT category manager.
   *
   * @var \Drupal\arch_price\Manager\VatCategoryManagerInterface
   */
  protected $vatCategoryManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Price factory.
   *
   * @var \Drupal\arch_price\Price\PriceFactoryInterface
   */
  protected $priceFactory;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Cart instance.
   *
   * @var \Drupal\arch_cart\Cart\CartInterface
   */
  protected $cart;

  /**
   * Session data.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * CartHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\arch_price\Manager\PriceTypeManagerInterface $price_type_manager
   *   Price type manager.
   * @param \Drupal\arch_price\Manager\VatCategoryManagerInterface $vat_category_manager
   *   VAT category manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\arch_price\Price\PriceFactoryInterface $price_factory
   *   Price factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Temp store factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Session.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    PriceTypeManagerInterface $price_type_manager,
    VatCategoryManagerInterface $vat_category_manager,
    ModuleHandlerInterface $module_handler,
    AccountInterface $current_user,
    PriceFactoryInterface $price_factory,
    TimeInterface $time,
    PrivateTempStoreFactory $temp_store_factory,
    RequestStack $request_stack,
    SessionInterface $session
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->priceTypeManager = $price_type_manager;
    $this->vatCategoryManager = $vat_category_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->priceFactory = $price_factory;
    $this->time = $time;

    if (
      $this->currentUser->isAnonymous()
      && !$request_stack->getCurrentRequest()->hasSession()
    ) {
      $request_stack->getCurrentRequest()->setSession($session);
      $session->start();
    }
    $this->tempStore = $temp_store_factory->get('arch_cart');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('price_type.manager'),
      $container->get('vat_category.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('datetime.time'),
      $container->get('price_factory'),
      $container->get('private.cart_store'),
      $container->get('request_stack'),
      $container->get('session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCart($force_read = FALSE) {
    if (!$this->cart || $force_read) {
      $this->cart = new Cart($this->tempStore);
    }

    if (!$this->cart->getModuleHandler()) {
      $this->cart->setModuleHandler($this->moduleHandler);
    }

    if (!$this->cart->getPriceFactory()) {
      $this->cart->setPriceFactory($this->priceFactory);
    }

    $total_base_values = $this->getTotalBaseValues();
    $this->cart->setTotalBaseValues($total_base_values);

    $default_price = $this->getDefaultPriceValues();
    $this->cart->setDefaultPriceValues($default_price);

    return $this->cart;
  }

  /**
   * Get default price values.
   *
   * @return array
   *   Price values.
   */
  protected function getDefaultPriceValues() {
    if (!isset($this->defaultPriceValues)) {
      $price_type = $this->priceTypeManager->getDefaultPriceType();
      $vat_category = $this->vatCategoryManager->getVatCategory($price_type->getDefaultVatCategory());
      $this->defaultPriceValues = [
        'base' => $price_type->getDefaultCalculationBase(),
        'price_type' => $price_type->id(),
        'currency' => $price_type->getDefaultCurrency(),
        'net' => 0,
        'gross' => 0,
        'vat_category' => $vat_category->id(),
        'vat_rate' => $vat_category->getRate(),
        'vat_value' => 0,
        'date_from' => NULL,
        'date_to' => NULL,
      ];
    }

    return $this->defaultPriceValues;
  }

  /**
   * Get total base values.
   *
   * @return array
   *   Altered
   */
  protected function getTotalBaseValues() {
    $values = [
      'base' => 'net',
      'price_type' => 'default',
      'currency' => NULL,
      'net' => 0,
      'gross' => 0,
      'vat_category' => 'custom',
      'vat_rate' => 0,
      'vat_value' => 0,
      'date_from' => NULL,
      'date_to' => NULL,
    ];
    $this->moduleHandler->alter('cart_total_base_values', $values);

    return $values;
  }

}
