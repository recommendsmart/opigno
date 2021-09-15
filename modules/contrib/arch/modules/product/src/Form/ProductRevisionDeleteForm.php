<?php

namespace Drupal\arch_product\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a product revision.
 *
 * @internal
 */
class ProductRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The product revision.
   *
   * @var \Drupal\arch_product\Entity\ProductInterface
   */
  protected $revision;

  /**
   * The product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productStorage;

  /**
   * The product type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new ProductRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $product_storage
   *   The product storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $product_type_storage
   *   The product type storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   DateFormatter.
   */
  public function __construct(
    EntityStorageInterface $product_storage,
    EntityStorageInterface $product_type_storage,
    Connection $connection,
    DateFormatterInterface $date_formatter
  ) {
    $this->productStorage = $product_storage;
    $this->productTypeStorage = $product_type_storage;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('product'),
      $entity_manager->getStorage('product_type'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'product_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete the revision from %revision-date?',
      ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.product.version_history', ['product' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $product_revision = NULL) {
    $this->revision = $this->productStorage->loadRevision($product_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->productStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('product')->notice(
      '@type: deleted %title revision %revision.',
      [
        '@type' => $this->revision->bundle(),
        '%title' => $this->revision->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]
    );
    $product_type = $this->productTypeStorage->load($this->revision->bundle())->label();
    $this->messenger()
      ->addStatus($this->t(
        'Revision from %revision-date of @type %title has been deleted.',
        [
          '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
          '@type' => $product_type,
          '%title' => $this->revision->label(),
        ]
      ));
    $form_state->setRedirect(
      'entity.product.canonical',
      ['product' => $this->revision->id()]
    );
    $query = $this->connection->query(
      'SELECT COUNT(DISTINCT vid) FROM {arch_product_field_revision} WHERE pid = :pid',
      [':pid' => $this->revision->id()]
    );
    if ($query->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.product.version_history',
        ['product' => $this->revision->id()]
      );
    }
  }

}
