<?php

namespace Drupal\arch_compare\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Commerce Compare' block.
 *
 * @Block(
 *   id = "arch_compare_products_queue_block",
 *   admin_label = @Translation("Compare block", context = "arch_compare")
 * )
 */
class CompareBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Compare settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ImmutableConfig $settings,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = $settings;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
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
      $container->get('config.factory')->get('arch_compare.settings'),
      $container->get('module_handler'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable in https://www.drupal.org/node/2483181
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $limit = (int) $this->settings->get('limit');
    $max_age = (int) $this->settings->get('compare_selection_preservation_time');
    $url = new Url('arch_compare.compare_page');
    return [
      '#theme' => 'compare_block',
      '#url' => $url,
      '#text' => $this->t('Compare', [], ['context' => 'arch_compare']),
      '#limit' => $limit,
      '#attributes' => [
        'class' => ['compare-block', 'compare-items--empty'],
      ],
      '#attached' => [
        'library' => [
          'arch_compare/compare_block',
        ],
        'drupalSettings' => [
          'arch_compare' => [
            'limit' => $limit,
            'max_age' => $max_age,
            'selector' => [
              'compare_item' => '.compare-item',
              'compare_item_input' => '.compare-item input',
              'compare_item_remove' => '.compare-block .compare-item-remove',
              'compare_clear_all' => '.compare-block .compare-list--clear-all',
            ],
          ],
        ],
      ],
    ];
  }

}
