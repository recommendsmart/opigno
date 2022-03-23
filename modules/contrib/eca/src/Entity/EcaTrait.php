<?php

namespace Drupal\eca\Entity;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\eca\Service\Conditions;
use Drupal\eca\PluginManager\Condition;
use Drupal\eca\PluginManager\Event;
use Drupal\eca\PluginManager\Modeller;

/**
 *
 */
trait EcaTrait {

  /**
   * @var \Drupal\eca\PluginManager\Modeller
   */
  protected Modeller $modellerPluginManager;

  /**
   * @var \Drupal\eca\PluginManager\Event
   */
  protected Event $eventPluginManager;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\eca\PluginManager\Condition
   */
  protected Condition $conditionPluginManager;

  /**
   * @var \Drupal\Core\Action\ActionManager
   */
  protected ActionManager $actionPluginManager;

  /**
   * @var \Drupal\eca\Service\Conditions
   */
  protected Conditions $conditionServices;

  /**
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCache
   */
  protected MemoryCache $memoryCache;

  /**
   * Initializes the modeller plugin manager.
   *
   * @return \Drupal\eca\PluginManager\Modeller
   *   The modeller plugin manager.
   */
  protected function modellerPluginManager(): Modeller {
    if (!isset($this->modellerPluginManager)) {
      $this->modellerPluginManager = \Drupal::service('plugin.manager.eca.modeller');
    }
    return $this->modellerPluginManager;
  }

  /**
   * Initializes the event plugin manager.
   *
   * @return \Drupal\eca\PluginManager\Event
   *   The event plugin manager.
   */
  protected function eventPluginManager(): Event {
    if (!isset($this->eventPluginManager)) {
      $this->eventPluginManager = \Drupal::service('plugin.manager.eca.event');
    }
    return $this->eventPluginManager;
  }

  /**
   * Initializes the condition plugin manager.
   *
   * @return \Drupal\eca\PluginManager\Condition
   *   The condition plugin manager.
   */
  protected function conditionPluginManager(): Condition {
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.eca.condition');
    }
    return $this->conditionPluginManager;
  }

  /**
   * Initializes the action plugin manager.
   *
   * @return \Drupal\Core\Action\ActionManager
   *   The action plugin manager.
   */
  protected function actionPluginManager(): ActionManager {
    if (!isset($this->actionPluginManager)) {
      $this->actionPluginManager = \Drupal::service('plugin.manager.action');
    }
    return $this->actionPluginManager;
  }

  /**
   * Initializes the condition plugin manager.
   *
   * @return \Drupal\eca\Service\Conditions
   *   The condition services.
   */
  protected function conditionServices(): Conditions {
    if (!isset($this->conditionServices)) {
      $this->conditionServices = \Drupal::service('eca.service.condition');
    }
    return $this->conditionServices;
  }

  /**
   * Initializes the memory cache service.
   *
   * @return \Drupal\Core\Cache\MemoryCache\MemoryCache
   *   The memory cache service.
   */
  protected function memoryCache(): MemoryCache {
    if (!isset($this->memoryCache)) {
      $this->memoryCache = \Drupal::service('eca.memory_cache');
    }
    return $this->memoryCache;
  }

}
