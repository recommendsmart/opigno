<?php

namespace Drupal\arch_cart\Plugin\Block;

use Drupal\arch_cart\Cart\CartHandlerInterface;
use Drupal\arch_product\Entity\ProductTypeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Commerce Mini Cart' block.
 *
 * @Block(
 *   id = "arch_cart_mini_cart",
 *   admin_label = @Translation("Mini cart", context = "arch_cart")
 * )
 */
class MiniCartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Image style storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Product type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * Cart.
   *
   * @var \Drupal\arch_cart\Cart\CartInterface
   */
  protected $cart;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Image style options.
   *
   * @var string[][]
   */
  protected $imageStyleOptions;

  /**
   * Bundle settings.
   *
   * @var array
   */
  protected $bundleInfo = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    CartHandlerInterface $cart_handler,
    RouteMatchInterface $route_match,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->imageStyleStorage = $entity_type_manager->getStorage('image_style');
    $this->productTypeStorage = $entity_type_manager->getStorage('product_type');
    $this->cart = $cart_handler->getCart();
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('arch_cart_handler'),
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable in https://www.drupal.org/node/2483181
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'show_cart_item_count' => TRUE,
      'click_event' => 'open',
      'allow_modify_quantity' => TRUE,
      'allow_remove' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $form['show_cart_item_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show cart item count', [], ['context' => 'arch_cart_minicart_config']),
      '#default_value' => $config['show_cart_item_count'],
    ];
    $form['click_event'] = [
      '#type' => 'select',
      '#title' => $this->t('When user clicks on cart icon', [], ['context' => 'arch_cart_minicart_config']),
      '#default_value' => $config['click_event'],
      '#options' => [
        'open' => $this->t('Open mini cart block', [], ['context' => 'arch_cart_minicart_config']),
        'link' => $this->t('Go to cart page', [], ['context' => 'arch_cart_minicart_config']),
      ],
    ];

    $form['allow_modify_quantity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow modify quantities', [], ['context' => 'arch_cart_minicart_config']),
      '#default_value' => $config['allow_modify_quantity'],
      '#states' => [
        'visible' => [
          ':input[name="settings[click_event]"]' => ['value' => 'open'],
        ],
      ],
    ];

    $form['allow_remove'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow removing items', [], ['context' => 'arch_cart_minicart_config']),
      '#default_value' => $config['allow_remove'],
      '#states' => [
        'visible' => [
          ':input[name="settings[click_event]"]' => ['value' => 'open'],
        ],
      ],
    ];

    $form['bundle_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Bundle settings'),
      '#access' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="settings[click_event]"]' => ['value' => 'open'],
        ],
      ],
    ];
    foreach ($this->getProductTypes() as $product_type) {
      $form['product_type_' . $product_type->id()] = [
        '#type' => 'details',
        '#access' => FALSE,
        '#title' => $product_type->label(),
        '#group' => 'bundle_settings',
        '#states' => [
          'visible' => [
            ':input[name="settings[click_event]"]' => ['value' => 'open'],
          ],
        ],
      ];
    }

    foreach ($this->getProductTypesWithImageField() as $bundle) {
      $image_sources = $this->getImageSourcesForProductType($bundle);
      if (empty($image_sources)) {
        continue;
      }

      $form['bundle_settings']['#access'] = TRUE;
      $form['product_type_' . $bundle->id()]['#access'] = TRUE;
      $source_options = [];
      foreach ($image_sources as $image_source) {
        $source_options[$image_source['field']] = $image_source['label'];
      }
      $form['product_type_' . $bundle->id()]['image_source'] = [
        '#type' => 'select',
        '#title' => $this->t('Image source', [], ['context' => 'arch_cart_minicart_config']),
        '#default_value' => !empty($config['bundle_settings'][$bundle->id()]['image_source']) ? $config['bundle_settings'][$bundle->id()]['image_source'] : '',
        '#options' => $source_options,
      ];

      $form['product_type_' . $bundle->id()]['image_style'] = [
        '#type' => 'select',
        '#title' => $this->t('Image style', [], ['context' => 'arch_cart_minicart_config']),
        '#default_value' => !empty($config['bundle_settings'][$bundle->id()]['image_style']) ? $config['bundle_settings'][$bundle->id()]['image_style'] : '',
        '#options' => $this->getImageStyleOptions(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['show_cart_item_count'] = (bool) $form_state->getValue('show_cart_item_count');
    $this->configuration['click_event'] = $form_state->getValue('click_event');
    if ($this->configuration['click_event'] == 'open') {
      $this->configuration['allow_modify_quantity'] = (bool) $form_state->getValue('allow_modify_quantity');
      $this->configuration['allow_remove'] = (bool) $form_state->getValue('allow_remove');

      foreach ($this->getProductTypes() as $bundle) {
        $this->configuration['bundle_settings'][$bundle->id()] = [
          'image_source' => $form_state->getValue([
            'product_type_' . $bundle->id(),
            'image_source',
          ]),
          'image_style' => $form_state->getValue([
            'product_type_' . $bundle->id(),
            'image_style',
          ]),
        ];
      }
    }
    else {
      $this->configuration['allow_modify_quantity'] = FALSE;
      $this->configuration['allow_remove'] = FALSE;
      $this->configuration['bundle_settings'] = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = new Url('arch_cart.content');

    $link_attributes = [
      'class' => [
        'cart--icon',
        'api-cart--link',
      ],
      'data-count' => $this->cart->getCount(),
    ];

    $settings = [
      'theme' => $this->themeManager->getActiveTheme()->getName(),
      'show_cart_item_count' => $this->configuration['show_cart_item_count'],
      'click_event' => $this->configuration['click_event'],
      'allow_modify_quantity' => $this->configuration['allow_modify_quantity'],
      'allow_remove' => $this->configuration['allow_remove'],
      'bundle_settings' => isset($this->configuration['bundle_settings']) ? $this->configuration['bundle_settings'] : [],
    ];

    if (!$this->allowMiniCartDisplay()) {
      $settings['allow_modify_quantity'] = FALSE;
      $settings['allow_remove'] = FALSE;

      $link_attributes['class'][] = 'api-cart--display-disabled';
      $link_attributes['data-api-cart-disabled'] = 'disabled';
    }

    $build = [
      '#cache' => [
        'context' => [
          'user',
          'session',
          'route.name',
        ],
      ],
      '#theme' => 'mini_cart',
      '#settings' => $settings,
      '#url' => $url,
      '#text' => $this->t('Cart', [], ['context' => 'arch_cart']),
      '#attributes' => [
        'class' => ['mini-cart'],
      ],
      '#count' => $this->cart->getCount(),
      '#link_attributes' => $link_attributes,
      '#templates' => [
        '#theme' => 'api_cart_template',
      ],
    ];

    return $build;
  }

  /**
   * Get list of product types.
   *
   * @return \Drupal\arch_product\Entity\ProductTypeInterface[]
   *   Product type list.
   */
  protected function getProductTypes() {
    /** @var \Drupal\arch_product\Entity\ProductTypeInterface[] $product_types */
    $product_types = $this->productTypeStorage->loadMultiple();
    return $product_types;
  }

  /**
   * Get product types with image fields.
   *
   * @return \Drupal\arch_product\Entity\ProductTypeInterface[]
   *   Product type entity list.
   */
  protected function getProductTypesWithImageField() {
    return array_filter($this->getProductTypes(), [$this, 'typeHasImageFields']);
  }

  /**
   * Get image style options.
   *
   * @return string[][]
   *   Image style options.
   */
  protected function getImageStyleOptions() {
    if (!isset($this->imageStyleOptions)) {
      $options = [
        '' => $this->t('Default'),
      ];
      foreach ($this->imageStyleStorage->loadMultiple() as $image_style) {
        /** @var \Drupal\image\ImageStyleInterface $image_style */
        $options[$image_style->id()] = $image_style->label();
      }
      $this->imageStyleOptions = $options;
    }

    return $this->imageStyleOptions;
  }

  /**
   * Check if given product type has image fields.
   *
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $product_type
   *   Product type.
   *
   * @return bool
   *   Return TRUE if given bundle has image field.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function typeHasImageFields(ProductTypeInterface $product_type) {
    $image_fields = $this->getImageSourcesForProductType($product_type);
    return !empty($image_fields);
  }

  /**
   * Get list of field can use as image source.
   *
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $product_type
   *   Product type.
   *
   * @return array
   *   Image sources.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getImageSourcesForProductType(ProductTypeInterface $product_type) {
    $key = 'product_type:' . $product_type->id() . ':image_fields';
    if (!isset($this->bundleInfo[$key])) {
      $this->bundleInfo[$key] = $this->getImageSourcesForBundle('product', $product_type);
    }
    return $this->bundleInfo[$key];
  }

  /**
   * Get list of fields can use as image source.
   *
   * @param string $target_entity_type
   *   Entity type ID.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   Entity bundle config.
   * @param null|string $prefix
   *   Label prefix.
   *
   * @return array
   *   Image source info.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getImageSourcesForBundle($target_entity_type, ConfigEntityInterface $bundle, $prefix = NULL) {
    $fields = $this->entityFieldManager->getFieldDefinitions($target_entity_type, $bundle->id());
    $sources = [];
    foreach ($fields as $id => $field) {
      if (
        $field->getType() === 'image'
        || (
          $field->getType() === 'entity_reference'
          && $field->getSetting('target_type') == 'image'
        )
      ) {
        $sources[$id] = [
          'field' => $id,
          'type' => 'image',
          'label' => $this->fieldLabel($field->getLabel(), $prefix),
        ];
        continue;
      }

      if (
        $field->getType() === 'entity_reference'
        && $field->getSetting('target_type') == 'media'
      ) {
        $handler_settings = $field->getSetting('handler_settings');
        $media_bundles = !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : [];
        foreach ($media_bundles as $media_bundle_id) {
          $media_bundle = $this->entityTypeManager->getStorage('media_type')->load($media_bundle_id);
          $media_image_fields = $this->getImageSourcesForBundle('media', $media_bundle, $field->getLabel());
          foreach ($media_image_fields as $field_name => $media_image_field) {
            $sources[$id . ':' . $field_name] = [
              'field' => $id . ':' . $media_image_field['field'],
              'type' => $media_image_field['type'],
              'label' => $this->fieldLabel($media_image_field['label'], $prefix),
            ];
          }
        }
      }
    }

    return $sources;
  }

  /**
   * Build field label.
   *
   * @param string $label
   *   Field label.
   * @param string $prefix
   *   Field prefix.
   *
   * @return string
   *   Option label.
   */
  protected function fieldLabel($label, $prefix) {
    if (empty($prefix)) {
      return $label;
    }

    return $prefix . ' - ' . $label;
  }

  /**
   * Check if minicart display is allowed.
   *
   * @return bool
   *   Return result.
   */
  protected function allowMiniCartDisplay() {
    if ($this->routeMatch->getRouteName() === 'arch_checkout.checkout') {
      $return = AccessResult::forbidden();
    }
    else {
      $return = AccessResult::neutral();
    }

    $results = $this->moduleHandler->invokeAll('arch_minicart_display_allowed');
    foreach ($results as $result) {
      if ($result instanceof AccessResultInterface) {
        $return->andIf($result);
      }
    }

    $this->moduleHandler->alter('arch_minicart_display_allowed', $return);

    if ($return instanceof AccessResultInterface) {
      return !$return->isForbidden();
    }
    return (bool) $return;
  }

}
