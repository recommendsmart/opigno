<?php

namespace Drupal\arch_compare\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings for arch_compare module.
 */
class CompareConfigForm extends ConfigFormBase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs an CartConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   Entity display repository.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    PathValidatorInterface $path_validator,
    RequestContext $request_context,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    parent::__construct($config_factory);

    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arch_compare_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'arch_compare.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('arch_compare.settings');

    $form['limit'] = [
      '#type' => 'select',
      '#options' => $this->getLimitOptions(),
      '#title' => $this->t('Compare max limit', [], ['context' => 'arch_compare_settings']),
      '#default_value' => $config->get('limit'),
      '#description' => $this->t('Compare queue max limit.', [], ['context' => 'arch_compare_settings']),
    ];

    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->getViewModeOptions(),
      '#title' => $this->t('Product display', [], ['context' => 'arch_compare_settings']),
      '#default_value' => $config->get('view_mode'),
      '#description' => $this->t('Display view mode on compare page.', [], ['context' => 'arch_compare_settings']),
    ];

    $form['compare_selection_preservation_time'] = [
      '#title' => $this->t('Preserve compare selection for', [], ['context' => 'arch_compare_settings']),
      '#type' => 'select',
      '#options' => $this->timeLimitOptions(),
      '#default_value' => $config->get('compare_selection_preservation_time'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('arch_compare.settings')
      ->set('limit', (int) $form_state->getValue('limit'))
      ->set('compare_selection_preservation_time', (int) $form_state->getValue('compare_selection_preservation_time'))
      ->set('view_mode', $form_state->getValue('view_mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get limit options.
   *
   * @return array
   *   Limit options.
   */
  protected function getLimitOptions() {
    $options = range(2, 10);
    return array_combine($options, $options);
  }

  /**
   * Get view mode options.
   *
   * @return string[]
   *   View mode options.
   */
  protected function getViewModeOptions() {
    $view_modes = $this->entityDisplayRepository->getViewModes('product');
    $view_mode_options = [];
    foreach ($view_modes as $view_mode_key => $view_mode) {
      $view_mode_options[$view_mode_key] = $view_mode['label'];
    }

    return $view_mode_options;
  }

  /**
   * Get time limit options.
   *
   * @return array
   *   Time limit options.
   */
  protected function timeLimitOptions() {
    $options = [];
    $options[0] = $this->t('- Selection not expire -', [], ['context' => 'arch_compare_settings']);

    // One day is 60 * 60 * 24 = 8640 seconds.
    for ($days = 1; $days <= 7; $days++) {
      $options[86400 * $days] = $this->formatPlural(
        $days,
        '1 day',
        '@days days',
        ['@days' => $days],
        ['context' => 'arch_compare_settings']
      );
    }

    return $options;
  }

}
