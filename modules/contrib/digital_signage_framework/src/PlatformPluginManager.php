<?php

namespace Drupal\digital_signage_framework;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\digital_signage_framework\DeviceInterface;
use Drupal\digital_signage_framework\Annotation\DigitalSignagePlatform;
use Psr\Log\LoggerInterface;
use Traversable;

/**
 * DigitalSignagePlatform plugin manager.
 */
class PlatformPluginManager extends DefaultPluginManager {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs DigitalSignagePlatformPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LoggerInterface $logger) {
    $this->logger = $logger;
    parent::__construct(
      'Plugin/DigitalSignagePlatform',
      $namespaces,
      $module_handler,
      PlatformInterface::class,
      DigitalSignagePlatform::class
    );
    $this->alterInfo('digital_signage_platform_info');
    $this->setCacheBackend($cache_backend, 'digital_signage_platform_plugins', ['digital_signage_platform_plugins']);
  }

  /**
   * @return PlatformInterface[]
   */
  public function getAllPlugins(): array {
    static $plugins;
    if ($plugins === NULL) {
      $plugins = [];
      foreach ($this->getDefinitions() as $definition) {
        try {
          $plugins[] = $this->createInstance($definition['id']);
        } catch (PluginException $e) {
          // Ignore this.
        }
      }
      $this->logger->debug('Found %count platforms.', ['%count' => count($plugins)]);
    }
    return $plugins;
  }

  /**
   * Sync devices of all enabled platforms.
   *
   * @param string $platform
   */
  public function syncDevices($platform = NULL) {
    try {
      $plugins = ($platform === NULL) ?
        $this->getAllPlugins() :
        [$this->createInstance($platform)];
      foreach ($plugins as $plugin) {
        $plugin->syncDevices();
      }
    }
    catch (PluginException $e) {
      // TODO: write a log entry.
    }
    catch (EntityStorageException $e) {
      // TODO: write a log entry.
    }
  }

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   * @param bool $debug
   * @param bool $reload_assets
   * @param bool $reload_content
   */
  public function pushSchedule($device, $debug = FALSE, $reload_assets = FALSE, $reload_content = FALSE) {
    /** @var \Drupal\digital_signage_framework\PlatformInterface $plugin */
    $plugin = $this->createInstance($device->bundle());
    $plugin->pushSchedule($device, $debug, $reload_assets, $reload_content);
  }

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   * @param bool $debug
   * @param bool $reload_schedule
   * @param bool $reload_assets
   * @param bool $reload_content
   */
  public function pushConfiguration(DeviceInterface $device, bool $debug, bool $reload_schedule, bool $reload_assets, bool $reload_content) {
    /** @var \Drupal\digital_signage_framework\PlatformInterface $plugin */
    $plugin = $this->createInstance($device->bundle());
    $plugin->pushConfiguration($device, $debug, $reload_schedule, $reload_assets, $reload_content);
  }

}
