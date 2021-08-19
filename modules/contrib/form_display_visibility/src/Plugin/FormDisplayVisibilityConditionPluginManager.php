<?php

namespace Drupal\form_display_visibility\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages form display visibility condition plugins.
 */
class FormDisplayVisibilityConditionPluginManager extends DefaultPluginManager {

  /**
   * Constructs a FormDisplayVisibilityConditionPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FormDisplayVisibilityCondition', $namespaces, $module_handler, 'Drupal\form_display_visibility\Plugin\FormDisplayVisibilityConditionInterface', 'Drupal\form_display_visibility\Annotation\FormDisplayVisibilityCondition');
    $this->alterInfo('form_display_visibility_condition_info');
    $this->setCacheBackend($cache_backend, 'form_display_visibility_condition_plugins', ['form_display_visibility_condition_plugins']);
  }

}
