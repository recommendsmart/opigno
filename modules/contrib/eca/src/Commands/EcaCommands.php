<?php

namespace Drupal\eca\Commands;

use Drupal\eca\Service\Modellers;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class EcaCommands extends DrushCommands {

  /**
   * @var \Drupal\eca\Service\Modellers
   */
  protected Modellers $modellerServices;

  /**
   * EcaCommands constructor.
   *
   */
  public function __construct(Modellers $modeller_services) {
    parent::__construct();
    $this->modellerServices = $modeller_services;
  }

  /**
   * Import a single ECA file.
   *
   * @usage eca:import
   *   Import a single ECA file.
   *
   * @param string $plugin_id
   *   The id of the modeller plugin.
   * @param string $filename
   *   The file name to import, relative to the Drupal root or absolute.
   *
   * @command eca:import
   */
  public function import(string $plugin_id, string $filename): void {
    $modeller = $this->modellerServices->getModeller($plugin_id);
    if ($modeller === NULL) {
      $this->io()->error('This modeller plugin does not exist.');
      return;
    }
    if (!file_exists($filename)) {
      $this->io()->error('This file does not exist.');
      return;
    }
    $modeller->save(file_get_contents($filename), $filename);
  }

  /**
   * Update all previously imported ECA files.
   *
   * @usage eca:reimport
   *   Update all previously imported ECA files.
   *
   * @command eca:reimport
   */
  public function reimportAll(): void {
    $this->modellerServices->reimportAll();
  }

  /**
   * Export templates for all ECA modellers.
   *
   * @command eca:export:templates
   */
  public function exportTemplates(): void {
    $this->modellerServices->exportTemplates();
  }

  /**
   * Update all models if plugins got changed.
   *
   * @usage eca:update
   *   Update all models if plugins got changed.
   *
   * @command eca:update
   */
  public function updateAllModels(): void {
    $this->modellerServices->updateAllModels();
  }

}
