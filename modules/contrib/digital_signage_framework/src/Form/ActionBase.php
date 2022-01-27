<?php

namespace Drupal\digital_signage_framework\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\digital_signage_framework\Entity\Device;
use Drupal\digital_signage_framework\Query;
use Drupal\digital_signage_framework\ScheduleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enable or disable emergency mode on devices.
 */
abstract class ActionBase extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\digital_signage_framework\ScheduleManager
   */
  protected $scheduleManager;

  /**
   * @var \Drupal\digital_signage_framework\DeviceInterface[]
   */
  protected $devices;

  /**
   * @var \Drupal\digital_signage_framework\Query
   */
  protected $queryService;

  /**
   * Constructs a new EmergencyMode confirm form.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\digital_signage_framework\ScheduleManager $schedule_manager
   * @param \Drupal\digital_signage_framework\Query $query_service
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, ScheduleManager $schedule_manager, Query $query_service) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->scheduleManager = $schedule_manager;
    $this->queryService = $query_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('schedule.manager.digital_signage_platform'),
      $container->get('digital_signage_content_setting.queries')
    );
  }

  abstract protected function id();

  /**
   * {@inheritdoc}
   */
  final public function getFormId() {
    return $this->id() . '_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.digital_signage_device.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();
    if ($id = $this->getRequest()->get('entity_id')) {
      $this->tempStoreFactory->get($this->id())->set($current_user_id, [Device::load($id)]);
      $form['entity_id'] = ['#type' => 'hidden', '#value' => $id];
    }
    $form = parent::buildForm($form, $form_state);
    $this->devices = $this->tempStoreFactory
      ->get($this->id())
      ->get($current_user_id);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();
    $this->devices = $this->tempStoreFactory
      ->get($this->id())
      ->get($current_user_id);
    $this->tempStoreFactory
      ->get($this->id())
      ->delete($current_user_id);
    if ($id = $form_state->getValue('entity_id')) {
      $form_state->setRedirect('entity.digital_signage_device.canonical', [
        'digital_signage_device' => $id,
      ]);
    }
    else {
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

}
