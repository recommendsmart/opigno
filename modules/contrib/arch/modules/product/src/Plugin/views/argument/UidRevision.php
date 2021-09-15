<?php

namespace Drupal\arch_product\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\Uid;

/**
 * Product owner filter.
 *
 * Filter handler to accept a user id to check for products that
 * user posted or created a revision on.
 *
 * @ViewsArgument("product_uid_revision")
 */
class UidRevision extends Uid {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression(0, "{$this->tableAlias}.uid = {$placeholder} OR ((SELECT COUNT(DISTINCT vid) FROM {arch_product_revision} pr WHERE pr.revision_uid = {$placeholder} AND pr.pid = $this->tableAlias.pid) > 0)", [$placeholder => $this->argument]);
  }

}
