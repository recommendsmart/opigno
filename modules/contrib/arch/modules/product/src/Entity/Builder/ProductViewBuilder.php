<?php

namespace Drupal\arch_product\Entity\Builder;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for products.
 */
class ProductViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(
    array &$build,
    array $entities,
    array $displays,
    $view_mode
  ) {
    /** @var \Drupal\arch_product\Entity\ProductInterface[] $entities */
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
              !empty($entity->inPreview),
              $entity->isDefaultRevision() ? NULL : $entity->getLoadedRevisionId(),
            ],
          ],
        ];
      }

      // Add Language field text element to product render array.
      if ($display->getComponent('langcode')) {
        $build[$id]['langcode'] = [
          '#type' => 'item',
          '#title' => $this->t('Language'),
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

    // Don't cache products that are in 'preview' mode.
    if (isset($defaults['#cache']) && isset($entity->inPreview)) {
      unset($defaults['#cache']);
    }

    return $defaults;
  }

  /**
   * The #lazy_builder callback; builds a product's links.
   *
   * @param string $product_entity_id
   *   The product entity ID.
   * @param string $view_mode
   *   The view mode in which the product entity is being viewed.
   * @param string $langcode
   *   The language in which the product entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the product is currently being previewed.
   * @param string|null $revision_id
   *   (optional) The identifier of the product revision to be loaded. If none
   *   is provided, the default revision will be loaded.
   *
   * @return array
   *   A renderable array representing the product links.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function renderLinks(
    $product_entity_id,
    $view_mode,
    $langcode,
    $is_in_preview,
    $revision_id = NULL
  ) {
    $links = [
      '#theme' => 'links__product',
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      $storage = \Drupal::entityTypeManager()->getStorage('product');
      /** @var \Drupal\arch_product\Entity\ProductInterface $revision */
      $revision = !isset($revision_id) ? $storage->load($product_entity_id) : $storage->loadRevision($revision_id);
      $entity = $revision->getTranslation($langcode);
      $links['product'] = static::buildLinks($entity, $view_mode);

      // Allow other modules to alter the product links.
      $hook_context = [
        'view_mode' => $view_mode,
        'langcode' => $langcode,
      ];
      \Drupal::moduleHandler()->alter('product_links', $links, $entity, $hook_context);
    }
    return $links;
  }

  /**
   * Build the default links (Read more) for a product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $entity
   *   The product object.
   * @param string $view_mode
   *   A view mode identifier.
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected static function buildLinks(ProductInterface $entity, $view_mode) {
    $links = [];

    // Always display a read more link on teasers because we have no way
    // to know when a teaser view is different than a full view.
    if ($view_mode == 'teaser') {
      $product_title_stripped = strip_tags($entity->label());
      $links['product-readmore'] = [
        'title' => t('Read more<span class="visually-hidden"> about @title</span>', [
          '@title' => $product_title_stripped,
        ]),
        'url' => $entity->toUrl('canonical'),
        'language' => $entity->language(),
        'attributes' => [
          'rel' => 'tag',
          'title' => $product_title_stripped,
        ],
      ];
    }

    return [
      '#theme' => 'links__product__product',
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

}
