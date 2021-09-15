<?php

namespace Drupal\arch_cart\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings for arch_cart module.
 */
class CartConfigForm extends ConfigFormBase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

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
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory);

    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arch_cart_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'arch_cart.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('arch_cart.settings');

    $form['arch_cart']['combine_items'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine items', [], ['context' => 'arch_cart_settings']),
      '#default_value' => $config->get('combine_items'),
      '#description' => $this->t('Combine same products within cart on add to cart event.', [], ['context' => 'arch_cart_settings']),
    ];

    $form['arch_cart']['ajax_addtocart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use ajax on Add to cart form', [], ['context' => 'arch_cart_settings']),
      '#default_value' => $config->get('ajax_addtocart'),
      '#description' => $this->t('Enable or disable ajaxified Add to cart form.', [], ['context' => 'arch_cart_settings']),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('arch_cart.settings')
      ->set('combine_items', (bool) $form_state->getValue('combine_items'))
      ->set('ajax_addtocart', (bool) $form_state->getValue('ajax_addtocart'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
