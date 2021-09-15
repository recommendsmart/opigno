<?php

namespace Drupal\arch_checkout\CheckoutType;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checkout type plugin interface.
 *
 * @package Drupal\arch_checkout\CheckoutType
 */
interface CheckoutTypePluginInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface, PluginInspectionInterface, CacheableDependencyInterface, DerivativeInspectionInterface {

  /**
   * Indicates the panel label (title) should be displayed to end users.
   */
  const VISIBLE = 'visible';

  /**
   * Returns the user-facing panel label.
   *
   * @todo Provide other specific label-related methods in
   *   https://www.drupal.org/node/2025649.
   *
   * @return string
   *   The panel label.
   */
  public function label();

  /**
   * Returns the class name path of the form that accomplish checkout page.
   *
   * @return string|null
   *   Class name path if found, NULL otherwise.
   */
  public function getCheckoutFormClass();

  /**
   * Indicates whether the checkout type should be shown.
   *
   * This method allows base implementations to add general access restrictions
   * that should apply to all extending panel plugins.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function access(AccountInterface $account, $return_as_object = FALSE);

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build();

  /**
   * Build empty cart message.
   *
   * @return array
   *   Render array.
   */
  public function buildEmptyCartMessage();

  /**
   * Build form to display.
   *
   * @return array
   *   Form render array.
   */
  public function buildForm();

  /**
   * Sets a particular value in the block settings.
   *
   * @param string $key
   *   The key of PluginBase::$configuration to set.
   * @param mixed $value
   *   The value to set for the provided key.
   *
   * @todo This doesn't belong here. Move this into a new base class in
   *   https://www.drupal.org/node/1764380.
   * @todo This does not set a value in \Drupal::config(), so the name is confusing.
   *
   * @see \Drupal\Component\Plugin\PluginBase::$configuration
   */
  public function setConfigurationValue($key, $value);

  /**
   * Suggests a machine name to identify an instance of this block.
   *
   * The block plugin need not verify that the machine name is at all unique. It
   * is only responsible for providing a baseline suggestion; calling code is
   * responsible for ensuring whatever uniqueness is required for the use case.
   *
   * @return string
   *   The suggested machine name.
   */
  public function getMachineNameSuggestion();

}
