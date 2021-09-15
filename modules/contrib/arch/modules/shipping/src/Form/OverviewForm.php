<?php

namespace Drupal\arch_shipping\Form;

use Drupal\arch_shipping\ShippingMethodInterface;
use Drupal\arch_shipping\ShippingMethodManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shipping overview form.
 *
 * @package Drupal\arch_shipping\Form
 */
class OverviewForm extends FormBase {

  /**
   * The shipping method plugins listed.
   *
   * @var \Drupal\arch_shipping\ShippingMethodInterface[]
   */
  protected $methods = [];

  /**
   * Shipping method manager.
   *
   * @var \Drupal\arch_shipping\ShippingMethodManagerInterface
   */
  protected $shippingMethodManager;

  /**
   * OverviewForm constructor.
   *
   * @param \Drupal\arch_shipping\ShippingMethodManagerInterface $shipping_method_manager
   *   Shipping method manager.
   */
  public function __construct(
    ShippingMethodManagerInterface $shipping_method_manager
  ) {
    $this->shippingMethodManager = $shipping_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.shipping_method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shipping_method_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['methods'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => $this->t('There are no @label yet.', [
        '@label' => $this->t('shipping methods', [], ['context' => 'arch_shipping']),
      ]),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      '#attributes' => [
        'id' => 'shipping-methods',
      ],
    ];

    $this->methods = $this->load();
    $delta = 10;
    $count = count($this->methods);
    if ($count > 20) {
      $delta = ceil($count / 2);
    }

    uasort($this->methods, function (ShippingMethodInterface $a, ShippingMethodInterface $b) {
      if ($a->getWeight() == $b->getWeight()) {
        return 0;
      }
      return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
    });

    // Change the delta of the weight field if have more than 20 entities.
    foreach ($this->methods as $method) {
      $row = $this->buildRow($method);
      if (isset($row['weight'])) {
        $row['weight']['#delta'] = $delta;
      }
      $form['methods'][$method->getPluginId()] = $row;
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('methods') as $id => $value) {
      if (
        isset($this->methods[$id])
        && $this->methods[$id]->getWeight() != $value['weight']
      ) {
        $this->methods[$id]->setWeight($value['weight']);
      }
    }
  }

  /**
   * Build table header.
   */
  public function buildHeader() {
    $header = [
      'drag' => NULL,
      'label' => $this->t('Method name', [], ['context' => 'arch_shipping']),
      'weight' => $this->t('Weight'),
      'status' => $this->t('Status', [], ['context' => 'arch_shipping']),

      'operations' => $this->t('Operations'),
    ];
    return $header;
  }

  /**
   * Build table row.
   */
  public function buildRow(ShippingMethodInterface $method) {
    $row = [
      'drag' => NULL,
      'label' => NULL,
      'weight' => NULL,
      'status' => NULL,
      'operations' => NULL,
    ];

    $row['drag'] = [
      'data' => NULL,
      'class' => [],
    ];
    // Override default values to markup elements.
    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = $method->getWeight();
    // Add weight column.
    $row['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for @title', ['@title' => $method->getAdminLabel()]),
      '#title_display' => 'invisible',
      '#default_value' => $method->getWeight(),
      '#attributes' => ['class' => ['weight']],
    ];

    $row['label'] = [
      '#markup' => $method->getAdminLabel(),
    ];
    $row['status'] = [
      '#markup' => $method->isActive() ? $this->t('Enabled') : $this->t('Disabled'),
    ];

    $row['operations']['data'] = [
      '#type' => 'operations',
      '#links' => $this->getOperations($method),
    ];

    return $row;
  }

  /**
   * Builds a renderable list of operation links for the shipping method.
   *
   * @param \Drupal\arch_shipping\ShippingMethodInterface $method
   *   The method plugin on which the linked operations will be performed.
   *
   * @return array
   *   A renderable array of operation links.
   */
  protected function getOperations(ShippingMethodInterface $method) {
    $operations = [];

    $config_url = Url::fromRoute(
      'arch_shipping.configure_plugin',
      ['shipping_method' => $method->getPluginId()],
      ['query' => $this->getRedirectDestination()->getAsArray()]
    );

    $operations['configure'] = [
      'title' => $this->t('Configure'),
      'weight' => -10,
      'url' => $config_url,
    ];
    if ($method->isActive()) {
      $operations['disable'] = [
        'title' => $this->t('Disable'),
        'weight' => 1,
        'url' => Url::fromRoute(
          'arch_shipping.disable_method',
          ['shipping_method' => $method->getPluginId()],
          ['query' => $this->getRedirectDestination()->getAsArray()]
        ),
      ];
    }
    else {
      $operations['enable'] = [
        'title' => $this->t('Enable'),
        'weight' => 2,
        'url' => Url::fromRoute(
          'arch_shipping.enable_method',
          ['shipping_method' => $method->getPluginId()],
          ['query' => $this->getRedirectDestination()->getAsArray()]
        ),
      ];
    }
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }

  /**
   * List of shipping methods.
   *
   * @return \Drupal\arch_shipping\ShippingMethodInterface[]
   *   Shipping method plugin list.
   */
  protected function load() {
    /** @var \Drupal\arch_shipping\ShippingMethodInterface[] $methods */
    $methods = $this->shippingMethodManager->getAllShippingMethods();

    return $methods;
  }

}
