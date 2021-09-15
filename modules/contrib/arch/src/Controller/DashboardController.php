<?php

namespace Drupal\arch\Controller;

use Drupal\arch\StoreDashboardPanel\StoreDashboardPanelManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard page controller.
 *
 * @package Drupal\arch\Controller
 */
class DashboardController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Panel manager.
   *
   * @var \Drupal\arch\StoreDashboardPanel\StoreDashboardPanelManagerInterface
   */
  protected $panelManager;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a ProductController object.
   *
   * @param \Drupal\arch\StoreDashboardPanel\StoreDashboardPanelManagerInterface $panel_manager
   *   Panel manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Theme manager.
   */
  public function __construct(
    StoreDashboardPanelManagerInterface $panel_manager,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager
  ) {
    $this->panelManager = $panel_manager;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.store_dashboard_panel'),
      $container->get('module_handler'),
      $container->get('theme.manager')
    );
  }

  /**
   * Build content of dashboard page.
   *
   * @return array
   *   Render array.
   */
  public function dashboard() {
    $build = [];

    $build['tasks'] = $this->buildTasks();
    $build['panels'] = $this->buildPanels();
    $build['#attached']['library'][] = 'arch/dashboard';

    $build['#cache']['contexts'][] = 'user.permissions';
    $build['#cache']['contexts'][] = 'languages:language_interface';
    $build['#cache']['contexts'][] = 'theme';

    $this->moduleHandler->alter('arch_dashboard', $build);
    $this->themeManager->alter('arch_dashboard', $build);

    return [$build];
  }

  /**
   * Task list.
   *
   * @return array
   *   Render array.
   */
  protected function buildTasks() {
    $build = [];

    $tasks = $this->moduleHandler->invokeAll('arch_tasks');
    $this->moduleHandler->alter('arch_tasks', $tasks);
    $this->themeManager->alter('arch_tasks', $tasks);

    foreach ($tasks as $task) {
      $task += [
        '#type' => 'link',
        '#title' => NULL,
        '#url' => NULL,
        '#weight' => 0,
      ];
      if (empty($task['#url'])) {
        continue;
      }
      $build['#items'][] = $task;
    }

    if (!empty($build)) {
      $build['#theme'] = 'item_list';
      $build['#attributes']['class'] = 'arch-dashboard-tasks';
    }

    return $build;
  }

  /**
   * Panels.
   *
   * @return array
   *   Render array.
   */
  protected function buildPanels() {
    $build = [];

    $panels = [];
    foreach ($this->panelManager->getDefinitions() as $id => $definition) {
      /** @var \Drupal\arch\StoreDashboardPanel\StoreDashboardPanelPluginInterface $plugin */
      $plugin = $this->panelManager->createInstance($id, []);
      if (empty($plugin)) {
        continue;
      }
      $panel_content = $plugin->build();
      if (empty($panel_content)) {
        continue;
      }

      $block_id = '';
      if (!empty($plugin->getConfiguration()['provider'])) {
        $block_id = $plugin->getConfiguration()['provider'] . ':';
      }

      $panels[$block_id] = [
        '#panel_definition' => $definition,
        '#panel' => $plugin,
        'content' => $panel_content,
      ];
    }

    $this->moduleHandler->alter('arch_dashboard_panels', $panels);
    $this->themeManager->alter('arch_dashboard_panels', $panels);

    foreach ($panels as $name => $panel) {
      if (empty($panel) || !is_array($panel)) {
        continue;
      }
      $build[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'arch-dashboard-panel',
            'arch-dashboard-panel--' . Html::cleanCssIdentifier($name),
          ],
        ],
        'content' => $panel,
      ];
    }

    if (!empty($build)) {
      $build['#type'] = 'container';
      $build['#attributes']['class'] = 'arch-dashboard-panels';
    }

    return $build;
  }

}
