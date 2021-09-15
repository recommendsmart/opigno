<?php

namespace Drupal\arch_price\Access;

use Drupal\arch_price\Entity\PriceTypeInterface;
use Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the price module.
 */
class PriceTypePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface
   */
  protected $priceTypeStorage;

  /**
   * Constructs a TaxonomyPermissions instance.
   *
   * @param \Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface $price_type_storage
   *   Price type storage.
   */
  public function __construct(
    PriceTypeStorageInterface $price_type_storage
  ) {
    $this->priceTypeStorage = $price_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('price_type')
    );
  }

  /**
   * Get price type permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    foreach ($this->priceTypeStorage->loadMultiple() as $price_type) {
      $permissions += $this->buildPermissions($price_type);
    }
    return $permissions;
  }

  /**
   * Builds a standard list of taxonomy term permissions for a given vocabulary.
   *
   * @param \Drupal\arch_price\Entity\PriceTypeInterface $price_type
   *   The price type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(PriceTypeInterface $price_type) {
    $id = $price_type->id();
    $args = ['%price_type' => $price_type->label()];

    return [
      "purchase with {$id} price" => [
        'title' => $this->t('Purchase with %price_type price', $args, ['context' => 'arch_price_type']),
      ],
      "create {$id} price" => [
        'title' => $this->t('Create %price_type price', $args, ['context' => 'arch_price_type']),
        'restrict access' => TRUE,
      ],
      "delete {$id} price" => [
        'title' => $this->t('Delete %price_type price', $args, ['context' => 'arch_price_type']),
        'restrict access' => TRUE,
      ],
      "edit {$id} price" => [
        'title' => $this->t('Edit %price_type price', $args, ['context' => 'arch_price']),
        'restrict access' => TRUE,
      ],
    ];
  }

}
