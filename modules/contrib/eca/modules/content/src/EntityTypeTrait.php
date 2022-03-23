<?php

namespace Drupal\eca_content;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * A trait for ECA event and condition plugins and for content entity events.
 *
 * It provides helper functions for bundles of content entity types.
 */
trait EntityTypeTrait {

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
  private static ?array $typesAndBundles = NULL;

  /**
   * Builds and returns the field for modeller templates to select entity type
   * and their bundle.
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
    if (self::$typesAndBundles === NULL) {
      self::$typesAndBundles = [];
      if ($include_any) {
        self::$typesAndBundles[] = [
          'name' => '- any -',
          'value' => '_all',
        ];
      }
      foreach ($this->entityTypeManager->getDefinitions() as $definition) {
        $entity_keys = $definition->get('entity_keys');
        if ($definition instanceof ContentEntityTypeInterface) {
          if ($include_bundles_any) {
            self::$typesAndBundles[] = [
              'name' => $definition->getLabel() . ': - any -',
              'value' => $definition->id() . ' _all',
            ];
          }
          if (isset($entity_keys['bundle']) || !$include_bundles_any) {
            $bundles = $this->entityTypeBundleInfo()->getBundleInfo($definition->id());
            foreach ($bundles as $bundle => $bundleDef) {
              self::$typesAndBundles[] = [
                'name' => $definition->getLabel() . ': ' . $bundleDef['label'],
                'value' => $definition->id() . ' ' . $bundle,
              ];
            }
          }
        }
      }
      usort(self::$typesAndBundles, static function($p1, $p2) {
        if ($p1['name'] < $p2['name']) {
          return -1;
        }
        if ($p1['name'] > $p2['name']) {
          return 1;
        }
        return 0;
      });
    }
    return [
      'name' => 'type',
      'label' => 'Type (and bundle)',
      'type' => 'Dropdown',
      'value' => self::$typesAndBundles[0]['value'],
      'extras' => [
        'choices' => self::$typesAndBundles,
      ],
    ];
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
   * Get the entity type bundle info service.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The entity type bundle info service.
   */
  protected function entityTypeBundleInfo(): EntityTypeBundleInfoInterface {
    if (!isset($this->entityTypeBundleInfo)) {
      $this->entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');
    }
    return $this->entityTypeBundleInfo;
  }

}
