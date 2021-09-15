<?php

namespace Drupal\arch_order\Form;

use Drupal\arch_order\Services\OrderStatusServiceInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for order status add and edit forms.
 */
abstract class OrderStatusBaseForm extends EntityForm {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Order status service.
   *
   * @var \Drupal\arch_order\Services\OrderStatusServiceInterface
   */
  protected $orderStatusService;

  /**
   * Constructs a ContentEntityForm object.
   */
  public function __construct(AccountProxyInterface $currentUser, OrderStatusServiceInterface $orderStatusService) {
    $this->currentUser = $currentUser;
    $this->orderStatusService = $orderStatusService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('order.statuses')
    );
  }

  /**
   * Common elements of the order status addition and editing form.
   */
  public function commonForm(array &$form) {
    /** @var \Drupal\arch_order\Entity\OrderStatusInterface $order_status */
    $order_status = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 32,
      '#default_value' => $order_status->label(),
      '#required' => TRUE,
    ];

    if ($order_status->getId()) {
      $form['order_status_view'] = [
        '#type' => 'item',
        '#title' => $this->t('Machine name'),
        '#markup' => $order_status->id(),
      ];
      $form['order_status'] = [
        '#type' => 'value',
        '#value' => $order_status->id(),
      ];
    }
    else {
      $form['order_status'] = [
        '#type' => 'machine_name',
        '#machine_name' => [
          'exists' => [$this, 'exists'],
          'replace_pattern' => '[^a-z0-9_.]+',
          'source' => ['label'],
          'label' => $this->t('Machine name'),
        ],
        '#title' => $this->t('Machine name'),
        '#maxlength' => 32,
        '#required' => TRUE,
        '#default_value' => '',
        '#disabled' => !$order_status->isNew(),
        '#description' => $this->t('Use lower-case version of Order status name <em>Examples: "cart", "processing"</em>.', [], ['context' => 'arch_order_status']),
      ];
    }

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description', [], ['context' => 'arch_order_status']),
      '#maxlength' => 255,
      '#default_value' => $order_status->getDescription(),
      '#required' => FALSE,
      '#placeholder' => $this->t('Briefly describe what this order status means.', [], ['context' => 'arch_order_status']),
    ];

    $form['default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default', [], ['context' => 'arch_order_status']),
      '#default_value' => $order_status->getIsDefault(),
    ];
  }

  /**
   * Determines if the order status already exists.
   *
   * @param string $id
   *   The order status ID.
   *
   * @return bool
   *   TRUE if the order status exists, FALSE otherwise.
   */
  public function exists($id) {
    $action = $this->orderStatusService->load($id);
    return !empty($action);
  }

  /**
   * Validates the order status editing element.
   */
  public function validateCommon(array $form, FormStateInterface $form_state) {
    // Ensure sane field values for order status and name.
    if (!isset($form['order_status_view']) && !preg_match('/^[a-z0-9_]{1,32}$/', $form_state->getValue('order_status'))) {
      $form_state->setErrorByName('order_status', $this->t('%field must be a valid Order Status tag. Only lower case letters and underscore "_" are accepted.', [
        '%field' => $form['order_status']['#title'],
      ]));
    }

    if ($form_state->getValue('label') != Html::escape($form_state->getValue('label'))) {
      $form_state->setErrorByName('label', $this->t('%field cannot contain any markup.', ['%field' => $form['label']['#title']]));
    }
  }

}
