<?php

namespace Drupal\arch_cart\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to for the /cart page.
 */
class CartAccessCheck implements AccessInterface {

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * UserRegisterAccessCheck constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   Current route match.
   */
  public function __construct(
    CurrentRouteMatch $routeMatch
  ) {
    $this->routeMatch = $routeMatch;
  }

  /**
   * Checks access to the /cart page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account) {
    if (
      $this->routeMatch->getRouteName() == 'arch_cart.content'
      && $account->hasPermission('access content')
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

}
