<?php

namespace Drupal\arch_order\Entity;

use Drupal\arch_cart\Cart\CartInterface;
use Drupal\arch_order\OrderAddressDataInterface;
use Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemInterface;
use Drupal\arch_order\Services\OrderAddressServiceInterface;
use Drupal\arch_payment\PaymentMethodInterface;
use Drupal\arch_price\Price\ModifiedPriceInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\arch_product\Entity\Product;
use Drupal\arch_shipping\ShippingMethodInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the order entity class.
 *
 * @ingroup order
 *
 * @ContentEntityType(
 *   id = "order",
 *   label = @Translation("Order", context = "arch_order"),
 *   label_collection = @Translation("Orders", context = "arch_order"),
 *   label_singular = @Translation("order", context = "arch_order"),
 *   label_plural = @Translation("orders", context = "arch_order"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order",
 *     plural = "@count orders",
 *     context = "arch_order"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\arch_order\Entity\Storage\OrderStorage",
 *     "storage_schema" = "Drupal\arch_order\Entity\Storage\OrderStorageSchema",
 *     "view_builder" = "Drupal\arch_order\Entity\Builder\OrderViewBuilder",
 *     "access" = "Drupal\arch_order\Access\OrderAccessControlHandler",
 *     "views_data" = "Drupal\arch_order\Entity\Views\OrderViewsData",
 *     "form" = {
 *       "default" = "Drupal\arch_order\Form\OrderForm",
 *       "add" = "Drupal\arch_order\Form\OrderForm",
 *       "edit" = "Drupal\arch_order\Form\OrderForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\arch_order\Routing\OrderRouteProvider",
 *     },
 *     "list_builder" = "Drupal\arch_order\Entity\Builder\OrderListBuilder",
 *   },
 *   base_table = "arch_order",
 *   revision_table = "arch_order_revision",
 *   show_revision_ui = TRUE,
 *   common_reference_target = TRUE,
 *   field_ui_base_route = "entity.order.admin_form",
 *   translatable = FALSE,
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "collection" = "/admin/store/orders",
 *     "canonical" = "/order/{order}",
 *     "add-form" = "/admin/store/order/add",
 *     "edit-form" = "/admin/store/order/{order}/edit",
 *     "version-history" = "/order/{order}/revisions",
 *     "revision" = "/order/{order}/revisions/{order_revision}/view"
 *   },
 *   entity_keys = {
 *     "id" = "oid",
 *     "label" = "order_number",
 *     "revision" = "vid",
 *     "status" = "status",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Order extends RevisionableContentEntityBase implements OrderInterface {

  use EntityChangedTrait;

  /**
   * Order address service.
   *
   * @var \Drupal\arch_order\Services\OrderAddressServiceInterface
   */
  protected $orderAddressService;

  /**
   * Shipping method selected for the order.
   *
   * @var \Drupal\arch_shipping\ShippingMethodInterface
   */
  protected $shippingMethod;

  /**
   * Payment method selected for the order.
   *
   * @var \Drupal\arch_payment\PaymentMethodInterface
   */
  protected $paymentMethod;

  /**
   * Billing address.
   *
   * @var \Drupal\arch_order\OrderAddressDataInterface
   */
  protected $billingAddress;

  /**
   * Shipping address.
   *
   * @var \Drupal\arch_order\OrderAddressDataInterface
   */
  protected $shippingAddress;

  /**
   * {@inheritdoc}
   */
  public static function createFromCart(CartInterface $cart) {
    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = NULL;

    // @todo Figure out something cool for this.
    $order_number = time();

    $order_prices = [
      'currency' => NULL,
      'subtotal_net' => 0.0,
      'subtotal_gross' => 0.0,
      'subtotal_vat_amount' => 0.0,
      'grandtotal_net' => 0.0,
      'grandtotal_gross' => 0.0,
      'grandtotal_vat_amount' => 0.0,
    ];

    $line_items = [];
    foreach ($cart->getItems() as $item) {
      if ($item['type'] == 'product') {
        $product = Product::load($item['id']);
        // Skip item if not a valid product.
        if (!$product) {
          continue;
        }

        /** @var \Drupal\arch_price\Price\PriceInterface $price */
        $price = $product->getActivePrice();

        if (empty($currency)) {
          $currency = $price->getCurrency();
          $order_prices['currency'] = $currency->id();
        }
        elseif ($price->getCurrencyId() !== $currency->id()) {
          $price = $price->getExchangedPrice($currency);
        }

        // @todo Type value should not be fixed here.
        $line_item = [
          'type' => OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_PRODUCT,
          'product_id' => $product->id(),
          'quantity' => $item['quantity'],
          'product_bundle' => $product->bundle(),
          'price_net' => $price->getNetPrice(),
          'price_gross' => $price->getGrossPrice(),
          'price_vat_rate' => $price->getVatRate(),
          'price_vat_amount' => $price->getVatValue(),
          'price_vat_cat_name' => $price->getVatCategoryId(),
          'calculated_net' => $price->getNetPrice(),
          'calculated_gross' => $price->getGrossPrice(),
          'calculated_vat_rate' => $price->getVatRate(),
          'calculated_vat_amount' => $price->getVatValue(),
          'calculated_vat_cat_name' => $price->getVatCategoryId(),
          'reason_of_diff' => $price->getReasonOfDifference(),
          'data' => '',
        ];
        if ($price instanceof ModifiedPriceInterface) {
          $original_price = $price->getOriginalPrice();
          $line_item['price_net'] = $original_price->getNetPrice();
          $line_item['price_gross'] = $original_price->getGrossPrice();
          $line_item['price_vat_rate'] = $original_price->getVatRate();
          $line_item['price_vat_amount'] = $original_price->getVatValue();
          $line_item['price_vat_cat_name'] = $original_price->getVatCategoryId();
        }

        // Subtotals only contains values from product line items.
        // Otherwise, there would not be no difference between them.
        $order_prices['subtotal_net'] += $line_item['quantity'] * $line_item['calculated_net'];
        $order_prices['subtotal_gross'] += $line_item['quantity'] * $line_item['calculated_gross'];
        $order_prices['subtotal_vat_amount'] += $line_item['quantity'] * $line_item['calculated_vat_amount'];
      }
      else {
        // @todo This value should not be fixed here.
        $type = OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_DISCOUNT;
        if ($item['type'] === 'shipping') {
          $type = OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_SHIPPING;
        }

        $line_item = [
          'type' => $type,
          'product_id' => 1,
          'product_bundle' => 1,
          'price_net' => 0,
          'price_gross' => 0,
          'price_vat_rate' => 0,
          'price_vat_amount' => 0,
          'price_vat_cat_name' => 0,
          'calculated_net' => 0,
          'calculated_gross' => 0,
          'calculated_vat_rate' => 0,
          'calculated_vat_amount' => 0,
          'calculated_vat_cat_name' => 0,
          'reason_of_diff' => '',
          'data' => '',
        ];
      }

      $order_prices['grandtotal_net'] += $line_item['quantity'] * $line_item['calculated_net'];
      $order_prices['grandtotal_gross'] += $line_item['quantity'] * $line_item['calculated_gross'];
      $order_prices['grandtotal_vat_amount'] += $line_item['quantity'] * $line_item['calculated_vat_amount'];

      $line_items[] = $line_item;
    }

    $data = [
      'order_number' => $order_number,
      'line_items' => $line_items,
    ] + $order_prices;

    if (empty($data['currency'])) {
      /** @var \Drupal\arch_price\Manager\PriceTypeManagerInterface $price_type_manager */
      $price_type_manager = \Drupal::service('price_type.manager');
      $default_price_type = $price_type_manager->getDefaultPriceType();
      $data['currency'] = $default_price_type->getDefaultCurrency();
    }

    \Drupal::moduleHandler()->alter(
      'arch_order_create_from_cart_data',
      $data,
      $cart
    );

    // DO NOT SAVE HERE!
    return static::create($data);
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    foreach ($entities as $entity) {
      $entity->setCalculatedValues();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    $this->setCalculatedValues();
  }

  /**
   * Set calculated values.
   */
  public function setCalculatedValues() {
    if ($this->id()) {
      $this->billingAddress = $this->getOrderAddressService()->getByType($this->id(), OrderAddressServiceInterface::TYPE_BILLING);
      if ($this->billingAddress) {
        $this->set('billing_address', $this->billingAddress->toArray());
      }

      $this->shippingAddress = $this->getOrderAddressService()->getByType($this->id(), OrderAddressServiceInterface::TYPE_SHIPPING);
      if ($this->shippingAddress) {
        $this->set('shipping_address', $this->shippingAddress->toArray());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($this->billingAddress) {
      if (!$this->billingAddress->getOrderId()) {
        $this->billingAddress->setOrderId($this->id());
      }
      if (!$update) {
        $this->getOrderAddressService()->insertAddress(OrderAddressServiceInterface::TYPE_BILLING, $this->billingAddress);
      }
      else {
        $this->getOrderAddressService()->updateAddress($this->billingAddress);
      }
    }

    if ($this->shippingAddress) {
      if (!$this->shippingAddress->getOrderId()) {
        $this->shippingAddress->setOrderId($this->id());
      }
      if (!$update) {
        $this->getOrderAddressService()->insertAddress(OrderAddressServiceInterface::TYPE_SHIPPING, $this->shippingAddress);
      }
      else {
        $this->getOrderAddressService()->updateAddress($this->shippingAddress);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['order_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order number', [], ['context' => 'arch_order']))
      ->addConstraint('UniqueField', [])
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 56)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['erp_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ERP ID', [], ['context' => 'arch_order']))
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('order_status')
      ->setLabel(t('Order status', [], ['context' => 'arch_order']))
      ->setDescription(t('The current status of the Order.', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'order_status',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'order_statuses_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Customer ID', [], ['context' => 'arch_order']))
      ->setDescription(t('The (customer) user ID of the order.', [], ['context' => 'arch_order']))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback('Drupal\arch_order\Entity\Order::getCurrentUserId')
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['langcode']
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['payment_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment method', [], ['context' => 'arch_order']))
      ->setDescription(t('The payment method the Order has been paid.', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['shipping_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Shipping method', [], ['context' => 'arch_order']))
      ->setDescription(t('The shipping method with which the order was delivered.', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['subtotal_net'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Subtotal net', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
        'settings' => [
          'thousand_separator' => ' ',
          'decimal_separator' => '.',
          'scale' => 3,
        ],
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number_decimal',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['subtotal_gross'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Subtotal gross', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
        'settings' => [
          'thousand_separator' => ' ',
          'decimal_separator' => '.',
          'scale' => 3,
        ],
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number_decimal',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['subtotal_vat_amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Subtotal VAT amount', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
        'settings' => [
          'thousand_separator' => ' ',
          'decimal_separator' => '.',
          'scale' => 3,
        ],
        'weight' => 9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number_decimal',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['grandtotal_net'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Grandtotal net', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
        'settings' => [
          'thousand_separator' => ' ',
          'decimal_separator' => '.',
          'scale' => 3,
        ],
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number_decimal',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['grandtotal_gross'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Grandtotal gross', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
        'settings' => [
          'thousand_separator' => ' ',
          'decimal_separator' => '.',
          'scale' => 3,
        ],
        'weight' => 11,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number_decimal',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['grandtotal_vat_amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Grandtotal VAT amount', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
        'settings' => [
          'thousand_separator' => ' ',
          'decimal_separator' => '.',
          'scale' => 3,
        ],
        'weight' => 12,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number_decimal',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency code', [], ['context' => 'arch_order']))
      ->setDescription(t('The currency code the Order has been paid. Use ISO 4217 alphabetic code (e.g.: USD, EUR, HUF etc.) for this field.', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 5)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 13,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on', [], ['context' => 'arch_order']))
      ->setDescription(t('The time that the order was created. Typically, when a cart assigned to a user.', [], ['context' => 'arch_order']))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 14,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed', [], ['context' => 'arch_order']))
      ->setDescription(t('The time that the order was last edited.', [], ['context' => 'arch_order']))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['billing_address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Billing address', [], ['context' => 'arch_order']))
      ->setComputed(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'address_default',
        'weight' => 15,
      ])
      ->setDisplayOptions('form', [
        'type' => 'address_default',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['shipping_address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Shipping address', [], ['context' => 'arch_order']))
      ->setComputed(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'address_default',
        'weight' => 16,
      ])
      ->setDisplayOptions('form', [
        'type' => 'address_default',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['line_items'] = BaseFieldDefinition::create('order_line_item')
      ->setLabel(t('Line items', [], ['context' => 'arch_order']))
      ->setDescription(t('The line items of the order.', [], ['context' => 'arch_order']))
      ->setRequired(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'order_line_item_formatter',
        'weight' => 20,
      ])
      ->setDisplayOptions('form', [
        'type' => 'order_line_item_widget',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data', [], ['context' => 'arch_order']))
      ->setDescription(t('A serialized array of additional data.', [], ['context' => 'arch_order']));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing product without adding a new revision,
      // we need to make sure $entity->revision_log is reset whenever it is
      // empty. Therefore, this code allows us to avoid clobbering an existing
      // log entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItemsCount() {
    return $this->get('line_items')->count();
  }

  /**
   * {@inheritdoc}
   */
  public function getProductsCount() {
    return count($this->getProducts());
  }

  /**
   * {@inheritdoc}
   */
  public function filterLineItems($callback) {
    /** @var \Drupal\Core\Field\FieldItemList $line_items */
    $line_items = $this->get('line_items');
    $result = [];
    foreach ($line_items as $item) {
      /** @var \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem $item */
      if (call_user_func($callback, $item)) {
        $result[] = $item;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getProducts() {
    return $this->filterLineItems(function ($item) {
      /** @var \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem $item */
      $value = (int) $item->get('type')->getValue();
      return OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_PRODUCT === $value;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingPrices() {
    return $this->filterLineItems(function ($item) {
      /** @var \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem $item */
      $value = (int) $item->get('type')->getValue();
      return OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_SHIPPING === $value;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getDiscounts() {
    return $this->filterLineItems(function ($item) {
      /** @var \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem $item */
      $value = (int) $item->get('type')->getValue();
      return OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_DISCOUNT === $value;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    if ($this->get('data')->isEmpty()) {
      return [];
    }

    return $this->get('data')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->get('data')->set(0, $data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataKey($key, $default = NULL) {
    $data = $this->getData();
    return array_key_exists($key, $data) ? $data[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataKey($key, $value) {
    $val = [];
    if (!is_null($this->get('data')->first())) {
      $val = $this->get('data')->first()->getValue();
    }

    if (isset($value)) {
      $val[$key] = $value;
    }
    else {
      unset($val[$key]);
    }
    return $this->setData($val);
  }

  /**
   * {@inheritdoc}
   */
  public function setShippingMethod(ShippingMethodInterface $shipping_method) {
    $this->shippingMethod = $shipping_method;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingMethod() {
    return $this->shippingMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethod(PaymentMethodInterface $payment_method) {
    $this->paymentMethod = $payment_method;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return $this->paymentMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderAddressService(OrderAddressServiceInterface $order_address_service) {
    $this->orderAddressService = $order_address_service;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderAddressService() {
    if (!$this->orderAddressService) {
      // @codingStandardsIgnoreStart
      $this->orderAddressService = \Drupal::service('order.address');
      // @codingStandardsIgnoreEnd
    }
    return $this->orderAddressService;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingAddress($address = NULL) {
    if ($address instanceof OrderAddressDataInterface) {
      $this->billingAddress = $address;
    }
    elseif (!isset($address)) {
      $this->billingAddress = NULL;
    }
    else {
      throw new \InvalidArgumentException();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingAddress() {
    return $this->billingAddress;
  }

  /**
   * {@inheritdoc}
   */
  public function setShippingAddress($address = NULL) {
    if ($address instanceof OrderAddressDataInterface) {
      $this->shippingAddress = $address;
    }
    elseif (!isset($address)) {
      $this->shippingAddress = NULL;
    }
    else {
      throw new \InvalidArgumentException();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingAddress() {
    return $this->shippingAddress;
  }

  /**
   * {@inheritdoc}
   */
  public function setShippingPrice(PriceInterface $price, $method_id) {
    $original_shipping_price = $price;
    if ($price instanceof ModifiedPriceInterface) {
      $original_shipping_price = $price->getOriginalPrice();
    }

    $shipping_line_item = [
      'type' => OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_SHIPPING,
      'product_id' => 0,
      'quantity' => 1,
      'product_bundle' => $method_id,
      'price_net' => $original_shipping_price->getNetPrice(),
      'price_gross' => $original_shipping_price->getGrossPrice(),
      'price_vat_rate' => $original_shipping_price->getVatRate(),
      'price_vat_amount' => $original_shipping_price->getVatValue(),
      'price_vat_cat_name' => $original_shipping_price->getVatCategoryId(),
      'calculated_net' => $price->getNetPrice(),
      'calculated_gross' => $price->getGrossPrice(),
      'calculated_vat_rate' => $price->getVatRate(),
      'calculated_vat_amount' => $price->getVatValue(),
      'calculated_vat_cat_name' => $price->getVatCategoryId(),
      'reason_of_diff' => NULL,
      'data' => NULL,
      'created' => NULL,
    ];

    /** @var \Drupal\Core\Field\FieldItemList $line_items */
    $line_items = $this->get('line_items');
    $line_items->appendItem($shipping_line_item);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentFee(PriceInterface $price, $method_id) {
    $original_fee = $price;
    if ($price instanceof ModifiedPriceInterface) {
      $original_fee = $price->getOriginalPrice();
    }

    $fee_line_item = [
      'type' => OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_PAYMENT_FEE,
      'product_id' => 0,
      'quantity' => 1,
      'product_bundle' => $method_id,
      'price_net' => $original_fee->getNetPrice(),
      'price_gross' => $original_fee->getGrossPrice(),
      'price_vat_rate' => $original_fee->getVatRate(),
      'price_vat_amount' => $original_fee->getVatValue(),
      'price_vat_cat_name' => $original_fee->getVatCategoryId(),
      'calculated_net' => $price->getNetPrice(),
      'calculated_gross' => $price->getGrossPrice(),
      'calculated_vat_rate' => $price->getVatRate(),
      'calculated_vat_amount' => $price->getVatValue(),
      'calculated_vat_cat_name' => $price->getVatCategoryId(),
      'reason_of_diff' => NULL,
      'data' => NULL,
      'created' => NULL,
    ];

    $this->get('line_items')->appendItem($fee_line_item);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateTotal() {
    $order_prices = [
      'subtotal_net' => 0.0,
      'subtotal_gross' => 0.0,
      'subtotal_vat_amount' => 0.0,
      'grandtotal_net' => 0.0,
      'grandtotal_gross' => 0.0,
      'grandtotal_vat_amount' => 0.0,
    ];

    /** @var \Drupal\Core\Field\FieldItemList $line_items */
    $line_items = $this->get('line_items');

    /** @var \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemInterface $line_item */
    foreach ($line_items as $line_item) {
      $quantity = $line_item->getQuantity();

      if ($line_item->getLineItemTypeId() == OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_PRODUCT) {
        $order_prices['subtotal_net'] += $quantity * $line_item->get('calculated_net')->getValue();
        $order_prices['subtotal_gross'] += $quantity * $line_item->get('calculated_gross')->getValue();
        $order_prices['subtotal_vat_amount'] += $quantity * $line_item->get('calculated_vat_amount')->getValue();
      }

      $order_prices['grandtotal_net'] += $quantity * $line_item->get('calculated_net')->getValue();
      $order_prices['grandtotal_gross'] += $quantity * $line_item->get('calculated_gross')->getValue();
      $order_prices['grandtotal_vat_amount'] += $quantity * $line_item->get('calculated_vat_amount')->getValue();
    }
    foreach ($order_prices as $price_name => $price_value) {
      $this->set($price_name, $price_value);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusId() {
    return $this->get('status')->entity->id();
  }

}
