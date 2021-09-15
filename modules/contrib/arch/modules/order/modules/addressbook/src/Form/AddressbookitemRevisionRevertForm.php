<?php

namespace Drupal\arch_addressbook\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\arch_addressbook\AddressbookitemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting an addressbookitem revision.
 *
 * @internal
 */
class AddressbookitemRevisionRevertForm extends ConfirmFormBase {

  /**
   * The addressbookitem revision revision.
   *
   * @var \Drupal\arch_addressbook\AddressbookitemInterface
   */
  protected $revision;

  /**
   * The addressbookitem storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $addressbookitemStorage;

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
   * Constructs a new AddressbookitemRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $addressbookitem_storage
   *   The addressbookitem storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityStorageInterface $addressbookitem_storage,
    DateFormatterInterface $date_formatter,
    TimeInterface $time
  ) {
    $this->addressbookitemStorage = $addressbookitem_storage;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('addressbookitem'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'addressbookitem_revision_revert_confirm';
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
    return new Url(
      'entity.addressbookitem.version_history',
      ['addressbookitem' => $this->revision->id()]
    );
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
  public function buildForm(array $form, FormStateInterface $form_state, $addressbookitem_revision = NULL) {
    $this->revision = $this->addressbookitemStorage->loadRevision($addressbookitem_revision);
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
    // @codingStandardsIgnoreStart
    $this->revision->revision_log_message = $this->t(
      'Copy of the revision from %date.',
      [
        '%date' => $this->dateFormatter->format($original_revision_timestamp, 'custom', 'Y F j - H:i')
      ]
    );
    // @codingStandardsIgnoreEnd
    $this->revision->setRevisionUserId($this->currentUser()->id());
    $this->revision->setRevisionCreationTime($this->time->getRequestTime());
    $this->revision->setChangedTime($this->time->getRequestTime());
    $this->revision->save();

    // @codingStandardsIgnoreStart
    $this->logger('content')->notice('Address reverted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()
      ->addStatus($this->t(
        'Address %title has been reverted to the revision from %revision-date.',
        [
          '%title' => $this->revision->label(),
          '%revision-date' => $this->dateFormatter->format($original_revision_timestamp, 'custom', 'Y F j - H:i'),
        ],
        ['context' => 'arch_addressbook']
      ));
    // @codingStandardsIgnoreEnd

    $form_state->setRedirect(
      'entity.addressbookitem.version_history',
      ['addressbookitem' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\arch_addressbook\AddressbookitemInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\arch_addressbook\AddressbookitemInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(AddressbookitemInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
