<?php

namespace Drupal\arch_order\Plugin\views\filter;

use Drupal\arch_order\Services\OrderStatusServiceInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\StringFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides filtering by Order Status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("order_status")
 */
class OrderStatusFilter extends StringFilter implements ContainerFactoryPluginInterface {

  /**
   * Order status service object.
   *
   * @var \Drupal\arch_order\Services\OrderStatusServiceInterface
   */
  protected $orderStatusService;

  /**
   * Constructs a new OrderStatusFilter instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\arch_order\Services\OrderStatusServiceInterface $orderStatusService
   *   Order status service object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, OrderStatusServiceInterface $orderStatusService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $connection);

    $this->orderStatusService = $orderStatusService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('order.statuses')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Value'),
      '#default_value' => $this->value,
      '#options' => [
        'All' => $this->t('- Any -'),
      ] + $this->listOrderStatuses(),
    ];
  }

  /**
   * Get order statuses as select option list.
   *
   * @return array
   *   Array of OrderStatus objects.
   */
  protected function listOrderStatuses() {
    /** @var \Drupal\arch_order\Entity\OrderStatus[] $statuses */
    $statuses = $this->orderStatusService->getOrderStatuses();
    $output = [];
    foreach ($statuses as $status) {
      $output[$status->id()] = $status->label();
    }

    return $output;
  }

}
