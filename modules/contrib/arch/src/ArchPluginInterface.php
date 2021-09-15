<?php

namespace Drupal\arch;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Arch plugin interface.
 *
 * @package Drupal\arch
 */
interface ArchPluginInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Is this checkout plugin active.
   *
   * @return bool
   *   Return TRUE if plugin is active.
   */
  public function isActive();

  /**
   * Enable checkout plugin.
   *
   * @return $this
   */
  public function enable();

  /**
   * Disable checkout plugin.
   *
   * @return $this
   */
  public function disable();

  /**
   * Get weight value.
   *
   * @return int
   *   Weight.
   */
  public function getWeight();

  /**
   * Set weight value.
   *
   * @param int $weight
   *   Weight value.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Check if this checkout plugin is available for given order.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order.
   *
   * @return bool
   *   Return TRUE if plugin is available for given order.
   */
  public function isAvailable(OrderInterface $order);

  /**
   * Get settings.
   *
   * @return array
   *   All setting value.
   */
  public function getSettings();

  /**
   * Get setting value.
   *
   * @param string $key
   *   Setting name.
   * @param mixed $default
   *   Default value.
   *
   * @return mixed
   *   Setting value.
   */
  public function getSetting($key, $default = NULL);

  /**
   * Set setting value.
   *
   * @param string $key
   *   Setting name.
   * @param mixed $value
   *   Setting value.
   *
   * @return $this
   */
  public function setSetting($key, $value);

}
