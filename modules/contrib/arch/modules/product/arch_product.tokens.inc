<?php
/**
 * @file
 * Builds placeholder replacement tokens for product/order-related data.
 */

use Drupal\arch_product\Entity\ProductAvailability;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function arch_product_token_info() {
  $tokens = [];

  // Product.
  $tokens['types']['product'] = [
    'name' => t('Products', [], ['context' => 'arch_product']),
    'description' => t('Tokens related to individual product items, or "products".', [], ['context' => 'arch_product']),
    'needs-data' => 'product',
  ];

  // Core tokens for products.
  $product['pid'] = [
    'name' => t('Product ID', [], ['context' => 'arch_product']),
    'description' => t('The unique ID of the content item, or "product".', [], ['context' => 'arch_product']),
  ];
  $product['vid'] = [
    'name' => t('Revision ID'),
    'description' => t("The unique ID of the product's latest revision.", [], ['context' => 'arch_product']),
  ];
  $product['type'] = [
    'name' => t('Product type', [], ['context' => 'arch_product']),
  ];
  $product['type-name'] = [
    'name' => t('Product type name', [], ['context' => 'arch_product']),
    'description' => t('The human-readable name of the product type.', [], ['context' => 'arch_product']),
  ];
  $product['title'] = [
    'name' => t('Title', [], ['context' => 'arch_product']),
  ];
  $product['description'] = [
    'name' => t('Description', [], ['context' => 'arch_product']),
    'description' => t('The main description text of the product.', [], ['context' => 'arch_product']),
  ];
  $product['summary'] = [
    'name' => t('Summary', [], ['context' => 'arch_product']),
    'description' => t("The summary of the products's main description text.", [], ['context' => 'arch_product']),
  ];
  $product['sku'] = [
    'name' => t('Product SKU', [], ['context' => 'arch_product']),
    'description' => t('The SKU of the "product".', [], ['context' => 'arch_product']),
  ];
  $product['erp_id'] = [
    'name' => t('ERP ID', [], ['context' => 'arch_product']),
    'description' => t('The ERP ID of the "product".', [], ['context' => 'arch_product']),
  ];
  $product['group_id'] = [
    'name' => t('Group ID', [], ['context' => 'arch_product']),
    'description' => t('The Group ID of the "product".', [], ['context' => 'arch_product']),
  ];
  $product['availability'] = [
    'name' => t('Availability', [], ['context' => 'arch_product']),
    'description' => t('The product availability.', [], ['context' => 'arch_product']),
  ];
  $product['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the product is written in.', [], ['context' => 'arch_product']),
  ];
  $product['url'] = [
    'name' => t('URL'),
    'description' => t('The URL of the product.', [], ['context' => 'arch_product']),
  ];
  $product['edit-url'] = [
    'name' => t('Edit URL'),
    'description' => t("The URL of the product's edit page.", [], ['context' => 'arch_product']),
  ];

  // Chained tokens for products.
  $product['created'] = [
    'name' => t('Date created', [], ['context' => 'arch_product']),
    'type' => 'date',
  ];
  $product['changed'] = [
    'name' => t('Date changed', [], ['context' => 'arch_product']),
    'description' => t('The date the product was most recently updated.', [], ['context' => 'arch_product']),
    'type' => 'date',
  ];
  $product['author'] = [
    'name' => t('Creator', [], ['context' => 'arch_product']),
    'type' => 'user',
  ];

  $tokens['tokens']['product'] = $product;

  return $tokens;
}

/**
 * Implements hook_tokens().
 */
