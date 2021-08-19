<?php

namespace Drupal\form_display_visibility\Plugin\FormDisplayVisibilityCondition;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\form_display_visibility\Plugin\FormDisplayVisibilityConditionInterface;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds a register self operation for the timeslot.
 *
 * @FormDisplayVisibilityCondition(
 *   id = "access_by_permission",
 *   label = @Translation("Access by permission"),
 *   description = @Translation("Select the permissions to be able to see the widget."),
 * )
 */
class AccessByPermission extends PluginBase implements FormDisplayVisibilityConditionInterface, ContainerFactoryPluginInterface {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a AccessByPermission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;
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
      $container->get('user.permissions'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyCondition() {
    if (isset($this->configuration['field_settings']['third_party_settings']['form_display_visibility']['conditions']['access_by_permission'])) {
      $configuration = $this->configuration['field_settings']['third_party_settings']['form_display_visibility']['conditions']['access_by_permission'];

      if ($configuration['enabled'] && $configuration['perm']) {
        if ($this->currentUser->hasPermission($configuration['perm'])) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::forbidden();
        }
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    $form = [];
    $perms = [];

    if (isset($this->configuration['field_settings']['third_party_settings']['form_display_visibility']['conditions']['access_by_permission'])) {
      $configuration = $this->configuration['field_settings']['third_party_settings']['form_display_visibility']['conditions']['access_by_permission'];
    }

    $permissions = $this->permissionHandler->getPermissions();
    foreach ($permissions as $perm => $perm_item) {
      $provider = $perm_item['provider'];
      $display_name = $this->moduleHandler->getName($provider);
      $perms[$display_name][$perm] = strip_tags($perm_item['title']);
    }

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable access by permission'),
      '#default_value' => $configuration['enabled'] ?? FALSE,
    ];

    $form['perm'] = [
      '#type' => 'select',
      '#options' => $perms,
      '#title' => $this->t('Permission'),
      '#default_value' => $configuration['perm'] ?? FALSE,
      '#description' => $this->t('Only users with the selected permission flag will be able to access this widget.'),
      '#states' => [
        'visible' => [
          ':input[name$="[third_party_settings][form_display_visibility][conditions][access_by_permission][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function displaySummary() {
    if ($this->configuration['access_by_permission']['enabled']) {
      return $this->t('Enabled access for permission: @permission', ['@permission' => $this->configuration['access_by_permission']['perm']]);
    }

    return '';
  }

}
