<?php

namespace Drupal\form_display_visibility\Plugin\FormDisplayVisibilityCondition;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\form_display_visibility\Plugin\FormDisplayVisibilityConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds a register self operation for the timeslot.
 *
 * @FormDisplayVisibilityCondition(
 *   id = "access_by_role",
 *   label = @Translation("Access by role"),
 *   description = @Translation("Select the roles to be able to see the widget."),
 * )
 */
class AccessByRole extends PluginBase implements FormDisplayVisibilityConditionInterface, ContainerFactoryPluginInterface {

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyCondition() {
    if (isset($this->configuration['field_settings']['third_party_settings']['form_display_visibility']['conditions']['access_by_role'])) {
      $configuration = $this->configuration['field_settings']['third_party_settings']['form_display_visibility']['conditions']['access_by_role'];

      if ($configuration['enabled'] && $configuration['role']) {
        if (count(array_intersect($configuration['role'], $this->currentUser->getRoles()))) {
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
    if (isset($this->configuration['field_settings']['third_party_settings']['form_display_visibility']['conditions']['access_by_role'])) {
      $configuration = $this->configuration['field_settings']['third_party_settings']['form_display_visibility']['conditions']['access_by_role'];
    }

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable access by role'),
      '#default_value' => $configuration['enabled'] ?? FALSE,
    ];

    $form['role'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Role'),
      '#default_value' => $configuration['role'] ?? FALSE,
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#description' => $this->t('Only the checked roles will be able to access this widget.'),
      '#states' => [
        'visible' => [
          ':input[name$="[third_party_settings][form_display_visibility][conditions][access_by_role][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function displaySummary() {
    if ($this->configuration['access_by_role']['enabled']) {
      $enabled_role = array_filter($this->configuration['access_by_role']['role']);
      $enabled_role = reset($enabled_role);
      return $this->t('Enabled access for role: @role', ['@role' => $enabled_role]);
    }

    return '';
  }

}
