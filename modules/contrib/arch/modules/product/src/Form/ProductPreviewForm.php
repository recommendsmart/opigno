<?php

namespace Drupal\arch_product\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains a form for switching the view mode of a product during preview.
 *
 * @internal
 */
class ProductPreviewForm extends FormBase {

  /**
   * The entity manager service.
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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a new ProductPreviewForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'product_preview_form_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $product = NULL) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $view_mode = $product->preview_view_mode;

    $query_options = ['query' => ['uuid' => $product->uuid()]];
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $query_options['query']['destination'] = $query->get('destination');
    }

    $form['backlink'] = [
      '#type' => 'link',
      '#title' => $this->t('Back to product editing', [], ['context' => 'arch_product']),
      '#url' => $product->isNew() ? Url::fromRoute('product.add', ['product_type' => $product->bundle()]) : $product->toUrl('edit-form'),
      '#options' => ['attributes' => ['class' => ['product-preview-backlink']]] + $query_options,
    ];

    // Always show full as an option, even if the display is not enabled.
    $view_mode_options = ['full' => $this->t('Full')] + $this->entityDisplayRepository->getViewModeOptionsByBundle('product', $product->bundle());

    // Unset view modes that are not used in the front end.
    unset($view_mode_options['default']);
    unset($view_mode_options['rss']);
    unset($view_mode_options['search_index']);

    $form['uuid'] = [
      '#type' => 'value',
      '#value' => $product->uuid(),
    ];

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $view_mode_options,
      '#default_value' => $view_mode,
      '#attributes' => [
        'data-drupal-autosubmit' => TRUE,
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Switch'),
      '#attributes' => [
        'class' => ['js-hide'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_parameters = [
      'product_preview' => $form_state->getValue('uuid'),
      'view_mode_id' => $form_state->getValue('view_mode'),
    ];

    $options = [];
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $options['query']['destination'] = $query->get('destination');
      $query->remove('destination');
    }
    $form_state->setRedirect('entity.product.preview', $route_parameters, $options);
  }

}
