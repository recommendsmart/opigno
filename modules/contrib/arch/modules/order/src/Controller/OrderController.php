<?php

namespace Drupal\arch_order\Controller;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_order\Entity\Storage\OrderStorageInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for order routes.
 */
class OrderController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

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
   * Constructs an OrderController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity repository.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityRepositoryInterface $entity_repository,
    DateFormatterInterface $date_formatter,
    RendererInterface $renderer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Add action.
   *
   * @return array
   *   Renderable array of add order form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function add() {
    $order = $this->entityTypeManager()->getStorage('order')->create();
    return $this->entityFormBuilder()->getForm($order);
  }

  /**
   * Revisions overview page of an order.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order entity.
   *
   * @return mixed
   *   Renderable array of order revisions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionOverview(OrderInterface $order) {
    $account = $this->currentUser();
    $langcode = $order->language()->getId();
    /** @var \Drupal\arch_order\Entity\Storage\OrderStorageInterface $order_storage */
    $order_storage = $this->entityTypeManager()->getStorage('order');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $order->label()]);
    $header = [
      $this->t('Submitted on'),
      $this->t('Made by'),
      $this->t('Message'),
      $this->t('Operations'),
    ];

    $revert_permission = (($account->hasPermission("revert order revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer orders')) && $order->access('update'));

    $rows = [];
    $default_revision = $order->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($order, $order_storage) as $vid) {
      /** @var \Drupal\arch_order\Entity\OrderInterface $revision */
      $revision = $order_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->created->value, 'custom', 'Y F j - H:i');

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, as its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $url = new Url('entity.order.revision', [
            'order' => $order->id(),
            'order_revision' => $vid,
          ]);
          $link = Link::fromTextAndUrl($date, $url)->toString();
        }
        else {
          $link = $order->toLink($date)->toString();
          $current_revision_displayed = TRUE;
        }

        $row = [];

        // Submitted on.
        $row[] = [
          'data' => [
            '#markup' => $link,
          ],
        ];

        // Made by.
        $row[] = [
          'data' => [
            '#markup' => $this->renderer->renderPlain($username),
          ],
        ];

        // Message.
        $row[] = [
          'data' => [
            '#markup' => $revision->revision_log->value ? $revision->revision_log->value : ('<em>' . $this->t('No log message was set.') . '</em>'),
          ],
        ];

        // Operations.
        if ($is_current_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
        }
        else {
          $links = [];
          if ($revert_permission) {
            $url = Url::fromRoute('order.revision_revert_confirm', [
              'order' => $order->id(),
              'order_revision' => $vid,
            ]);
            $links['revert'] = [
              'title' => $vid < $order->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
              'url' => $url,
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = [
          'data' => $row,
          'class' => $is_current_revision ? ['revision-current'] : [],
        ];
      }
    }

    $build['order_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => ['arch_order/drupal.order.admin'],
      ],
      '#attributes' => ['class' => 'order-revision-table'],
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Displays an order revision.
   *
   * @param int $order_revision
   *   The order revision ID.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($order_revision) {
    $order = $this->entityTypeManager()->getStorage('order')->loadRevision($order_revision);
    $orderViewController = new OrderViewController(
      $this->entityTypeManager,
      $this->entityRepository,
      $this->renderer,
      $this->currentUser()
    );
    $page = $orderViewController->view($order);
    unset($page['#cache']);
    return $page;
  }

  /**
   * Page title callback for an order revision.
   *
   * @param int $order_revision
   *   The order revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($order_revision) {
    $order = $this->entityTypeManager()->getStorage('order')->loadRevision($order_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $order->label(),
      '%date' => $this->dateFormatter->format(
        $order->getRevisionCreationTime(),
        'medium',
        '',
        NULL,
        NULL
      ),
    ]);
  }

  /**
   * Gets a list of order revision IDs for a specific order.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   The order entity.
   * @param \Drupal\arch_order\Entity\Storage\OrderStorageInterface $orderStorage
   *   The order storage handler.
   *
   * @return int[]
   *   Order revision IDs (in descending order).
   */
  protected function getRevisionIds(OrderInterface $order, OrderStorageInterface $orderStorage) {
    $result = $orderStorage->getQuery()
      ->allRevisions()
      ->condition($order->getEntityType()->getKey('id'), $order->id())
      ->sort($order->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();

    return array_keys($result);
  }

}
