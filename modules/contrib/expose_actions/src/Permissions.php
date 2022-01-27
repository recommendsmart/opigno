<?php

namespace Drupal\expose_actions;

use Drupal;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Permissions
 *
 * @package Drupal\expose_action
 */
class Permissions {
  use StringTranslationTrait;

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function permissions(): array {
    $permissions = [];
    /** @var \Drupal\system\Entity\Action $action */
    foreach (Drupal::entityTypeManager()->getStorage('action')->loadMultiple() as $id => $action) {
      $permissions['access exposed action ' . $id] = [
        'title' => $this->t('Use the exposed action <a href="@url">@label</a>', ['@url' => $action->toUrl()->toString(), '@label' => $action->label()]),
      ];
    }
    return $permissions;
  }

}
