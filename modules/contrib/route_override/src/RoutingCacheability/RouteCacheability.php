<?php

namespace Drupal\route_override\RoutingCacheability;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\Routing\Route;

/**
 * Get or add cacheability as specified by override controller to override routes.
 */
final class RouteCacheability {

  const ROUTE_OPTION_CACHE_TAGS = 'route_override.cacheability.tags';

  const ROUTE_OPTION_CACHE_CONTEXTS = 'route_override.cacheability.contexts';

  public static function get(Route $route): CacheableDependencyInterface {
    return (new CacheableMetadata())
      ->addCacheTags($route->getOption(static::ROUTE_OPTION_CACHE_TAGS) ?? [])
      ->addCacheContexts($route->getOption(static::ROUTE_OPTION_CACHE_CONTEXTS) ?? []);
  }

  private static function set(Route $route, CacheableDependencyInterface $cacheability) {
    $route->setOption(static::ROUTE_OPTION_CACHE_TAGS, $cacheability->getCacheTags());
    $route->setOption(static::ROUTE_OPTION_CACHE_CONTEXTS, $cacheability->getCacheContexts());
  }

  public static function add(Route $route, CacheableDependencyInterface $cacheability) {
    $routeCacheability = static::get($route);
    assert($routeCacheability instanceof CacheableMetadata);
    $routeCacheability->addCacheableDependency($cacheability);
    static::set($route, $routeCacheability);
  }

}
