<?php

namespace Drupal\arch\StoreDashboardPanel;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;
use Drupal\Core\Plugin\FilteredPluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of panel plugins.
 */
interface StoreDashboardPanelManagerInterface extends ContextAwarePluginManagerInterface, CategorizingPluginManagerInterface, FilteredPluginManagerInterface {

}
