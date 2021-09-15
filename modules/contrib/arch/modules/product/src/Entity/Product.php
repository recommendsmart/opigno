<?php

namespace Drupal\arch_product\Entity;

use Drupal\arch_price\Price\MissingPriceInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Product entity.
 *
 * @ContentEntityType(
 *   id = "product",
 *   label = @Translation("Product", context = "arch_product"),
 *   label_collection = @Translation("Products", context = "arch_product"),
 *   label_singular = @Translation("product", context = "arch_product"),
 *   label_plural = @Translation("products", context = "arch_product"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product",
 *     plural = "@count products",
 *     context = "arch_product"
 *   ),
 *   bundle_label = @Translation("Product type", context = "arch_product"),
 *   bundle_entity_type = "product_type",
 *   handlers = {
 *     "storage" = "Drupal\arch_product\Entity\Storage\ProductStorage",
 *     "storage_schema" = "Drupal\arch_product\Entity\Storage\ProductStorageSchema",
 *     "view_builder" = "Drupal\arch_product\Entity\Builder\ProductViewBuilder",
 *     "access" = "Drupal\arch_product\Access\ProductAccessControlHandler",
 *     "views_data" = "Drupal\arch_product\Entity\Views\ProductViewsData",
 *     "form" = {
 *       "default" = "Drupal\arch_product\Form\ProductForm",
 *       "add" = "Drupal\arch_product\Form\ProductForm",
 *       "edit" = "Drupal\arch_product\Form\ProductForm",
 *       "delete" = "Drupal\arch_product\Form\ProductDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\arch_product\Routing\ProductRouteProvider",
 *     },
 *     "list_builder" = "Drupal\arch_product\Entity\Builder\ProductListBuilder",
 *     "translation" = "Drupal\arch_product\Entity\Translation\ProductTranslationHandler"
 *   },
 *   base_table = "arch_product",
 *   data_table = "arch_product_field_data",
 *   revision_table = "arch_product_revision",
 *   revision_data_table = "arch_product_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "pid",
 *     "sku" = "sku",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "published" = "status",
 *     "uid" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   field_ui_base_route = "entity.product_type.edit_form",
 *   common_reference_target = TRUE,
 *   permission_granularity = "bundle",
 *   links = {
 *     "collection" = "/admin/store/products",
 *     "add-page" = "/product/add",
 *     "add-form" = "/product/add/{product_type}",
 *     "edit-form" = "/product/{product}/edit",
 *     "delete-form" = "/product/{product}/delete",
 *     "delete-multiple-form" = "/admin/store/product/delete",
 *     "canonical" = "/product/{product}",
 *     "version-history" = "/product/{product}/revisions",
 *     "revision" = "/product/{product}/revisions/{product_revision}/view",
 *   }
 * )
 */
class Product extends EditorialContentEntityBase implements ProductInterface {

  /**
   * Whether the product is being previewed or not.
   *
   * The variable is set to public as it will give a considerable performance
   * improvement. See https://www.drupal.org/node/2498919.
   *
   * @var true|null
   *   TRUE if the product is being previewed and NULL if it is not.
   */
  public $inPreview = NULL;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision creator has been set explicitly, make the product owner
    // the revision creator.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Update the product access table for this product, but only if it is the
    // default revision. There's no need to delete existing records if the
    // product is new.
    if ($this->isDefaultRevision()) {
      /** @var \Drupal\arch_product\Access\ProductAccessControlHandlerInterface $access_control_handler */
      $access_control_handler = $this->entityTypeManager()->getAccessControlHandler('product');
      $grants = $access_control_handler->acquireGrants($this);
      \Drupal::service('product.grant_storage')->write($this, $grants, NULL, $update);
    }

