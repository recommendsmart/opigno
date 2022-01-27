<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a digital signage content setting entity type.
 */
interface ContentSettingInterface extends ContentEntityInterface {

  /**
   * @return bool
   */
  public function hasChanged(): bool;

  /**
   * Get the parent entity.
   *
   * @return array|NULL
   */
  public function getReverseEntity();

  /**
   * Sets the parent entity.
   *
   * @param ContentEntityInterface $entity
   *
   * @return \Drupal\digital_signage_framework\ContentSettingInterface
   */
  public function setReverseEntity($entity): ContentSettingInterface;

  /**
   * @return string|NULL
   */
  public function getReverseEntityType();

  /**
   * @return string|NULL
   */
  public function getReverseEntityBundle();

  /**
   * @return int
   */
  public function getReverseEntityId(): int;

  /**
   * Returns the digital signage content setting status.
   *
   * @return bool
   *   TRUE if the digital signage content setting is enabled, FALSE otherwise.
   */
  public function isReverseEntityEnabled(): bool;

  /**
   * Sets the digital signage content setting status.
   *
   * @param bool $status
   *   TRUE to enable this digital signage content setting, FALSE to disable.
   *
   * @return \Drupal\digital_signage_framework\ContentSettingInterface
   *   The called digital signage content setting entity.
   */
  public function setReverseEntityStatus($status): ContentSettingInterface;

  /**
   * Returns the digital signage content setting status.
   *
   * @return bool
   *   TRUE if the digital signage content setting is enabled, FALSE otherwise.
   */
  public function isEnabled(): bool;

  /**
   * Sets the digital signage content setting status.
   *
   * @param bool $status
   *   TRUE to enable this digital signage content setting, FALSE to disable.
   *
   * @return \Drupal\digital_signage_framework\ContentSettingInterface
   *   The called digital signage content setting entity.
   */
  public function setStatus($status): ContentSettingInterface;

  /**
   * @return int[]
   */
  public function getDeviceIds(): array;

  /**
   * @return int[]
   */
  public function getSegmentIds(): array;

  /**
   * Returns the priority
   *
   * @return int
   */
  public function getPriority(): int;

  /**
   * Returns the type of complexity
   *
   * @return string
   */
  public function getType(): string;

  /**
   * Returns whether the entity is critical
   *
   * @return bool
   */
  public function isCritical(): bool;

  /**
   * Returns whether the entity is dynamic content
   *
   * @return bool
   */
  public function isDynamic(): bool;

  /**
   * Sets the digital signage content setting dynamic content flag.
   *
   * @param bool $flag
   *   TRUE to declare this digital signage content setting dynamic, FALSE otherwise.
   *
   * @return \Drupal\digital_signage_framework\ContentSettingInterface
   *   The called digital signage content setting entity.
   */
  public function setDynamic($flag): ContentSettingInterface;

  /**
   * Returns the auto-label mode.
   *
   * @return bool
   *   TRUE if auto-label is enabled, FALSE otherwise.
   */
  public function isAutoLabel(): bool;

  /**
   * Returns the label.
   *
   * @return string
   */
  public function getLabel(): string;

  /**
   * Sets the label.
   *
   * @param string $label
   *
   * @return \Drupal\digital_signage_framework\ContentSettingInterface
   *   The called digital signage content setting entity.
   */
  public function setLabel($label): ContentSettingInterface;

}
