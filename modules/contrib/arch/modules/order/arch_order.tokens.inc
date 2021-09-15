<?php
/**
 * @file
 * Builds placeholder replacement tokens for order-related data.
 */

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemInterface;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\arch_price\Price\PriceFormatterInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function arch_order_token_info() {
  $tokens = [];

  // Order.
  $tokens['types']['order'] = [
    'name' => t('Orders', [], ['context' => 'arch_order']),
    'description' => t('Tokens related to individual orders.', [], ['context' => 'arch_order']),
    'needs-data' => 'order',
  ];

  // Core tokens for orders.
  $order['oid'] = [
    'name' => t('Order ID', [], ['context' => 'arch_order']),
    'description' => t('The unique ID of the order.', [], ['context' => 'arch_order']),
  ];
  $order['vid'] = [
    'name' => t('Revision ID'),
    'description' => t("The unique ID of the order's latest revision.", [], ['context' => 'arch_order']),
  ];
  $order['status'] = [
    'name' => t('Order status', [], ['context' => 'arch_order']),
  ];
  $order['status-name'] = [
    'name' => t('Order status name', [], ['context' => 'arch_order']),
    'description' => t('The human-readable name of the order status.', [], ['context' => 'arch_order']),
  ];

  $order['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the order is written in.', [], ['context' => 'arch_order']),
  ];
  $order['url'] = [
    'name' => t('URL'),
    'description' => t('The URL of the order.', [], ['context' => 'arch_order']),
  ];
  $order['edit-url'] = [
    'name' => t('Edit URL'),
    'description' => t("The URL of the order's edit page.", [], ['context' => 'arch_order']),
  ];
  $order['order-number'] = [
    'name' => t('Order number', [], ['context' => 'arch_order']),
    'description' => t('The number of the order.', [], ['context' => 'arch_order']),
  ];
  $order['payment-method'] = [
    'name' => t('Payment method', [], ['context' => 'arch_order']),
    'description' => t('Payment method', [], ['context' => 'arch_order']),
  ];
  $order['shipping-method'] = [
    'name' => t('Shipping method', [], ['context' => 'arch_order']),
    'description' => t('Shipping method', [], ['context' => 'arch_order']),
  ];
  $order['order-note'] = [
    'name' => t('Order note', [], ['context' => 'arch_order']),
    'description' => t('The note of the order.', [], ['context' => 'arch_order']),
  ];
  $order['line-items-table'] = [
    'name' => t('Line items (formatter)', [], ['context' => 'arch_order']),
    'description' => t('Line items in table format.', [], ['context' => 'arch_order']),
  ];

  // Chained tokens for orders.
  $order['created'] = [
    'name' => t('Date created', [], ['context' => 'arch_order']),
    'type' => 'date',
  ];
  $order['changed'] = [
    'name' => t('Date changed', [], ['context' => 'arch_order']),
    'description' => t('The date the order was most recently updated.', [], ['context' => 'arch_order']),
    'type' => 'date',
  ];
  $order['owner'] = [
    'name' => t('Customer', [], ['context' => 'arch_order']),
    'type' => 'user',
  ];

  $tokens['tokens']['order'] = $order;

  return $tokens;
}

/**
 * Implements hook_tokens().
 */
