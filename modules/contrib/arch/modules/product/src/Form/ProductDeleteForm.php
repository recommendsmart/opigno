<?php

namespace Drupal\arch_product\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting a product.
 *
 * @internal
 */
class ProductDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\arch_product\Entity\ProductInterface $entity */
    $entity = $this->getEntity();
    $product_type_storage = $this->entityTypeManager->getStorage('product_type');
    $product_type = $product_type_storage->load($entity->bundle())->label();

    if (!$entity->isDefaultTranslation()) {
      return $this->t('@language translation of the @type %label has been deleted.', [
        '@language' => $entity->language()->getName(),
        '@type' => $product_type,
        '%label' => $entity->label(),
      ]);
    }

    return $this->t('The @type %title has been deleted.', [
      '@type' => $product_type,
      '%title' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    /** @var \Drupal\arch_product\Entity\ProductInterface $entity */
    $entity = $this->getEntity();
    $this->logger('product')
      ->notice('@type: deleted %title.', [
        '@type' => $entity->getType(),
        '%title' => $entity->label(),
      ]);
  }

}
