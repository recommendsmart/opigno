<?php

namespace Drupal\arch_addressbook\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for Addressbookitems.
 */
class AddressbookitemViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('links')) {
        $build[$id]['links'] = [
          '#lazy_builder' => [
            get_called_class() . '::renderLinks', [
              $entity->id(),
              $view_mode,
              $entity->language()->getId(),
              !empty($entity->in_preview),
              $entity->isDefaultRevision() ? NULL : $entity->getLoadedRevisionId(),
            ],
          ],
        ];
      }

      if ($display->getComponent('langcode')) {
        $build[$id]['langcode'] = [
          '#type' => 'item',
          '#title' => $this->t('Language', [], ['context' => 'arch_addressbook']),
          '#markup' => $entity->language()->getName(),
          '#prefix' => '<div id="field-language-display">',
          '#suffix' => '</div>',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode);

    if (isset($defaults['#cache']) && isset($entity->in_preview)) {
      unset($defaults['#cache']);
    }

    return $defaults;
  }

}
