<?php

namespace Drupal\storage\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\storage\Entity\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StorageController.
 *
 *  Returns responses for Storage routes.
 */
class StorageController extends ControllerBase implements ContainerInjectionInterface {

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
   * Displays a Storage revision.
   *
   * @param int $storage_revision
   *   The Storage revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($storage_revision) {
    $storage = $this->entityTypeManager()->getStorage('storage')
      ->loadRevision($storage_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('storage');

    return $view_builder->view($storage);
  }

  /**
   * Page title callback for a Storage revision.
   *
   * @param int $storage_revision
   *   The Storage revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($storage_revision) {
    $storage = $this->entityTypeManager()->getStorage('storage')
      ->loadRevision($storage_revision);
    return $this->t('Revision of %name from %date', [
      '%name' => $storage->label(),
      '%date' => $this->dateFormatter->format($storage->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Storage.
   *
   * @param \Drupal\storage\Entity\StorageInterface $storage
   *   A Storage object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(StorageInterface $storage) {
    $account = $this->currentUser();
    $storage_storage = $this->entityTypeManager()->getStorage('storage');

    $langcode = $storage->language()->getId();
    $langname = $storage->language()->getName();
    $languages = $storage->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %name', ['@langname' => $langname, '%name' => $storage->label()]) : $this->t('Revisions for %name', ['%name' => $storage->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all storage revisions") || $account->hasPermission('administer storage entities')));
    $delete_permission = (($account->hasPermission("delete all storage revisions") || $account->hasPermission('administer storage entities')));

    $rows = [];

    $vids = $storage_storage->revisionIds($storage);

    $latest_revision = TRUE;
    $default_revision = $storage->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\storage\StorageInterface $revision */
      $revision = $storage_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = Link::fromTextAndUrl($date, new Url('entity.storage.revision', ['storage' => $storage->id(), 'storage_revision' => $vid]))->toString();
        } else {
          $link = $storage->toLink($date)->toString();
          $current_revision_displayed = TRUE;
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
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
              'url' => $has_translations ?
              Url::fromRoute('entity.storage.translation_revert', [
                'storage' => $storage->id(),
                'storage_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.storage.revision_revert', [
                'storage' => $storage->id(),
                'storage_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.storage.revision_delete', [
                'storage' => $storage->id(),
                'storage_revision' => $vid,
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
    }

    $build['storage_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
