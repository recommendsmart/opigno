<?php

namespace Drupal\if_then_else\core;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Class defined to have common functions for ifthenelse rules processing.
 */
interface IfthenelseUtilitiesInterface extends PluginManagerInterface, CachedDiscoveryInterface {

  /**
   * Get content entities and bundles.
   *
   * @return \Drupal\if_then_else\core\IfthenelseUtilitiesInterface[]
   *   Return array of Content entities and their bundles
   */
  public function getContentEntitiesAndBundles();

  /**
   * Get views name and Display ID list.
   *
   * @return \Drupal\if_then_else\core\IfthenelseUtilitiesInterface[]
   *   Return array of Content entities and their bundles
   */
  public function getViewsNameAndDisplay();

  /**
   * Get views name and Display ID list.
   *
   * @param array $content_entity_types
   *   List of content entities and bundles.
   * @param string $return_type
   *   Type of field.
   *
   * @return \Drupal\if_then_else\core\IfthenelseUtilitiesInterface[]
   *   Return array of Content entities and their bundles
   */
  public function getFieldsByEntityBundleId(array $content_entity_types, $return_type = 'field');

  /**
   * Get a specific field by entity, bundle id and field id.
   *
   * @param string $entity_id
   *   Content Entity id.
   * @param string $bundle_id
   *   Bundle id whose fields to be fetched.
   * @param string $field_name
   *   Field name.
   *
   * @return \Drupal\if_then_else\core\IfthenelseUtilitiesInterface[]
   *   Field definition and info for a specific field.
   */
  public function getFieldInfoByEntityBundleId($entity_id, $bundle_id, $field_name);

}
