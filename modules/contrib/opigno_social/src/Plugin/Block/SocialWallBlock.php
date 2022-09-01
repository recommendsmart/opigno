<?php

namespace Drupal\opigno_social\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\opigno_social\Form\CreatePostForm;
use Drupal\opigno_social\Services\OpignoPostsManager;
use Drupal\opigno_social\Services\UserConnectionManager;
use Drupal\user\UserInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the social wall block.
 *
 * @Block(
 *  id = "opigno_social_wall_block",
 *  admin_label = @Translation("Social wall"),
 *  category = @Translation("Opigno Social"),
 * )
 */
class SocialWallBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Whether the social features enabled or not.
   *
   * @var bool
   */
  protected $socialsEnabled;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The loaded current user.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $user = NULL;

  /**
   * The user view builder service.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The posts manager service.
   *
   * @var \Drupal\opigno_social\Services\OpignoPostsManager
   */
  protected $postsManager;

  /**
   * The CSRF token generator service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, FormBuilderInterface $form_builder, AccountInterface $account, EntityTypeManagerInterface $entity_type_manager, OpignoPostsManager $posts_manager, CsrfTokenGenerator $csrf_token, ...$default) {
    parent::__construct(...$default);
    $this->socialsEnabled = (bool) $config_factory->get('opigno_class.socialsettings')->get('enable_social_features') ?? FALSE;
    $this->formBuilder = $form_builder;
    $this->viewBuilder = $entity_type_manager->getViewBuilder('user');
    $this->postsManager = $posts_manager;
    $this->csrfToken = $csrf_token;
    $uid = (int) $account->id();

    try {
      $this->user = $entity_type_manager->getStorage('user')->load($uid);
    }
    catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
      watchdog_exception('opigno_social_exception', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('opigno_posts.manager'),
      $container->get('csrf_token'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->socialsEnabled || !$this->user instanceof UserInterface) {
      return [
        '#cache' => [
          'tags' => ['config:opigno_class.socialsettings'],
        ],
      ];
    }

    // Set the social wall access time for the user.
    $this->postsManager->setLastUserSocialWallAccessTime();
    $url = Url::fromRoute('opigno_social.check_new_posts')->toString();
    $options = [
      'attributes' => [
        'id' => 'opigno-new-posts-link',
        'class' => [
          'use-ajax',
          'btn',
          'btn-rounded',
          'btn-new-post',
        ],
        'data-opigno-social-check-posts-url' => $url instanceof GeneratedUrl ? $url->getGeneratedUrl() : $url,
      ],
    ];

    // Generate attachment links.
    $links = [];
    $attachment_links = [
      'training' => [
        'icon' => 'fi fi-rr-book-alt',
        'title' => $this->t('Training', [], ['context' => 'Opigno post']),
        'class' => 'training',
      ],
      'badge' => [
        'icon' => 'fi fi-rr-badge',
        'title' => $this->t('Badges', [], ['context' => 'Opigno post']),
        'class' => 'badges',
      ],
      'certificate' => [
        'icon' => 'fi fi-rr-diploma',
        'title' => $this->t('Certificates', [], ['context' => 'Opigno post']),
        'class' => 'certificate',
      ],
    ];

    foreach ($attachment_links as $type => $data) {
      $links[] = [
        '#type' => 'link',
        '#title' => Markup::create('<i class="' . $data['icon'] . '"></i>' . $data['title']),
        '#url' => $this->createProtectedUrl('opigno_social.get_shareable_content', ['type' => $type]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'awards-list__item',
            'awards-list__' . $data['class'],
          ],
        ],
      ];
    }

    $view = Views::getView('opigno_social_posts')->executeDisplay('posts');
    $view_attachments = $view['#attached'] ?? [];

    return [
      '#theme' => 'opigno_social_wall_block',
      '#user' => $this->viewBuilder->view($this->user, 'post_author'),
      '#create_post_form' => $this->formBuilder->getForm(CreatePostForm::class),
      '#attachment_links' => $links,
      '#posts' => $view,
      '#new_posts_link' => Link::createFromRoute($this->t('New post available'), 'opigno_social.display_new_posts', [], $options),
      '#attached' => array_merge_recursive([
        'library' => [
          'opigno_social/post_comment',
          'opigno_social/post_sharing',
        ],
      ], $view_attachments),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'config:opigno_class.socialsettings',
      UserConnectionManager::USER_CONNECTIONS_CACHE_TAG_PREFIX . $this->user->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

  /**
   * Protect the url with the CSRF token to make AJAX request secure.
   *
   * @param string $route
   *   The route name to create the url.
   * @param array $params
   *   The route parameters.
   * @param array $options
   *   The url options.
   *
   * @return \Drupal\Core\Url
   *   The protected url.
   */
  protected function createProtectedUrl(string $route, array $params = [], array $options = []): Url {
    $url = Url::fromRoute($route, $params, $options);
    $internal = $url->getInternalPath();
    $url->setOption('query', ['token' => $this->csrfToken->get($internal)]);

    return $url;
  }

}
