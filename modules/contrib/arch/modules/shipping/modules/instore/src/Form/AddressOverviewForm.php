<?php

namespace Drupal\arch_shipping_instore\Form;

use Drupal\arch_shipping\Form\ShippingMethodFormInterface;
use Drupal\arch_shipping\ShippingMethodManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Address overview form.
 *
 * @package Drupal\arch_shipping_instore\Form
 */
class AddressOverviewForm extends FormBase implements ShippingMethodFormInterface, PluginFormInterface {

  /**
   * The address list.
   *
   * @var array
   */
  protected $addresses = [];

  /**
   * Shipping method manager.
   *
   * @var \Drupal\arch_shipping\ShippingMethodManagerInterface
   */
  protected $shippingMethodManager;

  /**
   * In store shipping method plugin.
   *
   * @var \Drupal\arch_shipping\ShippingMethodInterface
   */
  protected $inStoreShippingMethod;

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
    $this->inStoreShippingMethod = $shipping_method_manager->getShippingMethod('instore');

    $this->addresses = $this->load();
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
    return 'arch_shipping_instore_address_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['addresses_actions'] = [
      '#cache' => ['contexts' => ['url']],
      '#prefix' => '<ul class="action-links">',
      '#suffix' => '</ul>',
      'items' => [
        [
          '#theme' => 'menu_local_action',
          '#link' => [
            'title' => $this->t('Add address', [], ['context' => 'arch_shipping_instore']),
            'url' => Url::fromRoute('arch_shipping_instore.address.add', []),
          ],
        ],
      ],
    ];
    $form['addresses'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => $this->t('There are no @label yet.', ['@label' => 'address']),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      '#attributes' => [
        'id' => 'instore-addresses',
      ],
    ];

    $delta = 10;
    $count = count($this->addresses);
    if ($count > 20) {
      $delta = ceil($count / 2);
    }

    // Change the delta of the weight field if have more than 20 entities.
    $rows = [];
    foreach ($this->addresses as $address) {
      $row = $this->buildRow($address);
      $row['weight']['#delta'] = $delta;
      $rows[$address['id']] = $row;
    }
    uasort($rows, '\Drupal\Component\Utility\SortArray::sortByWeightProperty');

    $form['addresses'] += $rows;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->addresses = $this->load();
    foreach ($form_state->getValue('addresses') as $id => $value) {
      if (
        isset($this->addresses[$id])
        && $this->addresses[$id]['weight'] != $value['weight']
      ) {
        $this->addresses[$id]['weight'] = (int) $value['weight'];
      }
    }
    $this->inStoreShippingMethod->setSetting('addresses', $this->addresses);
    $this->messenger()->addMessage($this->t('Address order changed', [], ['context' => 'arch_shipping_instore']));
  }

  /**
   * Build table header.
   */
  public function buildHeader() {
    $header = [
      'drag' => NULL,
      'label' => [
        'data' => $this->t('Label', [], ['context' => 'arch_shipping_instore']),
        'class' => [],
      ],
      'status' => [
        'data' => $this->t('Status', [], ['context' => 'arch_shipping_instore']),
        'class' => [],
      ],
      'weight' => [
        'data' => $this->t('Weight'),
        'class' => [],
      ],
      'operations' => $this->t('Operations'),
    ];
    return $header;
  }

  /**
   * Build table row.
   */
  public function buildRow(array $address) {
    $address += [
      'id' => NULL,
      'weight' => NULL,
      'label' => NULL,
      'status' => NULL,
    ];

    $row = [
      'drag' => NULL,
      'label' => NULL,
      'status' => NULL,
      'weight' => NULL,
      'operations' => NULL,
    ];

    $row['drag'] = [
      'data' => NULL,
      'class' => [],
    ];
    // Override default values to markup elements.
    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = $address['weight'];
    $row['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for @title', ['@title' => $address['label']]),
      '#title_display' => 'invisible',
      '#default_value' => $address['weight'],
      '#attributes' => ['class' => ['weight']],
    ];
    $row['label'] = [
      'data' => ['#markup' => $address['name']],
      'class' => [],
    ];
    $row['status'] = [
      'data' => ['#markup' => ($address['status'] ? $this->t('Enabled') : $this->t('Disabled'))],
      'class' => [],
    ];
    $row['operations'] = [
      'data' => [
        '#type' => 'operations',
        '#links' => $this->getOperations($address),
      ],
    ];

    return $row;
  }

  /**
   * Builds a renderable list of operation links for the address.
   *
   * @param array $address
   *   The address on which the linked operations will be performed.
   *
   * @return array
   *   A renderable array of operation links.
   */
  protected function getOperations(array $address) {
    $operations = [];
    $config_url = Url::fromRoute(
      'arch_shipping_instore.address.edit',
      ['address_id' => $address['id']],
      [
        'query' => [
          'destination' => Url::fromRoute('<current>')->toString(),
        ],
      ]
    );

    $operations['configure'] = [
      'title' => $this->t('Configure'),
      'weight' => -10,
      'url' => $config_url,
    ];
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }

  /**
   * List of adresses.
   *
   * @return array
   *   Address list.
   */
  protected function load() {
    return $this->inStoreShippingMethod->getSetting('addresses', []);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
