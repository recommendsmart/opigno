<?php

namespace Drupal\arch_product\Entity\Storage;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for products.
 *
 * This extends the base storage class, adding required special handling for
 * products entities.
 */
class ProductStorage extends SqlContentEntityStorage implements ProductStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ProductInterface $product) {
    return $this->database->query(
      'SELECT vid FROM {' . $this->getRevisionTable() . '} WHERE pid = :pid ORDER BY vid',
      [':pid' => $product->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {' . $this->getRevisionDataTable() . '} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ProductInterface $product) {
    return $this->database->query(
      'SELECT COUNT(*) FROM {' . $this->getRevisionDataTable() . '} WHERE pid = :pid AND default_langcode = 1',
      [':pid' => $product->id()]
    )->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function updateType($old_type, $new_type) {
    return $this->database->update($this->getBaseTable())
      ->fields(['type' => $new_type])
      ->condition('type', $old_type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update($this->getRevisionTable())
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    $is_new = $entity->isNew();
    $return = parent::doSave($id, $entity);

    if ($is_new) {
      $this->database->update($this->dataTable)
        ->fields(['group_id' => $entity->id()])
        ->condition('pid', $entity->id())
        ->execute();
    }

    return $return;
  }

}