function arch_order_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
  $date_formatter = \Drupal::service('date.formatter');

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = [];

  if ($type == 'order' && !empty($data['order'])) {
    /** @var \Drupal\arch_order\Entity\OrderInterface $order */
    $order = $data['order'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the order.
        case 'oid':
          $replacements[$original] = $order->id();
          break;

        case 'vid':
          $replacements[$original] = $order->getRevisionId();
          break;

        case 'status':
          $replacements[$original] = $order->getStatus();
          break;

        case 'order-number':
          $replacements[$original] = $order->get('order_number')->getString();
          break;

        case 'payment-method':
          if ($order->getPaymentMethod()) {
            $replacements[$original] = $order->getPaymentMethod()->getLabel();
          }
          if (
            !$order->get('payment_method')->isEmpty()
            && \Drupal::hasService('plugin.manager.payment_method')
          ) {
            $payment_method_id = $order->get('payment_method')->getString();

            /** @var \Drupal\arch_payment\PaymentMethodManagerInterface $payment_method_manager */
            $payment_method_manager = \Drupal::service('plugin.manager.payment_method');
            $method = $payment_method_manager->getPaymentMethod($payment_method_id);
            if (!empty($method)) {
              $replacements[$original] = $method->getLabel();
            }
          }
          break;

        case 'shipping-method':
          if ($order->getShippingMethod()) {
            $replacements[$original] = $order->getShippingMethod()->getLabel();
          }
          if (
            !$order->get('shipping_method')->isEmpty()
            && \Drupal::hasService('plugin.manager.shipping_method')
          ) {
            $payment_method_id = $order->get('shipping_method')->getString();

            /** @var \Drupal\arch_shipping\ShippingMethodManagerInterface $shipping_method_manager */
            $shipping_method_manager = \Drupal::service('plugin.manager.shipping_method');
            $method = $shipping_method_manager->getShippingMethod($payment_method_id);
            if (!empty($method)) {
              $replacements[$original] = $method->getLabel();
            }
          }
          break;

        case 'order-note':
          $order_data = $order->getData();
          $replacements[$original] = !empty($order_data['note']) ? $order_data['note'] : '';
          break;

        case 'line-items-table':
          try {
            $line_items_table = arch_order_tokens_line_item_table($order);
            if (!empty($line_items_table)) {
              $replacements[$original] = $line_items_table;
            }
          }
          catch (\Exception $e) {
            $replacements[$original] = '';
          }
          break;

        case 'status-name':
          $status_name = $order->getStatus()->getLabel();
          $replacements[$original] = $status_name;
          break;

        case 'langcode':
          $replacements[$original] = $order->language()->getId();
          break;

        case 'url':
          $replacements[$original] = $order->toUrl('canonical', $url_options)->toString();
          break;

        case 'edit-url':
          $replacements[$original] = $order->toUrl('edit-form', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case 'owner':
          $account = $order->getOwner() ? $order->getOwner() : User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = $date_formatter->format($order->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = $date_formatter->format($order->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($owner_tokens = $token_service->findWithPrefix($tokens, 'owner')) {
      $replacements += $token_service->generate('user', $owner_tokens, ['user' => $order->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $order->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $order->getChangedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}

/**
 * Gets a rendered order line items table.
 *
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Order object.
 *
 * @return string
 *   Rendered order line items table.
 *
 * @throws \Drupal\Core\TypedData\Exception\MissingDataException
 */
function arch_order_tokens_line_item_table(OrderInterface $order) {
  if ($order->get('line_items')->isEmpty()) {
    return '';
  }

  /** @var \Drupal\Core\Render\RendererInterface $renderer */
  $renderer = \Drupal::service('renderer');
  /** @var \Drupal\arch_price\Price\PriceFactoryInterface $price_factory */
  $price_factory = \Drupal::service('price_factory');
  /** @var \Drupal\arch_price\Price\PriceFormatterInterface $price_formatter */
  $price_formatter = \Drupal::service('price_formatter');

  $cell_style = 'padding:10px 20px';

  $shipping = 0;
  $discount = 0;
  $paymentfee = 0;

  $subtotal = 0;

  $rows = [];
  foreach ($order->get('line_items') as $line_item) {
    /** @var \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemInterface $line_item */

    if ($line_item->isShipping()) {
      $shipping = (float) $line_item->get('calculated_gross')->getValue();
      continue;
    }

    if ($line_item->isDiscount()) {
      $discount = (float) $line_item->get('calculated_gross')->getValue();
      continue;
    }

    if ($line_item->isPaymentFee()) {
      $paymentfee = (float) $line_item->get('calculated_gross')->getValue();
      continue;
    }

    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $line_item->getProduct();
    if (empty($product)) {
      continue;
    }

    $subtotal += (float) $line_item->get('calculated_gross')->getValue() * (float) $line_item->getQuantity();
    $formatted = arch_order_tokens__renderable_formatted_line_item_price($price_factory, $price_formatter, $order, $line_item);
    $renderer->renderPlain($formatted);

    $row = [
      [
        'data' => $product->label() . ' (' . $product->getSku() . ')',
        'style' => $cell_style . ';text-align:left',
      ],
      [
        'data' => $line_item->getQuantity(),
        'style' => $cell_style . ';text-align:right',
      ],
      [
        'data' => $formatted,
        'style' => $cell_style . ';text-align:right',
      ],
    ];
    $rows[] = [
      'data' => $row,
      'style' => 'border-top:1px solid #efefef',
    ];
  }

  $extra_rows = [
    [
      'label' => t('Shipping', [], ['context' => 'arch_order_token']),
      'value' => $shipping,
    ],
    [
      'label' => t('Discount', [], ['context' => 'arch_order_token']),
      'value' => $discount,
    ],
    [
      'label' => t('Payment fee', [], ['context' => 'arch_order_token']),
      'value' => $paymentfee,
    ],
  ];
  foreach ($extra_rows as $extra_row) {
    if (empty($extra_row['value'])) {
      continue;
    }

    $formatted = arch_order_tokens__renderable_formatted_price($price_factory, $price_formatter, $order, $extra_row['value']);
    $renderer->renderPlain($formatted);

    $row = [
      [
        'data' => $extra_row['label'],
        'colspan' => 2,
        'style' => $cell_style . ';text-align:right',
      ],
      [
        'data' => $formatted,
        'style' => $cell_style . ';text-align:right',
      ],
    ];
    $rows[] = $row;
  }

  $formatted = arch_order_tokens__renderable_formatted_price($price_factory, $price_formatter, $order, $subtotal);
  $renderer->renderPlain($formatted);
  $rows[] = [
    'data' => [
      [
        'data' => t('Subtotal', [], ['context' => 'arch_order_token']),
        'colspan' => 2,
        'style' => $cell_style . ';text-align:right',
      ],
      [
        'data' => $formatted,
        'style' => $cell_style . ';text-align:right;',
      ],
    ],
  ];

  $formatted = arch_order_tokens__renderable_formatted_price($price_factory, $price_formatter, $order, (float) $order->get('grandtotal_gross')->getString());
  $renderer->renderPlain($formatted);
  $rows[] = [
    [
      'data' => t('Grand total', [], ['context' => 'arch_order_token']),
      'colspan' => 2,
      'style' => $cell_style . ';text-align:right;border-top:2px solid #efefef;font-weight:bold',
    ],
    [
      'data' => $formatted,
      'style' => $cell_style . ';text-align:right;border-top:2px solid #efefef;font-weight:bold',
    ],
  ];

  $table = [
    '#type' => 'table',
    '#header' => [
      [
        'data' => t('Product', [], ['context' => 'arch_order_token']),
        'style' => $cell_style . ';text-align:left;border-bottom:2px solid #efefef',
      ],
      [
        'data' => t('Quantity', [], ['context' => 'arch_order_token']),
        'style' => $cell_style . ';text-align:right;border-bottom:2px solid #efefef',
      ],
      [
        'data' => t('Subtotal', [], ['context' => 'arch_order_token']),
        'style' => $cell_style . ';text-align:right;border-bottom:2px solid #efefef',
      ],
    ],
    '#rows' => $rows,
    '#attributes' => [
      'style' => 'border-collapse:collapse',
    ],
  ];

  $renderer->renderPlain($table);

  return $table['#markup'];
}

/**
 * Gets renderable formatted price from line item object.
 *
 * @param \Drupal\arch_price\Price\PriceFactoryInterface $price_factory
 *   Price factory service.
 * @param \Drupal\arch_price\Price\PriceFormatterInterface $price_formatter
 *   Price formatter service.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Order object.
 * @param \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemInterface $line_item
 *   Order line item object.
 *
 * @return array
 *   Renderable formatted price.
 *
 * @throws \Drupal\Core\TypedData\Exception\MissingDataException
 */
function arch_order_tokens__renderable_formatted_line_item_price(PriceFactoryInterface $price_factory, PriceFormatterInterface $price_formatter, OrderInterface $order, OrderLineItemInterface $line_item) {
  $formatter_defaults = [
    'vat_info' => FALSE,
    'label' => FALSE,
    'wrapper_element' => 'span',
  ];

  $price_instance = $price_factory->getInstance([
    'base' => 'gross',
    'net' => $line_item->get('calculated_net')->getValue() * (float) $line_item->getQuantity(),
    'gross' => $line_item->get('calculated_gross')->getValue() * (float) $line_item->getQuantity(),
    'vat_rate' => $line_item->get('calculated_vat_rate')->getValue(),
    'vat_category' => $line_item->get('calculated_vat_cat_name')->getValue(),
    'vat_amount' => $line_item->get('calculated_vat_amount')->getValue() * (float) $line_item->getQuantity(),
    'currency' => $order->get('currency')->getString(),
  ]);
  $formatted = $price_formatter->buildFormatted($price_instance, PriceFormatterInterface::FORMAT_GROSS, $formatter_defaults);

  return $formatted;
}

/**
 * Gets renderable formatted price from a single float value.
 *
 * @param \Drupal\arch_price\Price\PriceFactoryInterface $price_factory
 *   Price factory service.
 * @param \Drupal\arch_price\Price\PriceFormatterInterface $price_formatter
 *   Price formatter service.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Order object.
 * @param float $price
 *   Price value.
 *
 * @return array
 *   Renderable formatted price.
 */
function arch_order_tokens__renderable_formatted_price(PriceFactoryInterface $price_factory, PriceFormatterInterface $price_formatter, OrderInterface $order, float $price) {
  $formatter_defaults = [
    'vat_info' => FALSE,
    'label' => FALSE,
    'wrapper_element' => 'span',
  ];

  $price_instance = $price_factory->getInstance([
    'base' => 'gross',
    'net' => 0,
    'gross' => $price,
    'vat_rate' => 0,
    'vat_category' => 'custom',
    'vat_amount' => 0,
    'currency' => $order->get('currency')->getString(),
  ]);
  $formatted = $price_formatter->buildFormatted($price_instance, PriceFormatterInterface::FORMAT_GROSS, $formatter_defaults);

  return $formatted;
}
