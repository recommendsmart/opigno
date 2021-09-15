<?php

namespace Drupal\arch_product_group\Plugin\Field\FieldWidget;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\arch_product_group\GroupHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'product_group' widget.
 *
 * @FieldWidget(
 *   id = "product_group_select",
 *   label = @Translation("Product group select", context = "arch_product_group"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class ProductGroupWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  const MODE_STANDALONE = 0;
  const MODE_PARENT = 1;
  const MODE_CHILD = 2;

  /**
   * Group handler.
   *
   * @var \Drupal\arch_product_group\GroupHandlerInterface
   */
  protected $groupHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    GroupHandlerInterface $group_handler
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings
    );

    $this->groupHandler = $group_handler;
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
      $configuration['third_party_settings'],
      $container->get('product_group.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $items->getEntity();

    $mode = self::MODE_STANDALONE;
    $group_parent = NULL;

    $modes = [
      self::MODE_STANDALONE => $this->t('Not part of a group', [], ['context' => 'arch_product_group']),
      self::MODE_PARENT => $this->t('Has child products', [], ['context' => 'arch_product_group']),
      self::MODE_CHILD => $this->t('Part of group', [], ['context' => 'arch_product_group']),
    ];

    if ($product->isNew()) {
      // Possible to start new group.
      // Possible to select existing group.
      $mode = self::MODE_STANDALONE;
    }
    elseif ($this->groupHandler->isGroupParent($product)) {
      $mode = self::MODE_PARENT;
      $modes[self::MODE_STANDALONE] = $this->t('Dismiss group', [], ['context' => 'arch_product_group']);
    }
    elseif ($this->groupHandler->isPartOfGroup($product)) {
      $mode = self::MODE_CHILD;
      $group_parent = $this->groupHandler->getGroupParent($product);
      $modes = [
        self::MODE_STANDALONE => $this->t('Leave group', [], ['context' => 'arch_product_group']),
        self::MODE_CHILD => $this->t('Part of group', [], ['context' => 'arch_product_group']),
      ];
    }

    $element['product_group'] = [
      '#type' => 'container',
      '#attributes' => [],
    ];
    $element['product_group']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Group mode', [], ['context' => 'arch_product_group']),
      '#options' => $modes,
      '#default_value' => $mode,
    ];

    $element['product_group']['parent'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Parent product', [], ['context' => 'arch_product_group']),
      '#default_value' => $group_parent,
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-group-id-' . $delta . '-product-group-mode"]' => [
            'value' => self::MODE_CHILD,
          ],
        ],
        'required' => [
          ':input[data-drupal-selector="edit-group-id-' . $delta . '-product-group-mode"]' => [
            'value' => self::MODE_CHILD,
          ],
        ],
      ],
      '#target_type' => 'product',
      '#selection_settings' => [
        'target_bundles' => [
          $product->bundle() => $product->bundle(),
        ],
        'sort' => [
          'field' => 'sku',
          'direction' => 'ASC',
        ],
      ],
      '#selection_handler' => 'default:product',
      '#autocreate' => FALSE,
    ];

    // @todo add entity reference widget for select children.
    if ($this->groupHandler->isGroupParent($product)) {
      $element['product_group']['children'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $this->t('Group products', [], ['context' => 'arch_product_group']),
        'items' => $this->childrenList($product),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return array_map(function ($item) use ($form_state) {
      if ($item['product_group']['mode'] === self::MODE_STANDALONE) {
        /** @var \Drupal\arch_product\Form\ProductForm $form_object */
        $form_object = $form_state->getFormObject();
        /** @var \Drupal\arch_product\Entity\ProductInterface $product */
        $product = $form_object->getEntity();
        $product->_product_group_action_dismiss = TRUE;
        return $product->id();
      }
      return $item['product_group']['parent'];
    }, $values);
  }

  /**
   * Render children list.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Parent product.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function childrenList(ProductInterface $product) {
    $build = [
      '#theme' => 'table',
      '#header' => [
        'PID' => '#',
        'sku' => $this->t('SKU', [], ['context' => 'arch_product']),
        'name' => $this->t('Name', [], ['context' => 'arch_product']),
        'view' => '',
        'edit' => '',
      ],
      '#rows' => [],
      '#attributes' => [
        'class' => [
          'product-group-items',
        ],
      ],
    ];

    foreach ($this->groupHandler->getGroupProducts($product) as $group_item) {
      $view = $group_item->toLink($this->t('View'), 'canonical', [
        'attributes' => [
          'target' => '_blank',
        ],
      ]);
      $edit = $group_item->toLink($this->t('Edit'), 'edit-form', [
        'attributes' => [
          'target' => '_blank',
        ],
      ]);
      $build['#rows'][$group_item->id()] = [
        'pid' => $group_item->id(),
        'sku' => $group_item->getSku(),
        'name' => $group_item->label(),
        'view' => ['data' => $view->toRenderable()],
        'edit' => ['data' => $edit->toRenderable()],
      ];
    }

    return $build;
  }

}
