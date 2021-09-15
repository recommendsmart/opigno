<?php

namespace Drupal\arch_product\Plugin\views\argument;

use Drupal\arch_product\Entity\Storage\ProductStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a product id.
 *
 * @ViewsArgument("product_pid")
 */
class Pid extends NumericArgument {

  /**
   * The product storage.
   *
   * @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface
   */
  protected $productStorage;

  /**
   * Constructs the Pid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\arch_product\Entity\Storage\ProductStorageInterface $product_storage
   *   Product storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ProductStorageInterface $product_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->productStorage = $product_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('product')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the product.
   */
  public function titleQuery() {
    $titles = [];

    $products = $this->productStorage->loadMultiple($this->value);
    foreach ($products as $product) {
      $titles[] = $product->label();
    }
    return $titles;
  }

}