    // Reindex the product when it is updated. The product is automatically
    // indexed when it is added, simply by being added to the product table.
    if ($update) {
      product_reindex_product_search($this->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Ensure that all products deleted are removed from the search index.
    if (\Drupal::hasService('search.index')) {
      /** @var \Drupal\search\SearchIndexInterface $search_index */
      $search_index = \Drupal::service('search.index');
      foreach ($entities as $entity) {
        $search_index->clear('product_search', $entity->pid->value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $products) {
    parent::postDelete($storage, $products);
    \Drupal::service('product.grant_storage')->deleteProductRecords(array_keys($products));
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSku() {
    return $this->get('sku')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSku($sku) {
    $this->set('sku', $sku);
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
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailability() {
    return $this->get('availability')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAvailability($availability) {
    $this->set('availability', $availability);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPromoted() {
    return (bool) $this->get('promote')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPromoted($promoted) {
    $this->set('promote', $promoted ? ProductInterface::PROMOTED : ProductInterface::NOT_PROMOTED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSticky() {
    return (bool) $this->get('sticky')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSticky($sticky) {
    $this->set('sticky', $sticky ? ProductInterface::STICKY : ProductInterface::NOT_STICKY);
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
  public function getOwnerId() {
    return $this->getEntityKey('uid');
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
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrices() {
    return $this->getPriceNegotiation()->getProductPrices($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailablePrices(AccountInterface $account = NULL) {
    return $this->getPriceNegotiation()->getAvailablePrices($this, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function getActivePrice(AccountInterface $account = NULL) {
    return $this->getPriceNegotiation()->getActivePrice($this, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function hasPrice(AccountInterface $account = NULL) {
    $price = $this->getActivePrice($account);
    if (empty($price) || $price instanceof MissingPriceInterface) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function availableForSell(AccountInterface $account = NULL) {
    $result = AccessResult::neutral();

    $availability_result = AccessResult::allowedIf($this->getAvailability() !== ProductAvailability::STATUS_NOT_AVAILABLE);
    $result->andIf($availability_result);
    $price_result = $this->hasPrice($account) ? AccessResult::neutral() : AccessResult::forbidden();
    $result->andIf($price_result);

    /** @var \Drupal\Core\Access\AccessResultInterface[] $results */
    $results = $this->getModuleHandler()->invokeAll('product_available_for_sell', [
      'product' => $this,
      'account' => $account,
    ]);

    foreach ($results as $result) {
      if (empty($result) || !($result instanceof AccessResultInterface)) {
        continue;
      }
      $result->andIf($result);
    }

    $this->getModuleHandler()->alter('product_available_for_sell', $result, $this, $account);
    return !$result->isForbidden();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Display name', [], ['context' => 'arch_product']))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SKU', [], ['context' => 'arch_product']))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['erp_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ERP ID', [], ['context' => 'arch_product']))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['group_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group ID', [], ['context' => 'arch_product']))
      ->setRequired(FALSE)
      ->setDefaultValue(0)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE);

    $fields['availability'] = BaseFieldDefinition::create('product_availability')
      ->setLabel(t('Availability', [], ['context' => 'arch_product_availability']))
      ->setRequired(TRUE)
      ->setDefaultValue(ProductAvailabilityInterface::STATUS_AVAILABLE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values_function', '\Drupal\arch_product\Entity\ProductAvailability::getOptions')
      ->setSetting('max_length', 15)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'product_availability_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by', [], ['context' => 'arch_product']))
      ->setDescription(t('The username of the product creator.', [], ['context' => 'arch_product']))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\arch_product\Entity\Product::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on', [], ['context' => 'arch_product']))
      ->setDescription(t('The time that the product was created.', [], ['context' => 'arch_product']))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed', [], ['context' => 'arch_product']))
      ->setDescription(t('The time that the product was last edited.', [], ['context' => 'arch_product']))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['promote'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Promoted to front page', [], ['context' => 'arch_product']))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['sticky'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Sticky at top of lists', [], ['context' => 'arch_product']))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * Get price negotiation service.
   *
   * @return \Drupal\arch_price\Negotiation\PriceNegotiationInterface
   *   Service.
   */
  protected function getPriceNegotiation() {
    // @codingStandardsIgnoreStart
    return \Drupal::service('price.negotiation');
    // @codingStandardsIgnoreEnd
  }

  /**
   * Get module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   Module handler service.
   */
  protected function getModuleHandler() {
    // @codingStandardsIgnoreStart
    return \Drupal::service('module_handler');
    // @codingStandardsIgnoreEnd
  }

}
