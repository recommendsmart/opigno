<?php

namespace Drupal\arch_logger\Controller;

use Drupal\arch_logger\Services\ArchLogger;
use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for order log routes.
 */
class LogController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Arch logger service.
   *
   * @var \Drupal\arch_logger\Services\ArchLogger
   */
  protected $logger;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Order status storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $orderStatusStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * LogController constructor.
   *
   * @param \Drupal\arch_logger\Services\ArchLogger $logger
   *   Arch logger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityType Manager object.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ArchLogger $logger,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter,
    RendererInterface $renderer
  ) {
    $this->logger = $logger;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->orderStatusStorage = $entity_type_manager->getStorage('order_status');
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('arch_logger'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Log overview page of an order.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order entity.
   *
   * @return mixed
   *   Renderable array of order logs.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function listView(OrderInterface $order) {
    $logs = $this->logger->getByOrder($order);

    $build['#title'] = $this->t('Logs for %title', ['%title' => $order->label()]);
    $header = [
      '',
      $this->t('Message', [], ['context' => 'arch_logger']),
      $this->t('Order status', [], ['context' => 'arch_logger']),
      $this->t('User', [], ['context' => 'arch_logger']),
      $this->t('Date', [], ['context' => 'arch_logger']),
    ];

    $rows = [];
    foreach ($logs as $log) {
      $user = $this->userStorage->load($log->uid);

      $route_name = 'entity.order.history_item';
      $route_params = [
        'order' => $log->oid,
        'log_id' => $log->lid,
      ];

      $rows[] = [
        'id' => Link::createFromRoute('#' . $log->lid, $route_name, $route_params),
        'message' => Link::createFromRoute($log->message, $route_name, $route_params),
        'order_status' => $this->orderStatusStorage->load($log->status)->label(),
        'user' => $user->toLink(),
        'date' => $this->dateFormatter->format($log->created),
      ];
    }

    $build['order_log_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attributes' => ['class' => 'order-log-table'],
    ];

    return $build;
  }

  /**
   * Log details page.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order entity.
   * @param int $log_id
   *   Log ID.
   *
   * @return mixed
   *   Renderable array of order log details.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Exception
   */
  public function view(OrderInterface $order, $log_id) {
    $log = $this->logger->getByOrderAndId($order, $log_id);
    if (empty($log)) {
      throw new NotFoundHttpException();
    }

    $user = $this->userStorage->load($log->uid);

    $details = '-';
    if (!empty($log->data)) {
      $details_render_array = [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => var_export(unserialize($log->data), TRUE),
      ];
      $details = $this->renderer->render($details_render_array);
    }

    $build['#title'] = $this->t('Log for %title', ['%title' => $order->label()], ['context' => 'arch_logger']);
    $build['order_log_table'] = [
      '#theme' => 'table',
      '#rows' => [
        'order_number' => [
          $this->t('Order number', [], ['context' => 'arch_logger']),
          $order->toLink(),
        ],
        'message' => [
          $this->t('Message', [], ['context' => 'arch_logger']),
          $log->message,
        ],
        'order_status' => [
          $this->t('Order status', [], ['context' => 'arch_logger']),
          $this->orderStatusStorage->load($log->status)->label(),
        ],
        'user' => [
          $this->t('User', [], ['context' => 'arch_logger']),
          $user->toLink(),
        ],
        'date' => [
          $this->t('Date', [], ['context' => 'arch_logger']),
          $this->dateFormatter->format($log->created),
        ],
        'details' => [
          $this->t('Details', [], ['context' => 'arch_logger']),
          $details,
        ],
      ],
      '#attributes' => ['class' => 'order-log'],
    ];

    return $build;
  }

}
