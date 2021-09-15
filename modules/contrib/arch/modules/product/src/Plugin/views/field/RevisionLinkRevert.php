<?php

namespace Drupal\arch_product\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to revert a product to a revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("product_revision_link_revert")
 */
class RevisionLinkRevert extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->getEntity($row);
    return Url::fromRoute(
      'product.revision_revert_confirm',
      [
        'product' => $product->id(),
        'product_revision' => $product->getRevisionId(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Revert');
  }

}
