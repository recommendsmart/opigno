<?php

namespace Drupal\display_link_plus\Plugin\views\area;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Defines an area plugin to display a bundle-specific node/add link.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("display_link_plus")
 */
class DisplayLinkPlus extends AreaPluginBase {
  use RedirectDestinationTrait;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The current user.
   *
   * We'll need this service in order to check view access.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->accessManager = $access_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['display_id'] = ['default' => NULL];
    $options['label'] = ['default' => NULL];
    $options['class'] = ['default' => NULL];
    $options['target'] = ['default' => ''];
    $options['width'] = ['default' => '600'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // All displays.
    // $displays = $this->view->storage->get('display');
    $display_objects = $this->view->displayHandlers;
    $displays = [];

    foreach($display_objects as $display_object) {
      if ($display_object->display['display_plugin'] == 'page') {
        $displays[$display_object->display['id']] = $display_object->display['display_title'];
      }
    }
    $form['display_id'] = [
      '#title' => $this->t('Display'),
      '#description' => $this->t('The display to which we should link.'),
      '#type' => 'select',
      '#options' => $displays,
      '#default_value' => (!empty($this->options['display_id'])) ? $this->options['display_id'] : '',
      '#required' => TRUE,
    ];
    $form['label'] = [
      '#title' => $this->t('Label'),
      '#description' => $this->t('The text of the link. Leave blank to use the display\'s title'),
      '#type' => 'textfield',
      '#default_value' => $this->options['label'],
    ];
    // TODO: allow for multiple classes.
    $form['class'] = [
      '#title' => $this->t('Class'),
      '#description' => $this->t('A CSS class to apply to the link. If using multiple classes, separate them by spaces.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['class'],
    ];
    $form['target'] = [
      '#title' => $this->t('Target'),
      '#description' => $this->t('Optionally have the form open on-page in a modal or off-canvas dialog.'),
      '#type' => 'select',
      '#default_value' => $this->options['target'],
      '#options' => [
        '' => $this->t('Default'),
        'tray' => $this->t('Off-Screen Tray'),
        'modal' => $this->t('Modal Dialog'),
      ],
    ];
    $form['width'] = [
      '#title' => $this->t('Dialog Width'),
      '#description' => $this->t('How wide the dialog should appear.'),
      '#type' => 'number',
      '#min' => '100',
      '#default_value' => $this->options['width'],
      '#states' => [
        // Show this number field only if a dialog is chosen above.
        'invisible' => [
          ':input[name="options[target]"]' => ['value' => ''],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if ($empty && empty($this->options['display_id'])) {
      return [];
    }
    $account = $this->currentUser;
    $access = $this->view->access($this->options['display_id'], $account);
    $url = $this->view->getUrl(NULL, $this->options['display_id']);
    $url->setOption('query', $this->getDestinationArray());

    if (empty($this->options['label'])) {
      $display_objects = $this->view->displayHandlers;

      foreach($display_objects as $display_object) {
        if ($display_object->display['id'] == $this->options['display_id']) {
          $this->options['label'] = $display_object->display['display_title'];
          break;
        }
      }
    }

    // Parse and sanitize provided classes.
    if ($this->options['class']) {
      $classes = explode(' ', $this->options['class']);
      foreach ($classes as $index => $class) {
        $classes[$index] = Html::getClass($class);
      }
    }
    else {
      $classes = [];
    }
    // Assembled elements into a link render array.
    $element = [
      '#type' => 'link',
      '#title' => $this->options['label'],
      '#url' => $url,
      '#options' => [
        'attributes' => ['class' => $classes],
      ],
      '#access' => $access,
    ];
    // Apply the selected dialog options.
    if ($this->options['target']) {
      $element['#options']['attributes']['class'][] = 'use-ajax';
      $width = $this->options['width'] ?: 600;
      $element['#options']['attributes']['data-dialog-options'] = Json::encode(['width' => $width]);
      switch ($this->options['target']) {
        case 'tray':
          $element['#options']['attributes']['data-dialog-renderer'] = 'off_canvas';
          $element['#options']['attributes']['data-dialog-type'] = 'dialog';
          break;

        case 'modal':
          $element['#options']['attributes']['data-dialog-type'] = 'modal';
          break;
      }
    }
    return $element;
  }

}
