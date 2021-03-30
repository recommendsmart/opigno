<?php

namespace Drupal\views_pretty_path\PathProcessor;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\PathProcessor\InBoundPathProcessorInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\views_pretty_path\FilterHandlers\ViewsPrettyPathFilterHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Rewrite Views URLs to be human-readable
 */
class ViewsPrettyPathProcessor implements InBoundPathProcessorInterface, OutboundPathProcessorInterface {

  const DEFAULT_FILTER_SUBPATH = '/filter';

  /**
   * Whether the outbound path has been changed at least once
   *
   * @var boolean
   */
  protected $changedOutboundOnce;

  /**
   * Whether the inbound path has been changed at least once
   *
   * @var boolean
   */
  protected $changedInboundOnce;

  /**
   * The inbound path of the first path that required rewritten
   *
   * @var string
   */
  protected $originalRewrittenInboundPath;

  /**
   * Alias manager service
   *
   * @var AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Entity type manager service
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database service
   *
   * @var Connection
   */
  protected $database;

  /**
   * Collection of filter handlers, keyed by targeted filter plugin ID
   *
   * @var array
   */
  protected $filterHandlers;

  /**
   * Maps paths to view names where URL rewriting should be enabled
   *
   * - Modules can use hook_views_paths_to_rewrite_alter() to override
   * - Keys: paths, values: view names
   *
   * @var array
   */
  protected $pathsViewsToRewrite;

  /**
   * Map that keys view field names to user-defined override names
   *
   * - Modules can use hook_views_field_name_map_alter() to override
   * - Increases user-friendliness of a URL
   * - e.g. ['field_topic_target_id' => 'topics']
   *
   * @var false|array
   */
  protected $viewsFieldNameMap = FALSE;

  /**
   * Form state of submitted form
   *
   * @var FormStateInterface|null
   */
  protected $formState;

  /**
   * Views Pretty Path config
   *
   * @var array
   */
  protected $config;

  /**
   * Filter subpath
   *
   * @var string
   */
  protected $filterSubpath;

  /**
   * Query parameters
   *
   * @var array
   */
  protected $queryParameters;

  /**
   * Original system path
   *
   * @var string
   */
  protected $originalSystemPath;

  /**
   * This was a 404
   *
   * @var boolean
   */
  protected $notFound;

  /**
   * Constructs ViewsPrettyPathProcessor
   *
   * @param AliasManagerInterface $AliasManager
   * @param EntityTypeManagerInterface $EntityTypeManager
   * @param Connection $Connection
   * @param RequestStack $RequestStack
   * @param ConfigFactoryInterface $ConfigFactory
   */
  public function __construct(AliasManagerInterface $AliasManager, EntityTypeManagerInterface $EntityTypeManager, Connection $Connection, RequestStack $RequestStack, ConfigFactoryInterface $ConfigFactory) {
    $this->aliasManager = $AliasManager;
    $this->entityTypeManager = $EntityTypeManager;
    $this->database = $Connection;
    $this->changedOutboundOnce = FALSE;
    $this->changedInboundOnce = FALSE;
    $this->currentRequest = $RequestStack->getCurrentRequest();
    $this->config = $ConfigFactory->get('views_pretty_path.config');
    $this->sysConfig = $ConfigFactory->get('system.site');
    $this->filterSubpath = '/' . ltrim($this->config->get('filter_subpath') ? $this->config->get('filter_subpath') : self::DEFAULT_FILTER_SUBPATH, '/');
    $this->pathsViewsToRewrite = $this->loadPathsViewsToRewrite();
  }

