<?php

namespace Drupal\arch_addressbook\Plugin\views\row;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a row plugin for displaying a result as a rendered item.
 *
 * @ViewsRow(
 *   id = "addressbookitem_row",
 *   title = @Translation("Rendered AddressBookItem entity"),
 *   help = @Translation("Displays entity of the matching AddressBookItem item"),
 * )
 */
class AddressbookitemRow extends RowPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->entityTypeManager = $entity_type_manager;
    $this->displayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_modes'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $addressbookitem_view_modes = $this->displayRepository->getViewModes('addressbookitem');

    $view_modes = [];
    foreach ($addressbookitem_view_modes as $key => $view_mode) {
      $view_modes[$key] = $view_mode['label'];
    }

    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $view_modes,
      '#title' => $this->t('View mode for AddressBookItem', [], ['context' => 'arch_addressbook']),
      '#default_value' => 'full',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    /** @var \Drupal\arch_addressbook\Entity\Addressbookitem $addressbookitem */
    $addressbookitem =& $row->_entity;
    $addressbookitem->views_row_index = ($row->index + 1);

    /** @var \Drupal\arch_addressbook\Entity\AddressbookitemViewBuilder $view_builder */
    $view_builder = $this->entityTypeManager->getViewBuilder($addressbookitem->getEntityTypeId());
    return $view_builder->view($addressbookitem, $this->options['view_mode']);
  }

}
