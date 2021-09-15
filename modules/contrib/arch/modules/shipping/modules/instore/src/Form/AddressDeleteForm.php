<?php

namespace Drupal\arch_shipping_instore\Form;

use Drupal\arch_shipping\ShippingMethodManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Address delete form.
 *
 * @package Drupal\arch_shipping_instore\Form
 */
class AddressDeleteForm extends ConfirmFormBase {

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
   * Address.
   *
   * @var array
   */
  protected $address;

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
  public function buildForm(array $form, FormStateInterface $form_state, $address_id = NULL) {
    $addresses = $this->inStoreShippingMethod->getSetting('addresses', []);
    if (
      empty($address_id)
      || empty($addresses[$address_id])
    ) {
      throw new NotFoundHttpException();
    }

    $this->address = $addresses[$address_id];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete the address %label?',
      ['%label' => $this->address['name']],
      ['context' => 'arch_shipping_instore']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('arch_shipping_instore.address.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arch_shipping_instore_address_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $addresses = $this->inStoreShippingMethod->getSetting('addresses', []);
    unset($addresses[$this->address['id']]);
    $this->inStoreShippingMethod->setSetting('addresses', $addresses);
    $form_state->setRedirect('arch_shipping_instore.address.overview');
    $this->messenger()->addMessage($this->getDeletionMessage());
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t(
      'The %label address has been deleted.',
      ['%label' => $this->address['name']],
      ['context' => 'arch_shipping_instore']
    );
  }

}
