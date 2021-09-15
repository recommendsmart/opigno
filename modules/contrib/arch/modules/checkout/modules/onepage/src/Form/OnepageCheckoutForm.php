<?php

namespace Drupal\arch_onepage\Form;

use Drupal\arch_cart\Cart\CartHandlerInterface;
use Drupal\arch_order\OrderAddressData;
use Drupal\arch_order\Services\OrderAddressServiceInterface;
use Drupal\arch_order\Services\OrderStatusService;
use Drupal\arch_payment\PaymentMethodManagerInterface;
use Drupal\arch_shipping\ShippingMethodInterface;
use Drupal\arch_shipping\ShippingMethodManagerInterface;
use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Implements a Onepage Checkout form.
 */
class OnepageCheckoutForm extends FormBase {

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * User data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Shipping method manager.
   *
   * @var \Drupal\arch_shipping\ShippingMethodManagerInterface
   */
  protected $shippingMethodManager;

  /**
   * Payment method manager.
   *
   * @var \Drupal\arch_payment\PaymentMethodManagerInterface
   */
  protected $paymentMethodManager;

  /**
   * Order.
   *
   * @var \Drupal\arch_order\Entity\Order
   */
  protected $order;

  /**
   * Order status service.
   *
   * @var \Drupal\arch_order\Services\OrderStatusService
   */
  protected $orderStatus;

  /**
   * Cart.
   *
   * @var \Drupal\arch_cart\Cart\CartInterface
   */
  protected $cart;

  /**
   * Current request object.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Redirect destination object.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $destination;

  /**
   * Renderer object.
   *
   * @var \Drupal\Core\Render\Renderer|\Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Order address service.
   *
   * @var \Drupal\arch_order\Services\OrderAddressServiceInterface
   */
  protected $orderAddressService;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * OnepageCheckoutForm constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user object.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler object.
   * @param \Drupal\arch_shipping\ShippingMethodManagerInterface $shipping_method_manager
   *   Shipping method manager.
   * @param \Drupal\arch_payment\PaymentMethodManagerInterface $payment_method_manager
   *   Payment method manager.
   * @param \Drupal\arch_cart\Cart\CartHandlerInterface $cart_handler
   *   Cart handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   * @param \Drupal\arch_order\Services\OrderStatusService $order_status
   *   Order status service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   Redirect destination object.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer object.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\arch_order\Services\OrderAddressServiceInterface $order_address_service
   *   Order address service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    AccountInterface $current_user,
    UserDataInterface $user_data,
    ModuleHandlerInterface $module_handler,
    ShippingMethodManagerInterface $shipping_method_manager,
    PaymentMethodManagerInterface $payment_method_manager,
    CartHandlerInterface $cart_handler,
    RequestStack $request_stack,
    OrderStatusService $order_status,
    RedirectDestinationInterface $redirect_destination,
    RendererInterface $renderer,
    LanguageManagerInterface $language_manager,
    OrderAddressServiceInterface $order_address_service,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->currentUser = $current_user;
    $this->userData = $user_data;
    $this->moduleHandler = $module_handler;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->destination = $redirect_destination;
    $this->renderer = $renderer;

    $this->shippingMethodManager = $shipping_method_manager;
    $this->paymentMethodManager = $payment_method_manager;
    $this->cart = $cart_handler->getCart();
    $this->order = $this->cart->getOrder();
    $this->orderStatus = $order_status;
    $this->orderAddressService = $order_address_service;
    $this->languageManager = $language_manager;

