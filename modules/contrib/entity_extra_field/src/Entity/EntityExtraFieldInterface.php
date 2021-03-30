<?php

namespace Drupal\entity_extra_field\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Define entity extra field interface.
 */
interface EntityExtraFieldInterface extends ConfigEntityInterface {

  /**
   * Get extra field machine name.
   *
   * @return string
   *   The extra field machine name.
   */
  public function name();

  /**
   * Get the extra field description.
   *
   * @return string
   *   The extra field description.
   */
  public function description();

  /**
   * Should display the extra field label.
   *
   * @return boolean
   *   Return TRUE if the field label should be rendered; otherwise FALSE.
   */
  public function displayLabel();

  /**
   * Get extra field display.
   *
   * @return array
   *   An array of display information.
   */
  public function getDisplay();

  /**
   * Get extra field display type.
   *
   * @return string
   *   Get the display type.
   */
  public function getDisplayType();

  /**
   * Get field type plugin label.
   *
   * @return string
   *   The field type plugin label.
   */
  public function getFieldTypeLabel();

  /**
   * Get field type plugin identifier.
   *
   * @return string
   *   The field type plugin identifier.
   */
  public function getFieldTypePluginId();

  /**
   * Get field type plugin configuration
   *
   * @return array
   *   An array of the plugin configuration.
   */
  public function getFieldTypePluginConfig();

  /**
   * Get field type condition.
   *
   * @return array
   *   An array of condition plugin with configuration.
   */
  public function getFieldTypeCondition();

  /**
   * Get base entity type id.
   *
   * @return string
   *   The base entity type identifier.
   */
  public function getBaseEntityTypeId();

  /**
   * Get base bundle type id.
   *
   * @return string
   *   A base bundle type id.
   */
  public function getBaseBundleTypeId();

  /**
   * Get base entity type instance.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity type instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBaseEntityType();

  /**
   * Get base entity type bundle instance.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity type bundle instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBaseEntityTypeBundle();

  /**
   * Get the cache discovery identifier.
   *
   * @return string
   *   The cache identifier in the cache_discovery table.
   */
  public function getCacheDiscoveryId();

  /**
   * Get active field type conditions.
   *
   * @return array
   *   An array of active field type conditions.
   */
  public function getActiveFieldTypeConditions();

  /**
   * Get the build attachments.
   *
   * @return array
   *   An array of the build attachments.
   */
  public function getBuildAttachments();

  /**
   * Set a build attachment.
   *
   * @param $type
   *   The type of attachment (library, drupalSettings, etc)
   * @param array $attachment
   *   An array of attachment settings for the particular type.

   * @return $this
   */
  public function setBuildAttachment($type, array $attachment);

  /**
   * Check if entity identifier exist.
   *
   * @param $name
   *   The entity machine name.
   *
   * @return int
   *   Return TRUE if machine name exist; otherwise FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exists($name);

  /**
   * Build the extra field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity type the extra field is being attached too.
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display the extra field is apart of.
   *
   * @return array
   *   The extra field renderable array.
   */
  public function build(EntityInterface $entity, EntityDisplayInterface $display);

  /**
   * Extra field has display component.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   An entity display to check for the extra field.
   *
   * @return bool
   *   Return TRUE if the component exists in the display; otherwise FALSE.
   */
  public function hasDisplayComponent(EntityDisplayInterface $display);

  /**
   * Has extra field conditions been met.
   *
   * @param array $contexts
   *   An array of context values.
   * @param bool $all_must_pass
   *   Determine if all conditions must pass.
   *
   * @return bool
   *   Return TRUE if the extra field conditions have been met; otherwise FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function hasConditionsBeenMet(array $contexts, $all_must_pass = FALSE);
}
