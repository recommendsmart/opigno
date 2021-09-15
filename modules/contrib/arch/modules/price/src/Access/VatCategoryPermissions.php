<?php

namespace Drupal\arch_price\Access;

use Drupal\arch_price\Entity\VatCategoryInterface;
use Drupal\arch_price\Entity\Storage\VatCategoryStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the price module.
 */
class VatCategoryPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\arch_price\Entity\Storage\VatCategoryStorageInterface
   */
  protected $vatCategoryStorage;

  /**
   * Constructs a VatCategoryPermissions instance.
   *
   * @param \Drupal\arch_price\Entity\Storage\VatCategoryStorageInterface $vat_category_storage
   *   VAT Category storage.
   */
  public function __construct(
    VatCategoryStorageInterface $vat_category_storage
  ) {
    $this->vatCategoryStorage = $vat_category_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('vat_category')
    );
  }

  /**
   * Get VAT category permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    foreach ($this->vatCategoryStorage->loadMultiple() as $vat_category) {
      $permissions += $this->buildPermissions($vat_category);
    }
    return $permissions;
  }

  /**
   * Builds a standard list of VAT category permissions for a given type.
   *
   * @param \Drupal\arch_price\Entity\VatCategoryInterface $vat_category
   *   The VAT category.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(VatCategoryInterface $vat_category) {
    $id = $vat_category->id();
    $args = ['%vat_category' => $vat_category->label()];

    return [
      "create {$id} price" => [
        'title' => $this->t('Create %vat_category price', $args, ['context' => 'arch_vat_category']),
      ],
      "delete {$id} price" => [
        'title' => $this->t('Delete %vat_category price', $args, ['context' => 'arch_vat_category']),
      ],
      "edit {$id} price" => [
        'title' => $this->t('Edit %vat_category price', $args, ['context' => 'arch_vat_category']),
      ],
    ];
  }

}
