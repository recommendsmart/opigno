<?php

namespace Drupal\arch_product\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present link to delete a product revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("product_revision_link_delete")
 */
class RevisionLinkDelete extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->getEntity($row);
    return Url::fromRoute(
      'product.revision_delete_confirm',
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
    return $this->t('Delete');
  }

}
