<?php

namespace Drupal\eca\Plugin\ECA\Modeller;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface for ECA modeller plugins.
 */
interface ModellerInterface extends PluginInspectionInterface {

  /**
   * Add the ECA config entity to the modeller.
   *
   * This allows the modeller to call back to the currently operating ECA
   * config which holds additional information and functionality.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   *   The ECA config entity for the modeller to work on.
   *
   * @return \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface
   *   The modeller instance itself.
   */
  public function setConfigEntity(Eca $eca): ModellerInterface;

  /**
   * Generate an ID for the model.
   *
   * @return string
   *   The ID of the model.
   */
  public function generateId(): string;

  /**
   * Create a new ECA config and model entity.
   *
   * @param string $id
   *   The ID for the new model.
   * @param string $model_data
   *   The data for the new model.
   * @param string|null $filename
   *   The optional filename, if the modeller requires the model to be stored
   *   externally as a separate file.
   * @param bool $save
   *   TRUE, if the new entity should also be saved, FALSE otherwise (default).
   *
   * @return \Drupal\eca\Entity\Eca
   *   The new ECA config entity.
   */
  public function createNewModel(string $id, string $model_data, string $filename = NULL, bool $save = FALSE): Eca;

  /**
   * @param string $model_data
   *   The data of the model to be converted to ECA config and stored as the
   *   modeller's own data.
   * @param string|null $filename
   *   The optional filename, if the modeller requires the model to be stored
   *   externally as a separate file.
   *
   * @return bool
   *   Returns TRUE, if a reload of the saved model is required. That's the case
   *   when this is either a new model or if the label had changed. It returns
   *   FALSE otherwise, if none of those conditions applies.
   */
  public function save(string $model_data, string $filename = NULL): bool;

  /**
   * @param \Drupal\eca\Entity\Model $model
   *
   * @return bool
   */
  public function updateModel(Model $model): bool;

  /**
   * @return \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface
   */
  public function enable(): ModellerInterface;

  /**
   * @return \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface
   */
  public function disable(): ModellerInterface;

  /**
   * @return \Drupal\eca\Entity\Eca
   */
  public function clone(): Eca;

  /**
   * @return \Symfony\Component\HttpFoundation\Response|null
   */
  public function export(): ?Response;

  /**
   * @return string
   */
  public function getFilename(): string;

  /**
   * @return string
   */
  public function getModeldata(): string;

  /**
   * @return bool
   */
  public function isEditable(): bool;

  /**
   * @return bool
   */
  public function isExportable(): bool;

  /**
   * @return array
   */
  public function edit(): array;

  /**
   * @return string
   */
  public function getId(): string;

  /**
   * @return string
   */
  public function getLabel(): string;

  /**
   * @return array
   */
  public function getTags(): array;

  /**
   * @return string
   */
  public function getDocumentation(): string;

  /**
   * @return bool
   */
  public function getStatus(): bool;

  /**
   * @return string
   */
  public function getVersion(): string;

  /**
   * @param \Drupal\eca\Entity\Eca $eca
   *
   * @return \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface
   */
  public function readComponents(Eca $eca): ModellerInterface;

  /**
   * @return \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface
   */
  public function exportTemplates(): ModellerInterface;

}