    $this->entityTypeManager = $entity_type_manager;
    $this->userStorage = $this->entityTypeManager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('user.data'),
      $container->get('module_handler'),
      $container->get('plugin.manager.shipping_method'),
      $container->get('plugin.manager.payment_method'),
      $container->get('arch_cart_handler'),
      $container->get('request_stack'),
      $container->get('order.statuses'),
      $container->get('redirect.destination'),
      $container->get('renderer'),
      $container->get('language_manager'),
      $container->get('order.address'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arch_onepage_checkout';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $billing = NULL, $shipping = NULL) {
    $form['#prefix'] = '<div id="onepage-checkout-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['#attached']['library'][] = 'arch_onepage/onepage-checkout';

    $form['billing'] = $this->billingForm($billing);
    $form['shipping'] = $this->shippingForm($shipping, $form['billing']);
    $form['payment'] = $this->paymentForm();

    if ($this->currentUser->isAnonymous()) {
      $form['create_user'] = [
        '#type' => 'checkbox',
        '#attributes' => [
          'class' => [
            'create-user-option',
          ],
        ],
        '#default_value' => FALSE,
        '#title' => $this->t('Create new account', [], ['context' => 'arch_onepage']),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Checkout', [], ['context' => 'arch_onepage']),
        '#attributes' => [
          'class' => [
            'btn-success',
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If AJAX request, we should not have to validate form, because we only
    // chose a payment menthod.
    if ($this->currentRequest->isXmlHttpRequest()) {
      return TRUE;
    }

    // Clear default errors because of the case if we chose the 'sameas' value
    // for Shipping Address, but the 'shipping_*' fields are remains required
    // which cause errors but it should not.
    $form_errors = $form_state->getErrors();
    $form_state->clearErrors();

    // Remove 'shipping_*' related errors.
    foreach ($form_errors as $key => $field) {
      if (strpos($key, 'shipping_') > -1) {
        unset($form_errors[$key]);
      }
    }

    // Re-apply rest of the errors.
    foreach ($form_errors as $name => $error_message) {
      $form_state->setErrorByName($name, $error_message);
    }

    // @todo Implement 'Accept TOC & Privacy' if it is in use.
    // @todo Check existing paymentmethod..
    if ($this->currentUser->isAnonymous()) {
      $found = $this->userStorage->loadByProperties(['mail' => $form_state->getValue('email')]);
      // Check that given email address is exist, or not.
      if (!empty($found)) {
        $form_state->setErrorByName(
          'email',
          $this->t(
            'The given email address is already taken. Please <a href="@url">log in</a> to use this email address.',
            [
              '@url' => Url::fromUserInput('/user/login', ['query' => ['destination' => 'checkout']])->toString(),
            ],
            ['context' => 'arch_onepage']
          )
        );
        $form_state->setRebuild();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $orderExtraData = [];

    $order_currency = $this->order->get('currency')->getString();
    if (empty($order_currency)) {
      $order_currency = 'EUR';
    }

    /** @var \Drupal\arch_order\Entity\OrderStatusInterface[] $orderStatuses */
    $orderStatuses = $this->orderStatus->getOrderStatuses();
    if (isset($orderStatuses['checkout'])) {
      $this->order->set('status', $orderStatuses['checkout']->getId());
    }
    else {
      $defaultOrderStatus = $this->orderStatus->getDefaultOrderStatus();
      $this->order->set('status', $defaultOrderStatus->getId());
    }

    $user = $this->currentUser;
    if ($user->id() == 0 && $form_state->getValue('create_user') == 1) {
      $user = $this->createUser($form_state);
    }

    if (!empty($user)) {
      $this->order->setOwnerId($user->id());
    }

    $method = $form_state->getValue('shipping_methods');
    $this->order->set('shipping_method', $method);

    $paymentMethodId = $form_state->getValue('payment_method');
    $this->order->set('payment_method', $paymentMethodId);
    try {
      /** @var \Drupal\arch_payment\PaymentMethodInterface $payment_method */
      $payment_method = $this->paymentMethodManager->createInstance($paymentMethodId, []);
    }
    catch (\Exception $e) {
      $this->logger('Onepage Checkout')->error(
        'Failed to retrieve payment method instance. Reason: @reason',
        ['@reason' => $e->getMessage()]
      );

      $form_state->setRebuild();
      return;
    }

    $payment_fee_price = $payment_method->getPaymentFee($this->order);
    if ($payment_fee_price->getCurrencyId() !== $order_currency) {
      $payment_fee_price = $payment_fee_price->getExchangedPrice($order_currency);
    }
    $this->order->setPaymentFee($payment_fee_price, $payment_method->getPluginId());

    if (!empty($form_state->getValue('note'))) {
      $orderExtraData['note'] = $form_state->getValue('note');
    }

    if (!empty($orderExtraData)) {
      $this->order->set('data', $orderExtraData);
    }

    $billing_address = $this->buildBillingAddress($form_state);
    $shipping_address = NULL;
    $shipping_address_selector = $form_state->getValue('shipping_address_selector');

    if ($shipping_address_selector == 'sameas') {
      $shipping_address = clone $billing_address;
    }
    elseif ($shipping_address_selector == 'new_shipping') {
      $shipping_address = $this->buildShippingAddress($form_state);
    }
    elseif (
      $this->hasAddressbook()
      && $shipping_address_selector == 'choose_address'
    ) {
      $shipping_address = $this->loadSelectedShippingAddress($form_state);
    }

    $this->order->setBillingAddress($billing_address);
    if ($shipping_address) {
      $this->order->setShippingAddress($shipping_address);
    }

    if (
      ($shipping_method = $this->order->getShippingMethod())
      && $shipping_method instanceof ShippingMethodInterface
    ) {
      $shipping_price = $shipping_method->getShippingPrice($this->order);
      if ($shipping_price->getCurrencyId() !== $order_currency) {
        $shipping_price = $shipping_price->getExchangedPrice($order_currency);
      }
      $this->order->setShippingPrice($shipping_price, $shipping_method->getPluginId());
    }

    try {
      // Update total to have shipping & payment fees applied.
      $this->order->updateTotal();

      $this->order->save();

      $form_state->setRedirect($payment_method->getCallbackRoute(), ['order' => $this->order->id()]);
    }
    catch (\Exception $e) {
      $this->logger('Onepage Checkout')->error('Failed to save order. Reason: ' . $e->getMessage());
      return;
    }
  }

  /**
   * Gets the billing form fields.
   *
   * @param null|array $_billing
   *   Billing data from storage.
   *
   * @return array
   *   Billing form fields.
   */
  private function billingForm($_billing = NULL) {
    $billing = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personal Data', [], ['context' => 'arch_onepage']),
      '#collapsed' => FALSE,
      '#collapsible' => FALSE,
      '#attributes' => [
        'class' => [
          'checkout-fieldset',
          'checkout-billing-info',
          'billing-info',
        ],
      ],
      '#suffix' => $this->billingFormCollapsedView(),
    ];

    if ($this->currentUser->isAnonymous()) {
      $billing['login'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="checkout-login"><span>{{ text }}</span><a href="{{ button_url }}" class="login-button">{{ button_text }}</a></div>',
        '#context' => [
          'text' => $this->t('Already registered?', [], ['context' => 'arch_onepage']),
          'button_url' => '/user/login?destination=' . $this->destination->get(),
          'button_text' => $this->t('Please sign in.', [], ['context' => 'arch_onepage']),
        ],
      ];
    }

    $billing['email'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('Email Address', [], ['context' => 'arch_onepage']),
      '#title' => $this->t('Email Address', [], ['context' => 'arch_onepage']),
      '#attributes' => [
        'data-field' => 'email',
      ],
      '#readonly' => $this->currentUser->isAuthenticated(),
      '#default_value' => $this->currentUser->isAuthenticated() ? $this->currentUser->getEmail() : '',
      '#required' => TRUE,
    ];

    foreach ($this->getAddressFields() as $field_name => $field_descriptor) {
      $billing[$field_name] = $field_descriptor;
      $billing[$field_name]['#attributes']['data-field'] = $field_name;
    }

    $billing['next_to_shipping'] = [
      '#type' => 'button',
      '#id' => 'btn-next-to-shipping',
      '#value' => $this->t('Next to Shipping', [], ['context' => 'arch_onepage']),
      '#weight' => 200,
    ];

    return $billing;
  }

  /**
   * Summarized view of billing form field data.
   *
   * @return mixed
   *   Rendereable array of summarized view of billing form field data.
   */
  private function billingFormCollapsedView() {
    $output = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'preview-billing-infos',
        'class' => [
          'row',
          'hidden',
          'section-preview',
          'clearfix',
        ],
      ],
      'contact' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'col-md-4',
            'preview-billing-contact',
          ],
        ],
        'label' => [
          '#markup' => '<h3>' . $this->t('Contact', [], ['context' => 'arch_onepage']) . '</h3>',
        ],
        'name' => [
          '#type' => 'container',
          'firstname' => ['#markup' => '<span id="preview-billing-firstname"></span>'],
          'lastname' => ['#markup' => '<span id="preview-billing-lastname"></span>'],
        ],
        'email' => ['#markup' => '<div id="preview-billing-email"></div>'],
        'phone' => ['#markup' => '<div id="preview-billing-phone"></div>'],
      ],
      'billing' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'col-md-4',
            'preview-billing-data',
          ],
        ],
        'label' => [
          '#markup' => '<h3>' . $this->t('Billing Info', [], ['context' => 'arch_onepage']) . '</h3>',
        ],
        'company' => ['#markup' => '<div id="preview-billing-company"></div>'],
        'postcity' => [
          '#type' => 'container',
          'country' => ['#markup' => '<span id="preview-billing-country"></span>'],
          'postcode' => ['#markup' => '<span id="preview-billing-postcode"></span>'],
          'city' => ['#markup' => '<span id="preview-billing-city"></span>'],
        ],
        'addresses' => [
          '#type' => 'container',
          'address' => ['#markup' => '<span id="preview-billing-address"></span>'],
          'address2' => ['#markup' => '<span id="preview-billing-address2"></span>'],
        ],
        'tax' => ['#markup' => '<div id="preview-billing-tax"></div>'],
      ],
    ];

    return $this->renderer->render($output);
  }

  /**
   * Gets the shipping form fields.
   *
   * @param null|array $_shipping
   *   Shipping data from storage.
   * @param null|array $form_billing
   *   Billing form.
   *
   * @return array
   *   Shipping form fields.
   *
   * @throws \Exception
   */
  private function shippingForm($_shipping = NULL, $form_billing = NULL) {
    $shipping = [
      '#type' => 'fieldset',
      '#title' => $this->t('Shipping', [], ['context' => 'arch_onepage']),
      '#collapsed' => TRUE,
      '#collapsible' => FALSE,
      '#attributes' => [
        'class' => [
          'checkout-fieldset',
          'checkout-shipping-info',
          'shipping-info',
        ],
      ],
      '#suffix' => $this->shippingFormCollapsedView(),
    ];

    $default_shipping_method = 'instore';
    $shipping_methods = $this->getShippingMethods();
    $shipping['shipping_methods'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose a shipping method', [], ['context' => 'arch_onepage']),
      '#options' => $shipping_methods,
      '#default_value' => $default_shipping_method,
      '#attributes' => [
        'class' => [
          'onepage-shipping-methods',
          'col-md-4',
        ],
        'data-field' => 'shipping-method',
        'required' => 'required',
      ],
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'callback' => [$this, 'changeShippingMethod'],
      ],
    ];

    $shipping['clearfix'] = [
      '#markup' => '<div class="clearfix"></div>',
    ];

    $shipping['shipping_address_selector'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose a shipping address', [], ['context' => 'arch_onepage']),
      '#title_display' => 'invisible',
      '#default_value' => 'sameas',
      '#attributes' => [
        'class' => [
          'shipping-address-selector',
        ],
        'data-field' => 'shipping-address-selector',
      ],
      '#options' => [
        'sameas' => $this->t('Same as billing info', [], ['context' => 'arch_onepage']),
        'new_shipping' => $this->t('Add new shipping address', [], ['context' => 'arch_onepage']),
      ],
      '#states' => [
        'invisible' => [
          ':input[name="shipping_methods"]' => ['value' => 'instore'],
        ],
      ],
    ];

    if ($this->hasAddressbook()) {
      $choose_address = $this->getAddressListOptions();
      if (!empty($choose_address)) {
        $shipping['shipping_address_selector']['#options']['choose_address'] = $this->t('Choose from address list', [], ['context' => 'arch_onepage']);
      }

      $shipping['new_shipping'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Create new shipping address', [], ['context' => 'arch_onepage']),
        '#attributes' => [
          'class' => [
            'shipping-new-address',
          ],
        ],
        '#states' => [
          'visible' => [
            ':input[name="shipping_address_selector"]' => ['value' => 'new_shipping'],
          ],
        ],
        'fields' => $this->newShippingAddressForm(),
      ];

      $shipping['choose_address'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Address list', [], ['context' => 'arch_onepage']),
        '#attributes' => [
          'class' => [
            'shipping-choose-from-list',
          ],
        ],
        '#states' => [
          'visible' => [
            ':input[name="shipping_address_selector"]' => ['value' => 'choose_address'],
          ],
        ],
        'choose_address' => [
          '#type' => 'select',
          '#options' => $choose_address,
          '#title' => $this->t('Address list', [], ['context' => 'arch_onepage']),
          '#title_display' => 'invisible',
        ],
      ];
    }

    $shipping['phone'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'shipping-phone-phone-number',
          'clearfix',
        ],
      ],
    ];

    $shipping['phone']['label'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="form-section-subtitle">{{ label }}<span class="form-required">*</span></div>',
      '#context' => [
        'label' => $this->t('Phone Number', [], ['context' => 'arch_onepage']),
      ],
    ];

    $shipping['phone']['phone_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone Number Prefix', [], ['context' => 'arch_onepage']),
      '#title_display' => 'invisible',
      '#description' => $this->t('E.g.: +36', [], ['context' => 'arch_onepage']),
      '#required' => TRUE,
      '#maxlength' => 3,
    ];

    $shipping['phone']['phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone Number', [], ['context' => 'arch_onepage']),
      '#title_display' => 'invisible',
      '#description' => $this->t('E.g.: 30 123 4567', [], ['context' => 'arch_onepage']),
      '#required' => TRUE,
    ];

    $shipping['next_to_payment'] = [
      '#type' => 'button',
      '#id' => 'btn-next-to-payment',
      '#value' => $this->t('Next to Payment', [], ['context' => 'arch_onepage']),
    ];

    return $shipping;
  }

  /**
   * Summarized view of shipping form field data.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   Rendereable array of summarized view of shipping form field data.
   *
   * @throws \Exception
   */
  private function shippingFormCollapsedView() {
    // @codingStandardsIgnoreStart
    $output = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'preview-shipping-infos',
        'class' => [
          'row',
          'hidden',
          'section-preview',
          'clearfix',
        ],
      ],
      'method' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'col-md-4',
            'preview-shipping-contact',
          ],
        ],
        'label' => [
          '#markup' => '<h3>' . $this->t('Shipping Method', [], ['context' => 'arch_onepage']) . '</h3>',
        ],
        'method' => [
          '#type' => 'container',
          'method' => ['#markup' => '<span id="preview-shipping-method"></span>'],
          'instore' => ['#markup' => '<span id="preview-shipping-instore"></span>'],
        ],
      ],
      'shipping' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'col-md-4',
            'preview-shipping-data',
          ],
        ],
        'label' => [
          '#markup' => '<h3>' . $this->t('Shipping Info', [], ['context' => 'arch_onepage']) . '</h3>',
        ],
        'sameas' => ['#markup' => '<div id="preview-shipping-sameas" class="hidden">' . $this->t('Same as billing info', [], ['context' => 'arch_onepage']) . '</div>'],
        'company' => ['#markup' => '<div id="preview-shipping-company"></div>'],
        'postcity' => [
          '#type' => 'container',
          'country' => ['#markup' => '<span id="preview-shipping-country"></span>'],
          'postcode' => ['#markup' => '<span id="preview-shipping-postcode"></span>'],
          'city' => ['#markup' => '<span id="preview-shipping-city"></span>'],
        ],
        'addresses' => [
          '#type' => 'container',
          'address' => ['#markup' => '<span id="preview-shipping-address"></span>'],
          'address2' => ['#markup' => '<span id="preview-shipping-address2"></span>'],
        ],
        'tax' => ['#markup' => '<div id="preview-shipping-tax"></div>'],
      ],
    ];
    // @codingStandardsIgnoreEnd

    return $this->renderer->render($output);
  }

  /**
   * Address fields subform.
   *
   * @return array
   *   Address fields subform.
   */
  private function getAddressFields() {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $firstname_weight = 10;

    if ($language == 'hu') {
      $firstname_weight = 25;
    }

    // @codingStandardsIgnoreStart
    return [
      'firstname' => [
        '#type' => 'textfield',
        '#placeholder' => $this->t('First name', [], ['context' => 'arch_onepage']),
        '#title' => $this->t('First name', [], ['context' => 'arch_onepage']),
        '#required' => TRUE,
        '#weight' => $firstname_weight,
      ],
      'lastname' => [
        '#type' => 'textfield',
        '#placeholder' => $this->t('Last name', [], ['context' => 'arch_onepage']),
        '#title' => $this->t('Last name', [], ['context' => 'arch_onepage']),
        '#required' => TRUE,
        '#weight' => 20,
      ],
      'company' => [
        '#type' => 'textfield',
        '#placeholder' => $this->t('Company (optional)', [], ['context' => 'arch_onepage']),
        '#title' => $this->t('Company', [], ['context' => 'arch_onepage']),
        '#required' => FALSE,
        '#weight' => 30,
      ],
      'tax_number' => [
        '#type' => 'textfield',
        '#placeholder' => $this->t('TAX Number', [], ['context' => 'arch_onepage']),
        '#title' => $this->t('TAX Number', [], ['context' => 'arch_onepage']) . '<span class="form-required">*</span>',
        '#required' => FALSE,
        '#attributes' => [
          'autocomplete' => 'off',
        ],
        '#states' => [
          'required' => [
            ':input[name="company"]' => ['filled' => TRUE],
          ],
          'visible' => [
            ':input[name="company"]' => ['filled' => TRUE],
          ],
          'invisible' => [
            ':input[name="company"]' => ['filled' => FALSE],
          ],
        ],
        '#weight' => 40,
      ],
      'address' => [
        '#type' => 'textfield',
        '#placeholder' => $this->t('Address', [], ['context' => 'arch_onepage']),
        '#title' => $this->t('Address', [], ['context' => 'arch_onepage']),
        '#description' => $this->t('Street name, house number', [], ['context' => 'arch_onepage']),
        '#required' => TRUE,
        '#weight' => 50,
      ],
      'address2' => [
        '#type' => 'textfield',
        '#placeholder' => $this->t('Address (optional)', [], ['context' => 'arch_onepage']),
        '#title' => $this->t('Address (optional)', [], ['context' => 'arch_onepage']),
        '#description' => $this->t('Building, unit, floor, door number', [], ['context' => 'arch_onepage']),
        '#required' => FALSE,
        '#weight' => 60,
      ],
      'postcode' => [
        '#type' => 'textfield',
        '#placeholder' => $this->t('Postcode', [], ['context' => 'arch_onepage']),
        '#title' => $this->t('Postcode', [], ['context' => 'arch_onepage']),
        '#description' => $this->t('Type your post code here, e.g.: 1042', [], ['context' => 'arch_onepage']),
        '#required' => TRUE,
        '#weight' => 70,
      ],
      'country' => [
        '#type' => 'select',
        '#placeholder' => $this->t('Country', [], ['context' => 'arch_onepage']),
        '#title' => $this->t('Country', [], ['context' => 'arch_onepage']),
        '#options' => $this->getAvailableCountries(),
        '#default_value' => 'HU',
        '#required' => TRUE,
        '#weight' => 80,
      ],
      'city' => [
        '#type' => 'textfield',
        '#placeholder' => $this->t('City', [], ['context' => 'arch_onepage']),
        '#title' => $this->t('City', [], ['context' => 'arch_onepage']),
        '#required' => TRUE,
        '#weight' => 90,
      ],
    ];
    // @codingStandardsIgnoreEnd
  }

  /**
   * Payment subform.
   *
   * @return array
   *   Subform.
   */
  private function paymentForm() {
    $payment = [
      '#type' => 'fieldset',
      '#title' => $this->t('Payment', [], ['context' => 'arch_onepage']),
      '#collapsed' => TRUE,
      '#collapsible' => FALSE,
      '#attributes' => [
        'class' => [
          'checkout-fieldset',
          'checkout-payment-method',
          'payment-method',
        ],
      ],
    ];

    $payment['payment_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose a payment method', [], ['context' => 'arch_onepage']),
      '#options' => $this->getAvailablePaymentMethodsOptions(),
      '#required' => TRUE,
      '#attributes' => [
        'required' => 'required',
      ],
      '#ajax' => [
        'event' => 'change',
        'callback' => [$this, 'changePaymentMethod'],
        'wrapper' => 'onepage-checkout-form-wrapper',
      ],
    ];

    $payment['note'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Note', [], ['context' => 'arch_onepage']),
    ];

    return $payment;
  }

  /**
   * Get shipping methods for Onepage Checkout form.
   *
   * @return array
   *   Array of options with shipping methods.
   */
  private function getShippingMethods() {
    $shipping_methods = $this->shippingMethodManager->getAvailableShippingMethods($this->order);

    $methods = [];
    foreach ($shipping_methods as $shipping_method) {
      $methods[$shipping_method->getPluginId()] = $shipping_method->getLabel();
    }

    return $methods;
  }

  /**
   * Get a list of available countries to ship.
   *
   * @return array
   *   Array of countries.
   */
  private function getAvailableCountries() {
    // @todo These should comes from settings.
    return [
      'HU' => $this->t('Hungary'),
    ];
  }

  /**
   * Get payment methods for Onepage Checkout form.
   *
   * @return array
   *   Array of options with payment methods.
   */
  private function getAvailablePaymentMethods() {
    return $this->paymentMethodManager->getAvailablePaymentMethods($this->order);
  }

  /**
   * Get payment method options for Onepage Checkout form.
   *
   * @return array
   *   Array of options with payment methods.
   */
  private function getAvailablePaymentMethodsOptions() {
    $methods = [];
    foreach ($this->getAvailablePaymentMethods() as $method) {
      /** @var \Drupal\arch_payment\PaymentMethodInterface $method */
      $methods[$method->getPluginId()] = $method->getLabel();
    }

    return $methods;
  }

  /**
   * Change payment method AJAX callback.
   *
   * @param array $form
   *   Form data.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state data.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response to payment method change.
   */
  public function changePaymentMethod(array &$form, FormStateInterface $form_state) {
    // @todo Should we check here for the available shipping methods after we
    // have changed the payment method.
    $response = new AjaxResponse();
    $response->addCommand(
      new InvokeCommand('body', 'trigger', ['checkout.onepage.phaseRecheck'])
    );

    return $response;
  }

  /**
   * Change shipping method AJAX callback.
   *
   * @param array $form
   *   Form data.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state data.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response to shipping method change.
   */
  public function changeShippingMethod(array &$form, FormStateInterface $form_state) {
    $method = $form_state->getValue('shipping_methods');
    $method = Xss::filter($method);
    $shipping_method = $this->shippingMethodManager->getShippingMethod($method);
    $this->order->setShippingMethod($shipping_method);

    $response = new AjaxResponse();

    $this->moduleHandler->invokeAll(
      'shipping_method_changed',
      [
        $shipping_method,
        $response,
        $this->cart,
      ]
    );

    return $response;
  }

  /**
   * Create user from Personal Data.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return bool|\Drupal\user\Entity\User
   *   User object, or FALSE when error occured.
   */
  private function createUser(FormStateInterface $form_state) {
    // @todo TEST this.
    /** @var \Drupal\user\Entity\User $user */
    $user = User::create();
    $user->setEmail($form_state->getValue('email'));

    $username = substr($form_state->getValue('email'), 0, strpos($form_state->getValue('email'), '@'));
    $username = str_replace('+', '_', $username);
    $_username = $username . '_' . mt_rand(999, 99999);
    while (empty($this->userStorage->loadByProperties(['name' => $_username]))) {
      $_username = $username . '_' . mt_rand(999, 99999);
    }
    $user->setUsername($_username);

    $rnd = new Random();
    $pwd = $rnd->name(12);
    $user->setPassword($pwd);

    $user->activate();
    $user->enforceIsNew();
    try {
      $user->save();

      // Login in user programmatically.
      // @codingStandardsIgnoreStart
      user_login_finalize($user);
      // @codingStandardsIgnoreEnd

      // Send email to user about details.
      // @codingStandardsIgnoreStart
      _user_mail_notify('register_no_approval_required', $user);
      // @codingStandardsIgnoreEnd

      return $user;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * A new shipping address form fields.
   *
   * @return array
   *   Form fields.
   */
  private function newShippingAddressForm() {
    $exclude = ['tax_number'];
    $addressform = [];
    foreach ($this->getAddressFields() as $field_name => $field_descriptor) {
      if (in_array($field_name, $exclude)) {
        continue;
      }

      $addressform['shipping_' . $field_name] = $field_descriptor;
      $addressform['shipping_' . $field_name]['#required'] = FALSE;
      $addressform['shipping_' . $field_name]['#attributes']['data-field'] = 'shipping-' . $field_name;

      if (isset($addressform['shipping_' . $field_name]['#required'])) {
        $addressform['shipping_' . $field_name]['#states'] = [
          'required' => [
            ':input[name="shipping_address_selector"]' => ['value' => 'new_shipping'],
          ],
        ];
      }
    }

    return $addressform;
  }

  /**
   * Collect addresses from modules to build a select options.
   *
   * @return array
   *   Select options array.
   */
  private function getAddressListOptions() {
    // @todo Rewrite to use address service to get user's addresses.
    $addresses = $this->moduleHandler->invokeAll('commerce_shipping_addresses');
    $this->moduleHandler->alter('commerce_shipping_addresses', $addresses);

    $options = [];
    foreach ($addresses as $id => $address) {
      $options[$id] = $address;
    }

    return $options;
  }

  /**
   * Build billing address data.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\arch_order\OrderAddressDataInterface
   *   Order address instance.
   */
  protected function buildBillingAddress(FormStateInterface $form_state) {
    $phone = $form_state->getValue('phone_prefix') . $form_state->getValue('phone_number');
    $data = [
      'country_code' => $form_state->getValue('country'),
      'locality' => $form_state->getValue('city'),
      'postal_code' => $form_state->getValue('postcode'),
      'address_line1' => $form_state->getValue('address'),
      'address_line3' => $form_state->getValue('address2'),

      'organization' => $form_state->getValue('company'),
      'given_name' => $form_state->getValue('firstname'),
      'family_name' => $form_state->getValue('lastname'),

      'tax_id' => $form_state->getValue('tax_number'),
      'phone' => $phone,
    ];
    return new OrderAddressData($data);
  }

  /**
   * Build shipping address data.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\arch_order\OrderAddressDataInterface
   *   Order address instance.
   */
  protected function buildShippingAddress(FormStateInterface $form_state) {
    $phone = $form_state->getValue('phone_prefix') . $form_state->getValue('phone_number');
    $data = [
      'country_code' => $form_state->getValue('shipping_country'),
      'locality' => $form_state->getValue('shipping_city'),
      'postal_code' => $form_state->getValue('shipping_postcode'),
      'address_line1' => $form_state->getValue('shipping_address'),
      'address_line2' => $form_state->getValue('shipping_address2'),

      'organization' => $form_state->getValue('shipping_company'),
      'given_name' => $form_state->getValue('shipping_firstname'),
      'family_name' => $form_state->getValue('shipping_lastname'),

      'tax_id' => $form_state->getValue('tax_number'),
      'phone' => $phone,
    ];
    return new OrderAddressData($data);
  }

  /**
   * Get selected shipping address as OrderAddressDataInterface.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\arch_order\OrderAddressDataInterface|null
   *   OrderAddressDataInterface instance or NULL on failure.
   */
  protected function loadSelectedShippingAddress(FormStateInterface $form_state) {
    if (!$this->hasAddressbook()) {
      return NULL;
    }

    $selection = $form_state->getValue('choose_address');
    [$entity_type, $entity_id] = explode(':', $selection);
    if (
      $entity_type !== 'addressbookitem'
      || empty($entity_id)
    ) {
      return NULL;
    }

    /** @var \Drupal\arch_addressbook\AddressbookitemInterface[] $addressbookitems */
    $addressbookitems = $this->getAddressbookItemStorage()->loadByProperties([
      'user_id' => $this->order->getOwnerId(),
      'id' => $entity_id,
    ]);

    if (empty($addressbookitems)) {
      return NULL;
    }

    $addressbookitem = current($addressbookitems);
    return $addressbookitem->toOrderAddress();
  }

  /**
   * Check if addressbook feature is available for customers.
   *
   * @return bool
   *   Returns TRUE if addressbook feature is available.
   */
  protected function hasAddressbook() {
    return $this->moduleHandler->moduleExists('arch_addressbook');
  }

  /**
   * Get addressbook item storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAddressbookItemStorage() {
    return $this->entityTypeManager->getStorage('addressbookitem');
  }

}
