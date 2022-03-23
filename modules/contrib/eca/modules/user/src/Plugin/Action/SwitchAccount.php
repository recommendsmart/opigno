<?php

namespace Drupal\eca_user\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\CleanupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Switch current account.
 *
 * @Action(
 *   id = "eca_switch_account",
 *   label = @Translation("User: switch current account")
 * )
 */
class SwitchAccount extends ConfigurableActionBase implements CleanupInterface {

  /**
   * A flag indicating whether an account switch was done.
   *
   * @var bool
   */
  protected bool $switched = FALSE;

  /**
   * Whether the "account" context stack is available and ready to use.
   *
   * @var bool
   */
  protected bool $useContextStack = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->useContextStack = $container->get('module_handler')->moduleExists('context_stack_account');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'user_id' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID (UID)'),
      '#default_value' => $this->configuration['user_id'],
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['user_id'] = $form_state->getValue('user_id');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if (!isset($this->configuration['user_id']) || $this->configuration['user_id'] === '') {
      return;
    }
    $user = NULL;

    $uid = (string) $this->tokenServices->replaceClear($this->configuration['user_id']);
    if ($uid !== '' && ctype_digit(strval($uid))) {
      $uid = (int) $uid;
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
    }
    if ($user && !$this->switched) {
      if ($this->useContextStack) {
        /** @var \Drupal\context_stack\ContextStackInterface $context_stack */
        $context_stack = \Drupal::service('context_stack.account');
        $context_stack->addContext(\Drupal\context_stack\Plugin\Context\SwitchAccountContext::fromEntity(...[$user]));
      }
      else {
        /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
        $account_switcher = \Drupal::service('account_switcher');
        $account_switcher->switchTo($user);
      }
      $this->switched = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupAfterSuccessors(): void {
    if ($this->switched) {
      if ($this->useContextStack) {
        /** @var \Drupal\context_stack\ContextStackInterface $context_stack */
        $context_stack = \Drupal::service('context_stack.account');
        $context_stack->pop();
      }
      else {
        /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
        $account_switcher = \Drupal::service('account_switcher');
        $account_switcher->switchBack();
      }
      $this->switched = FALSE;
    }
  }

}
