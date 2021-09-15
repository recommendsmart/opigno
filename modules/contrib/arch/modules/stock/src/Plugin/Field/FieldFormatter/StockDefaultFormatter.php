<?php

namespace Drupal\arch_stock\Plugin\Field\FieldFormatter;

use Drupal\arch_product\Entity\ProductAvailability;
use Drupal\arch_product\Entity\ProductAvailabilityInterface;
use Drupal\arch_stock\StockKeeperInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'stock' formatter.
 *
 * @FieldFormatter(
 *   id = "stock_default",
 *   label = @Translation("Stock default", context = "arch_stock__field_formatter"),
 *   field_types = {
 *     "stock"
 *   }
 * )
 */
class StockDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Stock keeper.
   *
   * @var \Drupal\arch_stock\StockKeeperInterface
   */
  protected $stockKeeper;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * {@inheritdoc}
   */
  public function __construct(
    string $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    string $label,
    string $view_mode,
    array $third_party_settings,
    StockKeeperInterface $stock_keeper,
    AccountInterface $account,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager
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

    $this->stockKeeper = $stock_keeper;
    $this->currentUser = $account;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('arch_stock.stock_keeper'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $items->getEntity();
    $stock_total = $this->stockKeeper->getTotalProductStock($product, $this->currentUser);
    $overbooking_allowed = $this->stockKeeper->isNegativeStockAllowed($product, $this->currentUser);
    $availability = $product->getAvailability();
    if ($stock_total <= 0) {
      $availability = $overbooking_allowed ? ProductAvailabilityInterface::STATUS_PREORDER : ProductAvailabilityInterface::STATUS_NOT_AVAILABLE;
    }

    return [
      '#theme' => 'stock_info_field',
      '#status' => $availability,
      '#status_label' => $this->getAvailabilityLabel($availability),
      '#product' => $product,
      '#stock_total' => $stock_total,
      '#overbooking_allowed' => $overbooking_allowed,
    ];
  }

  /**
   * Get availability labels.
   *
   * @param string $available
   *   Availability value.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   Labels.
   */
  protected function getAvailabilityLabel($available) {
    $options = ProductAvailability::getOptions();
    $this->moduleHandler->alter('arch_stock_availability_labels', $options);
    $this->themeManager->alter('arch_stock_availability_labels', $options);

    return !empty($options[$available]) ? $options[$available] : NULL;
  }

}
