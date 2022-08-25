<?php

namespace Drupal\route_override\RoutingCacheability;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Rebuild routes if route tags are invalidated.
 */
final class RoutingCacheabilityCacheTagsInvalidator implements CacheTagsInvalidatorInterface {

  protected RouteProviderInterface $routeProvider;

  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  public function __construct() {
    // Load services lazily to prevent circular dependencies.
    // Neither setter injection nor lazy: true with prosy class resolves this.
  }


  public function invalidateTags(array $tags) {
    $routingTags = $this->getRoutingCacheability()->getCacheTags();
    if (array_intersect($tags, $routingTags)) {
      \Drupal::service('router.builder')->rebuild();
      // @see \Drupal\Core\Routing\RouteProvider::getRouteCollectionForRequest
      $this->cacheTagsInvalidator()->invalidateTags(['route_match']);
    }
  }

  public function getRoutingCacheability(): CacheableDependencyInterface {
    try {
      $route = $this->routeProvider()->getRouteByName(RoutingCacheabilityRouteSubscriber::FAKE_ROUTE_NAME);
    } catch (RouteNotFoundException $e) {
      return new CacheableMetadata();
    }
    return RouteCacheability::get($route);
  }

  public function routeProvider(): RouteProviderInterface {
    if (!isset($this->routeProvider)) {
      $this->routeProvider = \Drupal::service('router.route_provider');
    }
    return $this->routeProvider;
  }

  public function cacheTagsInvalidator(): CacheTagsInvalidatorInterface {
    if (!isset($this->cacheTagsInvalidator)) {
      $this->cacheTagsInvalidator = \Drupal::service('cache_tags.invalidator');
    }
    return $this->cacheTagsInvalidator;
  }

}
