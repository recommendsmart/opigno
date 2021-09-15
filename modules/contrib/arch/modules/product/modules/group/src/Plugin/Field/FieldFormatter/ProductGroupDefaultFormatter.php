<?php

namespace Drupal\arch_product_group\Plugin\Field\FieldFormatter;

use Drupal\arch_product\Entity\Builder\ProductViewBuilder;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\arch_product_group\GroupHandlerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'product group' formatter.
 *
 * @FieldFormatter(
 *   id = "product_group_default",
 *   label = @Translation("Product group default", context = "arch_product_group__field_formatter"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class ProductGroupDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Group handler.
   *
   * @var \Drupal\arch_product_group\GroupHandlerInterface
   */
  protected $groupHandler;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Product view builder.
   *
   * @var \Drupal\arch_product\Entity\Builder\ProductViewBuilder
   */
  protected $productViewBuilder;

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
    array $third_party_settings,
    GroupHandlerInterface $group_handler,
    EntityDisplayRepositoryInterface $entity_display_repository,
    ProductViewBuilder $product_view_builder
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

    $this->groupHandler = $group_handler;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->productViewBuilder = $product_view_builder;
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
      $container->get('product_group.handler'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')->getViewBuilder('product')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => '_link',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $view_modes = [
      '_link' => $this->t('Link', [], ['context' => 'arch_product_group_formatter_mode']),
    ];
    $view_modes += $this->entityDisplayRepository->getViewModeOptions('product');
    $elements['view_mode'] = [
      '#type' => 'select',
      '#options' => $view_modes,
      '#title' => $this->t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $items->getEntity();
    if (!$this->groupHandler->isPartOfGroup($product)) {
      return ['#markup' => ''];
    }

    $group = $this->groupHandler->getGroupProducts($product);
    if (empty($group)) {
      return ['#markup' => ''];
    }

    return [
      '#theme' => 'item_list',
      '#attributes' => [
        'class' => [
          'product-group--group-items',
        ],
      ],
      '#items' => array_map(function (ProductInterface $group_item) use ($product) {
        $item_classes = [
          'product-group--group-item',
        ];
        if ($product->id() == $group_item->id()) {
          $item_classes[] = 'product-group--group-item--selected';
          $item_classes[] = 'active';
        }

        $view_mode = $this->getSetting('view_mode');
        if ($view_mode == '_link') {
          $data = $group_item->toLink()->toRenderable();
        }
        else {
          $data = $this->productViewBuilder->view($group_item, $view_mode);
        }
        return [
          'data' => $data,
          '#wrapper_attributes' => ['class' => $item_classes],
        ];
      }, $group),
      '#cache' => [
        'contexts' => [
          'url',
        ],
        'tags' => $product->getCacheTags(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getFieldStorageDefinition()->getTargetEntityTypeId() != 'product') {
      return FALSE;
    }

    return $field_definition->getName() == 'group_id';
  }

}
