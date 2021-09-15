<?php

namespace Drupal\arch_shipping_instore\Form;

use Drupal\arch_shipping\ShippingMethodManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Address form.
 *
 * @package Drupal\arch_shipping_instore\Form
 */
class AddressForm extends FormBase {

  /**
   * In store shipping method plugin.
   *
   * @var \Drupal\arch_shipping\ShippingMethodInterface
   */
  protected $inStoreShippingMethod;

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
    $this->inStoreShippingMethod = $shipping_method_manager->getShippingMethod('instore');
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
    return 'arch_shipping_instore_address';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $address_id = NULL) {
    $addresses = $this->inStoreShippingMethod->getSetting('addresses', []);
    if (!empty($address_id) && !isset($addresses[$address_id])) {
      throw new NotFoundHttpException();
    }

    $address = isset($addresses[$address_id]) ? $addresses[$address_id] : [];
    $address += [
      'id' => NULL,
      'name' => NULL,
      'weight' => NULL,
      'status' => TRUE,
    ];

    $form = [];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label', [], ['context' => 'arch_shipping_instore']),
      '#default_value' => $address['name'],
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $address['id'],
      '#maxlength' => 32,
      '#disabled' => !empty($address['id']),
      '#machine_name' => [
        'exists' => [
          'Drupal\arch_shipping_instore\Form\AddressForm',
          'loadAddress',
        ],
        'source' => ['name'],
      ],
      '#description' => $this->t(
        'A unique machine-readable name for this address. It must only contain lowercase letters, numbers, and underscores.',
        [],
        ['context' => 'arch_shipping_instore']
      ),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => (bool) $address['status'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    if (!empty($address['id'])) {
      $form['actions']['delete'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('arch_shipping_instore.address.delete', [
          'address_id' => $address['id'],
        ]),
        '#title' => $this->t('Delete'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $addresses = $this->inStoreShippingMethod->getSetting('addresses', []);
    $addresses[$form_state->getValue('id')] = [
      'id' => $form_state->getValue('id'),
      'status' => (bool) $form_state->getValue('status'),
      'name' => trim($form_state->getValue('name')),
      'weight' => 0,
    ];

    $this->inStoreShippingMethod->setSetting('addresses', $addresses);
    $this->messenger()->addMessage($this->t(
      'The %label address has been created',
      ['%label' => $addresses[$form_state->getValue('id')]['name']],
      ['context' => 'arch_shipping_instore']
    ));
    $form_state->setRedirect('arch_shipping.configure_plugin', [
      'shipping_method' => 'instore',
    ]);
  }

  /**
   * Load address by ID.
   *
   * @param string $id
   *   Address ID.
   *
   * @return array|null
   *   Address data or NULL on failure.
   */
  public static function loadAddress($id) {
    /** @var \Drupal\arch_shipping\ShippingMethodManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.shipping_method');
    /** @var \Drupal\arch_shipping_instore\Plugin\ShippingMethod\InStoreShippingMethod $instore */
    $instore = $manager->getShippingMethod('instore');
    $addresses = $instore->getSetting('addresses', []);
    return isset($addresses[$id]) ? $addresses[$id] : NULL;
  }

}
