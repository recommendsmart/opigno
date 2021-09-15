<?php

namespace Drupal\arch_order\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form handler for the order edit forms.
 *
 * @internal
 */
class OrderForm extends ContentEntityForm {

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
   * Constructs a OrderForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Current request.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
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
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
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
    $form = parent::form($form, $form_state);

    /** @var \Drupal\arch_order\Entity\OrderInterface $order */
    $order = $this->entity;

    // Sets 'Create new revision' checked by default if order is not a new one.
    if (!$order->isNew()) {
      $form['revision']['#default_value'] = TRUE;
    }

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit @type <em>@title</em>', [
        '@type' => $order->getEntityTypeId(),
        '@title' => $order->label(),
      ]);
    }

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $order->getChangedTime(),
    ];

    $form['advanced']['#attributes']['class'][] = 'entity-meta';

    $form['meta'] = [
      '#type' => 'details',
      '#group' => 'advanced',
      '#weight' => -10,
      '#title' => $this->t('Status', [], ['context' => 'arch_order']),
      '#attributes' => ['class' => ['entity-meta__header']],
      '#tree' => TRUE,
      '#access' => $this->currentUser->hasPermission('administer orders'),
    ];

    if ($order->isNew()) {
      $changed_markup = $this->t('Not saved yet');
    }
    else {
      $changed_markup = $this->dateFormatter->format($order->getChangedTime(), 'short');
    }
    $form['meta']['changed'] = [
      '#type' => 'item',
      '#title' => $this->t('Last saved'),
      '#markup' => $changed_markup,
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved']],
    ];

    $form['meta']['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Customer', [], ['context' => 'arch_order']),
      '#markup' => (!empty($order->getOwner()) ? $order->getOwner()->getDisplayName() : ''),
      '#wrapper_attributes' => ['class' => ['entity-meta__author']],
    ];

    // Order creator information for administrators.
    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['order-form-author'],
      ],
      '#attached' => [
        'library' => ['arch_order/drupal.order'],
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

    $form['#attached']['library'][] = 'arch_order/form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\arch_order\Entity\Order $order */
    $order = $this->entity;
    $insert = $order->isNew();
    $order->save();
    $order_link = $order->toLink($this->t('View'))->toString();
    $context = [
      '@type' => $order->getEntityTypeId(),
      '%title' => $order->label(),
      'link' => $order_link,
    ];
    $t_args = [
      '%title' => $order->toLink($order->label())->toString(),
    ];

    if ($insert) {
      $this->logger('arch')->notice('@type: added %title.', $context);
      $this->messenger()->addStatus($this->t('Order %title has been created.', $t_args, ['context' => 'arch_order']));
    }
    else {
      $this->logger('arch')->notice('@type: updated %title.', $context);
      $this->messenger()->addStatus($this->t('Order %title has been updated.', $t_args, ['context' => 'arch_order']));
    }

    if ($order->id()) {
      $form_state->setValue('oid', $order->id());
      $form_state->set('oid', $order->id());
      if ($order->access('view')) {
        $form_state->setRedirect(
          'entity.order.canonical',
          ['order' => $order->id()]
        );
      }
      else {
        $form_state->setRedirect('<front>');
      }
    }
    else {
      // In the unlikely case something went wrong on save, the order will be
      // rebuilt and order form redisplayed the same way as in preview.
      $this->messenger()->addError($this->t('The order could not be saved.', [], ['context' => 'arch_order']));
      $form_state->setRebuild();
    }
  }

}
