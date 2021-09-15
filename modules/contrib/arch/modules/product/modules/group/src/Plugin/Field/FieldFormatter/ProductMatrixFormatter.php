<?php

namespace Drupal\arch_product_group\Plugin\Field\FieldFormatter;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\arch_product_group\GroupHandlerInterface;
use Drupal\arch_product_group\ProductMatrixInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'product matrix' formatter.
 *
 * @FieldFormatter(
 *   id = "product_matrix",
 *   label = @Translation("Product matrix", context = "arch_product_group__field_formatter"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class ProductMatrixFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Group handler.
   *
   * @var \Drupal\arch_product_group\GroupHandlerInterface
   */
  protected $groupHandler;

  /**
   * Product matrix.
   *
   * @var \Drupal\arch_product_group\ProductMatrixInterface
   */
  protected $productMatrix;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Current language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $currentLanguage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    GroupHandlerInterface $group_handler,
    ProductMatrixInterface $product_matrix,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityFieldManagerInterface $entity_field_manager,
    LanguageManagerInterface $language_manager
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
    $this->productMatrix = $product_matrix;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->currentLanguage = $language_manager->getCurrentLanguage();
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
      $container->get('product_matrix'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'ajax' => FALSE,
      'fields' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings() + [
      'ajax' => FALSE,
      'fields' => [],
    ];
    $elements['ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('AJAX Update', [], ['context' => 'arch_product_group']),
      '#default_value' => $settings['ajax'],
    ];

    $elements['fields'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Matrix fields', [], ['context' => 'arch_product_group']),
    ];
    $entity_type = $form['#entity_type'];
    $bundle = $form['#bundle'];
    $options = [
      '' => $this->t('- Not used -', [], ['context' => 'arch_product_group']),
    ];
    $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);

    $skipp_fields = [
      'price',
      'stock',
      'description',
    ];
    foreach ($this->entityFieldManager->getFieldDefinitions($entity_type, $bundle) as $field_name => $definition) {
      if (
        in_array($field_name, $skipp_fields)
        || empty($storage_definitions[$field_name])
        || $storage_definitions[$field_name]->isBaseField()
      ) {
        continue;
      }
      $options[$field_name] = (string) $definition->getLabel();
    }

    for ($i = 0; $i < 5; $i++) {
      $select = [
        '#type' => 'select',
        '#title' => $this->t('Dimension @dimension', ['@dimension' => $i + 1], ['context' => 'arch_product_group']),
        '#options' => $options,
        '#default_value' => !empty($settings['fields'][$i]) ? $settings['fields'][$i] : NULL,
      ];

      if ($i) {
        $select['#states']['invisible'] = [
          ':input[data-drupal-selector="edit-fields-group-id-settings-edit-form-settings-fields-' . ($i - 1) . '"]' => [
            'value' => '',
          ],
        ];
      }

      $elements['fields'][$i] = $select;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $items->getEntity();
    $group_id = $this->groupHandler->getGroupId($product);
    $group = $this->groupHandler->getGroupProducts($product);
    if (empty($group_id) || empty($group)) {
      return [];
    }

    $fields = array_filter($this->getSetting('fields'));
    $matrix = $this->productMatrix->getFieldValueMatrix($fields, $product);
    if (empty($matrix)) {
      return [];
    }

    $items = $this->buildItems($fields, $matrix, $group, $product, $langcode);
    $build['content'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['product-matrix'],
        'data-product-group-id' => $group_id,
        'data-product-id' => $product->id(),
      ],
      'items' => $items,
    ];
    $build['#attached']['drupalSettings']['arch_product_matrix'][$group_id] = $matrix;
    $build['#cache']['contexts'][] = 'url';
    $build['#cache']['contexts'][] = 'user';
    $build['#cache']['contexts'][] = 'session';
    $build['#cache']['tags'] = $product->getCacheTags();
    $build['#cache']['tags'][] = 'product_group:' . $group_id;

    return [$build];
  }

  /**
   * Get group items.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Displayed product.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface[]
   *   Group items.
   */
  protected function getGroup(ProductInterface $product) {
    if (!$this->groupHandler->isPartOfGroup($product)) {
      return [];
    }

    $group_id = $this->groupHandler->getGroupId($product);
    if (empty($group_id)) {
      return [];
    }

    return $this->groupHandler->getGroupProducts($product);
  }

  /**
   * Build items to display.
   *
   * @param array $fields
   *   Field list used for matrix.
   * @param array $matrix
   *   Product matrix.
   * @param \Drupal\arch_product\Entity\ProductInterface[] $group_items
   *   Group items.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Displayed product.
   * @param string|null $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildItems(
    array $fields,
    array $matrix,
    array $group_items,
    ProductInterface $product,
    $langcode = NULL
  ) {
    if (empty($langcode)) {
      $langcode = $this->currentLanguage->getId();
    }

    $items = [];
    foreach ($fields as $field_name) {
      if (empty($matrix[$field_name])) {
        continue;
      }
      $field = $product->get($field_name);
      $field_definition = $field->getFieldDefinition();
      $items[$field_name] = [
        '#theme' => 'item_list__product_matrix_field',
        '#wrapper_attributes' => [
          'class' => [
            'product-matrix-field',
            'product-matrix-field--' . $field_name,
          ],
          'data-product-matrix-field' => $field_name,
        ],
        '#attributes' => [
          'class' => [
            'product-matrix-field-items',
            'product-matrix-field-items--' . $field_name,
          ],
          'data-product-matrix-field' => $field_name,
        ],

        '#title' => $field_definition->getLabel(),
        '#items' => [],
      ];

      $current_value = $this->productMatrix->getFieldValue($field_name, $product);

      foreach ($matrix[$field_name] as $value => $variations) {
        $available_variations = array_filter($variations);
        $is_active = $current_value == $value;
        $label = $this->getValueLabel($product, $field_definition, $value, $langcode);
        if (empty($available_variations)) {
          $item = $this->buildDisabledItem($label, $is_active);
        }
        else {
          $product_id = current($available_variations);
          $item = $this->buildAvailableItem($label, $group_items[$product_id], $is_active, $field_name);
        }

        $item['#wrapper_attributes']['class'][] = 'product-matrix-value';
        $item['#wrapper_attributes']['data-product-matrix-field-value'] = $value;
        if ($is_active) {
          $item['#wrapper_attributes']['class'][] = 'is-active';
        }

        $items[$field_name]['#items'][] = $item;
      }

      usort($items[$field_name]['#items'], [$this, 'sortMatrixItemByLabel']);
    }

    return $items;
  }

  /**
   * User-defined sort method for matrix element.
   *
   * @param array $a
   *   Element 'A'.
   * @param array $b
   *   Element 'B'.
   *
   * @return int
   *   An integer number. For more info, please check http://php.net/strcmp url.
   */
  public function sortMatrixItemByLabel(array $a, array $b) {
    return strcmp($a['data']['#title'], $b['data']['#title']);
  }

  /**
   * Build disabled item.
   *
   * @param string $label
   *   Variation label.
   * @param bool $is_active
   *   Mark item as active.
   *
   * @return array
   *   Render array.
   */
  protected function buildDisabledItem($label, $is_active) {
    $item = [
      'data' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $label,
      ],
      '#wrapper_attributes' => [
        'class' => [
          'product-matrix-value--missing',
        ],
      ],
    ];

    if ($is_active) {
      $item['#wrapper_attributes']['class'][] = 'active';
    }

    return $item;
  }

  /**
   * Build available item.
   *
   * @param string $label
   *   Variation label.
   * @param \Drupal\arch_product\Entity\ProductInterface $product_variation
   *   Product variation.
   * @param bool $is_active
   *   Mark item as active.
   * @param string $field_name
   *   Actual field name.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function buildAvailableItem($label, ProductInterface $product_variation, $is_active, $field_name) {
    $attributes = [
      'data-product-matrix-group-id' => $this->groupHandler->getGroupId($product_variation),
      'data-product-matrix-product-id' => $product_variation->id(),
      'class' => ['product-matrix-value-link'],
    ];
    if ($is_active) {
      $attributes['class'][] = 'active';
    }

    $url = $product_variation->toUrl();
    $url->setOption('attributes', $attributes);

    $link = [
      '#type' => 'link',
      '#url' => $url,
      '#title' => $label,
      '#attached' => [
        'library' => [
          'arch_product_group/product_matrix_ajax',
        ],
      ],
    ];

    if ($this->getSetting('ajax')) {
      $url = Url::fromRoute('product_matrix.product', [
        'group_id' => $this->groupHandler->getGroupId($product_variation),
        'product' => $product_variation->id(),
      ]);
      $link['#ajax'] = [
        'url' => $url,
        'event' => 'click',
      ];
      $link['#attached']['library'][] = 'arch_product_group/product_matrix_ajax';
    }

    return [
      'data' => $link,
      '#wrapper_attributes' => [
        'class' => [
          'product-matrix-value--available',
        ],
      ],
    ];
  }

  /**
   * Get value label.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Current product.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param mixed $value
   *   Value.
   * @param string $langcode
   *   View langcode.
   *
   * @return string|null
   *   Rendered label.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getValueLabel(ProductInterface $product, FieldDefinitionInterface $field_definition, $value, $langcode) {
    if ($field_definition->getType() === 'entity_reference') {
      $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
      $entity = $this->entityTypeManager->getStorage($target_type)->load($value);
      if (
        $entity instanceof TranslatableInterface
        && $entity->hasTranslation($langcode)
      ) {
        $entity = $entity->getTranslation($langcode);
      }

      return $entity->label();
    }

    // @todo handle text/number list fields.
    return $value;
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
