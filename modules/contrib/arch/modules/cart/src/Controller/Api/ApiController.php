<?php

namespace Drupal\arch_cart\Controller\Api;

use Drupal\arch_cart\Cart\CartHandlerInterface;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\arch_price\Price\PriceFormatterInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Cart API controller.
 *
 * @package Drupal\arch_cart\Controller\Api
 */
class ApiController extends ControllerBase {

  /**
   * Price factory.
   *
   * @var \Drupal\arch_price\Price\PriceFactoryInterface
   */
  protected $priceFactory;

  /**
   * Price formatter.
   *
   * @var \Drupal\arch_price\Price\PriceFormatterInterface
   */
  protected $priceFormatter;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Cart.
   *
   * @var \Drupal\arch_cart\Cart\CartInterface
   */
  protected $cart;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Current language code.
   *
   * @var string
   */
  protected $currentLanguageCode;

  /**
   * Block config.
   *
   * @var array
   */
  protected $blockConfig;

  /**
   * ApiController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\arch_price\Price\PriceFactoryInterface $price_factory
   *   Price factory.
   * @param \Drupal\arch_price\Price\PriceFormatterInterface $price_formatter
   *   Price formatter.
   * @param \Drupal\arch_cart\Cart\CartHandlerInterface $cart_handler
   *   Cart handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Theme manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    PriceFactoryInterface $price_factory,
    PriceFormatterInterface $price_formatter,
    CartHandlerInterface $cart_handler,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager,
    RendererInterface $renderer,
    RequestStack $request_stack
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->priceFactory = $price_factory;
    $this->priceFormatter = $price_formatter;
    $this->renderer = $renderer;
    $this->request = $request_stack->getCurrentRequest();
    $this->cart = $cart_handler->getCart();
    $this->languageManager = $language_manager;
    $this->currentLanguageCode = $language_manager->getCurrentLanguage()->getId();
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;

    try {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $block_storage */
      $request_theme = $this->request->get('theme');
      if (empty($request_theme)) {
        $request_theme = $this->themeManager->getActiveTheme()->getName();
      }
      $block_storage = $entity_type_manager->getStorage('block');
      $block_config = $block_storage->loadByProperties([
        'theme' => $request_theme,
        'plugin' => 'arch_cart_mini_cart',
      ]);
      if (!empty($block_config)) {
        /** @var \Drupal\block\Entity\Block $block */
        $block = current($block_config);
        /** @var \Drupal\arch_cart\Plugin\Block\MiniCartBlock $plugin */
        $plugin = $block->getPlugin();
        $this->blockConfig = $plugin->getConfiguration();
      }
    }
    catch (\Exception $e) {
      // @todo handle exception.
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('price_factory'),
      $container->get('price_formatter'),
      $container->get('arch_cart_handler'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('theme.manager'),
      $container->get('renderer'),
      $container->get('request_stack')
    );
  }

  /**
   * Get info about cart.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function cart() {
    $status_code = NULL;
    if ($this->request->isMethod('GET')) {
      $data = $this->buildCart(TRUE);
    }
    else {
      $status_code = 405;
      $data = [
        'error' => TRUE,
        'message' => 'Method not allowed',
      ];
    }
    return $this->sendJson($data, $status_code);
  }

  /**
   * Add item to cart.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function addItem() {
    $status_code = NULL;
    if ($this->request->isMethod('POST')) {
      $product_id = $this->request->request->get('id');
      $quantity = (float) $this->request->request->get('quantity');
      $product = $this->loadProduct($product_id);
      if (!$product) {
        $status_code = 400;
        $data = [
          'error' => TRUE,
          'message' => 'Failed to add product. Reason: Invalid product id.',
        ];
      }
      else {
        $this->cart->addItem([
          'type' => 'product',
          'id' => $product_id,
          'quantity' => $quantity,
        ]);
        $data = $this->buildCart();
        $data['do'][] = 'update_cart';
        $data['do'][] = 'show_cart';
      }
    }
    else {
      $status_code = 405;
      $data = [
        'error' => TRUE,
        'message' => 'Method not allowed',
      ];
    }

    return $this->sendJson($data, $status_code);
  }

  /**
   * Change product quantity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function quantity() {
    $status_code = NULL;
    if ($this->request->isMethod('POST')) {
      $type = $this->request->request->get('type');
      $id = $this->request->request->get('id');
      $item_key = $this->request->request->get('key');
      $quantity = (float) $this->request->request->get('quantity');
      try {
        if (isset($type) && isset($id)) {
          try {
            $this->cart->updateItemQuantityById($type, $id, $quantity);
          }
          catch (\Exception $e) {
            if ($e->getCode() !== 1001) {
              throw $e;
            }

            $this->cart->addItem([
              'type' => $type,
              'id' => $id,
              'quantity' => $quantity,
            ]);
          }
        }
        elseif (isset($item_key)) {
          $this->cart->updateItemQuantity($item_key, $quantity);
        }
        else {
          throw new \Exception('Cannot update missing item');
        }
        $data = $this->buildCart();
        $data['do'][] = 'update_cart';
        $data['do'][] = 'show_cart';
      }
      catch (\Exception $e) {
        $data = [
          'error' => TRUE,
          'message' => $e->getMessage(),
        ];
        $status_code = 400;
      }
    }
    else {
      $status_code = 405;
      $data = [
        'error' => TRUE,
        'message' => 'Method not allowed',
      ];
    }

    return $this->sendJson($data, $status_code);
  }

  /**
   * Remove item from cart.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function removeItem() {
    $status_code = NULL;
    if ($this->request->isMethod('POST')) {
      $type = $this->request->request->get('type');
      $id = $this->request->request->get('id');
      $item_key = $this->request->request->get('key');
      try {
        if (isset($type) && isset($id)) {
          $this->cart->removeItemById($type, $id);
        }
        elseif (isset($item_key)) {
          $this->cart->removeItem($item_key);
        }
        else {
          throw new \Exception('Cannot remove missing item');
        }
        $data = $this->buildCart();
        $data['do'][] = 'update_cart';
        $data['do'][] = 'show_cart';
      }
      catch (\Exception $e) {
        $data = [
          'error' => TRUE,
          'message' => $e->getMessage(),
        ];
        $status_code = 400;
      }
    }
    else {
      $status_code = 405;
      $data = [
        'error' => TRUE,
        'message' => 'Method not allowed',
      ];
    }

    return $this->sendJson($data, $status_code);
  }

  /**
   * Build cart data.
   *
   * @return array
   *   Cart data.
   */
  protected function buildCart($clear_messages = FALSE) {
    $data = [
      'cart' => [
        'items' => [],
        'products' => 0,
        'quantity' => 0,
        'total' => [
          'raw' => 0,
          'formatted' => NULL,
        ],
        'net_total' => [
          'raw' => 0,
          'formatted' => NULL,
        ],
        'messages' => [],
      ],
      'error' => FALSE,
      'messages' => [],
    ];

    /** @var \Drupal\arch_price\Price\PriceInterface[] $totals */
    $totals = [];
    $products = [];
    foreach ($this->cart->getProducts() as $key => $line_item) {
      /** @var \Drupal\arch_product\Entity\ProductInterface $product */
      $product = $this->loadProduct($line_item['id']);
      if (empty($product)) {
        $this->cart->removeItemById($line_item['type'], $line_item['id']);
        continue;
      }

      $data['cart']['products'] += 1;
      $data['cart']['quantity'] += (float) $line_item['quantity'];

      $item = $this->buildCartItem($key, $line_item, $product);

      /** @var \Drupal\arch_price\Price\PriceInterface $price */
      $price = $product->getActivePrice();
      $total_price = $this->totalPrice($item['quantity'], $price);
      $totals[] = $total_price;
      $products[] = $item;
    }

    $total_base_values = [
      'base' => 'net',
      'price_type' => 'default',
      'currency' => NULL,
      'net' => 0,
      'gross' => 0,
      'vat_category' => 'custom',
      'vat_rate' => 0,
      'vat_value' => 0,
      'date_from' => NULL,
      'date_to' => NULL,
    ];
    $this->moduleHandler()->alter('cart_total_base_values', $total_base_values);

    $total_price_values = [
      '_fallback' => TRUE,
      'base' => 'net',
      'net' => 0,
      'gross' => 0,
      'currency' => 'XXX',
      'vat_category' => 'default',
      'vat_rate' => 0,
    ];

    foreach ($totals as $total) {
      if (!empty($total_price_values) && !empty($total_price_values['_fallback'])) {
        $total_price_values = $total->getValues();
        continue;
      }

      $total_price_values['net'] += $total->getNetPrice();
      $total_price_values['gross'] += $total->getGrossPrice();
    }

    $total_price_values['base'] = $total_base_values['base'];
    $total_price_values['vat_category'] = $total_base_values['vat_category'];
    $total_price_values['vat_rate'] = $total_base_values['vat_rate'];
    $total_price_values['vat_amount'] = 0;
    if (isset($total_base_values['vat_amount'])) {
      $total_price_values['vat_amount'] = $total_base_values['vat_amount'];
    }

    // @todo Handle if no totals: !empty($total_price_values['_fallback']).
    $total_cart_price = $this->cart->getTotalPrice();

    $data['cart']['total'] = [
      'raw' => $total_cart_price->getGrossPrice(),
      'formatted' => $this->formatPrice($total_cart_price, PriceInterface::FORMAT_GROSS),
    ];
    $data['cart']['net_total'] = [
      'raw' => $total_cart_price->getNetPrice(),
      'formatted' => $this->formatPrice($total_cart_price, PriceInterface::FORMAT_NET),
    ];

    $data['cart']['items'] = $products;
    $data['cart']['messages'] = array_values(array_merge($data['messages'], $this->cart->displayMessages($clear_messages)));

    $this->moduleHandler()->alter('api_cart_data', $data, $this->cart);
    return $data;
  }

  /**
   * Send JSON response.
   *
   * @param array $data
   *   Response data.
   * @param int $status_code
   *   Status code.
   * @param string $status_text
   *   Status text.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  protected function sendJson(array $data, $status_code = NULL, $status_text = NULL) {
    $response = new JsonResponse();
    $response->setData($data);
    if (isset($status_code)) {
      $response->setStatusCode($status_code, $status_text);
    }
    return $response;
  }

  /**
   * Load product.
   *
   * @param int $pid
   *   Product ID.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface|null
   *   Product.
   */
  protected function loadProduct($pid) {
    try {
      $storage = $this->entityTypeManager()->getStorage('product');
      /** @var \Drupal\arch_product\Entity\ProductInterface $product */
      $product = $storage->load($pid);
      if (!$product) {
        return NULL;
      }
      if ($product->hasTranslation($this->currentLanguageCode)) {
        $product = $product->getTranslation($this->currentLanguageCode);
      }
      return $product;
    }
    catch (\Exception $e) {
      // @todo handle this.
    }
    return NULL;
  }

  /**
   * Load file.
   *
   * @param int $fid
   *   File ID.
   *
   * @return \Drupal\file\FileInterface|null
   *   Node.
   */
  protected function loadFile($fid) {
    try {
      $storage = $this->entityTypeManager()->getStorage('file');
      /** @var \Drupal\file\FileInterface $file */
      $file = $storage->load($fid);
      return $file;
    }
    catch (\Exception $e) {
      // @todo handle this.
    }
    return NULL;
  }

  /**
   * Load image style.
   *
   * @param string $style
   *   Style name.
   *
   * @return \Drupal\image\ImageStyleInterface
   *   Image style.
   */
  protected function loadImageStyle($style) {
    try {
      $storage = $this->entityTypeManager()->getStorage('image_style');
      /** @var \Drupal\image\ImageStyleInterface $style */
      $style = $storage->load($style);
      return $style;
    }
    catch (\Exception $e) {
      // @todo handle this.
    }
    return NULL;
  }

  /**
   * Total price.
   *
   * @param float $quantity
   *   Quantity.
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Item price.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Total price.
   */
  protected function totalPrice($quantity, PriceInterface $price) {
    $values = $price->getValues();
    $values['net'] = $values['net'] * $quantity;
    $values['gross'] = $values['gross'] * $quantity;
    return $this->priceFactory->getInstance($values);
  }

  /**
   * Formatting price value.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price instance.
   * @param string $mode
   *   Formatting mode.
   *
   * @return string
   *   Rendered price.
   */
  protected function formatPrice(PriceInterface $price, $mode) {
    return $this->priceFormatter->format($price, $mode, [
      'label' => FALSE,
      'vat_info' => FALSE,
    ]);
  }

  /**
   * Render product image.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   *
   * @return array
   *   Render array.
   *
   * @throws \Exception
   */
  protected function showImage(ProductInterface $product) {
    $image_uri = $this->loadImage($product);
    $this->moduleHandler->alter('arch_cart_mini_cart_product_image', $image_uri, $product);
    $this->themeManager->alter('arch_cart_mini_cart_product_image', $image_uri, $product);
    if (empty($image_uri)) {
      // @todo find blank image.
      return NULL;
    }

    $image_style_id = $this->getImageStyle($product->bundle());
    $this->moduleHandler->alter('arch_cart_mini_cart_product_image_style', $image_style_id, $product);
    $this->themeManager->alter('arch_cart_mini_cart_product_image_style', $image_style_id, $product);

    $style = $this->loadImageStyle($image_style_id);

    $image_formatted = [
      '#theme' => 'image_style',
      '#uri' => $image_uri,
      '#style_name' => $style->id(),
      '#attributes' => [
        'data-retina-src' => NULL,
      ],
    ];
    return [
      'raw' => file_create_url($image_uri),
      'styled_url' => $style->buildUrl($image_uri),
      'formatted' => $this->renderer->render($image_formatted),
    ];
  }

  /**
   * Build a cart item for JSON response.
   *
   * @param int $key
   *   Cart item key.
   * @param array $line_item
   *   Line item.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return array
   *   Cart item array.
   */
  protected function buildCartItem($key, array $line_item, ProductInterface $product) {
    $item = [];
    $item['_index'] = $key;
    foreach (['type', 'id', 'quantity'] as $key) {
      $item['_line_item'][$key] = isset($line_item[$key]) ? $line_item[$key] : NULL;
    }

    $item['product_id'] = $product->id();
    $item['quantity'] = (float) $line_item['quantity'];
    $item['title'] = $product->label();

    try {
      $item['url'] = $product->toUrl()->toString();
    }
    catch (\Exception $e) {
      $item['url'] = '/product/' . $line_item['id'];
    }

    /** @var \Drupal\arch_price\Price\PriceInterface $price */
    $price = $product->getActivePrice();
    $total_price = $this->totalPrice($item['quantity'], $price);

    $item['net_price'] = [
      'raw' => $price->getNetPrice(),
      'formatted' => $this->formatPrice($price, PriceInterface::FORMAT_NET),
    ];
    $item['net_total'] = [
      'raw' => $total_price->getNetPrice(),
      'formatted' => $this->formatPrice($total_price, PriceInterface::FORMAT_NET),
    ];

    $item['price'] = [
      'raw' => $price->getGrossPrice(),
      'formatted' => $this->formatPrice($price, PriceInterface::FORMAT_GROSS),
    ];
    $item['total'] = [
      'raw' => $total_price->getGrossPrice(),
      'formatted' => $this->formatPrice($total_price, PriceInterface::FORMAT_GROSS),
    ];

    try {
      $item['image'] = $this->showImage($product);
    }
    catch (\Exception $e) {
      $item['image'] = NULL;
    }

    return $item;
  }

  /**
   * Get image URI.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product to display.
   *
   * @return string|null
   *   Image URI or NULL on failure.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function loadImage(ProductInterface $product) {
    if (
      empty($this->blockConfig)
      || empty($this->blockConfig['bundle_settings'][$product->bundle()]['image_source'])
    ) {
      return NULL;
    }

    list($field, $sub_field) = explode(':', $this->blockConfig['bundle_settings'][$product->bundle()]['image_source']);
    if (
      !$product->hasField($field)
      || $product->get($field)->isEmpty()
    ) {
      return NULL;
    }

    /** @var \Drupal\file\FileInterface|null $file */
    $file = NULL;

    $product_field = $product->get($field);
    $field_definition = $product_field->getFieldDefinition();
    if ($field_definition->getType() == 'image') {
      $file = $product_field->get(0)->enity;
    }
    elseif (
      $field_definition->getType() == 'entity_reference'
      && $field_definition->getSetting('target_type') == 'media'
    ) {
      /** @var \Drupal\media\MediaInterface|null $media */
      $media = $product_field->get(0)->entity;
      if (
        $media->hasField($sub_field)
        && !$media->get($sub_field)->isEmpty()
      ) {
        $file = $media->get($sub_field)->get(0)->entity;
      }
    }

    if (
      empty($file)
      || !($file instanceof FileInterface)
    ) {
      return NULL;
    }

    return $file->getFileUri();
  }

  /**
   * Get image style for bundle.
   *
   * @param string $product_type_id
   *   Product type ID.
   *
   * @return string
   *   Image style ID.
   */
  protected function getImageStyle($product_type_id) {
    if (
      !empty($this->blockConfig)
      && isset($this->blockConfig['bundle_settings'][$product_type_id]['image_style'])
      && !empty($this->blockConfig['bundle_settings'][$product_type_id]['image_style'])
    ) {
      /** @var \Drupal\image\ImageStyleInterface $image_style */
      $image_style = $this->loadImageStyle($this->blockConfig['bundle_settings'][$product_type_id]['image_style']);
      if (!empty($image_style)) {
        return $image_style->id();
      }
    }

    return 'thumbnail';
  }

}
