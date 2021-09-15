<?php

namespace Drupal\arch_product\Form;

use Drupal\arch_product\Entity\ProductTypeInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for product type forms.
 *
 * @internal
 */
class ProductTypeForm extends BundleEntityFormBase {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs the ProductTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\arch_product\Entity\ProductTypeInterface $type */
    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add product type', [], ['context' => 'arch_product']);
      $fields = $this->entityFieldManager->getBaseFieldDefinitions('product');
      // Create a product with a fake bundle using the type's UUID so that we
      // can get the default values for workflow settings.
      // @todo Make it possible to get default values without an entity.
      //   https://www.drupal.org/node/2318187
      $product = $this->entityTypeManager->getStorage('product')->create([
        'type' => $type->uuid(),
      ]);
    }
    else {
      $form['#title'] = $this->t(
        'Edit %label product type',
        ['%label' => $type->label()],
        ['context' => 'arch_product_type']
      );
      $fields = $this->entityFieldManager->getFieldDefinitions('product', $type->id());
      // Create a product to get the current values for workflow settings
      // fields.
      $product = $this->entityTypeManager->getStorage('product')->create([
        'type' => $type->id(),
      ]);
    }

    $form['name'] = [
      '#title' => $this->t('Name', [], ['context' => 'arch_product_type']),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => $this->t('The human-readable name of this product type. This text will be displayed as part of the list on the <em>Add product</em> page. This name must be unique.', [], ['context' => 'arch_product_type']),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['type'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $type->isLocked(),
      '#machine_name' => [
        'exists' => [
          'Drupal\arch_product\Entity\ProductType',
          'load',
        ],
        'source' => ['name'],
      ],
      '#description' => $this->t(
        'A unique machine-readable name for this product type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %product-add page, in which underscores will be converted into hyphens.',
        [
          '%product-add' => $this->t('Add product', [], ['context' => 'arch_product']),
        ],
        ['context' => 'arch_product_type']
      ),
    ];

    $form['description'] = [
      '#title' => $this->t('Description', [], ['context' => 'arch_product_type']),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => $this->t(
        'This text will be displayed on the <em>Add new product</em> page.',
        [],
        ['context' => 'arch_product_type']
      ),
    ];

    $form['product_type_features'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Product features', [], ['context' => 'arch_product_type']),
      '#attached' => [
        'library' => ['arch_product/drupal.product_types'],
      ],
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['arch_product/drupal.product_types'],
      ],
    ];

    $form['submission'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission form settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    ];
    $form['submission']['title_label'] = [
      '#title' => $this->t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $fields['title']->getLabel(),
      '#required' => TRUE,
    ];
    $form['submission']['preview_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Preview before submitting'),
      '#default_value' => $type->getPreviewMode(),
      '#options' => [
        DRUPAL_DISABLED => $this->t('Disabled'),
        DRUPAL_OPTIONAL => $this->t('Optional'),
        DRUPAL_REQUIRED => $this->t('Required'),
      ],
    ];
    $form['submission']['help'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Explanation or submission guidelines'),
      '#default_value' => $type->getHelp(),
      '#description' => $this->t('This text will be displayed at the top of the page when creating or editing product of this type.', [], ['context' => 'arch_product']),
    ];
    $form['workflow'] = [
      '#type' => 'details',
      '#title' => $this->t('Publishing options'),
      '#group' => 'additional_settings',
    ];
    $workflow_options = [
      'status' => $product->status->value,
      'promote' => $product->promote->value,
      'sticky' => $product->sticky->value,
      'revision' => $type->shouldCreateNewRevision(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    $workflow_options = array_combine($keys, $keys);
    $form['workflow']['options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default options'),
      '#default_value' => $workflow_options,
      '#options' => [
        'status' => $this->t('Published'),
        'promote' => $this->t('Promoted to front page'),
        'sticky' => $this->t('Sticky at top of lists'),
        'revision' => $this->t('Create new revision'),
      ],
      '#description' => $this->t(
        'Users with the <em>Administer products</em> permission will be able to override these options.',
        [],
        ['context' => 'arch_product_type']
      ),
    ];
    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('product', $type->id());
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'product',
          'bundle' => $type->id(),
        ],
        '#default_value' => $language_configuration,
      ];
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save product type', [], ['context' => 'arch_product_type']);
    $actions['delete']['#value'] = $this->t('Delete product type', [], ['context' => 'arch_product_type']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName(
        'type',
        $this->t(
          'Invalid machine-readable name. Enter a name other than %invalid.',
          ['%invalid' => $id],
          ['context' => 'arch_product_type']
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_product\Entity\ProductTypeInterface $type */
    $type = $this->entity;
    $type->setNewRevision($form_state->getValue(['options', 'revision']));
    $type->set('type', trim($type->id()));
    $type->set('name', trim($type->label()));

    $status = $type->save();

    $t_args = ['%name' => $type->label()];

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The product type %name has been updated.', $t_args, ['context' => 'arch_product_type']));
    }
    elseif ($status == SAVED_NEW) {
      $this->addPriceField($type);
      $this->addDescriptionField($type);
      $this->messenger()->addStatus($this->t('The product type %name has been added.', $t_args, ['context' => 'arch_product_type']));
      $context = array_merge(
        $t_args,
        [
          'link' => $type->toLink($this->t('View'), 'collection')->toString(),
        ]
      );
      $this->logger('product')->notice('Added product type %name.', $context);
    }

    $fields = $this->entityFieldManager->getFieldDefinitions('product', $type->id());
    // Update title field definition.
    $title_field = $fields['title'];
    $title_label = $form_state->getValue('title_label');
    if ($title_field->getLabel() != $title_label) {
      $title_field->getConfig($type->id())->setLabel($title_label)->save();
    }
    // Update workflow options.
    // @todo Make it possible to get default values without an entity.
    //   https://www.drupal.org/node/2318187
    $product = $this->entityTypeManager->getStorage('product')->create(['type' => $type->id()]);
    foreach (['status', 'promote', 'sticky'] as $field_name) {
      $value = (bool) $form_state->getValue(['options', $field_name]);
      if ($product->$field_name->value != $value) {
        $fields[$field_name]->getConfig($type->id())->setDefaultValue($value)->save();
      }
    }

    $this->entityFieldManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($type->toUrl('collection'));
  }

  /**
   * Add price field to product type.
   *
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $type
   *   Product type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addPriceField(ProductTypeInterface $type) {
    $this->addField($type, [
      'name' => 'price',
      'config' => [
        'label' => 'Price',
      ],
      'form_display' => [
        'type' => 'price_default',
      ],
      'display' => [
        'default' => [
          'label' => 'hidden',
          'type' => 'price_default',
        ],
        'teaser' => [
          'label' => 'hidden',
          'type' => 'price_default',
        ],
      ],
    ]);
  }

  /**
   * Add description field to product type.
   *
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $type
   *   Product type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addDescriptionField(ProductTypeInterface $type) {
    $this->addField($type, [
      'name' => 'description',
      'config' => [
        'label' => 'Description',
        'settings' => ['display_summary' => TRUE],
      ],
      'form_display' => [
        'type' => 'text_textarea_with_summary',
      ],
      'display' => [
        'default' => [
          'label' => 'hidden',
          'type' => 'text_default',
        ],
        'teaser' => [
          'label' => 'hidden',
          'type' => 'text_summary_or_trimmed',
        ],
      ],
    ]);
  }

  /**
   * Add field to product type.
   *
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $type
   *   Product type.
   * @param array $definition
   *   Field definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addField(ProductTypeInterface $type, array $definition) {
    // Add or remove the description field, as needed.
    $field_storage = FieldStorageConfig::loadByName('product', $definition['name']);
    $field = FieldConfig::loadByName('product', $type->id(), $definition['name']);
    if (empty($field)) {
      $field = FieldConfig::create($definition['config'] + [
        'field_storage' => $field_storage,
        'bundle' => $type->id(),
      ]);
      $field->setTranslatable(FALSE);
      $field->save();

      // Assign widget settings for the 'default' form mode.
      $this->getEntityFormDisplay($type->id())
        ->setComponent($definition['name'], $definition['form_display'])
        ->save();

      // The teaser view mode is created by the Standard profile and therefore
      // might not exist.
      $view_modes = $this->entityDisplayRepository->getViewModes('product');
      // Assign display settings for the 'default' and 'teaser' view modes.
      foreach ($definition['display'] as $view_mode => $config) {
        if (isset($view_modes[$view_mode]) || 'default' == $view_mode) {
          $this->getEntityDisplay($type->id(), $view_mode)
            ->setComponent($definition['name'], $definition['display'][$view_mode])
            ->save();
        }
      }
    }
  }

  /**
   * Get form display config.
   *
   * @param string $bundle
   *   Product type ID.
   *
   * @return \Drupal\Core\Entity\Entity\EntityFormDisplay|\Drupal\Core\Entity\EntityInterface
   *   Entity form display.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityFormDisplay($bundle) {
    $entity_type = 'product';
    $form_mode = 'default';

    $entity_form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type . '.' . $bundle . '.' . $form_mode);
    if (!$entity_form_display) {
      $entity_form_display = EntityFormDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $form_mode,
        'status' => TRUE,
      ]);
    }

    return $entity_form_display;
  }

  /**
   * Get view mode config.
   *
   * @param string $bundle
   *   Product type ID.
   * @param string $view_mode
   *   View mode ID.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay|\Drupal\Core\Entity\EntityInterface
   *   View mode config.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityDisplay($bundle, $view_mode) {
    $entity_type = 'product';
    $display = $this->entityTypeManager->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.' . $view_mode);
    if (!$display) {
      $display = EntityViewDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $view_mode,
        'status' => TRUE,
      ]);
    }

    return $display;
  }

}
