<?php

namespace Drupal\tms\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tms\Entity\TicketInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TicketController.
 *
 *  Returns responses for Ticket routes.
 */
class TicketController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Ticket revision.
   *
   * @param int $ticket_revision
   *   The Ticket revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($ticket_revision) {
    $ticket = $this->entityTypeManager()->getStorage('ticket')
      ->loadRevision($ticket_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('ticket');

    return $view_builder->view($ticket);
  }

  /**
   * Page title callback for a Ticket revision.
   *
   * @param int $ticket_revision
   *   The Ticket revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($ticket_revision) {
    $ticket = $this->entityTypeManager()->getStorage('ticket')
      ->loadRevision($ticket_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $ticket->label(),
      '%date' => $this->dateFormatter->format($ticket->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Ticket.
   *
   * @param \Drupal\tms\Entity\TicketInterface $ticket
   *   A Ticket object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(TicketInterface $ticket) {
    $account = $this->currentUser();
    $ticket_storage = $this->entityTypeManager()->getStorage('ticket');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $ticket->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all ticket revisions") || $account->hasPermission('administer ticket entities')));
    $delete_permission = (($account->hasPermission("delete all ticket revisions") || $account->hasPermission('administer ticket entities')));

    $rows = [];

    $vids = $ticket_storage->revisionIds($ticket);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\tms\TicketInterface $revision */
      $revision = $ticket_storage->loadRevision($vid);
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $ticket->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.ticket.revision', [
            'ticket' => $ticket->id(),
            'ticket_revision' => $vid,
          ]));
        }
        else {
          $link = $ticket->toLink($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $date,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.ticket.revision_revert', [
                'ticket' => $ticket->id(),
                'ticket_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.ticket.revision_delete', [
                'ticket' => $ticket->id(),
                'ticket_revision' => $vid,
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

        $rows[] = $row;
    }

    $build['ticket_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
