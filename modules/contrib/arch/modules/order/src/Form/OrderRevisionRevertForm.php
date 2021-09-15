<?php

namespace Drupal\arch_order\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\arch_order\Entity\OrderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting an order revision.
 *
 * @internal
 */
class OrderRevisionRevertForm extends ConfirmFormBase {

  /**
   * The order revision revision.
   *
   * @var \Drupal\arch_order\Entity\OrderInterface
   */
  protected $revision;

  /**
   * The order storage.
   *
   * @var \Drupal\arch_order\Entity\Storage\OrderStorageInterface
   */
  protected $orderStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new OrderRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $order_storage
   *   The order storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityStorageInterface $order_storage,
    DateFormatterInterface $date_formatter,
    TimeInterface $time
  ) {
    $this->orderStorage = $order_storage;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('order'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'order_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // @codingStandardsIgnoreStart
    return $this->t(
      'Are you sure you want to revert to the revision from %revision-date?',
      [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime(), 'custom', 'Y F j - H:i')
      ]
    );
    // @codingStandardsIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.order.version_history', ['order' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $order_revision = NULL) {
    $this->revision = $this->orderStorage->loadRevision($order_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $formatted_date = $this->dateFormatter->format($original_revision_timestamp, 'custom', 'Y F j - H:i');
    $this->revision->revision_log = $this->t('Copy of the revision from %date.', ['%date' => $formatted_date]);
    $this->revision->setRevisionUserId($this->currentUser()->id());
    $this->revision->setRevisionCreationTime($this->time->getRequestTime());
    $this->revision->setChangedTime($this->time->getRequestTime());
    $this->revision->save();

    // @codingStandardsIgnoreStart
    $this->logger('content')->notice('Order reverted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()
      ->addStatus($this->t(
        'Order %title has been reverted to the revision from %revision-date.',
        [
          '%title' => $this->revision->label(),
          '%revision-date' => $this->dateFormatter->format($original_revision_timestamp, 'custom', 'Y F j - H:i'),
        ],
        ['context' => 'arch_order']
      ));
    // @codingStandardsIgnoreEnd
    $form_state->setRedirect(
      'entity.order.version_history',
      ['order' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\arch_order\Entity\OrderInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(OrderInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
