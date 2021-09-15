<?php

namespace Drupal\arch_product\Plugin\StoreDashboardPanel;

use Drupal\arch\StoreDashboardPanel\StoreDashboardPanel;
use Drupal\arch\StoreDashboardPanel\StoreDashboardPanelPluginInterface;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a fallback plugin for missing dashboard panel plugins.
 *
 * @StoreDashboardPanel(
 *   id = "product_count",
 *   admin_label = @Translation("Product count", context = "arch_product"),
 * )
 */
class ProductCount extends StoreDashboardPanel implements StoreDashboardPanelPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Product type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    Connection $db,
    EntityStorageInterface $product_type_storage
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $this->db = $db;
    $this->productTypeStorage = $product_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('product_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['total'] = $this->buildTotal();
    $build['types'] = $this->buildByTypes();

    // @todo Add cache.
    return $build;
  }

  /**
   * Total number row for panel.
   *
   * @return array
   *   Render array.
   */
  protected function buildTotal() {
    $total_select = $this->db->select('arch_product', 'p');
    $total_select->addExpression('COUNT(DISTINCT p.pid)');
    $total = (int) $total_select->execute()->fetchField();

    $url = Url::fromRoute('entity.product.collection');

    return [
      '#type' => 'inline_template',
      '#template' => '<p{{attributes}}><a href="{{ url }}">{{ "Number of products"|t }}</a>: <strong>{{ count }}</strong></p>',
      '#context' => [
        'attributes' => new Attribute([
          'class' => [
            'product-total-count',
          ],
        ]),
        'count' => $total,
        'url' => $url,
      ],
    ];
  }

  /**
   * Get numbers of products by type.
   *
   * @return array|null
   *   Render array.
   */
  protected function buildByTypes() {
    $type_select = $this->db->select('arch_product', 'p');
    $type_select->distinct(TRUE);
    $type_select->addField('p', 'type');
    $type_select->addExpression('COUNT(DISTINCT pid)', 'c');
    $type_select->groupBy('type');
    $type_select->orderBy('c');
    $types = $type_select->execute()->fetchAllKeyed();

    if (empty($types)) {
      return NULL;
    }

    $build = [
      '#title' => $this->t('Product count by type', [], ['context' => 'arch_product']),
      '#theme' => 'item_list',
    ];
    foreach ($types as $type_name => $count) {
      $type = $this->productTypeStorage->load($type_name);
      $url = Url::fromRoute('entity.product.collection', [], [
        'query' => [
          'type' => $type->id(),
        ],
      ]);
      $build['#items'][] = [
        '#type' => 'inline_template',
        '#template' => '<a href="{{ url }}"><strong>{{ type_label }}</strong></a>: {{ count }}',
        '#context' => [
          'type_label' => $type->label(),
          'count' => $count,
          'url' => $url,
        ],
      ];
    }

    return $build;
  }

}
