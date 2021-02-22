<?php

namespace Drupal\content_as_config\Commands;

use Drupal\content_as_config\Controller\BlockContentController;
use Drupal\content_as_config\Controller\EntityControllerBase;
use Drupal\content_as_config\Controller\FeedsController;
use Drupal\content_as_config\Controller\MenuLinksController;
use Drupal\content_as_config\Controller\TaxonomiesController;
use Drush\Commands\DrushCommands;

/**
 * Drush command file for importing/exporting content as configuration.
 */
class ContentAsConfigCommands extends DrushCommands {

  /**
   * Exports content to configuration.
   *
   * @command content_as_config:export
   * @aliases cac-export cacx
   * @param string $entity_type
   *   The type of entity to be imported: 'block', 'menu', 'term', or 'feed'.
   * @usage drush content_as_config:export block
   */
  public function export(string $entity_type) {
    $controller = $this->getController($entity_type);
    $this->output()->writeln('Exporting ' . $entity_type . 's...');
    $controller->export(['drush' => TRUE]);
    $this->output()->writeln('Successfully exported blocks.');
  }

  /**
   * Imports content from configuration.
   *
   * @command content_as_config:import
   * @aliases cac-import caci
   * @param string $entity_type
   *   The type of entity to be imported: 'block', 'menu', 'term', or 'feed'.
   * @option style
   *   Which style of import to be used: safe, full, or force.
   * @usage drush content_as_config:import block --style=full
   */
  public function import(string $entity_type, array $options = ['style' => 'safe']) {
    $controller = $this->getController($entity_type);
    $this->output()->writeln('Exporting ' . $entity_type . 's...');
    $controller->export(['drush' => TRUE, 'style' => $options['style']]);
    $this->output()->writeln('Successfully exported ' . $entity_type . 's.');
  }

  /**
   * Exports all eligible content to configuration.
   *
   * @command content_as_config:export-all
   * @aliases cac-export-all cacxa
   * @usage drush content_as_config:export-all
   */
  public function exportAll() {
    $types = ['block', 'menu', 'term'];
    if (\Drupal::moduleHandler()->moduleExists('feed')) {
      $types[] = 'feed';
    }
    foreach ($types as $type) {
      $this->export($type);
    }
  }

  /**
   * Imports all eligible content from configuration.
   *
   * @command content_as_config:import-all
   * @aliases cac-import-all cacia
   * @usage drush content_as_config:import-all --style=full
   */
  public function importAll(array $options = ['style' => 'safe']) {
    $types = ['block', 'menu', 'term'];
    if (\Drupal::moduleHandler()->moduleExists('feed')) {
      $types[] = 'feed';
    }
    foreach ($types as $type) {
      $this->import($type, $options);
    }
  }

  /**
   * Fetches the appropriate controller for the given entity type.
   *
   * @param string $entity_type
   *   The type of entity whose controller is to be fetched.
   *
   * @return \Drupal\content_as_config\Controller\EntityControllerBase
   *   The controller appropriate to the entity.
   *
   * @throws \RuntimeException
   *   Thrown if there is no controller available for the given entity type.
   */
  protected function getController(string $entity_type): EntityControllerBase {
    switch ($entity_type) {
      case 'block':
        return BlockContentController::create(\Drupal::getContainer());

      case 'menu':
        return MenuLinksController::create(\Drupal::getContainer());

      case 'term':
        return TaxonomiesController::create(\Drupal::getContainer());

      case 'feed':
        if (!\Drupal::moduleHandler()->moduleExists('feed')) {
          throw new \RuntimeException('Feeds module is not enabled.');
        }
        return FeedsController::create(\Drupal::getContainer());
    }
    throw new \RuntimeException('Unknown entity type "' . $entity_type . '". Valid values are "block", "menu", "term" and "feed".');
  }

}
