<?php

namespace Drupal\arch_price\Negotiation;

use Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Price negotiation.
 *
 * @package Drupal\arch_price\Negotiation
 */
class PriceNegotiation implements PriceNegotiationInterface, ContainerInjectionInterface {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Price factory.
   *
   * @var \Drupal\arch_price\Price\PriceFactoryInterface
   */
  protected $priceFactory;

  /**
   * PriceNegotiation constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\arch_price\Price\PriceFactoryInterface $price_factory
   *   Price factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    AccountInterface $current_user,
    PriceFactoryInterface $price_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->priceFactory = $price_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('price_factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProductPrices(ProductInterface $product) {
    /** @var \Drupal\arch_price\Plugin\Field\FieldType\PriceFieldItemList $price_field */
    $price_field = $product->get('price');
    return $price_field->getPriceList();
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailablePrices(ProductInterface $product, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    $prices = array_filter($this->getProductPrices($product), function (PriceItemInterface $item) use ($product, $account) {
      return $this->filterAvailablePrices($item, $product, $account);
    });
    $this->moduleHandler->alter('product_available_prices', $prices, $product, $account);

    return $prices;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivePrice(ProductInterface $product, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    $price_list = $this->getPriceList($product, $account);
    $prices = array_map(function ($item) {
      /** @var \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface $item */
      return $item->toPrice();
    }, $price_list);

    if (empty($prices)) {
      $prices = $this->getDefaultPrices($product, $account);
    }

    /** @var \Drupal\arch_price\Price\PriceInterface $price */
    $price = current($prices);
    $context = [
      'account' => $account,
      'product' => $product,
      'prices' => $prices,
    ];
    $this->moduleHandler->alter('product_active_price', $price, $context);
    return $price;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalPrice(ProductInterface $product, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    $price_list = [];

    $available_prices = array_filter($this->getProductPrices($product), function (PriceItemInterface $item) use ($product, $account) {
      return $this->filterAvailablePrices($item, $product, $account);
    });
    $price_list[] = current($available_prices);
    $prices = array_map(function ($item) {
      /** @var \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface $item */
      return $item->toPrice();
    }, $price_list);

    if (empty($prices)) {
      $prices = $this->getDefaultPrices($product, $account);
    }

    /** @var \Drupal\arch_price\Price\PriceInterface $price */
    $price = current($prices);
    $context = [
      'account' => $account,
      'product' => $product,
      'prices' => $available_prices,
    ];
    $this->moduleHandler->alter('product_original_price', $price, $context);
    return $price;
  }

  /**
   * Get price list.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Customer.
   *
   * @return \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface[]
   *   List of available prices.
   */
  protected function getPriceList(ProductInterface $product, AccountInterface $account) {
    // Group prices by type.
    $prices_by_type = [];
    $available_prices = $this->getAvailablePrices($product, $account);
    foreach ($available_prices as $delta => $item) {
      /** @var \Drupal\arch_price\Plugin\Field\FieldType\PriceItem $item */
      $prices_by_type[$item->getPriceTypeId()][$delta] = $item;
    }

    // Filter multiple prices by type.
    $price_list = [];
    foreach ($prices_by_type as $price_items) {
      if (count($price_items) > 1) {
        uasort($price_items, [$this, 'comparePriceItems']);
        reset($price_items);
      }
      $key = key($price_items);
      $price_list[$key] = $price_items[$key];
    }
    ksort($price_list);

    $this->moduleHandler->alter('price_negotiation_prices', $price_list, $product, $account);
    return $price_list;
  }

  /**
   * Get default prices.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   *
   * @return \Drupal\arch_price\Price\PriceInterface[]
   *   Price list.
   */
  protected function getDefaultPrices(ProductInterface $product, AccountInterface $account) {
    $prices = [
      $this->priceFactory->getMissingPriceInstance(),
    ];
    $this->moduleHandler->alter('price_negotiation_empty_price_list', $prices, $product, $account);
    return $prices;
  }

  /**
   * List filter callback.
   *
   * @param \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface $item
   *   Price item.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User.
   *
   * @return bool
   *   Return TRUE if given price is available for given user.
   */
  protected function filterAvailablePrices(PriceItemInterface $item, ProductInterface $product, AccountInterface $account) {
    // Exclude if price is not available at this time.
    if (!$item->isAvailable()) {
      return FALSE;
    }

    $priceType = $item->getPriceType();
    if (empty($priceType)) {
      return FALSE;
    }

    // Exclude if type of price is not available.
    $type_access = $priceType->access('view', $account, TRUE);
    if ($type_access->isForbidden()) {
      return FALSE;
    }

    // Get modules response.
    $result = AccessResult::allowedIf($item->isAvailable());
    $access_results = $this->moduleHandler->invokeAll('price_access', [
      $item,
      $product,
      $account,
    ]);
    foreach ($access_results as $access) {
      $result->andIf($access);
    }

    // Allow only if not forbidden.
    return !$result->isForbidden();
  }

  /**
   * Uasort callback.
   *
   * @param \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface $a
   *   Price A.
   * @param \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface $b
   *   Price B.
   *
   * @return int
   *   Compare result.
   */
  protected static function comparePriceItems(PriceItemInterface $a, PriceItemInterface $b) {
    $a_full_limit = $a->getAvailableFrom() && $a->getAvailableTo();
    $b_full_limit = $b->getAvailableFrom() && $b->getAvailableTo();
    if ($a_full_limit && !$b_full_limit) {
      return -1;
    }
    if (!$a_full_limit && $b_full_limit) {
      return 1;
    }

    $a_default = !$a->getAvailableFrom() && !$a->getAvailableTo();
    $b_default = !$b->getAvailableFrom() && !$b->getAvailableTo();
    if ($a_default && !$b_default) {
      return 1;
    }
    if (!$a_default && $b_default) {
      return -1;
    }

    if ($a->getAvailableFrom() !== $b->getAvailableFrom()) {
      return $a->getAvailableFrom() < $b->getAvailableFrom() ? -1 : 1;
    }
    if ($a->getAvailableTo() !== $b->getAvailableTo()) {
      return $a->getAvailableTo() < $b->getAvailableTo() ? -1 : 1;
    }

    return 0;
  }

}
