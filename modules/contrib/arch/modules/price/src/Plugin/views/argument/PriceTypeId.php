<?php

namespace Drupal\arch_price\Plugin\views\argument;

use Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a price type id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("price_type_id")
 */
class PriceTypeId extends StringArgument {

  /**
   * The price type storage.
   *
   * @var \Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface
   */
  protected $priceTypeStorage;

  /**
   * Constructs the PriceTypeId object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface $price_type_storage
   *   The price type storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PriceTypeStorageInterface $price_type_storage
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->priceTypeStorage = $price_type_storage;
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
      $container->get('entity_type.manager')->getStorage('price_type')
    );
  }

  /**
   * Override the behavior of title(). Get the name of the price type.
   */
  public function title() {
    $price_type = $this->priceTypeStorage->load($this->argument);
    if ($price_type) {
      return $price_type->label();
    }

    return $this->t('No price type', [], ['context' => 'arch_price']);
  }

}
