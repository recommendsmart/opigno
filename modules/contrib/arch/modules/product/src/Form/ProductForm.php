<?php

namespace Drupal\arch_product\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form handler for the product edit forms.
 *
 * @internal
 */
class ProductForm extends ContentEntityForm {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a ProductForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Current request.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    PrivateTempStoreFactory $temp_store_factory,
    DateFormatterInterface $date_formatter,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    AccountInterface $current_user,
    RequestStack $request_stack
  ) {
    parent::__construct(
      $entity_repository,
      $entity_type_bundle_info,
      $time
    );
    $this->dateFormatter = $date_formatter;
    $this->tempStoreFactory = $temp_store_factory;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('tempstore.private'),
      $container->get('date.formatter'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Try to restore from temp store, this must be done before calling
    // parent::form().
    $store = $this->tempStoreFactory->get('product_preview');

    // Attempt to load from preview when the uuid is present unless we are
    // rebuilding the form.
    $request_uuid = $this->requestStack->getCurrentRequest()->query->get('uuid');
    if (
      !$form_state->isRebuilding()
      && $request_uuid
      && $preview = $store->get($request_uuid)
    ) {
      /** @var \Drupal\Core\Form\FormStateInterface $preview */
      $form_state->setStorage($preview->getStorage());
      $form_state->setUserInput($preview->getUserInput());

      // Rebuild the form.
      $form_state->setRebuild();

      // The combination of having user input and rebuilding the form means
      // that it will attempt to cache the form state which will fail if it is
      // a GET request.
      $form_state->setRequestMethod('POST');

      $this->entity = $preview->getFormObject()->getEntity();
      $this->entity->inPreview = NULL;

      $form_state->set('has_been_previewed', TRUE);
    }

    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @type</em> @title', [
        '@type' => product_get_type_label($product),
        '@title' => $product->label(),
      ]);
    }

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $product->getChangedTime(),
    ];

    $form = parent::form($form, $form_state);

    $form['title']['#weight'] = -10;
    $form['product_ids'] = [
      '#type' => 'details',
      '#weight' => 0,
      '#title' => $this->t('ID', [], ['context' => 'arch_product']),
      '#attributes' => ['class' => ['entity-product_ids']],
      '#tree' => FALSE,
      '#open' => TRUE,
    ];
    $form['sku']['#group'] = 'product_ids';
    $form['erp_id']['#group'] = 'product_ids';
    $form['group_id']['#group'] = 'product_ids';

    $form['advanced']['#attributes']['class'][] = 'entity-meta';

    $form['meta'] = [
      '#type' => 'details',
      '#group' => 'advanced',
      '#weight' => -10,
      '#title' => $this->t('Status', [], ['context' => 'arch_product']),
      '#attributes' => ['class' => ['entity-meta__header']],
      '#tree' => TRUE,
      '#access' => $this->currentUser->hasPermission('administer products'),
    ];
    $form['meta']['published'] = [
      '#type' => 'item',
      '#markup' => $product->isPublished() ? $this->t('Published') : $this->t('Not published'),
      '#access' => !$product->isNew(),
      '#wrapper_attributes' => ['class' => ['entity-meta__title']],
    ];

    if ($product->isNew()) {
      $changed_markup = $this->t('Not saved yet');
    }
    else {
      $changed_markup = $this->dateFormatter->format($product->getChangedTime(), 'short');
    }
    $form['meta']['changed'] = [
      '#type' => 'item',
      '#title' => $this->t('Last saved', [], ['context' => 'arch_product']),
      '#markup' => $changed_markup,
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved']],
    ];
    $form['meta']['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Creator', [], ['context' => 'arch_product']),
      '#markup' => $product->getOwner()->getDisplayName(),
      '#wrapper_attributes' => ['class' => ['entity-meta__author']],
    ];

    $form['status']['#group'] = 'footer';

    // Product creator information for administrators.
    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['product-form-author'],
      ],
      '#attached' => [
        'library' => ['arch_product/drupal.product'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    // Product options for administrators.
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Promotion options'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['product-form-options'],
      ],
      '#attached' => [
        'library' => ['arch_product/drupal.product'],
      ],
      '#weight' => 95,
      '#optional' => TRUE,
    ];

    if (isset($form['promote'])) {
      $form['promote']['#group'] = 'options';
    }

    if (isset($form['sticky'])) {
      $form['sticky']['#group'] = 'options';
    }

    $form['#attached']['library'][] = 'arch_product/form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $product = $this->entity;
    $preview_mode = $product->type->entity->getPreviewMode();

    $element['submit']['#access'] = $preview_mode != DRUPAL_REQUIRED || $form_state->get('has_been_previewed');

    $element['preview'] = [
      '#type' => 'submit',
      '#access' => $preview_mode != DRUPAL_DISABLED && ($product->access('create') || $product->access('update')),
      '#value' => $this->t('Preview'),
      '#weight' => 20,
      '#submit' => ['::submitForm', '::preview'],
    ];

    $element['delete']['#access'] = $product->access('delete');
    $element['delete']['#weight'] = 100;

    return $element;
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $store = $this->tempStoreFactory->get('product_preview');
    $this->entity->inPreview = TRUE;
    $store->set($this->entity->uuid(), $form_state);

    $route_parameters = [
      'product_preview' => $this->entity->uuid(),
      'view_mode_id' => 'full',
    ];

    $options = [];
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $options['query']['destination'] = $query->get('destination');
      $query->remove('destination');
    }
    $form_state->setRedirect('entity.product.preview', $route_parameters, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $this->entity;
    $insert = $product->isNew();
    $product->save();
    $product_link = $product->toLink($this->t('View'))->toString();
    $context = [
      '@type' => $product->getType(),
      '%title' => $product->label(),
      'link' => $product_link,
    ];
    $t_args = [
      '@type' => product_get_type_label($product),
      '%title' => $product->toLink($product->label())->toString(),
    ];

    if ($insert) {
      $this->logger('arch')->notice('@type: added %title.', $context);
      $this->messenger()->addStatus($this->t('@type %title has been created.', $t_args));
    }
    else {
      $this->logger('arch')->notice('@type: updated %title.', $context);
      $this->messenger()->addStatus($this->t('@type %title has been updated.', $t_args));
    }

    if ($product->id()) {
      $form_state->setValue('pid', $product->id());
      $form_state->set('pid', $product->id());
      if ($product->access('view')) {
        $form_state->setRedirect(
          'entity.product.canonical',
          ['product' => $product->id()]
        );
      }
      else {
        $form_state->setRedirect('<front>');
      }

      // Remove the preview entry from the temp store, if any.
      $store = $this->tempStoreFactory->get('product_preview');
      $store->delete($product->uuid());
    }
    else {
      // In the unlikely case something went wrong on save, the product will be
      // rebuilt and product form redisplayed the same way as in preview.
      $this->messenger()->addError($this->t('The product could not be saved.', [], ['context' => 'arch_product']));
      $form_state->setRebuild();
    }
  }

}
