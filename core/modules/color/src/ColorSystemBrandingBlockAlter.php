<?php

namespace Drupal\color;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Provides a trusted callback to alter the system branding block.
 *
 * @see color_block_view_system_branding_block_alter()
 */
class ColorSystemBrandingBlockAlter implements RenderCallbackInterface {

  /**
   * #pre_render callback: Sets color preset logo.
   */
  public static function preRender($build) {
    $theme_key = \Drupal::theme()->getActiveTheme()->getName();
    $config = \Drupal::config('color.theme.' . $theme_key);
    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($config)
      ->applyTo($build);

    // Override logo.
    $paths = \Drupal::service('color.theme_decorator')->getThemeFiles($theme_key);
    if (!empty($paths['logo']) && $build['content']['site_logo'] && preg_match('!' . $theme_key . '/logo.svg$!', $build['content']['site_logo']['#uri'])) {
      $build['content']['site_logo']['#uri'] = \Drupal::service('file_url_generator')->generateString($paths['logo']);
    }

    return $build;
  }

}
