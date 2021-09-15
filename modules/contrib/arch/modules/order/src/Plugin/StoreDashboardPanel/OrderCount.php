<?php

namespace Drupal\arch_order\Plugin\StoreDashboardPanel;

use Drupal\arch\StoreDashboardPanel\StoreDashboardPanel;
use Drupal\arch\StoreDashboardPanel\StoreDashboardPanelPluginInterface;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a dashboard panel plugins.
 *
 * @StoreDashboardPanel(
 *   id = "order_count",
 *   admin_label = @Translation("Order count", context = "arch_order"),
 * )
 */
class OrderCount extends StoreDashboardPanel implements StoreDashboardPanelPluginInterface, ContainerFactoryPluginInterface {

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
  protected $orderStatusStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    Connection $db,
    EntityStorageInterface $order_status_storage
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $this->db = $db;
    $this->orderStatusStorage = $order_status_storage;
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
      $container->get('entity_type.manager')->getStorage('order_status')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['total'] = $this->buildTotal();
    $build['types'] = $this->buildByStatus();

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
    $total_select = $this->db->select('arch_order', 'o');
    $total_select->addExpression('COUNT(o.oid)');
    $total = (int) $total_select->execute()->fetchField();

    $url = Url::fromRoute('entity.order.collection');

    return [
      '#type' => 'inline_template',
      '#template' => '<p{{attributes}}><a href="{{ url }}">{{ "Number of orders"|t }}</a>: <strong>{{ count }}</strong></p>',
      '#context' => [
        'attributes' => new Attribute([
          'class' => [
            'order-total-count',
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
  protected function buildByStatus() {
    $type_select = $this->db->select('arch_order', 'o');
    $type_select->addField('o', 'status');
    $type_select->addExpression('COUNT(oid)', 'c');
    $type_select->groupBy('status');
    $type_select->orderBy('c');
    $types = $type_select->execute()->fetchAllKeyed();

    if (empty($types)) {
      return NULL;
    }

    $build = [
      '#title' => $this->t('Order count by status', [], ['context' => 'arch_order']),
      '#theme' => 'item_list',
    ];
    foreach ($types as $status_id => $count) {
      $status = $this->orderStatusStorage->load($status_id);
      if (empty($status)) {
        continue;
      }

      $url = Url::fromRoute('entity.order.collection', [], [
        'query' => [
          'status' => $status->id(),
        ],
      ]);
      $build['#items'][] = [
        '#type' => 'inline_template',
        '#template' => '<a href="{{ url }}"><strong>{{ label }}</strong></a>: {{ count }}',
        '#context' => [
          'label' => $status->label(),
          'count' => $count,
          'url' => $url,
        ],
      ];
    }

    return $build;
  }

}
