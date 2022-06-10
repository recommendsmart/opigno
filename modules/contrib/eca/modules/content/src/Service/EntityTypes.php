<?php

namespace Drupal\eca_content\Service;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service class to handle types and bundles fields.
 */
class EntityTypes {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity type and bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Array containing all entity types and their bundles.
   *
   * @var array|null
   */
  private ?array $typesAndBundles = NULL;

  /**
   * Get the service instance of this class.
   *
   * @return \Drupal\eca_content\Service\EntityTypes
   *   The service instance.
   */
  public static function get(): EntityTypes {
    return \Drupal::service('eca_content.service.entity_types');
  }

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_bundle_info;
  }

  /**
   * The bundle field method.
   *
   * <p>Builds and returns the field for modeller templates to select entity
   * type and their bundle.</p>
   *
   * @param bool $include_any
   *   If set to TRUE, the field template will contain an option to select
   *   any content type and bundle. Defaults to FALSE, where this options will
   *   be missing.
   * @param bool $include_bundles_any
   *   If set to TRUE, entity types may be selected without specifying a certain
   *   bundle. Defaults to TRUE.
   *
   * @return array
   *   The array with the field template.
   */
  public function bundleField(bool $include_any = FALSE, bool $include_bundles_any = TRUE): array {
    if ($this->typesAndBundles === NULL) {
      $this->typesAndBundles = [];
      if ($include_any) {
        $this->typesAndBundles[] = [
          'name' => '- any -',
          'value' => '_all',
        ];
      }
      $this->typesAndBundles = array_merge($this->typesAndBundles,
        $this->doGetTypesAndBundles($include_bundles_any));
      usort($this->typesAndBundles, static function ($p1, $p2) {
        if ($p1['name'] < $p2['name']) {
          return -1;
        }
        if ($p1['name'] > $p2['name']) {
          return 1;
        }
        return 0;
      });
    }
    return empty($this->typesAndBundles) ? [] : [
      'name' => 'type',
      'label' => 'Type (and bundle)',
      'type' => 'Dropdown',
      'value' => $this->typesAndBundles[0]['value'],
      'extras' => [
        'choices' => $this->typesAndBundles,
      ],
    ];
  }

  /**
   * Gets the type and bundles.
   *
   * @param bool $include_bundles_any
   *   Flag to include any bundles.
   *
   * @return array
   *   The type and bundles.
   */
  private function doGetTypesAndBundles(bool $include_bundles_any): array {
    $result = [];
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        if ($include_bundles_any) {
          $result[] = [
            'name' => $definition->getLabel() . ': - any -',
            'value' => $definition->id() . ' _all',
          ];
        }
        $entity_keys = $definition->get('entity_keys');
        if (isset($entity_keys['bundle']) || !$include_bundles_any) {
          $bundles = $this->entityTypeBundleInfo->getBundleInfo($definition->id());
          foreach ($bundles as $bundle => $bundleDef) {
            $result[] = [
              'name' => $definition->getLabel() . ': ' . $bundleDef['label'],
              'value' => $definition->id() . ' ' . $bundle,
            ];
          }
        }
      }
    }
    return $result;
  }

  /**
   * Determines if the selected $type matches the type and bundle of $entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity which will be verified.
   * @param string $type
   *   The $type string as being selected from a modeller field prepared by
   *   the bundleField() method above.
   *
   * @return bool
   *   TRUE is the given entity matches the selected type and bundle, including
   *   all the various "any" options globally or per entity type.
   */
  public function bundleFieldApplies(EntityInterface $entity, string $type): bool {
    if ($type === '_all') {
      return TRUE;
    }
    [$entityType, $bundle] = explode(' ', $type);
    if ($bundle === '_all') {
      return $entity->getEntityTypeId() === $entityType;
    }
    return $entity->getEntityTypeId() === $entityType && $entity->bundle() === $bundle;
  }

  /**
   * Gets the types and bundles or NULL.
   *
   * @return array|null
   *   The types and bundles or NULL.
   */
  public function getTypesAndBundles(): ?array {
    return $this->typesAndBundles;
  }

}
