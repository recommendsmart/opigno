<?php

namespace Drupal\arch_order\Entity\Builder;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for orders.
 */
class OrderViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(
    array &$build,
    array $entities,
    array $displays,
    $view_mode
  ) {
    /** @var \Drupal\arch_order\Entity\OrderInterface[] $entities */
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
              $entity->isDefaultRevision() ? NULL : $entity->getLoadedRevisionId(),
            ],
          ],
        ];
      }

      // Add Language field text element to order render array.
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
   * The #lazy_builder callback; builds an order's links.
   *
   * @param string $entity_id
   *   The order entity ID.
   * @param string $view_mode
   *   The view mode in which the order entity is being viewed.
   * @param string $langcode
   *   The language in which the order entity is being viewed.
   * @param string|null $revision_id
   *   (optional) The identifier of the order revision to be loaded. If none
   *   is provided, the default revision will be loaded.
   *
   * @return array
   *   A renderable array representing the order links.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function renderLinks(
    $entity_id,
    $view_mode,
    $langcode,
    $revision_id = NULL
  ) {
    $links = [
      '#theme' => 'links__order',
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    $storage = \Drupal::entityTypeManager()->getStorage('order');
    /** @var \Drupal\arch_order\Entity\OrderInterface $revision */
    $revision = !isset($revision_id) ? $storage->load($entity_id) : $storage->loadRevision($revision_id);
    $links['order'] = static::buildLinks($revision, $view_mode);

    // Allow other modules to alter the order links.
    $hook_context = [
      'view_mode' => $view_mode,
      'langcode' => $langcode,
    ];
    \Drupal::moduleHandler()->alter('order_links', $links, $revision, $hook_context);

    return $links;
  }

  /**
   * Build the default links (Read more) for an order.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $entity
   *   The order object.
   * @param string $view_mode
   *   A view mode identifier.
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected static function buildLinks(OrderInterface $entity, $view_mode) {
    $links = [];

    // Always display a read more link on teasers because we have no way
    // to know when a teaser view is different than a full view.
    if ($view_mode == 'teaser') {
      $title_stripped = strip_tags($entity->label());
      $links['order-readmore'] = [
        'title' => t('Read more<span class="visually-hidden"> about @title</span>', [
          '@title' => $title_stripped,
        ]),
        'url' => $entity->toUrl('canonical'),
        'language' => $entity->language(),
        'attributes' => [
          'rel' => 'tag',
          'title' => $title_stripped,
        ],
      ];
    }

    return [
      '#theme' => 'links__order__order',
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

}
