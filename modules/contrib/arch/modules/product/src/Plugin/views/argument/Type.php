<?php

namespace Drupal\arch_product\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a product type.
 *
 * @ViewsArgument("product_type")
 */
class Type extends StringArgument {

  /**
   * ProductType storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * Constructs a new Product Type object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $product_type_storage
   *   The entity storage class.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityStorageInterface $product_type_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->productTypeStorage = $product_type_storage;
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
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_manager->getStorage('product_type')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Get the user friendly version of the product type.
   */
  public function summaryName($data) {
    return $this->productType($data->{$this->name_alias});
  }

  /**
   * {@inheritdoc}
   *
   * Get the user friendly version of the product type.
   */
  public function title() {
    return $this->productType($this->argument);
  }

  /**
   * Get product type label.
   *
   * @param string $type_name
   *   Product type id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null|string
   *   Product type labe.
   */
  public function productType($type_name) {
    $type = $this->productTypeStorage->load($type_name);
    $output = $type ? $type->label() : $this->t('Unknown product type', [], ['context' => 'arch_product']);
    return $output;
  }

}
