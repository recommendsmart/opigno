<?php

namespace Drupal\digital_signage_framework\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\digital_signage_framework\Emergency;
use Drupal\digital_signage_framework\DeviceInterface;
use Drupal\digital_signage_framework\DigitalSignageFrameworkEvents;
use Drupal\digital_signage_framework\Event\Libraries;
use Drupal\digital_signage_framework\Event\Overlays;
use Drupal\digital_signage_framework\Event\Rendered;
use Drupal\digital_signage_framework\Event\Underlays;
use Drupal\digital_signage_framework\Renderer;
use Drupal\digital_signage_framework\Entity\Device;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use function Jawira\PlantUml\encodep;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the API controller.
 */
class Api implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\digital_signage_framework\DeviceInterface
   */
  protected $device;

  /**
   * @var \Drupal\digital_signage_framework\ScheduleInterface
   */
  protected $schedule;

  /**
   * @var \Drupal\digital_signage_framework\PlatformInterface
   */
  protected $platform;

  /**
   * @var \Drupal\digital_signage_framework\Renderer
   */
  protected $dsRenderer;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\digital_signage_framework\Emergency
   */
  protected $emergency;

  /**
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
  protected $assetResolver;

  /**
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $jsAssetCollectionRenderer;

  /**
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $cssAssetCollectionRenderer;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var string
   */
  protected $theme;

  /**
   * Api constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\digital_signage_framework\Renderer $ds_renderer
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\digital_signage_framework\Emergency $emergency
   * @param \Drupal\Core\Asset\AssetResolverInterface $asset_resolver
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $js_asset_collection_renderer
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $css_asset_collection_renderer
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, LibraryDiscoveryInterface $library_discovery, RequestStack $request_stack, Renderer $ds_renderer, EventDispatcherInterface $event_dispatcher, ClientFactory $client_factory, RendererInterface $renderer, AccountProxyInterface $current_user, Emergency $emergency, AssetResolverInterface $asset_resolver, AssetCollectionRendererInterface $js_asset_collection_renderer, AssetCollectionRendererInterface $css_asset_collection_renderer, LanguageManagerInterface $language_manager) {
    $this->config = $config_factory->get('digital_signage_framework.settings');
    $this->theme = $config_factory->get('system.theme')->get('default');
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->libraryDiscovery = $library_discovery;
    $this->request = $request_stack->getCurrentRequest();
    $this->dsRenderer = $ds_renderer;
    $this->eventDispatcher = $event_dispatcher;
    $this->clientFactory = $client_factory;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
    $this->emergency = $emergency;

    // We need to load the device first, otherwise we can't verify fingerprint.
    if ($deviceId = $this->request->query->get('deviceId')) {
      $this->device = Device::load($deviceId);
    }
    if (!$current_user->hasPermission('digital signage framework access preview') && $this->request->headers->get('x-digsig-fingerprint') === NULL) {
      return;
    }

    if ($this->device) {
      $this->platform = $this->device->getPlugin();
      $stored = $this->request->query->get('storedSchedule', 'true') === 'true';
      $this->schedule = $this->device->getSchedule($stored);
    }
    $this->assetResolver = $asset_resolver;
    $this->jsAssetCollectionRenderer = $js_asset_collection_renderer;
    $this->cssAssetCollectionRenderer = $css_asset_collection_renderer;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Api {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('library.discovery'),
      $container->get('request_stack'),
      $container->get('digital_signage_framework.renderer'),
      $container->get('event_dispatcher'),
      $container->get('http_client_factory'),
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('digital_signage_content_setting.emergency'),
      $container->get('asset.resolver'),
      $container->get('asset.js.collection_renderer'),
      $container->get('asset.css.collection_renderer'),
      $container->get('language_manager')
    );
  }

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   *
   * @return string
   */
  public static function fingerprint(DeviceInterface $device): string {
    return Crypt::hmacBase64($device->extId(), Settings::getHashSalt() . $device->id());
  }

  /**
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(): AccessResult {
    if (empty($this->device)) {
      return AccessResult::forbidden('Missing or broken device definition.');
    }
    if (!$this->currentUser->hasPermission('digital signage framework access preview') && $this->request->headers->get('x-digsig-fingerprint') !== self::fingerprint($this->device)) {
      return AccessResult::forbidden('Wrong fingerprint.');
    }

    switch ($this->request->query->get('mode')) {
      case 'load':
      /** @noinspection PhpMissingBreakStatementInspection */
      case 'preview':
        switch ($this->request->query->get('type', 'html')) {
          case 'css':
            break;

          case 'content':
            $contentPath = $this->request->query->get('contentPath');
            if (empty($contentPath)) {
              return AccessResult::forbidden('Missing content path.');
            }
            break;

          default:
            $entityType = $this->request->query->get('entityType');
            $entityId = (int) $this->request->query->get('entityId');
            if (empty($entityType) || empty($entityId)) {
              return AccessResult::forbidden('Missing entity definition.');
            }
        }
        // Intentionally missing break statement.

      case 'schedule':
      case 'diagram':
      case 'screenshot':
      case 'log':
        if (empty($this->schedule)) {
          return AccessResult::forbidden('Device has no schedule.');
        }
        if (isset($entityType, $entityId)) {
          foreach ($this->schedule->getItems() as $item) {
            if ($item['entity']['type'] === $entityType && $item['entity']['id'] === $entityId) {
              return AccessResult::allowed();
            }
          }
          foreach ($this->emergency->all() as $item) {
            if ($item['entity']['type'] === $entityType && $item['entity']['id'] === $entityId) {
              return AccessResult::allowed();
            }
          }
          return AccessResult::forbidden('Requested entity is not published on this device.');
        }
        return AccessResult::allowed();

      default:
        return AccessResult::forbidden('Missing or forbidden mode.');
    }
  }

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function request(): Response {
    switch ($this->request->query->get('mode')) {
      case 'diagram':
        $response = $this->diagram();
        break;

      case 'preview':
        if ($this->request->query->get('type', 'html') === 'html') {
          $response = $this->preview();
        }
        else {
          $response = $this->previewBinary();
        }
        break;

      case 'load':
        switch ($this->request->query->get('type', 'html')) {
          case 'html':
            $response = $this->load();
            break;

          case 'css':
            $response = $this->loadCSS();
            break;

          case 'content':
            $response = $this->loadContent();
            break;

          default:
            $response = $this->loadBinary();

        }
        break;

      case 'schedule':
        $response = $this->getSchedule();
        break;

      case 'screenshot':
        $response = $this->screenshot();
        break;

      case 'log':
        $response = $this->log();
        break;

      default:
        // This will never happen as we checked this in the above access callback.
        $response = new AjaxResponse();
    }

    $event = new Rendered($response, $this->device);
    $this->eventDispatcher->dispatch($event, DigitalSignageFrameworkEvents::RENDERED);

    return $event->getResponse();
  }

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function screenshot(): AjaxResponse {
    if ($screenshot = $this->device->getPlugin()->getScreenshot($this->device, (bool) $this->request->query->get('refresh'))) {
      $content = '<img alt="screenshot" src="' . $screenshot['uri'] . '" /><div class="screenshot-widget">' . $screenshot['takenAt'] . '</div>';
    }
    else {
      $content = '<p>' . $this->t('No screenshot available.') . '</p>';
    }
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#digital-signage-preview .popup > .content-wrapper > .content', $content));
    return $response;
  }

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function log(): AjaxResponse {
    if ($this->request->query->get('type', 'debug') === 'error') {
      $logs = $this->device->getPlugin()->showErrorLog($this->device);
    }
    else {
      $logs = $this->device->getPlugin()->showDebugLog($this->device);
    }
    $rows = [];
    foreach ($logs as $item) {
      $rows[] = [
        'time' => $item['time'],
        'payload' => is_scalar($item['payload']) ? $item['payload'] : json_encode($item['payload'], JSON_PRETTY_PRINT),
      ];
    }
    $log = [
      '#type' => 'table',
      '#header' => [
        'time' => $this->t('Time'),
        'payload' => $this->t('Message'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
      '#prefix' => '<div class="table-wrapper pre">',
      '#suffix' => '</div>',
    ];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#digital-signage-preview .popup > .content-wrapper > .content', $log));
    return $response;
  }

  /**
   * @param array $libraries
   * @param array $settings
   * @param array $items
   *
   * @return array
   */
  private function prepareItems(array &$libraries, array &$settings, array $items): array {
    $content = [];
    foreach ($items as $key => $item) {
      if ($item['type'] === 'html') {
        $uid = $item['entity']['type'] . '_' . $item['entity']['id'];
        if (!isset($content[$uid])) {
          $elements = [
            '#theme' => 'digital_signage_framework',
            '#content' => $this->dsRenderer->buildEntityView(
              $item['entity']['type'],
              $item['entity']['id'],
              $this->device
            ),
            '#full_html' => FALSE,
          ];
          $content[$uid] = $this->dsRenderer->renderPlain($elements);
          $htmlHead = $this->prepareHtmlHead($elements);
          if (!empty($htmlHead)) {
            $content[$uid] .= $this->renderer->renderPlain($htmlHead);
          }
          if (!empty($elements['#attached']['library'])) {
            foreach ($elements['#attached']['library'] as $library) {
              $libraries[] = $library;
            }
          }
          if (!empty($elements['#attached']['drupalSettings'])) {
            foreach ($elements['#attached']['drupalSettings'] as $settingKey => $value) {
              $settings[$settingKey] = $value;
            }
          }
        }
        $items[$key]['content'] = $content[$uid];
      }
    }
    return $items;
  }

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  private function getSchedule(): JsonResponse {
    $underlays = new Underlays($this->device);
    $this->eventDispatcher->dispatch($underlays, DigitalSignageFrameworkEvents::UNDERLAYS);
    $overlays = new Overlays($this->device);
    $this->eventDispatcher->dispatch($overlays, DigitalSignageFrameworkEvents::OVERLAYS);

    $event = new Libraries($this->device);
    $this->eventDispatcher->dispatch($event, DigitalSignageFrameworkEvents::LIBRARIES);
    $libraries = $event->getLibraries();
    $libraries[] = 'digital_signage_framework/schedule.content';
    $libraries[] = 'digital_signage_framework/schedule.timer';
    foreach ($overlays->getLibraries() as $library) {
      $libraries[] = $library;
    }
    foreach ($underlays->getLibraries() as $library) {
      $libraries[] = $library;
    }
    $settings = $event->getSettings();
    $response = new JsonResponse();
    $data = [
      'schedule' => $this->prepareItems($libraries, $settings, $this->schedule->getItems()),
      'underlays' => $underlays->getUnderlays(),
      'overlays' => $overlays->getOverlays(),
      'emergencyentities' => $this->prepareItems($libraries, $settings, $this->emergency->all()),
    ];
    $data['assets'] = $this->renderAssets($libraries, $settings, FALSE);
    $response->setData($data);
    return $response;
  }

  /**
   * @return array
   */
  private function buildEntityView(): array {
    return $this->dsRenderer->buildEntityView(
      $this->request->query->get('entityType'),
      $this->request->query->get('entityId'),
      $this->device
    );
  }

  /**
   * @return \Drupal\Core\Render\AttachmentsInterface
   */
  private function load(): AttachmentsInterface {
    $output = [
      '#theme' => 'digital_signage_framework',
      '#content' => $this->buildEntityView(),
    ];
    return $this->dsRenderer->buildHtmlResponse($output);
  }

  /**
   * @return string
   */
  private function getFileUri(): string {
    /** @var \Drupal\media\MediaInterface $media */
    /** @var \Drupal\file\FileInterface $file */
    if (($media = Media::load($this->request->query->get('entityId'))) && $file = File::load($media->getSource()
        ->getSourceFieldValue($media))) {
      $file_uri = $file->getFileUri();
      try {
        /** @var \Drupal\image\ImageStyleInterface $image_style */
        if (($media->bundle() === 'image') && $image_style = $this->entityTypeManager->getStorage('image_style')
            ->load('digital_signage_' . $this->device->getOrientation())) {
          $derivative_uri = $image_style->buildUri($file_uri);
          if (file_exists($derivative_uri) || $image_style->createDerivative($file_uri, $derivative_uri)) {
            $file_uri = $derivative_uri;
          }
        }
      } catch (InvalidPluginDefinitionException $e) {
      } catch (PluginNotFoundException $e) {
      }
      return $file_uri;
    }
    return DRUPAL_ROOT . theme_get_setting('logo.url', $this->theme);
  }

  /**
   * @param array $elements
   *
   * @return array
   */
  private function prepareHtmlHead(array $elements): array {
    if (!isset($elements['#attached']['html_head'])) {
      return [];
    }
    $result = [];
    foreach ($elements['#attached']['html_head'] as $item) {
      if (is_array($item)) {
        foreach ($item as $value) {
          if (is_array($value) && isset($value['#tag']) && $value['#tag'] === 'style') {
            $value['#type'] = 'html_tag';
            $result[] = $value;
          }
        }
      }
    }
    return $result;
  }

  /**
   * @param array $libraries
   * @param array $settings
   * @param bool $inlineCSS
   * @param array $htmlHead
   *
   * @return string
   */
  private function renderAssets(array $libraries, array $settings, bool $inlineCSS, array $htmlHead = []): string {
    $assets = new AttachedAssets();
    $assets->setLibraries($libraries);
    $assets->setSettings($settings);
    [$js_assets_header, $js_assets_footer] = $this->assetResolver->getJsAssets($assets, TRUE);
    $css_assets = $this->assetResolver->getCssAssets($assets, TRUE);
    if ($inlineCSS) {
      $fonts = Yaml::decode($this->config->get('fonts')) ?? [];
      $fontCSS = '';
      foreach ($fonts as $font) {
        if ($font['enabled']) {
          $fontCSS .= '@font-face {font-family: "' . $font['family'] . '";';
          $fontCSS .= 'font-weight:' . $font['weight'] . ';';
          $fontCSS .= 'font-style:' . $font['style'] . ';';
          $fontCSS .= 'src:';
          $firstFont = TRUE;
          foreach ($font['formats'] as $format => $url) {
            if (!$firstFont) {
              $fontCSS .= ',';
            }
            else {
              $firstFont = FALSE;
            }
            $fontCSS .= 'url("' . $url . '") format("' . $format . '")';
          }
          $fontCSS .= ';}';
        }
      }
      $css = '<style>' . $fontCSS . $this->prepareCSS($css_assets) . '</style>';
    }
    else {
      $cssAsset = $this->cssAssetCollectionRenderer->render($css_assets);
      $css = $this->renderer->renderPlain($cssAsset);
    }
    $assets_header = $this->jsAssetCollectionRenderer->render($js_assets_header);
    $assets_footer = $this->jsAssetCollectionRenderer->render($js_assets_footer);
    foreach ($htmlHead as $item) {
      $assets_header[] = $item;
    }
    return $css . $this->renderer->renderPlain($assets_header) . $this->renderer->renderPlain($assets_footer);
  }

  /**
   * @param array $build
   *
   * @return string
   */
  private function prepareCSSJS(array $build): string {
    $event = new Libraries($this->device);
    $event->addLibrary('digital_signage_framework/schedule.preview-iframe');
    $event->addLibrary('digital_signage_framework/schedule.timer');

    if (!empty($build['#attached']['library'])) {
      foreach ($build['#attached']['library'] as $library) {
        $event->addLibrary($library);
      }
    }
    if (!empty($build['#attached']['drupalSettings'])) {
      foreach ($build['#attached']['drupalSettings'] as $key => $value) {
        $event->addSettings($key, (array) $value);
      }
    }

    $this->eventDispatcher->dispatch($event, DigitalSignageFrameworkEvents::LIBRARIES);
    return $this->renderAssets($event->getLibraries(), $event->getSettings(), TRUE, $this->prepareHtmlHead($build));
  }

  /**
   * @param array $files
   *
   * @return string
   */
  private function prepareCSS($files = []): string {
    $cssFiles = [];
    foreach ($files as $file) {
      $cssFiles[] = $file['data'];
    }
    if ($this->moduleHandler->moduleExists('layout_builder')) {
      foreach ($this->libraryDiscovery->getLibrariesByExtension('layout_builder') as $library) {
        foreach ($library['css'] as $css) {
          $cssFiles[] = $css['data'];
        }
      }
    }
    $cssFiles[] = drupal_get_path('module', 'digital_signage_framework') . '/css/digital-signage.css';
    $cssFiles[] = drupal_get_path('module', 'digital_signage_framework') . '/css/overlays.css';
    foreach (explode(PHP_EOL, str_replace("\r", '', $this->config->get('css'))) as $file) {
      $cssFiles[] = $file;
    }

    $css = '';
    foreach ($cssFiles as $cssFile) {
      if (!empty($cssFile) && file_exists($cssFile)) {
        $css .= file_get_contents($cssFile) . PHP_EOL;
      }
    }
    return $css;
  }

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   */
  private function loadCSS(): Response {
    $css = $this->prepareCSS();
    $headers = [
      'Content-Type' => 'text/css',
    ];
    return new Response($css, 200, $headers);
  }

  /**
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  private function loadContent(): BinaryFileResponse {
    $content_path = Url::fromUserInput('/', [
      'absolute' => TRUE,
      'language' => $this->languageManager->getLanguage(LanguageInterface::LANGCODE_NOT_SPECIFIED),
    ])->toString() . substr(base64_decode($this->request->query->get('contentPath')), 1);
    $file_uri = 'temporary://content-' . hash('md5', $content_path);
    try {
      $client = $this->clientFactory->fromOptions(['base_uri' => $content_path]);
      $options = [];
      if ($authorization = $this->request->headers->get('authorization')) {
        $options[RequestOptions::HEADERS]['Authorization'] = is_array($authorization) ?
          reset($authorization) :
          $authorization;
      }
      $response = $client->request('get', NULL, $options);
      $content = $response->getBody()->getContents();
      if ($this->config->get('hotfix_svg')) {
        /** @noinspection SubStrUsedAsStrPosInspection */
        if (substr($content, 0, 4) === '<svg') {
          $content = '<?xml version="1.0" standalone="no"?>' . $content;
        }
      }
      file_put_contents($file_uri, $content);
    } catch (GuzzleException $e) {
      file_put_contents($file_uri, '');
    }

    $headers = [
      'Content-Type' => mime_content_type($file_uri),
    ];
    return new BinaryFileResponse($file_uri, 200, $headers);
  }

  /**
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  private function loadBinary(): BinaryFileResponse {
    $file_uri = $this->getFileUri();
    $headers = [
      'Content-Type' => mime_content_type($file_uri),
    ];
    return new BinaryFileResponse($file_uri, 200, $headers);
  }

  /**
   * @param array|string $output
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  private function popupContent($output): AjaxResponse {
    $underlays = new Underlays($this->device);
    $this->eventDispatcher->dispatch($underlays, DigitalSignageFrameworkEvents::UNDERLAYS);
    $overlays = new Overlays($this->device);
    $this->eventDispatcher->dispatch($overlays, DigitalSignageFrameworkEvents::OVERLAYS);

    $content = is_array($output) ? $this->renderer->renderRoot($output) : $output;
    /** @var array $build */
    $build = is_array($output) ? $output : [];
    foreach ($overlays->getLibraries() as $library) {
      $build['#attached']['library'][] = $library;
    }
    foreach ($underlays->getLibraries() as $library) {
      $build['#attached']['library'][] = $library;
    }

    $origin = Url::fromUserInput('/', [
      'absolute' => TRUE,
      'language' => $this->languageManager->getLanguage(LanguageInterface::LANGCODE_NOT_SPECIFIED),
    ])->toString();
    $build['#attached']['drupalSettings']['digital_signage_preview_iframe'] = [
      'origin' => $origin,
    ];
    $cssjs = $this->prepareCSSJS($build);
    $content = '<div id="underlays">' . implode('', $underlays->getUnderlays()) . '</div>' . $content . '<div id="overlays">' . implode('', $overlays->getOverlays()) . '</div>';
    $content = '<iframe srcdoc="' . htmlspecialchars($cssjs . $content) . '" src="' . $origin . '" width="' . $this->device->getWidth() . '" height="' . $this->device->getHeight() . '"></iframe>';

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#digital-signage-preview .popup > .content-wrapper > .content', $content));
    return $response;
  }

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  private function preview(): AjaxResponse {
    return $this->popupContent($this->buildEntityView());
  }

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  private function previewBinary(): AjaxResponse {
    $file_uri = file_create_url($this->getFileUri());
    switch ($this->request->query->get('type')) {
      case 'image':
        $output = '<img src="' . $file_uri . '" alt="" />';
        break;

      case 'video':
        $output = '<video src="' . $file_uri . '" autoplay="autoplay"></video>';
        break;

      default:
        $output = 'Problem loading media';
    }
    return $this->popupContent($output);
  }

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  private function diagram(): AjaxResponse {
    $items = $this->schedule->getItems();
    switch ($this->request->query->get('umlType')) {
      case 'activity':
        $uml = $this->umlActivity($items);
        break;

      case 'sequence':
      default:
        $uml = $this->umlSequence($items);
    }

    try {
      $client = $this->clientFactory->fromOptions(['base_uri' => $this->config->get('plantuml_url') . '/svg/' . encodep($uml)]);
      $response = $client->request('get', NULL);
      $output = '<div class="uml-diagram">' . $response->getBody()->getContents() . '</div>';
    }
    catch (GuzzleException $e) {
      $output = '';
    }
    catch (Exception $e) {
      $output = 'Problem encoding UML: <br><pre>' . $uml . '</pre>';
    }
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#digital-signage-preview .popup > .content-wrapper > .content', $output));
    return $response;
  }

  /**
   * @param string $type
   * @param int $id
   *
   * @return string
   */
  private function getEntityLabel(string $type, int $id): string {
    try {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->entityTypeManager->getStorage($type)->load($id);
      if (($entity !== NULL) && $label = $entity->label()) {
        return $label;
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // Can be ignored.
    }
    return 'N/A';
  }

  /**
   * @param array $items
   *
   * @return string
   */
  private function umlActivity(array $items): string {
    $uml = '@startuml' . PHP_EOL . 'while (Loop)' . PHP_EOL;
    foreach ($items as $item) {
      $uml .= strtr(':**%label**%EOL%type: **%entityType/%entityId**%EOL%duration seconds%dynamic;%EOL', [
        '%EOL' => PHP_EOL,
        '%label' => $this->getEntityLabel($item['entity']['type'], $item['entity']['id']),
        '%type' => $item['type'],
        '%entityType' => $item['entity']['type'],
        '%entityId' => $item['entity']['id'],
        '%duration' => $item['duration'],
        '%dynamic' => $item['dynamic'] ? '/dynamic' : '',
      ]);
    }
    $uml .= 'endwhile' . PHP_EOL . 'stop' . PHP_EOL . '@enduml';
    return $uml;
  }

  /**
   * @param array $items
   *
   * @return string
   */
  private function umlSequence(array $items): string {
    $uml = '@startuml' . PHP_EOL;
    foreach ($items as $item) {
      $uml .= strtr('participant "%label" as %id << %type: %path >>%EOL', [
        '%EOL' => PHP_EOL,
        '%label' => $this->getEntityLabel($item['entity']['type'], $item['entity']['id']),
        '%type' => $item['type'],
        '%path' => $item['entity']['type'] . '/' . $item['entity']['id'],
        '%id' => $item['entity']['type'] . '_' . $item['entity']['id'],
      ]);
    }
    $previous = FALSE;
    foreach ($items as $item) {
      $current = $item['entity']['type'] . '_' . $item['entity']['id'];
      if ($previous) {
        $uml .= strtr('%previous --> %id: %duration seconds%EOL', [
          '%EOL' => PHP_EOL,
          '%previous' => $previous,
          '%id' => $current,
          '%duration' => $item['duration'],
        ]);
      }
      $previous = $current;
    }
    $uml .= '@enduml';
    return $uml;
  }

}
