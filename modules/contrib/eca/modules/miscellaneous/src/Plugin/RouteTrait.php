<?php

namespace Drupal\eca_misc\Plugin;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Trait for route related actions and conditions.
 *
 * @see \Drupal\eca_misc\Plugin\Action\TokenLoadRouteParameter
 * @see \Drupal\eca_misc\Plugin\ECA\Condition\RouteMatch
 */
trait RouteTrait {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'request') {
      return [
        RouteInterface::ROUTE_CURRENT => $this->t('current'),
        RouteInterface::ROUTE_PARENT => $this->t('parent'),
        RouteInterface::ROUTE_MAIN => $this->t('main'),
      ];
    }
    return parent::getOptions($id);
  }

  /**
   * Builds and return the reoute match depending on the plugin configuration.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   */
  protected function getRouteMatch(): RouteMatchInterface {
    if (!isset($this->routeMatch)) {
      $requestStack = $this->requestStack ?? \Drupal::requestStack();
      switch ($this->configuration['request']) {
        case RouteInterface::ROUTE_MAIN:
          $request = $requestStack->getMasterRequest();
          break;

        case RouteInterface::ROUTE_PARENT:
          $request = $requestStack->getParentRequest();
          break;

        case RouteInterface::ROUTE_CURRENT:
          $request = $requestStack->getCurrentRequest();
          break;

      }
    }
    if (isset($request)) {
      $this->routeMatch = RouteMatch::createFromRequest($request);
    }
    return $this->routeMatch;
  }

  /**
   * Builds the configuration form for route related plugins to decide, which
   * request (main, parent or current) should be used for route matches.
   *
   * @param array $form
   *   The form to which the config field should be added.
   */
  protected function requestFormField(array &$form): void {
    $form['request'] = [
      '#type' => 'select',
      '#title' => $this->t('Request'),
      '#default_value' => $this->configuration['request'],
      '#options' => $this->getOptions('request'),
      '#weight' => -11,
    ];
  }

}
