<?php

namespace Drupal\eca_user\Event;

use Drupal\Core\Session\AccountInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Class UserBase
 *
 * @package Drupal\eca_user\Event
 */
abstract class UserBase extends Event {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * ContentEntityBase constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * @return \Drupal\Core\Session\AccountInterface
   */
  public function getAccount(): AccountInterface {
    return $this->account;
  }

}
