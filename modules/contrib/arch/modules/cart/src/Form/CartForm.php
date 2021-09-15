<?php

namespace Drupal\arch_cart\Form;

use Drupal\arch_cart\Cart\CartHandlerInterface;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\arch_price\Price\PriceFormatterInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\MainContent\AjaxRenderer;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Cart form.
 *
 * @package Drupal\arch_cart\Form
 */
class CartForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The ajax renderer service.
   *
   * @var \Drupal\Core\Render\MainContent\AjaxRenderer
   */
  protected $ajaxRenderer;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Cart handler.
   *
   * @var \Drupal\arch_cart\Cart\CartHandlerInterface
   */
  protected $cartHandler;

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
   * Cart.
   *
   * @var \Drupal\arch_cart\Cart\CartInterface
   */
  protected $cart;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Current language code.
   *
   * @var string
   */
  protected $currentLanguageCode;

  /**
   * Constructs a CartForm object.
   *
   * @param \Drupal\arch_cart\Cart\CartHandlerInterface $cart_handler
   *   Cart handler.
   * @param \Drupal\arch_price\Price\PriceFactoryInterface $price_factory
   *   Price factory.
   * @param \Drupal\arch_price\Price\PriceFormatterInterface $price_formatter
   *   Price formatter.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Theme manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Render\MainContent\AjaxRenderer $ajax_renderer
   *   The ajax renderer service.
   */
  public function __construct(
    CartHandlerInterface $cart_handler,
    PriceFactoryInterface $price_factory,
    PriceFormatterInterface $price_formatter,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager,
    RequestStack $request_stack,
    RouteMatchInterface $route_match,
    RendererInterface $renderer,
    AjaxRenderer $ajax_renderer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
    $this->ajaxRenderer = $ajax_renderer;
    $this->renderer = $renderer;
    $this->cartHandler = $cart_handler;
    $this->priceFactory = $price_factory;
    $this->priceFormatter = $price_formatter;
    $this->languageManager = $language_manager;
    $this->currentLanguageCode = $language_manager->getCurrentLanguage()->getId();
    $this->cart = $cart_handler->getCart();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('arch_cart_handler'),
      $container->get('price_factory'),
      $container->get('price_formatter'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('theme.manager'),
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('renderer'),
      $container->get('main_content_renderer.ajax')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arch_cart_form';
  }

  /**
   * Get product entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Product entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getProductStorage() {
    return $this->entityTypeManager->getStorage('product');
  }

  /**
   * Get file entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   File entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFileStorage() {
    return $this->entityTypeManager->getStorage('file');
  }

  /**
   * Get cart.
   *
   * @return \Drupal\arch_cart\Cart\CartInterface
   *   Cart instance.
   */
  protected function getCart() {
    if (
      !$this->cart
      || !$this->cart->getModuleHandler()
    ) {
      $this->cart = $this->cartHandler->getCart();
    }
    return $this->cart;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->getCart()->getProducts())) {
      $form['products'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'text-center',
            'cart-empty',
          ],
        ],
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Your cart is currently empty.', [], ['context' => 'arch_cart']),
        ],
      ];
      return $form;
    }

    $form = [];
    $form['#cart'] = $this->getCart();
    $form['#prefix'] = '<div id="arch-cart-form-wrapper" class="arch-cart-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['#attributes']['class'][] = 'arch-cart-form-form';

    $form['products'] = $this->buildProductsTable();

    $form['sum'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'cart-form-actions',
          'text-right',
          'clearfix',
        ],
      ],
    ];
    $form['sum']['totals'] = $this->buildCartTotals();

    $form['sum']['cart-actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'form-actions',
          'cart-actions-wrapper',
          'cart-form-actions__item',
          'clearfix',
        ],
      ],
      'back-button' => [
        '#type' => 'link',
        '#url' => NULL,
        '#title' => $this->t('Continue shopping', [], ['context' => 'arch_cart']),
        '#attributes' => [
          'class' => [
            'btn',
            'btn-default',
            'btn-continue-shopping',
          ],
        ],
      ],
      'checkout-button' => [
        '#type' => 'submit',
        '#value' => $this->t('Place order', [], ['context' => 'arch_cart']),
        '#disabled' => empty($this->getCart()->getCount()),
        '#attributes' => [
          'class' => [
            'btn-success',
            'btn-process-order',
          ],
        ],
      ],
    ];

    // Uses fromRoute instead of fromUri to being more flexible. E.g.: on a path
    // change on this product search view.
    $form['sum']['cart-actions']['back-button']['#url'] = Url::fromRoute('view.product_search.products');

    return $form;
  }

  /**
   * Price format settings.
   *
   * @return array
   *   Settings.
   */
  protected function getPriceFormatSettings() {
    return [
      'label' => FALSE,
      'vat_info' => FALSE,
    ];
  }

  /**
   * Build formatted price.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price instance.
   * @param string $mode
   *   Display mode.
   *
   * @return array
   *   Render array.
   */
  protected function renderPrice(PriceInterface $price, $mode) {
    return $this->priceFormatter->buildFormatted($price, $mode, $this->getPriceFormatSettings());
  }

  /**
   * Load product.
   *
   * @param int $pid
   *   Product ID.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   Product with given ID.
   */
  protected function loadProduct($pid) {
    try {
      /** @var \Drupal\arch_product\Entity\ProductInterface $product */
      $product = $this->getProductStorage()->load($pid);
      if (!$product) {
        return NULL;
      }
      if ($product->hasTranslation($this->currentLanguageCode)) {
        $product = $product->getTranslation($this->currentLanguageCode);
      }
      return $product;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Build products table.
   *
   * @return array
   *   Render array.
   */
  protected function buildProductsTable() {
    $build = [
      '#type' => 'table',
      '#header' => [],
    ];
    $build['#header']['product'] = [
      'data' => $this->t('Product', [], ['context' => 'arch_product']),
      'class' => 'th-product',
    ];
    $build['#header']['details'] = [
      'data' => '',
      'class' => 'th-details',
    ];
    $build['#header']['quantity'] = [
      'data' => $this->t('Quantity', [], ['context' => 'arch_cart']),
      'class' => 'th-quantity',
    ];
    $build['#header']['net_item_price'] = [
      'data' => $this->t('Net price', [], ['context' => 'arch_price']),
      'class' => 'th-net-price th-net-item-price th-item-price',
    ];
    $build['#header']['gross_item_price'] = [
      'data' => $this->t('Gross price', [], ['context' => 'arch_price']),
      'class' => 'th-gross-price th-gross-item-price th-item-price',
    ];
    $build['#header']['net_total_price'] = [
      'data' => $this->t('Total net price', [], ['context' => 'arch_price']),
      'class' => 'th-net-price th-net-total-price th-total-price',
    ];
    $build['#header']['gross_total_price'] = [
      'data' => $this->t('Total gross price', [], ['context' => 'arch_price']),
      'class' => 'th-gross-price th-gross-total-price th-total-price',
    ];
    $build['#header']['remove'] = [
      'data' => '',
      'class' => 'th-remove',
    ];

    foreach ($this->getCart()->getProducts() as $key => $item) {
      $item_id = $item['type'] . ':' . $item['id'];
      /** @var \Drupal\arch_product\Entity\ProductInterface $product */
      $product = $this->loadProduct($item['id']);
      if (empty($product)) {
        continue;
      }

      $build[$item_id] = $this->buildProductsTableRow($key, $item, $product);
    }

    return $build;
  }

  /**
   * Build products table row.
   *
   * @param int $key
   *   Cart key.
   * @param array $item
   *   Line item.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return array
   *   Render array.
   */
  protected function buildProductsTableRow($key, array $item, ProductInterface $product) {
    $item_id = $item['type'] . ':' . $item['id'];

    /** @var \Drupal\arch_price\Price\PriceInterface $price */
    $price = $product->getActivePrice();

    $build = [
      '#attributes' => [
        'class' => ['cart-product-row'],
        'data-cart-line-item-item-id' => $item_id,
        'data-cart-line-item-key' => $key,
        'data-cart-line-item-type' => $item['type'],
        'data-cart-line-item-id' => $item['id'],
      ],
      '#product' => $product,
    ];
    try {
      $product_label = $product->toLink()->toRenderable();
      $product_label['#attributes']['class'][] = 'product-name';
      $product_label['#attributes']['class'][] = 'product-link';
    }
    catch (\Exception $e) {
      $product_label = [
        '#type' => 'inline_template',
        '#template' => '<span{{ attributes }}>{{ label }}</span>',
        '#context' => [
          'label' => $product->label(),
          'attributes' => new Attribute([
            'class' => 'product-name',
          ]),
        ],
      ];
    }

    $details = $this->buildProductsTableRowProperties($item, $product);
    $build['key'] = [
      '#wrapper_attributes' => [
        'class' => ['td-product'],
      ],
      'item_type' => [
        '#type' => 'type',
        '#value' => $item['type'],
      ],
      'item_id' => [
        '#type' => 'type',
        '#value' => $item['id'],
      ],
      'pid' => [
        '#type' => 'value',
        '#value' => $product->id(),
      ],
      'key' => [
        '#type' => 'value',
        '#value' => $key,
      ],
      'label' => $product_label,
      'image' => isset($details['image']) ? $details['image'] : NULL,
    ];
    $build['properties'] = [
      '#wrapper_attributes' => [
        'class' => ['td-details'],
      ],
      'properties' => isset($details['properties']) ? $details['properties'] : NULL,
    ];
    $build['quantity'] = [
      '#wrapper_attributes' => [
        'class' => ['td-quantity'],
      ],
      '#type' => 'number',
      '#default_value' => $item['quantity'],
      '#min' => 1,
      '#ajax' => [
        'event' => 'change',
        'callback' => [$this, 'changeArticleAmount'],
        'wrapper' => 'arch-cart-form-wrapper',
      ],
      '#attributes' => [
        'data-item-type' => $item['type'],
        'data-item-id' => $item['id'],
        'data-item-item-id' => $item_id,
        'data-item-key' => $key,
      ],
    ];

    $build += $this->buildProductsTableRowPrices($item['quantity'], $price);

    $build['remove'] = [
      '#wrapper_attributes' => [
        'class' => ['td-remove'],
      ],
      '#type' => 'button',
      '#value' => $this->t('Remove item', [], ['context' => 'arch_cart']),
      '#return_value' => [
        'item_id' => $item_id,
        'key' => $key,
        'type' => $item['type'],
        'id' => $item['id'],
      ],
      '#attributes' => [
        'data-item-type' => $item['type'],
        'data-item-id' => $item['id'],
        'data-item-key' => $key,
      ],
      '#name' => 'remove:' . $item_id,
      '#ajax' => [
        'callback' => [$this, 'removeArticle'],
        'wrapper' => 'arch-cart-form-wrapper',
      ],
    ];

    $alter_context = [
      'line_item' => $item,
      'product' => $product,
      'cart' => $this->cart,
    ];
    $this->moduleHandler->alter('arch_cart_product_table_row', $build, $alter_context);
    $this->themeManager->alter('arch_cart_product_table_row', $build, $alter_context);

    return $build;
  }

  /**
   * Build product properties.
   *
   * @param array $line_item
   *   Line item.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product instance.
   *
   * @return array
   *   Render array.
   */
  protected function buildProductsTableRowProperties(array $line_item, ProductInterface $product) {
    $alter_context = [
      'line_item' => $line_item,
      'product' => $product,
      'cart' => $this->cart,
    ];

    $details = [];

    $image = [];
    $this->moduleHandler->alter('arch_cart_product_image', $image, $alter_context);
    $this->themeManager->alter('arch_cart_product_image', $image, $alter_context);
    if (!empty($image)) {
      $details['image'] = $image;
    }

    $properties = [];
    $this->moduleHandler->alter('arch_cart_product_properties', $properties, $alter_context);
    $this->themeManager->alter('arch_cart_product_properties', $properties, $alter_context);
    if (!empty($properties)) {
      $details['properties'] = $properties;
    }

    $this->moduleHandler->alter('arch_cart_product_details', $details, $alter_context);
    $this->themeManager->alter('arch_cart_product_details', $details, $alter_context);
    return $details;
  }

  /**
   * Total cells for products table row.
   *
   * @param float $quantity
   *   Item quantity.
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Item price.
   *
   * @return array
   *   Render array.
   */
  protected function buildProductsTableRowPrices($quantity, PriceInterface $price) {
    $total_price_values = $price->getValues();
    $total_price_values['net'] = $total_price_values['net'] * $quantity;
    $total_price_values['gross'] = $total_price_values['gross'] * $quantity;
    $total_price = $this->priceFactory->getInstance($total_price_values);

    $build['net_item_price'] = [
      '#wrapper_attributes' => [
        'class' => [
          'td-net-price',
          'td-net-item-price',
        ],
        'data-price-type' => 'net_item_price',
      ],
      'value' => $this->renderPrice($price, PriceInterface::FORMAT_NET),
    ];
    $build['gross_item_price'] = [
      '#wrapper_attributes' => [
        'class' => [
          'td-gross-price',
          'td-gross-item-price',
        ],
        'data-price-type' => 'gross_item_price',
      ],
      'value' => $this->renderPrice($price, PriceInterface::FORMAT_GROSS),
    ];

    $build['net_total_price'] = [
      '#wrapper_attributes' => [
        'class' => [
          'td-net-price',
          'td-net-total-price',
        ],
        'data-price-type' => 'net_total_price',
      ],
      'value' => $this->renderPrice($total_price, PriceInterface::FORMAT_NET),
    ];
    $build['gross_total_price'] = [
      '#wrapper_attributes' => [
        'class' => [
          'td-gross-price',
          'td-gross-total-price',
        ],
        'data-price-type' => 'gross_total_price',
      ],
      'value' => $this->renderPrice($total_price, PriceInterface::FORMAT_GROSS),
    ];
    return $build;
  }

  /**
   * Change article amount callback.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Response or render array.
   *
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function changeArticleAmount(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $line_item_values = $trigger_element['#attributes'];
    $amount = (float) Xss::filter($trigger_element['#value']);

    if (empty($amount) || $amount <= 0) {
      return $this->doRemoveArticle($form, [
        'type' => $line_item_values['data-item-type'],
        'id' => $line_item_values['data-item-id'],
        'item_id' => $line_item_values['data-item-item-id'],
      ]);
    }

    return $this->doChangeArticleAmount($form, [
      'type' => $line_item_values['data-item-type'],
      'id' => $line_item_values['data-item-id'],
    ], $amount);
  }

  /**
   * Totals block.
   *
   * @return array
   *   Render array.
   */
  protected function buildCartTotals() {
    $price_format_settings = $this->getPriceFormatSettings();
    $total_cart_price = $this->getCart()->getTotalPrice();
    $grand_total_price = $this->getCart()->getGrandTotalPrice();
    return [
      '#theme' => 'cart_page_totals',
      '#cart' => $this->getCart(),
      '#total_price' => $total_cart_price,
      '#grand_total_price' => $grand_total_price,
      '#price_format_settings' => $price_format_settings,
    ];
  }

  /**
   * Remove article ajax callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function removeArticle(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $remove_item_values = $trigger_element['#return_value'];

    return $this->doRemoveArticle($form, $remove_item_values);
  }

  /**
   * Do change article amount.
   */
  protected function doChangeArticleAmount(array &$form, $line_item_values, $amount) {
    // We have to reread cart from storage before remove items.
    $this->cart = $this->cartHandler->getCart(TRUE);
    $this->getCart()->updateItemQuantityById($line_item_values['type'], $line_item_values['id'], $amount);

    $totals = $this->buildCartTotals();
    $form['sum']['totals'] = $totals;

    if ($this->ajaxRenderer) {
      // DO NOT re-render the whole form!
      /** @var \Drupal\Core\Ajax\AjaxResponse $response */
      $response = new AjaxResponse();

      $response->addCommand(
        new InvokeCommand('body', 'trigger', [
          'commerce.minicart.updated',
          [
            'count' => $this->getCart()->getCount(),
            'total' => $this->getCart()->getTotal()['net'],
            'grand_total' => $this->getCart()->getGrandTotal()['net'],
            'amount_updated' => TRUE,
          ],
        ])
      );
      $response->addCommand(
        new InvokeCommand('body', 'trigger', ['arch_cart_api_cart_update'])
      );

      $product = $this->loadProduct($line_item_values['id']);
      if ($product) {
        $price = $product->getActivePrice();
        $row_selector = '.cart-product-row[data-cart-line-item-item-id="' . $line_item_values['type'] . ':' . $line_item_values['id'] . '"]';
        foreach ($this->buildProductsTableRowPrices($amount, $price) as $field => $value) {
          $response->addCommand(new ReplaceCommand($row_selector . ' td[data-price-type="' . $field . '"] .price', $value['value']));
        }
      }

      $totals_rendered = (string) $this->renderer->render($totals);
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="edit-sum"] .totals', $totals_rendered));

      return $response;
    }

    $form['#cache']['contexts'][] = 'user';
    $form['#cache']['contexts'][] = 'session';
    $form['#cache']['max-age'] = 0;
    // In this case, sending event to Google Analytics will be skipped.
    return $form;
  }

  /**
   * Do remove article.
   */
  protected function doRemoveArticle(array &$form, array $remove_item_values) {
    // We have to reread cart from storage before remove items.
    $this->cart = $this->cartHandler->getCart(TRUE);
    $this->getCart()->removeItemById($remove_item_values['type'], $remove_item_values['id']);

    // We have to update totals.
    $totals = $this->buildCartTotals();
    $form['sum']['totals'] = $totals;

    if ($this->ajaxRenderer) {
      // We have to drop row from product table.
      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('[data-drupal-selector="edit-products"] [data-cart-line-item-item-id="' . $remove_item_values['item_id'] . '"]'));

      $totals_rendered = (string) $this->renderer->render($totals);
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="edit-sum"] .totals', $totals_rendered));

      // Force minicart to update.
      $response->addCommand(
        new InvokeCommand('body', 'trigger', ['arch_cart_api_cart_update'])
      );

      return $response;
    }

    $form['#cache']['contexts'][] = 'user';
    $form['#cache']['contexts'][] = 'session';
    $form['#cache']['max-age'] = 0;
    // In this case, sending event to Google Analytics will be skipped.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement this.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('arch_checkout.checkout');
  }

}
