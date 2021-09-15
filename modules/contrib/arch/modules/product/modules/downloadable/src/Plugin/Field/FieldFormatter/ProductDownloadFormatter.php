<?php

namespace Drupal\arch_downloadable_product\Plugin\Field\FieldFormatter;

use Drupal\arch_downloadable_product\DownloadUrlBuilderInterface;
use Drupal\arch_downloadable_product\ProductFileAccessInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Plugin\Field\FieldFormatter\DescriptionAwareFileFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "arch_downloadable_product",
 *   label = @Translation("Product download"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class ProductDownloadFormatter extends DescriptionAwareFileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Product file access.
   *
   * @var \Drupal\arch_downloadable_product\ProductFileAccessInterface
   */
  protected $downloadAccess;

  /**
   * Download URL builder.
   *
   * @var \Drupal\arch_downloadable_product\DownloadUrlBuilderInterface
   */
  protected $downloadUrlBuilder;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    $third_party_settings,
    AccountInterface $current_user,
    ProductFileAccessInterface $product_access,
    DownloadUrlBuilderInterface $download_url_builder,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

    $this->currentUser = $current_user;
    $this->downloadAccess = $product_access;
    $this->downloadUrlBuilder = $download_url_builder;
    $this->userStorage = $entity_type_manager->getStorage('user');
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
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('downloadable_product.access'),
      $container->get('download_url_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $items->getEntity();

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->userStorage->load($this->currentUser->id());

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      /** @var \Drupal\file\FileInterface $file */
      if (!$this->downloadAccess->check($product, $file, $account)) {
        continue;
      }

      $item = $file->_referringItem;
      $url = $this->downloadUrlBuilder->getDownloadUrl($product, $file, $account);

      $tags = array_merge(
        $file->getCacheTags(),
        $product->getCacheTags(),
        $account->getCacheTags()
      );

      $title = [
        'title' => ['#markup' => $file->label()],
      ];
      if ($this->getSetting('use_description_as_link_text')) {
        $title['description'] = [
          '#markup' => $item->description,
        ];
      }

      $elements[$delta] = [
        '#type' => 'link',
        '#url' => $url,
        '#title' => $title,
        '#cache' => [
          'tags' => $tags,
        ],
      ];
      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += ['#attributes' => []];
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getFieldStorageDefinition()->getTargetEntityTypeId() != 'product') {
      return FALSE;
    }

    return $field_definition->getName() == 'product_file';
  }

}
