<?php

namespace Drupal\flow_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a UI listing of Flow config entities.
 *
 * @see \Drupal\flow\Entity\Flow
 */
class FlowUiListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = $this->t('Type');
    $header['bundle'] = $this->t('Bundle');
    $header['mode'] = $this->t('Mode');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\flow\Entity\FlowInterface $entity */
    $row['type'] = $entity->getTargetEntityTypeId();
    $row['bundle'] = $entity->getTargetBundle();
    $row['mode'] = $entity->getTaskMode();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No Flow configuration items available.',
    );

    return $build;
  }

}
