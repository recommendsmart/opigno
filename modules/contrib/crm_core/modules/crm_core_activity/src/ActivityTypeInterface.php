<?php

namespace Drupal\crm_core_activity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines methods for CRM ActivityType entities.
 */
interface ActivityTypeInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\crm_core_activity\ActivityTypePluginInterface
   *   Instantiated plugin.
   */
  public function getPlugin();

  /**
   * Sets the plugin id.
   *
   * @param string $plugin_id
   *   The id of the plugin.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

  /**
   * Sets the plugin configuration.
   *
   * @param array $plugin_configuration
   *   The configuration for the plugin.
   *
   * @return $this
   */
  public function setPluginConfiguration(array $plugin_configuration);

  /**
   * Returns lazy plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The plugin collection.
   */
  public function getPluginCollection();

}
