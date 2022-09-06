<?php

namespace Drupal\lbl;

use Drupal\Core\Layout\LayoutDefinition;
use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Helper class for css grid.
 */
class CssGenerator implements CssGeneratorInterface {

  /**
   * Breakpoint manager.
   *
   * @var \Drupal\breakpoint\BreakpointManagerInterface
   */
  protected $breakpointManager;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Css generator for lbl.
   *
   * @param \Drupal\breakpoint\BreakpointManagerInterface $manager
   *   Breakpoint manager.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Theme manager.
   */
  public function __construct(BreakpointManagerInterface $manager, ThemeManagerInterface $theme_manager) {
    $this->breakpointManager = $manager;
    $this->themeManager = $theme_manager;
  }

  /**
   * Get breakpoints with fallback.
   *
   * @return \Drupal\breakpoint\BreakpointInterface[]
   *   Breakpoints.
   */
  public function getBreakpoints(): array {
    $breakpoints = $this->breakpointManager->getBreakpointsByGroup($this->themeManager->getActiveTheme()->getName());
    if (empty($breakpoints)) {
      $breakpoints = $this->breakpointManager->getBreakpointsByGroup('lbl');
    }
    return $breakpoints;
  }

  /**
   * {@inheritdoc}
   */
  public function build(LayoutDefinition $definition) : String|NULL {
    $definitions = $definition->get('variants');
    $id = $definition->id();
    $defs = array_keys($definitions);
    $breakpoints = $this->getBreakpoints();

    if (count($breakpoints) == 0 || !$definitions || !is_array($definitions) || count($definitions) == 0) {
      return NULL;
    }
    $css = array_reduce($defs, function ($value, $key) use ($definitions, $id) {
      $v = $definitions[$key];
      $map = $v['map'];

      $regions = array_reduce($map, function ($css, $vv) {
        return $css . '"' . implode(" ", $vv) . '" ';
      }, "");

      $cols = array_reduce($map, function ($max, $vv) {
        return (count($vv) > $max) ? count($vv) : $max;
      }, 0);

      $rows = count($map);

      $class = implode('-', [$id, $key]);
      $class = Html::cleanCssIdentifier($class);
      $grid = " { display: grid; grid-template-rows:  repeat($rows, auto); grid-template-areas: $regions; grid-template-columns: repeat($cols, auto) }";

      $value[] = [
        'class' => $class,
        'grid' => $grid,
      ];
      return $value;
    }, []);

    $breakCss = array_map(function ($b) use ($css) {
      $bCss = array_map(function ($v) use ($b) {
        return '.' . $v['class'] . '-' . Html::cleanCssIdentifier($b->getPluginId(), ['.' => '-']) . ' ' . $v['grid'];
      }, $css);
      if ($b->getMediaQuery() == "") {
        return implode("\n", $bCss);
      }
      return '@media ' . $b->getMediaQuery() . ' { ' . implode("\n", $bCss) . '}';
    }, $breakpoints);

    return \Minify_CSSmin::minify(implode("\n", $breakCss));
  }

}
