<?php

namespace Drupal\tms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tms\Entity\Ticket;
use Drupal\tms\Entity\TicketType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TicketForm' Block.
 *
 * @Block(
 *   id = "ticket_block",
 *   admin_label = @Translation("Ticket Form Block"),
 *   category = @Translation("Ticket Form Block"),
 * )
 */
class TicketBlock extends BlockBase implements ContainerFactoryPluginInterface  {


  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TicketBlock
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager)
  {
    parent::__construct($configuration, $plugin_id,$plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
   return new static(
    $configuration, $plugin_id,$plugin_definition, $container->get('entity_type.manager')
   );
  }

 /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    
    $form['formblock_ticket_type'] = [
      '#title' => $this->t('Ticket type'),
      '#description' => $this->t('Select the Ticket type whose form will be shown in the block.'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $this->getTicketTypes(),
      '#default_value' => $this->configuration['type'],
    ];  
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['type'] = $form_state->getValue('formblock_ticket_type');
  }

  /**
   * Get an array of node types.
   *
   * @return array
   *   An array of node types keyed by machine name.
   */
  protected function getTicketTypes() {
    $options = [];
    /** @var \Drupal\tms\Entity\TicketTypeInterface $types */
    $types = $this->entityTypeManager->getStorage('ticket_type')->loadMultiple();
    foreach ($types as $type) {
      $options[$type->id()] = $type->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */ 
  public function build() {
    /** @var \Drupal\tms\Entity\TicketInterface $entity */
    $entity = $this->entityTypeManager->getStorage('ticket')->create([
      'type' => $this->configuration['type'],
    ]);
    return \Drupal::service('entity.form_builder')->getForm($entity,'default');
  }

}