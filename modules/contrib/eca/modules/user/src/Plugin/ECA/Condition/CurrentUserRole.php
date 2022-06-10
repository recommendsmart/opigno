<?php

namespace Drupal\eca_user\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\OptionsInterface;
use Drupal\user\Entity\Role;

/**
 * Plugin implementation of the ECA condition of the current user's role.
 *
 * @EcaCondition(
 *   id = "eca_current_user_role",
 *   label = "Role of current user"
 * )
 */
class CurrentUserRole extends BaseUser implements OptionsInterface {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $userRoles = $this->currentUser->getRoles();
    $result = in_array($this->configuration['role'], $userRoles, TRUE);
    return $this->negationCheck($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'role' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('User role'),
      '#default_value' => $this->configuration['role'],
      '#options' => $this->getOptions('role'),
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['role'] = $form_state->getValue('role');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'role') {
      $roles = [];
      /** @var \Drupal\user\RoleInterface $role */
      foreach (Role::loadMultiple() as $role) {
        $roles[$role->id()] = $role->label();
      }
      return $roles;
    }
    return NULL;
  }

}
