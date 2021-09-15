<?php

namespace Drupal\arch_addressbook\Controller;

use Drupal\arch_addressbook\AddressbookitemInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for addressbookitem routes.
 */
class AddressbookController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Addressbook item storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $addressBookItemStorage;

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
   * Constructs an AddressbookController object.
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
    $this->addressBookItemStorage = $entity_type_manager->getStorage('addressbookitem');
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
   * Revision an address revisions.
   *
   * @param \Drupal\arch_addressbook\AddressbookitemInterface $addressbookitem
   *   Addressbookitem entity.
   *
   * @return array|mixed
   *   Renderable array of available revisions from the entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function revisionOverview(AddressbookitemInterface $addressbookitem) {
    $account = $this->currentUser();
    $langcode = $addressbookitem->language()->getId();

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $addressbookitem->label()]);
    $header = [
      $this->t('Submitted on'),
      $this->t('Made by'),
      $this->t('Message'),
      $this->t('Operations'),
    ];

    $revert_permission = (($account->hasPermission("revert addressbookitem revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer addressbookitems')) && $addressbookitem->access('update'));

    $rows = [];
    $default_revision = $addressbookitem->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($addressbookitem, $this->addressBookItemStorage) as $vid) {
      /** @var \Drupal\arch_addressbook\AddressbookitemInterface $revision */
      $revision = $this->addressBookItemStorage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'custom', 'Y F j - H:i');

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, as its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = Link::fromTextAndUrl(
            $date,
            new Url('entity.addressbookitem.revision', [
              'addressbookitem' => $addressbookitem->id(),
              'addressbookitem_revision' => $vid,
            ])
          );
        }
        else {
          $link = $addressbookitem->toLink($date);
          $current_revision_displayed = TRUE;
        }

        $row = [];

        // Submitted on.
        $row[] = [
          'data' => [
            '#markup' => $link->toString(),
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
            $links['revert'] = [
              'title' => $vid < $addressbookitem->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
              'url' => Url::fromRoute('addressbookitem.revision_revert_confirm', [
                'addressbookitem' => $addressbookitem->id(),
                'addressbookitem_revision' => $vid,
              ]),
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

    $build['addressbookitem_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => ['arch_addressbook/drupal.addressbookitem.admin'],
      ],
      '#attributes' => ['class' => 'addressbookitem-revision-table'],
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Displays an addressbookitem revision.
   *
   * @param int $addressbookitem_revision
   *   The addressbookitem revision ID.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($addressbookitem_revision) {
    $addressbookitem = $this->addressBookItemStorage->loadRevision($addressbookitem_revision);
    $addressbookitemViewController = new AddressbookViewController(
      $this->entityTypeManager,
      $this->entityRepository,
      $this->renderer,
      $this->currentUser()
    );
    $page = $addressbookitemViewController->view($addressbookitem);
    unset($page['#cache']);
    return $page;
  }

  /**
   * Page title callback for an addressbookitem revision.
   *
   * @param int $addressbookitem_revision
   *   The addressbookitem revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($addressbookitem_revision) {
    $addressbookitem = $this->entityTypeManager()->getStorage('addressbookitem')->loadRevision($addressbookitem_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $addressbookitem->label(),
      '%date' => $this->dateFormatter->format(
        $addressbookitem->getRevisionCreationTime(),
        'medium',
        '',
        NULL,
        NULL
      ),
    ]);
  }

  /**
   * Gets a list of addressbookitem revision IDs for a specific addressbookitem.
   *
   * @param \Drupal\arch_addressbook\AddressbookitemInterface $addressbookitem
   *   The addressbookitem entity.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entityStorage
   *   The addressbookitem storage handler.
   *
   * @return int[]
   *   Addressbookitem revision IDs (in descending order).
   */
  protected function getRevisionIds(AddressbookitemInterface $addressbookitem, EntityStorageInterface $entityStorage) {
    $result = $entityStorage->getQuery()
      ->allRevisions()
      ->condition($addressbookitem->getEntityType()->getKey('id'), $addressbookitem->id())
      ->sort($addressbookitem->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();

    return array_keys($result);
  }

}
