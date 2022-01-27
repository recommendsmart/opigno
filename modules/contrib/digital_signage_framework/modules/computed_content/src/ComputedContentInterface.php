<?php

namespace Drupal\digital_signage_computed_content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a digsig_computed_content entity type.
 */
interface ComputedContentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the digsig_computed_content title.
   *
   * @return string
   *   Title of the digsig_computed_content.
   */
  public function getTitle();

  /**
   * Sets the digsig_computed_content title.
   *
   * @param string $title
   *   The digsig_computed_content title.
   *
   * @return \Drupal\digital_signage_computed_content\ComputedContentInterface
   *   The called digsig_computed_content entity.
   */
  public function setTitle($title);

  /**
   * Gets the digsig_computed_content creation timestamp.
   *
   * @return int
   *   Creation timestamp of the digsig_computed_content.
   */
  public function getCreatedTime();

  /**
   * Sets the digsig_computed_content creation timestamp.
   *
   * @param int $timestamp
   *   The digsig_computed_content creation timestamp.
   *
   * @return \Drupal\digital_signage_computed_content\ComputedContentInterface
   *   The called digsig_computed_content entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the digsig_computed_content status.
   *
   * @return bool
   *   TRUE if the digsig_computed_content is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the digsig_computed_content status.
   *
   * @param bool $status
   *   TRUE to enable this digsig_computed_content, FALSE to disable.
   *
   * @return \Drupal\digital_signage_computed_content\ComputedContentInterface
   *   The called digsig_computed_content entity.
   */
  public function setStatus($status);

}
