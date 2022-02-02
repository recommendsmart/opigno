<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\digital_signage_framework\Annotation\DigitalSignageScheduleGenerator;
use Traversable;

/**
 * DigitalSignageScheduleGenerator plugin manager.
 */
class ScheduleGeneratorPluginManager extends DefaultPluginManager {

  /**
   * Constructs DigitalSignageScheduleGeneratorPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/DigitalSignageScheduleGenerator',
      $namespaces,
      $module_handler,
      ScheduleGeneratorInterface::class,
      DigitalSignageScheduleGenerator::class
    );
    $this->alterInfo('digital_signage_schedule_generator_info');
    $this->setCacheBackend($cache_backend, 'digital_signage_schedule_generator_plugins', ['digital_signage_schedule_generator_plugins']);
  }

}
