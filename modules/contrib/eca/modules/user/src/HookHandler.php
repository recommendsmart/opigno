<?php

namespace Drupal\eca_user;

use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Event\BaseHookHandler;
use Drupal\user\UserInterface;

/**
 * The handler for hook implementations within the eca_user.module file.
 */
class HookHandler extends BaseHookHandler {

  /**
   * @param \Drupal\user\UserInterface $account
   */
  public function login(UserInterface $account): void {
    $this->triggerEvent->dispatchFromPlugin('user:login', $account);
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function logout(AccountInterface $account): void {
    $this->triggerEvent->dispatchFromPlugin('user:logout', $account);
  }

  /**
   * @param array $edit
   * @param \Drupal\user\UserInterface $account
   * @param string $method
   */
  public function cancel(array $edit, UserInterface $account, string $method): void {
    $this->triggerEvent->dispatchFromPlugin('user:cancel', $account);
  }

}
