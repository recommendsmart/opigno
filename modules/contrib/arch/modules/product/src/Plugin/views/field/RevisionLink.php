<?php

namespace Drupal\arch_product\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to a product revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("product_revision_link")
 */
class RevisionLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->getEntity($row);
    // Current revision uses the product view path.
    if (!$product->isDefaultRevision()) {
      return Url::fromRoute('entity.product.revision', [
        'product' => $product->id(),
        'product_revision' => $product->getRevisionId(),
      ]);
    }
    return $product->toUrl('canonical');
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->getEntity($row);
    if (!$product->getRevisionid()) {
      return '';
    }
    $text = parent::renderLink($row);
    $this->options['alter']['query'] = $this->getDestinationArray();
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('View');
  }

}
