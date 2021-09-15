<?php

namespace Drupal\arch_product\Plugin\EntityReferenceSelection;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the product entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:product",
 *   label = @Translation("Product selection", context = "arch_product"),
 *   entity_types = {"product"},
 *   group = "default",
 *   weight = 1
 * )
 */
class ProductSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    $query = $this->entityManager->getStorage($target_type)->getQuery();

    // If 'target_bundles' is NULL, all bundles are referenceable, no further
    // conditions are needed.
    if (is_array($configuration['target_bundles'])) {
      // If 'target_bundles' is an empty array, no bundle is referenceable,
      // force the query to never return anything and bail out early.
      if ($configuration['target_bundles'] === []) {
        $query->condition($entity_type->getKey('id'), NULL, '=');
        return $query;
      }
      else {
        $query->condition($entity_type->getKey('bundle'), $configuration['target_bundles'], 'IN');
      }
    }

    if (isset($match)) {
      $condition = $query->orConditionGroup();
      $label_key = $entity_type->getKey('label');
      $sku_key = $entity_type->getKey('sku');
      $condition->condition($label_key, $match, $match_operator);
      $condition->condition($sku_key, $match, $match_operator);
      $query->condition($condition);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if ($configuration['sort']['field'] !== '_none') {
      $query->sort($configuration['sort']['field'], $configuration['sort']['direction']);
    }

    // Adding the 'product_access' tag is sadly insufficient for products: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'. We need to do that as long as there are no access control
    // modules in use on the site. As long as one access control module is
    // there, it is supposed to handle this check.
    if (
      !$this->currentUser->hasPermission('bypass product access')
      && !count($this->moduleHandler->getImplementations('product_grants'))
    ) {
      $query->condition('status', ProductInterface::PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $product = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable product, it needs to published.
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product->setPublished(TRUE);

    return $product;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (
      !$this->currentUser->hasPermission('bypass product access')
      && !count($this->moduleHandler->getImplementations('product_grants'))
    ) {
      $entities = array_filter($entities, function ($product) {
        /** @var \Drupal\arch_product\Entity\ProductInterface $product */
        return $product->isPublished();
      });
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    /** @var \Drupal\arch_product\Entity\ProductInterface[] $entities */
    $entities = $this->entityManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $translation = $this->entityManager->getTranslationFromContext($entity);
      $label = $translation->get('sku')->value . ' - ' . $translation->label();
      $options[$bundle][$entity_id] = Html::escape($label);
    }

    return $options;
  }

}
