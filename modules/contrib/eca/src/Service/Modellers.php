<?php

namespace Drupal\eca\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\eca\Plugin\ECA\Modeller\ModellerInterface;
use Drupal\eca\PluginManager\Event;
use Drupal\eca\PluginManager\Modeller;

/**
 * Service class for ECA modellers.
 */
class Modellers {

  use ServiceTrait;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $configStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $modelStorage;

  /**
   * @var \Drupal\eca\PluginManager\Modeller
   */
  protected Modeller $pluginManagerModeller;

  /**
   * @var \Drupal\eca\PluginManager\Event
   */
  protected Event $pluginManagerEvent;

  /**
   * @var \Drupal\eca\Service\Actions
   */
  protected Actions $actionServices;

  /**
   * @var \Drupal\eca\Service\Conditions
   */
  protected Conditions $conditionServices;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Modellers constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\eca\PluginManager\Modeller $plugin_manager_modeller
   * @param \Drupal\eca\PluginManager\Event $plugin_manager_event
   * @param \Drupal\eca\Service\Actions $action_services
   * @param \Drupal\eca\Service\Conditions $condition_services
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Modeller $plugin_manager_modeller, Event $plugin_manager_event, Actions $action_services, Conditions $condition_services, LoggerChannelInterface $logger) {
    $this->configStorage = $entity_type_manager->getStorage('eca');
    $this->modelStorage = $entity_type_manager->getStorage('eca_model');
    $this->pluginManagerModeller = $plugin_manager_modeller;
    $this->pluginManagerEvent = $plugin_manager_event;
    $this->actionServices = $action_services;
    $this->conditionServices = $condition_services;
    $this->logger = $logger;
  }

  /**
   * Save a model as config.
   *
   * @param \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface $modeller
   *
   * @return bool
   *   Returns TRUE, if a reload of the saved model is required. That's the case
   *   when this is either a new model or if the label had changed. It returns
   *   FALSE otherwise, if none of those conditions applies.
   */
  public function saveModel(ModellerInterface $modeller): bool {
    $id = mb_strtolower($modeller->getId());
    /** @var \Drupal\eca\Entity\Eca $config */
    $config = $this->configStorage->load($id);
    if ($config === NULL) {
      $config = $this->configStorage->create([
        'id' => $id,
        'modeller' => $modeller->getPluginId(),
      ]);
      $requiresReload = TRUE;
    }
    else {
      $requiresReload = $config->label() !== $modeller->getLabel();
    }
    $config
      ->set('label', $modeller->getLabel())
      ->set('status', $modeller->getStatus())
      ->set('version', $modeller->getVersion())
      ->set('events', [])
      ->set('conditions', [])
      ->set('actions', []);
    $modeller->readComponents($config);
    try {
      $config->save();
      $config->getModel()
        ->setData($modeller)
        ->save();
    }
    catch (EntityStorageException | InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // @todo: Log these exceptions.
    }
    return $requiresReload;
  }

  /**
   * Update all previously imported models.
   */
  public function reimportAll(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      if ($modeller->isEditable()) {
        // Editable models have no external files.
        continue;
      }
      try {
        $model = $eca->getModel();
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        // @todo: Log this exception.
        continue;
      }
      $filename = $model->getFilename();
      if (!file_exists($filename)) {
        $this->logger->error('This file '. $filename . ' does not exist.');
        continue;
      }
      $modeller->save(file_get_contents($filename), $filename);
    }
  }

  /**
   * Returns an instance of the modeller for the given id.
   *
   * @param $plugin_id
   *   The id of the modeller plugin.
   *
   * @return \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface|null
   *   The modeller instance, or NULL if the plugin doesn't exist.
   */
  public function getModeller($plugin_id): ?ModellerInterface {
    /** @var \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface $modeller */
    try {
      $modeller = $this->pluginManagerModeller->createInstance($plugin_id);
    }
    catch (PluginException $e) {
      return NULL;
    }
    return $modeller;
  }

  /**
   * Returns a sorted list of event plugins.
   *
   * @return \Drupal\eca\Plugin\ECA\Event\EventInterface[]
   *   The sorted list of events.
   */
  public function events(): array {
    static $events;
    if ($events === NULL) {
      $events = [];
      foreach ($this->pluginManagerEvent->getDefinitions() as $plugin_id => $definition) {
        try {
          $events[] = $this->pluginManagerEvent->createInstance($plugin_id);
        }
        catch (PluginException $e) {
          // Can be ignored
        }
      }
    }
    $this->sortPlugins($events);
    return $events;
  }

  /**
   * Export components for all ECA modellers.
   */
  public function exportTemplates(): void {
    foreach ($this->pluginManagerModeller->getDefinitions() as $plugin_id => $definition) {
      try {
        /** @var \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface $modeller */
        $modeller = $this->pluginManagerModeller->createInstance($plugin_id);
        $modeller->exportTemplates();
      }
      catch (PluginException $e) {
        // Can be ignored
      }
    }
  }

  /**
   * Updates all existing ECA entities by calling ::updateModel in their modeller.
   *
   * It is the modeller's responsibility to load all existing plugins and find
   * out if the model data, which is proprietary to them, needs to be updated.
   */
  public function updateAllModels(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      try {
        $model = $eca->getModel();
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        // @todo: Log this exception.
        continue;
      }
      if ($modeller->updateModel($model)) {
        $filename = $model->getFilename();
        if ($filename && file_exists($filename)) {
          file_put_contents($filename, $model->getModeldata());
        }
        $modeller->save($model->getModeldata(), $filename);
      }
    }
  }

}
