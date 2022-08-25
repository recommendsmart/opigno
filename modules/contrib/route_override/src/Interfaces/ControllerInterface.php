<?php

namespace Drupal\route_override\Interfaces;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

interface ControllerInterface {

  public function access(RouteMatchInterface $routeMatch, AccountInterface $account, Request $request): AccessResultInterface;

  /**
   * @return array|\Symfony\Component\HttpFoundation\Response
   */
  public function build(RouteMatchInterface $routeMatch, Request $request);

}
