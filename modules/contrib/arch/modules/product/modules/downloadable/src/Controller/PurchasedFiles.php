<?php

namespace Drupal\arch_downloadable_product\Controller;

use Drupal\arch_downloadable_product\DownloadUrlBuilderInterface;
use Drupal\arch_product\Entity\ProductTypeInterface;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Purchased files.
 *
 * @package Drupal\arch_downloadable_product\Controller
 */
class PurchasedFiles extends ControllerBase {

  /**
   * Product type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * Product storage.
   *
   * @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface
   */
  protected $productStorage;

  /**
   * Order storage.
   *
   * @var \Drupal\arch_order\Entity\Storage\OrderStorageInterface
   */
  protected $orderStorage;

  /**
   * File storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * Download URL builder.
   *
   * @var \Drupal\arch_downloadable_product\DownloadUrlBuilderInterface
   */
  protected $downloadUrlBuilder;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * ProductDownloadController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\arch_downloadable_product\DownloadUrlBuilderInterface $download_url_builder
   *   Download URL builder.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    DownloadUrlBuilderInterface $download_url_builder,
    DateFormatterInterface $date_formatter,
    AccountInterface $current_user,
    RequestStack $request_stack
  ) {
    $this->productTypeStorage = $entity_type_manager->getStorage('product_type');
    $this->productStorage = $entity_type_manager->getStorage('product');
    $this->orderStorage = $entity_type_manager->getStorage('order');
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->downloadUrlBuilder = $download_url_builder;
    $this->dateFormatter = $date_formatter;

    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('download_url_builder'),
      $container->get('date.formatter'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * Access callback.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current account.
   * @param \Drupal\user\UserInterface $user
   *   User to view.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   Access result.
   */
  public function fileListAccess(AccountInterface $account, UserInterface $user) {
    return $user->access('view', $account, TRUE);
  }

  /**
   * Build page of purchased files.
   *
   * @param \Drupal\user\UserInterface $user
   *   Displayed user.
   *
   * @return array
   *   Render array.
   */
  public function fileList(UserInterface $user) {
    $bundles = $this->getDownloadableBundles();
    if (empty($bundles)) {
      // @todo Display empty message.
      return [];
    }

    $bundle_ids = array_map(function (ProductTypeInterface $type) {
      return $type->id();
    }, $bundles);

    $order_ids = $this->getOrderIds($user, $bundle_ids);
    if (empty($order_ids)) {
      // @todo Display empty message.
      return [];
    }

    $order_data = $this->getDataOfPurchasedProducts($order_ids, $bundle_ids);

    $list = [];
    foreach ($order_data as $product_data) {
      $list += $this->buildRowData($user, $product_data);
    }
    if (empty($list)) {
      // @todo Display empty message.
      return [];
    }

    $table = [
      '#theme' => 'table__purchased_files',
      '#header' => [
        'name' => $this->t('Filename'),
        'size' => $this->t('Size'),
        'date' => $this->t('Date'),
        'product' => $this->t('Product', [], ['context' => 'arch_product']),
        'order' => $this->t('Order', [], ['context' => 'arch_order']),
      ],
      '$items' => $list,
      '#rows' => [],
    ];

    foreach ($list as $item) {
      $row = [
        'name' => NULL,
        'size' => NULL,
        'date' => NULL,
        'product' => NULL,
        'order' => NULL,
      ];

      $row['name'] = $item['file_name'];
      if (
        $user->id() == $this->currentUser()->id()
        && $item['url'] instanceof Url
      ) {
        $download_link = Link::fromTextAndUrl($item['file_name'], $item['url']);
        $row['name'] = [
          'data' => $download_link->toRenderable(),
        ];
      }
      $row['size'] = format_size($item['file_size']);
      $row['date'] = $this->dateFormatter->format($item['purchase_date']);

      $product_url = Url::fromRoute('entity.product.canonical', ['product' => $item['product_id']]);
      $product_link = Link::fromTextAndUrl($item['product_label'], $product_url);
      $row['product'] = [
        'data' => $product_link->toRenderable(),
      ];

      $order_url = Url::fromRoute('entity.order.canonical', ['order' => $item['order_id']]);
      $order_link = Link::fromTextAndUrl('#' . $item['order_id'], $order_url);
      $row['order'] = [
        'data' => $order_link->toRenderable(),
      ];

      $table['#rows'][] = $row;
    }

    return $table;
  }

  /**
   * Get list of downloadable product types.
   *
   * @return \Drupal\arch_product\Entity\ProductTypeInterface[]
   *   Product type list.
   */
  protected function getDownloadableBundles() {
    $bundles = $this->productTypeStorage->loadMultiple();
    return array_filter($bundles, function (ProductTypeInterface $type) {
      return $type->getThirdPartySetting('arch_downloadable_product', 'is_downloadable');
    });
  }

  /**
   * Get list of order IDs with purchased files.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Customer.
   * @param array $bundle_ids
   *   List of downloadable product types.
   *
   * @return int[]
   *   List of order IDs.
   */
  protected function getOrderIds(AccountInterface $user, array $bundle_ids) {
    $order_query = $this->orderStorage->getQuery();
    $order_query->condition('uid', $user->id());
    $order_query->condition('line_items.product_bundle', $bundle_ids, 'IN');

    return array_map('intval', array_values($order_query->execute()));
  }

  /**
   * Get data of purchased products.
   *
   * @param array $order_ids
   *   List of order IDs.
   * @param array $bundle_ids
   *   Downloadable product types.
   *
   * @return array
   *   Product data.
   */
  protected function getDataOfPurchasedProducts(array $order_ids, array $bundle_ids) {
    $products = [];
    foreach ($order_ids as $order_id) {
      /** @var \Drupal\arch_order\Entity\OrderInterface $order */
      $order = $this->orderStorage->load($order_id);
      foreach ($order->getProducts() as $product_line_item) {
        try {
          /** @var \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem $product_line_item */
          if (!in_array($product_line_item->get('product_bundle')
            ->getValue(), $bundle_ids)) {
            continue;
          }

          $product_id = (int) $product_line_item->get('product_id')->getValue();
          $products[$product_id] = [
            'product_id' => $product_id,
            'purchase_date' => (int) $order->getCreatedTime(),
            'order_id' => (int) $order->id(),
          ];
        }
        catch (\Exception $e) {
          // @todo Handle error!.
        }
      }
    }

    uasort($products, function ($a, $b) {
      $fields = [
        'purchase_date',
        'order_id',
        'product_id',
      ];
      foreach ($fields as $field) {
        $date = SortArray::sortByKeyInt($a, $b, $field);
        if ($date !== 0) {
          return $date;
        }
      }
      return 0;
    });
    return $products;
  }

  /**
   * Build row data.
   *
   * @param \Drupal\user\UserInterface $user
   *   Customer.
   * @param array $data
   *   Purchased product data.
   *
   * @return array
   *   Purchased file data.
   */
  protected function buildRowData(UserInterface $user, array $data) {
    $list = [];
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->productStorage->load($data['product_id']);
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    if ($product->hasTranslation($langcode)) {
      $product = $product->getTranslation($langcode);
    }

    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $file_field */
    $file_field = $product->get('product_file');
    foreach ($file_field as $item) {
      /** @var \Drupal\file\Plugin\Field\FieldType\FileItem $item */
      /** @var \Drupal\file\FileInterface $file */
      $file = $item->entity;

      $row = $data;
      $row['product_label'] = $product->label();
      $row['fid'] = (int) $file->id();
      $row['file_name'] = $file->getFilename();
      $row['file_size'] = (int) $file->getSize();
      $row['file_mime'] = $file->getMimeType();
      try {
        $row['file_description'] = $item->get('description')->getValue();
      }
      catch (\Exception $e) {
        $row['file_description'] = NULL;
      }
      $row['file_uri'] = $file->getFileUri();
      $row['url'] = NULL;
      if ($user->id() == $this->currentUser()->id()) {
        $row['url'] = $this->downloadUrlBuilder->getDownloadUrl($product, $file, $user);
      }

      $list[$file->id()] = $row;
    }

    return $list;
  }

}