function arch_product_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $product = NULL;

  if ($type == 'product' && !empty($data['product'])) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $data['product'];
  }

  if (
    $type == 'entity'
    && isset($data['entity_type'])
    && $data['entity_type'] == 'product'
    && isset($data['entity'])
  ) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $data['entity'];
  }

  if (empty($product)) {
    return NULL;
  }

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

  foreach ($tokens as $name => $original) {
    switch ($name) {
      // Simple key values on the product.
      case 'pid':
        $replacements[$original] = $product->id();
        break;

      case 'vid':
        $replacements[$original] = $product->getRevisionId();
        break;

      case 'type':
        $replacements[$original] = $product->getType();
        break;

      case 'type-name':
        $language_manager = \Drupal::languageManager();

        // Gets the current & override language.
        $currentLanguage = $language_manager->getConfigOverrideLanguage();
        $overrideLanguage = $language_manager->getLanguage($product->language()->getId());
        // Sets the override language to config override.
        $language_manager->setConfigOverrideLanguage($overrideLanguage);

        $type_name = product_get_type_label($product);

        // Returns to the previous state.
        $language_manager->setConfigOverrideLanguage($currentLanguage);

        $replacements[$original] = $type_name;
        break;

      case 'title':
        $replacements[$original] = $product->getTitle();
        break;

      case 'description':
      case 'summary':
        $translation = \Drupal::service('entity.repository')->getTranslationFromContext($product, $langcode, ['operation' => 'product_tokens']);
        if ($translation->hasField('description') && ($items = $translation->get('description')) && !$items->isEmpty()) {
          $item = $items[0];
          // If the summary was requested and is not empty, use it.
          if ($name == 'summary' && !empty($item->summary)) {
            $output = $item->summary_processed;
          }
          // Attempt to provide a suitable version of the 'description' field.
          else {
            $output = $item->processed;
            // A summary was requested.
            if ($name == 'summary') {
              // Generate an optionally trimmed summary of the description
              // field.
              //
              // Get the 'trim_length' size used for the 'teaser' mode, if
              // present, or use the default trim_length size.
              $display_options = \Drupal::service('entity_display.repository')->getViewDisplay('product', $product->getType(), 'teaser')->getComponent('description');
              if (isset($display_options['settings']['trim_length'])) {
                $length = $display_options['settings']['trim_length'];
              }
              else {
                $settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('text_summary_or_trimmed');
                $length = $settings['trim_length'];
              }

              $output = text_summary($output, $item->format, $length);
            }
          }
          // "processed" returns a \Drupal\Component\Render\MarkupInterface
          // via check_markup().
          $replacements[$original] = $output;
        }
        break;

      case 'sku':
        $replacements[$original] = $product->getSku();
        break;

      case 'erp_id':
        $replacements[$original] = $product->get('erp_id')->getString();
        break;

      case 'group_id':
        $replacements[$original] = $product->get('group_id')->getString();
        break;

      case 'availability':
        $availability_options = ProductAvailability::getOptions();
        $availability_value = $product->get('availability')->getString();
        if (isset($availability_options[$availability_value])) {
          $replacements[$original] = $availability_options[$availability_value];
        }
        else {
          $replacements[$original] = $availability_value;
        }
        break;

      case 'langcode':
        $replacements[$original] = $product->language()->getId();
        break;

      case 'url':
        $replacements[$original] = $product->toUrl('canonical', $url_options)->toString();
        break;

      case 'edit-url':
        $replacements[$original] = $product->toUrl('edit-form', $url_options)->toString();
        break;

      // Default values for the chained tokens handled below.
      case 'author':
        $account = $product->getOwner() ? $product->getOwner() : User::load(0);
        $bubbleable_metadata->addCacheableDependency($account);
        $replacements[$original] = $account->label();
        break;

      case 'created':
        $date_format = DateFormat::load('medium');
        $bubbleable_metadata->addCacheableDependency($date_format);
        $replacements[$original] = $date_formatter->format($product->getCreatedTime(), 'medium', '', NULL, $langcode);
        break;

      case 'changed':
        $date_format = DateFormat::load('medium');
        $bubbleable_metadata->addCacheableDependency($date_format);
        $replacements[$original] = $date_formatter->format($product->getChangedTime(), 'medium', '', NULL, $langcode);
        break;
    }
  }

  if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
    $replacements += $token_service->generate('user', $author_tokens, ['user' => $product->getOwner()], $options, $bubbleable_metadata);
  }

  if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
    $replacements += $token_service->generate('date', $created_tokens, ['date' => $product->getCreatedTime()], $options, $bubbleable_metadata);
  }

  if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
    $replacements += $token_service->generate('date', $changed_tokens, ['date' => $product->getChangedTime()], $options, $bubbleable_metadata);
  }

  return $replacements;
}
