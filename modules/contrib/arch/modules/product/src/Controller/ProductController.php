<?php

namespace Drupal\arch_product\Controller;

use Drupal\arch_product\Entity\ProductTypeInterface;
use Drupal\arch_product\Entity\Storage\ProductStorageInterface;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Component\Utility\Xss;
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
 * Returns responses for product routes.
 */
class ProductController extends ControllerBase implements ContainerInjectionInterface {

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
   * Constructs a ProductController object.
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
   * Displays add content links for available content types.
   *
   * Redirects to product/add/[type] if only one product type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the product types that can be added;
   *   however, if there is only one product type defined for the site, the
   *   function will return a RedirectResponse to the product add page for that
   *   one product type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addPage() {
    $build = [
      '#theme' => 'product_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('product_type')->getListCacheTags(),
      ],
    ];

    $content = [];

    // Only use product types the user has access to.
    foreach ($this->entityTypeManager()->getStorage('product_type')->loadMultiple() as $type) {
      $access = $this->entityTypeManager()->getAccessControlHandler('product')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the product/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('product.add', ['product_type' => $type->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Provides the product submission form.
   *
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $product_type
   *   The product type entity for the product.
   *
   * @return array
   *   A product submission form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function add(ProductTypeInterface $product_type) {
    $product = $this->entityTypeManager()->getStorage('product')->create([
      'type' => $product_type->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($product);

    return $form;
  }

  /**
   * Displays a product revision.
   *
   * @param int $product_revision
   *   The product revision ID.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($product_revision) {
    $product = $this->entityTypeManager()->getStorage('product')->loadRevision($product_revision);
    $product = $this->entityRepository->getTranslationFromContext($product);
    $product_view_controller = new ProductViewController(
      $this->entityTypeManager,
      $this->entityRepository,
      $this->renderer,
      $this->currentUser()
    );
    $page = $product_view_controller->view($product);
    unset($page['products'][$product->id()]['#cache']);
    return $page;
  }

  /**
   * Page title callback for a product revision.
   *
   * @param int $product_revision
   *   The product revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($product_revision) {
    $product = $this->entityTypeManager()->getStorage('product')
      ->loadRevision($product_revision);
    return $this->t(
      'Revision of %title from %date',
      [
        '%title' => $product->label(),
        '%date' => $this->dateFormatter->format($product->getRevisionCreationTime(), 'medium'),
      ]
    );
  }

  /**
   * Generates an overview table of older revisions of a product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   A product object.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionOverview(ProductInterface $product) {
    $account = $this->currentUser();
    $langcode = $product->language()->getId();
    $langname = $product->language()->getName();
    $languages = $product->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    /** @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface $product_storage */
    $product_storage = $this->entityTypeManager()->getStorage('product');
    $type = $product->getType();

    if ($has_translations) {
      $build['#title'] = $this->t('@langname revisions for %title', [
        '@langname' => $langname,
        '%title' => $product->label(),
      ]);
    }
    else {
      $build['#title'] = $this->t('Revisions for %title', [
        '%title' => $product->label(),
      ]);
    }
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert {$type} product revisions") || $account->hasPermission('revert all product revisions') || $account->hasPermission('administer products')) && $product->access('update'));
    $delete_permission = (($account->hasPermission("delete {$type} product revisions") || $account->hasPermission('delete all product revisions') || $account->hasPermission('administer products')) && $product->access('delete'));

    $rows = [];
    $default_revision = $product->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($product, $product_storage) as $vid) {
      /** @var \Drupal\arch_product\Entity\ProductInterface $revision */
      $revision = $product_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if (
        $revision->hasTranslation($langcode)
        && $revision->getTranslation($langcode)->isRevisionTranslationAffected()
      ) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, as its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = Link::fromTextAndUrl(
            $date,
            new Url('entity.product.revision', [
              'product' => $product->id(),
              'product_revision' => $vid,
            ])
          );
        }
        else {
          $link = $product->toLink($date)->toString();
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
                '#markup' => $revision->revision_log->value,
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        // @todo Simplify once https://www.drupal.org/node/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($is_current_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          $rows[] = [
            'data' => $row,
            'class' => ['revision-current'],
          ];
        }
        else {
          $links = [];
          if ($revert_permission) {
            if ($has_translations) {
              $url = Url::fromRoute(
                'product.revision_revert_translation_confirm',
                [
                  'product' => $product->id(),
                  'product_revision' => $vid,
                  'langcode' => $langcode,
                ]
              );
            }
            else {
              $url = Url::fromRoute(
                'product.revision_revert_confirm',
                [
                  'product' => $product->id(),
                  'product_revision' => $vid,
                ]
              );
            }
            $links['revert'] = [
              'title' => $vid < $product->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
              'url' => $url,
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('product.revision_delete_confirm', [
                'product' => $product->id(),
                'product_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];

          $rows[] = $row;
        }
      }
    }

    $build['product_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => ['arch/drupal.product.admin'],
      ],
      '#attributes' => ['class' => 'product-revision-table'],
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * The _title_callback for the product.add route.
   *
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $product_type
   *   The current product type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(ProductTypeInterface $product_type) {
    return $this->t('Create @name', ['@name' => $product_type->label()]);
  }

  /**
   * Gets a list of product revision IDs for a specific product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   The product entity.
   * @param \Drupal\arch_product\Entity\Storage\ProductStorageInterface $product_storage
   *   The product storage handler.
   *
   * @return int[]
   *   Product revision IDs (in descending order).
   */
  protected function getRevisionIds(ProductInterface $product, ProductStorageInterface $product_storage) {
    $result = $product_storage->getQuery()
      ->allRevisions()
      ->condition($product->getEntityType()->getKey('id'), $product->id())
      ->sort($product->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }

}
