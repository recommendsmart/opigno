<?php

namespace Drupal\react_calendar;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface CalendarConfigurationInterface.
 */
interface CalendarConfigurationInterface {

  /**
   * Returns a React Calendar.
   *
   * Wrapper for a configured calendar.
   *
   * @return array
   *   Return the calendar render array.
   */
  public function getCalendar();

  /**
   * Checks if an entity bundle is enabled.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that is the subject of the template.
   *
   * @return bool
   *   Is the entity type enabled for React Calendar.
   */
  public function isBundleEnabled(ContentEntityInterface $entity);

  /**
   * Returns a list of enabled entity types configuration.
   *
   * Example: if entity_type_id is node_type, returns the enabled content types.
   *
   * @param string $entity_type_id
   *   The entity type (e.g. node_type);.
   *
   * @return array
   *   List of enabled entity types configuration.
   */
  public function getEnabledEntityTypeBundlesConfiguration($entity_type_id);

  /**
   * Returns bundles that are enabled for the React Calendar.
   *
   * @return array
   *   List of enabled bundles for React Calendar grouped by entity type.
   *   Array of bundle_id, entity_type_id, date_field_name, date_field_type.
   */
  public function getEnabledBundles();

  /**
   * Returns React Calendar's settings for an entity type bundle.
   *
   * @param string $setting
   *   If 'all' is passed, all available settings are returned.
   * @param string $entity_type_id
   *   The id of the entity type to return settings for.
   * @param string $bundle_id
   *   The id of the bundle to return settings for.
   *
   * @return string|array
   *   The value of the given setting or an array of all settings.
   */
  public function getEntityBundleSettings($setting, $entity_type_id, $bundle_id);

  /**
   * Saves React Calendar's settings of an entity type bundle.
   *
   * @param array $settings
   *   The available settings for this bundle.
   * @param string $entity_type_id
   *   The id of the entity type to set the settings for.
   * @param string $bundle_id
   *   The id of the bundle to set the settings for.
   */
  public function setEntityBundleSettings(array $settings, $entity_type_id, $bundle_id);

  /**
   * Returns React Calendar's entity type bundle available settings.
   *
   * @return array
   *   List of entity bundle available settings.
   */
  public function availableEntityBundleSettings();

  /**
   * Defines default values for React Calendar settings.
   *
   * @return array
   *   List of entity bundle default settings.
   */
  public function getEntityBundleSettingDefaults();

  /**
   * Returns a list of date related fields (Date or Date Range).
   *
   * @param string $node_type
   *   Node entity type bundle.
   *
   * @return array
   *   List of date fields.
   */
  public function getDateFields($node_type);

}
