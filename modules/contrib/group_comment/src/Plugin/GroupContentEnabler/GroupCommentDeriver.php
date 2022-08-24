<?php

declare(strict_types = 1);

namespace Drupal\group_comment\Plugin\GroupContentEnabler;

use Drupal\comment\Entity\CommentType;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Driver for comment group content enabler.
 */
class GroupCommentDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];

    foreach (CommentType::loadMultiple() as $name => $comment_type) {
      $label = $comment_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => $this->t('Group comment (@type)', ['@type' => $label]),
        'description' => $this->t('Adds %type comments to groups.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
