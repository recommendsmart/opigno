<?php

namespace Drupal\arch_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a product type entity.
 */
interface ProductTypeInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Determines whether the product type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

  /**
   * Gets the preview mode.
   *
   * @return int
   *   DRUPAL_DISABLED, DRUPAL_OPTIONAL or DRUPAL_REQUIRED.
   */
  public function getPreviewMode();

  /**
   * Sets the preview mode.
   *
   * @param int $preview_mode
   *   DRUPAL_DISABLED, DRUPAL_OPTIONAL or DRUPAL_REQUIRED.
   */
  public function setPreviewMode($preview_mode);

  /**
   * Gets the help information.
   *
   * @return string
   *   The help information of this product type.
   */
  public function getHelp();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this product type.
   */
  public function getDescription();

}