  /**
   * Add a filter handler to the filterHandlers property
   *
   * @param ViewsPrettyPathFilterHandlerInterface $filter_handler
   * @param integer $priority
   */
  public function addFilterHandler(ViewsPrettyPathFilterHandlerInterface $filter_handler, $priority = 0) {
    foreach ($filter_handler->getTargetedFilterPluginIds() as $plugin_id) {
      $this->filterHandlers[$plugin_id] = $filter_handler;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $new_path = $path;
    // Check if the path should be rewritten.
    // Ensure the filter subpath is in the path, and that the alias matches a system path.
    if (
      ($alias = $this->shouldPathBeRewritten($request->getRequestUri())) &&
      ($this->isFilterInPath($alias, $request)) &&
      (!$this->doViewsParametersExistByAlias($alias)) &&
      ($system_path_of_alias = $this->aliasManager->getPathByAlias($alias))
    ) {
      $this->setViewsFieldNameMap();
      if (!$this->changedInboundOnce) {
        $this->changedInboundOnce = TRUE;
        $this->originalRewrittenInboundPath = $path;
        $this->originalSystemPath = $system_path_of_alias;
        $this->replaceQueryParameters($alias, $request);
        $new_path = $system_path_of_alias;
      }
      elseif ($this->originalRewrittenInboundPath == urldecode(strtok($request->getRequestUri(), '?'))) {
        $this->replaceQueryParameters($alias, $request);
        $new_path = $system_path_of_alias;
      }
    }
    $this->handle404($new_path, $request);
    return $new_path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = null, BubbleableMetadata $bubbleable_metadata = null) {
    if ($this->shouldOutboundPathBeRewritten($request, $path)) {
      $this->setViewsFieldNameMap();
      $this->changedOutboundOnce = TRUE;
      return $this->sanitizeOutboundPath($request);
    }
    return $path;
  }

  /**
   * Handle the minority of cases in which the request 404s
   *
   * @param string $path
   * @param Request $request
   * @return void
   */
  protected function handle404(&$path, $request) {
    if (isset($this->originalSystemPath) && in_array($path, [$this->sysConfig->get('page')[404], '/system/404'])) {
      $path = $this->originalSystemPath;
      $this->notFound = TRUE;
    }
    if ($this->notFound) {
      $this->replaceQueryParameters('', $request);
    }
  }

  /**
   * Sets the viewsFieldNameMap property
   *
   * - Should be done early, but only after conditionals pass
   *
   */
  protected function setViewsFieldNameMap() {
    if ($this->viewsFieldNameMap === FALSE) {
      $this->viewsFieldNameMap = [];
      foreach (preg_split("/\r\n|\n|\r/", trim($this->config->get('views_filter_name_map'))) as $value) {
        list($k, $v) = explode('|', $value);
        $this->viewsFieldNameMap[$k] = $v;
      }
    }
  }

  /**
   * Load paths & views to rewrite from config
   *
   * @return array
   */
  protected function loadPathsViewsToRewrite() {
    $paths_views_to_rewrite = [];
    $paths = $this->config->get('paths') ? $this->config->get('paths') : [];
    foreach ($paths as $path) {
      $paths_views_to_rewrite[$path['path']] = $path;
    }
    return $paths_views_to_rewrite;
  }

  /**
   * Replace the query parameters needed by Views, based on URL path items
   *
   * @param string $alias
   * @param Request $request
   */
  protected function replaceQueryParameters($alias, $request) {
    // Extract parameters the URL.
    if (is_null($this->queryParameters)) {
      $this->queryParameters = $this->extractQueryParamsfromUrl($alias, $request->getRequestUri());
    }
    // Replace pager.
    if ($page = $request->query->get('page')) {
      $this->queryParameters['page'] = $page;
    }
    // Insert into request query parameters.
    $request->query->replace($this->queryParameters);
  }

  /**
   * Check if path needs to be rewritten according to config
   *
   * @param string $request_or_path
   * @return boolean
   */
  protected function shouldPathBeRewritten($path) {
    foreach (array_keys($this->pathsViewsToRewrite) as $path_to_rewrite) {
      // Return path to rewrite if the path with the path to test and/or the filter subpath.
      if (
        $path == $path_to_rewrite ||
        strtok($path, '?') == $path_to_rewrite ||
        strpos($path, $path_to_rewrite . $this->filterSubpath) === 0
      ) {
          return $path_to_rewrite;
      }
    }
    return FALSE;
  }

  /**
   * Is filter in path
   *
   * @param string $alias
   * @param Request $request
   * @return boolean
   */
  protected function isFilterInPath($alias, Request $request) {
    if (strpos($request->getRequestUri(), $alias . $this->filterSubpath) === 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Should URL rewriting happen for the outbound path
   *
   * @param Request $request
   * @param string $path
   * @return boolean
   */
  protected function shouldOutboundPathBeRewritten($request, $path) {
    if (
      $request // Must be a valid request object (sometimes null).
      && $this->shouldPathBeRewritten($path)
      && $this->shouldPathBeRewritten($request->getRequestUri()) // Both full Request URI and path must pass rewrite condition.
      && strpos($request->getRequestUri(), $path) !== FALSE // The path must be part of full request URI.
      && !$this->changedOutboundOnce // Only change outbound path once. The first time this runs is always the HTTP request itself.
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Extract parameters to inject into the request query string from the URL path
   *
   * @param string $alias_to_rewrite
   * @param string $path
   * @return array $extracted_query_params
   */
  protected function extractQueryParamsfromUrl($alias_to_rewrite, $path) {
    $path_items = $this->explodeTranslatePathItems($alias_to_rewrite, $path);
    $filters_data = $this->getExposedFiltersDataByAlias($alias_to_rewrite);
    // Convert path items to array of associative arrays.
    $associative_path_items = [];
    foreach ($path_items as $key => $path_item) {
      if ($key%2 == 0 && isset($path_items[$key+1]) && isset($filters_data[$path_item])) {
        $associative_path_items[$path_item] = $path_items[$key+1];
      }
    }
    // Transform raw filter values to values suitable for the request query parameters.
    $extracted_query_params = [];
    foreach ($filters_data as $filter_name => $filter_data) {
      $raw_value_string = isset($associative_path_items[$filter_name]) ? $associative_path_items[$filter_name] : '';
      $extracted_query_params[$filter_name] = $this->transformFilterValuesForQuery($raw_value_string, $filter_data);
    }
    return $extracted_query_params;
  }

  /**
   * Extract the path item names based on field name map
   *
   * @param string $alias_to_rewrite
   * @param string $path
   * @return array
   */
  protected function explodeTranslatePathItems($alias_to_rewrite, $path) {
    $path_items = explode('/', strtok(ltrim(str_replace($alias_to_rewrite . $this->filterSubpath, '', $path), '/'), '?'));
    foreach ($path_items as $key => $path_item) {
      if ($key%2 == 0 && isset($path_items[$key+1]) && in_array($path_item, $this->viewsFieldNameMap)) {
        $path_items[$key] = array_search($path_item, $this->viewsFieldNameMap);
      }
    }
    return $path_items;
  }

  /**
   * Get the data of exposed filters of a view by alias
   *
   * @param string $alias
   * @return array $exposed_filters
   *   Keyed by filter identifier, values are plugin_id
   */
  protected function getExposedFiltersDataByAlias($alias) {
    return $this->getExposedFiltersDataByView($this->pathsViewsToRewrite[$alias]);
  }

  /**
   * Get exposed data on filters by view
   *
   * - Each filter data item is keyed by 'vid' & 'plugin_id'
   *
   * @param string $view_id
   * @return array
   */
  protected function getExposedFiltersDataByView($paths_view_item) {
    $view = $this->entityTypeManager->getStorage('view')->load($paths_view_item['view']);
    $filters = $view->getDisplay($paths_view_item['display'])['display_options']['filters'];
    $default_filters = $view->getDisplay('default')['display_options']['filters'];
    $filters = array_merge($default_filters, $filters ? $filters : []);
    $exposed_filters = [];
    foreach ($filters as $filter) {
      if (isset($filter['exposed']) && $filter['exposed']) {
        $exposed_filters[$filter['expose']['identifier']] = [
          'plugin_id' => $filter['plugin_id'],
        ];
        if (isset($filter['vid'])) {
          $exposed_filters[$filter['expose']['identifier']]['vid'] = $filter['vid'];
        }
      }
    }
    return $exposed_filters;
  }

  /**
   * Sanitize initial path of request object
   *
   * - Replaces + signs w/ ' ' because Drupal redirects infinitely
   *   if + signs are in the path.
   *
   * @param Request $request
   * @return string
   */
  protected function sanitizeOutboundPath($request) {
    // Using urldecode() prevents infinite redirects.
    // Prevent two '/' characters in front of the path.
    return '/' . ltrim(urldecode(strtok(str_replace('+', ' ', $request->getRequestUri()), '?')), '/');
  }

  /**
   * Convert the filter value string into value format that can be injected into
   * the request query parameters for Views filtering
   *
   * @param string $filter_value_string
   * @param array $filter_data
   *   Requires a 'plugin_id' array key.
   * @return array|string
   */
  protected function transformFilterValuesForQuery($filter_value_string, $filter_data) {
    if (array_key_exists($filter_data['plugin_id'], $this->filterHandlers)) {
      return $this->filterHandlers[$filter_data['plugin_id']]->transformPathValueForViewsQuery($filter_value_string, $filter_data);
    }
  }

  /**
   * Modify the query params in the pager URLs for rewritten views
   *
   * @param array $variables
   *   Drupal render array for the Views pager
   */
  public function preProcessPager(&$variables) {
    if ($this->shouldPathBeRewritten($this->currentRequest->getRequestUri())) {
      $pager_item_names = [
        'first',
        'previous',
        'pages',
        'next',
        'last',
      ];
      foreach ($pager_item_names as $name) {
        if (!empty($variables['items'][$name])) {
          $this->preProcessPagerItem($variables['items'][$name]);
          if ($name == 'pages') {
            foreach ($variables['items']['pages'] as &$page) {
              $this->preProcessPagerItem($page);
            }
          }
        }
      }
    }
  }

  /**
   * Modify the query params in the URL of a single pager item
   *
   * @param array $pager_item
   */
  protected function preProcessPagerItem(&$pager_item) {
    if (!empty($pager_item['href'])) {
      parse_str($pager_item['href'], $query_params);
      if (isset($query_params['page'])) {
        $pager_item['href'] = '?page=' . $query_params['page'];
      }
    }
  }

  /**
   * Handle views exposed form submit
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function handleViewsExposedFormSubmit($form, $form_state) {
    // Check if it's been submitted.
    if (
      ($alias = $this->shouldPathBeRewritten($this->currentRequest->getRequestUri())) &&
      ($this->doViewsParametersExistByAlias($alias, $form_state->get('view')->id()))
    ) {
      $this->formState = $form_state;
      $redirect_response = $this->translateSubmittedValuesIntoRewrittenRedirect($alias);
      $redirect_response->send();
      exit(); // Required to prevent infinite redirects.
    }
  }
  /**
   * Should the combination of an alias and a view be rewritten, and do the
   * correct views parameters exist
   *
   * - Controls for multiple views on a page, with different action URLs
   *
   * @param string $alias
   * @param string $view_id
   * @return boolean
   */
  protected function doViewsParametersExistByAlias($alias, $view_id = NULL) {
    if ($this->pathsViewsToRewrite[$alias]['view'] === $view_id || $view_id === NULL) {
      if(is_null($view_id)) {
        $view_id = $this->pathsViewsToRewrite[$alias]['view'];
      }
      // Check to ensure that the query string has a
      $filter_names = array_keys($this->getExposedFiltersDataByView($this->pathsViewsToRewrite[$alias]));
      $query_parameter_names = array_keys($_GET);
      $views_parameters_in_query = array_intersect($filter_names, $query_parameter_names);
      return count($views_parameters_in_query) > 0 ? TRUE : FALSE;
    }
    return FALSE;
  }
  /**
   * Translate the submitted values into the rewritten URL
   *
   * @param string $alias
   * @return TrustedRedirectResponse
   */
  protected function translateSubmittedValuesIntoRewrittenRedirect($alias) {
    // Get values from URL parts instead of form state, since they often are not there.
    $url_parts = UrlHelper::parse($this->currentRequest->getRequestUri());
    if (!empty($url_parts['query'])) {
      $raw_values = $url_parts['query'];
      $submitted_values = [];
      // Get filter config data.
      $filter_data = $this->getExposedFiltersDataByView($this->pathsViewsToRewrite[$alias]);
      foreach ($filter_data as $filter_name => $filter) {
        if (empty($raw_values[$filter_name])) {
          continue;
        }
        $submitted_values[$filter_name] = [
          'plugin_id' => $filter['plugin_id'],
        ];
        $submitted_values[$filter_name]['value'] = $raw_values[$filter_name];
      }
      $path_string = '';
      // Arrange submitted values keys alphabetically.
      ksort($submitted_values);
      foreach ($submitted_values as $field_name => $field) {
        $string_prefix = '/' . $this->translateSubmitFieldName($field_name) . '/';
        if (
          (array_key_exists($field['plugin_id'], $this->filterHandlers)) &&
          ($value_for_url = $this->filterHandlers[$field['plugin_id']]->transformSubmittedValueForUrl($field['value']))
        ) {
          $path_string .= $string_prefix . $value_for_url;
        }
      }
      $redirect_path = empty($path_string) ? $alias : $alias . $this->filterSubpath . $path_string;
    }
    $base_url = Url::fromUri('base:' . '/' , ['absolute' => TRUE]);
    return new TrustedRedirectResponse(rtrim($base_url->toString(), '/') . '/' .  ltrim($redirect_path, '/'));
  }

  /**
   * Translate the name of a submitted field in case there is an override
   *
   * @param string $field_name
   * @return string
   */
  protected function translateSubmitFieldName($field_name) {
    if (isset($this->viewsFieldNameMap[$field_name])) {
      return $this->viewsFieldNameMap[$field_name];
    }
    return $field_name;
  }

}
