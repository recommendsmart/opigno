<?php

namespace Drupal\personal_notes\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\UserInterface;

/**
 * Added current user id to menu path.
 */
class UserNotesTab extends LocalTaskDefault {

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Gets the current active user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   current user object.
   */
  protected function currentUser() {
    if (!$this->currentUser) {
      $this->currentUser = \Drupal::currentUser();
    }
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $user = $route_match->getParameter('user');
    if ($user instanceof UserInterface) {
      $uid = $user->id();
    }
    else {
      $uid = $user;
    }

    return [
      'user' => $uid,
    ];
  }

}
