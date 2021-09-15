<?php

namespace Drupal\arch_price\Price;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Price factory service.
 *
 * @package Drupal\arch_price
 */
class PriceFactory implements PriceFactoryInterface {

  /**
   * Service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->setContainer($container);

    return $instance;
  }

  /**
   * Service container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service container.
   */
  public function setContainer(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $values) {
    $price = Price::create(
      $this->container,
      $values
    );

    $price->setValues($values);

    return $price;
  }

  /**
   * {@inheritdoc}
   */
  public function getModifiedPriceInstance(array $values, PriceInterface $original_price) {
    $price = ModifiedPrice::create(
      $this->container,
      $values
    );
    $price->setValues($values);
    $price->setOriginalPrice($original_price);
    return $price;
  }

  /**
   * {@inheritdoc}
   */
  public function getMissingPriceInstance() {
    $price = new MissingPrice($this->container);
    return $price;
  }

}
